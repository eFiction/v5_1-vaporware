<?php
namespace Model;

class UserCP extends Base
{
	protected $menu = [];
	
	public function __construct()
	{
		parent::__construct();
		$this->menu = $this->panelMenu();
	}

	public function showMenu($selected=FALSE, array $data=[])
	{
		if ( $selected )
		{
			if ( $selected == "author")
			{
				// get associated author and curator data
				$authorData = $this->exec("SELECT U.uid, CONCAT(U.nickname, ' (',COUNT(DISTINCT SA.lid), ')') as label FROM `tbl_users`U LEFT JOIN `tbl_stories_authors`SA ON ( U.uid = SA.aid ) WHERE U.uid = {$_SESSION['userID']} OR U.curator = {$_SESSION['userID']} GROUP BY U.uid");
				foreach ( $authorData as $aD ) $authors["AUTHORS"][$aD["uid"]] = $aD["label"];
				$authors["ID"] = @$data['uid'];

				$sub = $this->panelMenu($selected, $authors);

				if ( isset($data['uid']) AND isset($authors["AUTHORS"][$data['uid']]) )
				{
					// create an empty array
					$status = [ 'id' => $data['uid'], -1 => 0, 0 => 0, 1 => 0 ];

					// get story count by completion status
					$authorData = $this->exec("SELECT S.completed, COUNT(DISTINCT S.sid) as count FROM `tbl_stories`S INNER JOIN `tbl_stories_authors`SA ON (S.sid = SA.sid AND SA.aid = :aid) GROUP BY S.completed", [ ":aid" => $data['uid'] ]);
					foreach ( $authorData as $aD ) $status[$aD["completed"]] = $aD["count"];

					// get second sub menu segment and place under selected author
					$sub2 = $this->panelMenu('author_sub', $status);
					
//					$menu = [];
					foreach ( $sub as $sKey => $sData )
					{
						if ( $sKey == "sub" ) $menu = array_merge($menu, $sub2);
						else $menu[$sKey] = $sData;
					}
//					print_r($menu);
				}
				else
				{
					unset ($sub["sub"]);
					$menu = $sub;
				}

				$this->menu[$selected]["sub"] = $menu;
			}
			else
				$this->menu[$selected]["sub"] = $this->panelMenu($selected, $data);
		}
		return $this->menu;
	}
	
	public function getCount($module="")
	{
		if ( $module = "library" )
		{
			$sql[]= "SET @bms  := (SELECT CONCAT_WS('//', IF(SUM(counter)>0,SUM(counter),0), GROUP_CONCAT(type,',',counter SEPARATOR '||')) FROM (SELECT SUM(1) as counter, F.type FROM `tbl_user_favourites`F WHERE F.uid={$_SESSION['userID']} AND F.bookmark=1 GROUP BY F.type) AS F1);";
			$sql[]= "SET @favs := (SELECT CONCAT_WS('//', IF(SUM(counter)>0,SUM(counter),0), GROUP_CONCAT(type,',',counter SEPARATOR '||')) FROM (SELECT SUM(1) as counter, F.type FROM `tbl_user_favourites`F WHERE F.uid={$_SESSION['userID']} AND F.bookmark=0 GROUP BY F.type) AS F1);";
			if(array_key_exists("recommendations", $this->config['modules_enabled']))
			{
				$sql[]= "SET @recs := (SELECT COUNT(1) FROM `tbl_recommendations` WHERE `uid` = {$_SESSION['userID']});";
			}
			else $sql[]= "SET @recs := NULL";
			$sql[]= "SELECT @bms as bookmark,@favs as favourite,@recs as recommendation;";

			$data = $this->exec($sql)[0];

			foreach( $data as $key => $count )
				list($counter[$key]['sum'], $counter[$key]['details']) = array_pad(explode("//",$count), 2, '');

			foreach ( $counter as &$count )
			{
				$cc = $this->cleanResult($count['details']);
				if(is_array($cc))
				{
					$count['details'] = [];
					foreach ( $cc as $c ) $count['details'][$c[0]] = $c[1];
				}
				else $count['details'] = "";
			}
			return $counter;
		}
	}
	
	public function ajax($key, $data)
	{
		$bind = NULL;
		
		if ( $key == "messaging" )
		{
			if(isset($data['recipient']))
			{
				$ajax_sql = "SELECT U.nickname as name, U.uid as id from `tbl_users`U WHERE U.nickname LIKE :nickname AND U.groups > 0 AND U.uid != {$_SESSION['userID']} ORDER BY U.nickname ASC LIMIT 10";
				$bind = [ ":nickname" =>  "%{$data['recipient']}%" ];
			}
		}

		if ( isset($ajax_sql) ) return $this->exec($ajax_sql, $bind);
		return NULL;

	}
	
	public function msgInbox($offset=0)
	{
		//$this->title[] = "__Inbox";
		
		$sql = "SELECT m.mid,UNIX_TIMESTAMP(m.date_sent) as date_sent,UNIX_TIMESTAMP(m.date_read) as date_read,m.subject,m.sender as name_id,u.nickname as name, TRUE as can_delete
						FROM `tbl_messaging`m 
							INNER JOIN `tbl_users`u ON ( m.sender = u.uid ) 
						WHERE m.recipient = ".$_SESSION['userID']." 
						ORDER BY date_sent DESC";
		return $this->exec($sql);
	}

	public function msgOutbox($offset=0)
	{
		//$this->title[] = "__Inbox";
		
		$sql = "SELECT m.mid,UNIX_TIMESTAMP(m.date_sent) as date_sent,UNIX_TIMESTAMP(m.date_read) as date_read,m.subject,m.recipient as name_id,u.nickname as name, IF(m.date_read IS NULL,TRUE,FALSE) as can_delete
						FROM `tbl_messaging`m 
							INNER JOIN `tbl_users`u ON ( m.recipient = u.uid ) 
						WHERE m.sender = ".$_SESSION['userID']." 
						ORDER BY date_sent DESC";
		return $this->exec($sql);
	}
	
	public function msgRead($msgID)
	{
		//$this->title[] = "__ReadMessage";
		
		$sql = "SELECT m.mid,UNIX_TIMESTAMP(m.date_sent) as date_sent,UNIX_TIMESTAMP(m.date_read) as date_read,m.subject,m.message,
								m.sender as sender_id, u1.nickname as sender,
								m.recipient as recipient_id, u2.nickname as recipient 
								FROM `tbl_messaging`m 
								INNER JOIN `tbl_users`u1 ON ( m.sender = u1.uid ) 
								INNER JOIN `tbl_users`u2 ON ( m.recipient = u2.uid ) 
								WHERE m.mid = :mid AND ( m.sender = {$_SESSION['userID']} OR m.recipient = {$_SESSION['userID']} ) ORDER BY date_sent DESC";
		$data = $this->exec($sql, [":mid" => $msgID]);
		if ( empty($data) ) return FALSE;

		/* if unread, set read date and change unread counter in SESSION */
		if($data[0]['date_read']==NULL AND $data[0]['recipient_id']==$_SESSION['userID'])
		{
			$this->exec("UPDATE `tbl_messaging`m SET m.date_read = CURRENT_TIMESTAMP WHERE m.mid = {$data[0]['mid']};");
			$_SESSION['mail'][1]--;
		}
		return $data[0];
	}
	
	public function msgReply($msgID=NULL)
	{
		//$this->title[] = "__ReadMessage";
		if ( $msgID )
		{
			$sql = "SELECT m.mid,UNIX_TIMESTAMP(m.date_sent) as date_sent,UNIX_TIMESTAMP(m.date_read) as date_read,m.subject,m.message,
									IF(m.recipient = {$_SESSION['userID']},m.sender,NULL) as recipient_id,
									IF(m.recipient = {$_SESSION['userID']},u1.nickname,NULL) as recipient,
									u1.nickname as sender
									FROM `tbl_messaging`m 
									INNER JOIN `tbl_users`u1 ON ( m.sender = u1.uid ) 
									INNER JOIN `tbl_users`u2 ON ( m.recipient = u2.uid ) 
									WHERE m.mid = :mid AND ( m.sender = {$_SESSION['userID']} OR m.recipient = {$_SESSION['userID']} ) ORDER BY date_sent DESC";
			$data = $this->exec($sql, [":mid" => $msgID]);
		}
		
		if ( empty($data) )
		{
			return
			[
				'recipient' => "",
				'subject' => "",
				'message' => ""
			];
		}

		$data = $data[0];
		$data['recipient'] = ($data['recipient']==NULL) ? "" : json_encode ( array ( "name"	=> $data['recipient'], "id" => $data['recipient_id'] ) );
		$data['subject'] = \Base::instance()->get('LN__PM_ReplySubject')." ".$data['subject'];

		return $data;
	}
	
	public function msgSave($save)
	{
		$saveSQL = "INSERT INTO `tbl_messaging`
					(`sender`, `recipient`, `subject`, `message`)
					VALUES
					( '".$_SESSION['userID']."', :recipient, :subject, :message );";
		
		foreach ( $save['recipient'] as $recipient )
		{
			$bind =
			[
				":recipient" 	=> $recipient,
				":subject"		=> $save['subject'],
				":message"		=> $save['message']
			];
			$data = $this->exec($saveSQL, $bind);
		}
		return TRUE;
	}
	
	public function libraryBookFavDelete($params)
	{
		if ( empty($params['id'][0]) OR empty($params['id'][1]) ) return FALSE;
		if ( in_array($params["id"][0],["AU","RC","SE","ST"]) )
		{
			$mapper=new \DB\SQL\Mapper($this->db, $this->prefix.'user_favourites');
			$mapper->load(array("uid=? AND item=? AND type=? AND bookmark=?",$_SESSION['userID'], $params["id"][1], $params["id"][0], (array_key_exists("bookmark",$params))?1:0 ));
			if ( NULL !== $fid = $mapper->get('fid') )
			{
				$mapper->erase();
				return TRUE;
			}
			unset($mapper);
			return FALSE;
		}
	}
	
	public function loadBookFav($params)
	{
		// ?
		if ( empty($params['id'][0]) OR empty($params['id'][1]) ) return FALSE;

		if ( $params['id'][0]=="AU" )
		{
			$sql = $this->sqlBookFav("AU", FALSE) . 
				"WHERE U.uid = :id ";
		}
		elseif ( $params['id'][0]=="SE" )
		{
			$sql = $this->sqlBookFav("SE", FALSE) . 
				"WHERE Ser.seriesid = :id ";
		}
		elseif ( $params['id'][0]=="ST" )
		{
			$sql = $this->sqlBookFav("ST", FALSE) . 
				"WHERE S.sid = :id ";
		}
		else return FALSE;

		$data = $this->exec($sql,[":bookmark" => (($params[0]=="bookmark")?1:0), ":id"=>$params['id'][1]]);
		if ( sizeof($data)==1 )
		{
			if ( isset($data[0]['authorblock']) )
				$data[0]['authorblock'] = json_decode($data[0]['authorblock'],TRUE);

			return $data[0];
		}
		return FALSE;
	}
	
	public function listBookFav($page, $sort, $params)
	{
		$limit = 10;
		$pos = $page - 1;
		
		if ( in_array($params[1],["AU","RC","SE","ST"]) )
		{
			$sql = $this->sqlBookFav($params[1]) . 
					"ORDER BY {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit;
		}
		else return FALSE;

		$data = $this->exec($sql,[":bookmark" => (($params[0]=="bookmark")?1:0) ] );

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/userCP/library/{$params[0]}/{$params[1]}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		return $data;
	}
	
	protected function sqlBookFav($type, $inner = TRUE)
	{
		$join = $inner ? "INNER" : "LEFT";
		$sql =
		[
			"AU"
				=>	"SELECT SQL_CALC_FOUND_ROWS 'AU' as type, U.uid as id, U.nickname as name, Fav.comments, Fav.visibility, Fav.notify, Fav.fid
						FROM `tbl_users`U 
						{$join} JOIN `tbl_user_favourites`Fav ON ( U.uid = Fav.item AND Fav.uid = {$_SESSION['userID']} AND Fav.type='AU' AND Fav.bookmark = :bookmark ) ",
			"ST"
				=>	"SELECT SQL_CALC_FOUND_ROWS 'ST' as type, S.sid as id, S.title as name, S.cache_authors as authorblock, Fav.comments, Fav.visibility, Fav.notify, Fav.fid
						FROM `tbl_stories`S 
						{$join} JOIN `tbl_user_favourites`Fav ON ( S.sid = Fav.item AND Fav.uid = {$_SESSION['userID']} AND Fav.type='ST' AND Fav.bookmark = :bookmark ) ",
			"SE"
				=>	"SELECT SQL_CALC_FOUND_ROWS 'SE' as type, Ser.seriesid as id, Ser.title as name, Ser.cache_authors, Fav.comments, Fav.visibility, Fav.notify, Fav.fid
						FROM `tbl_series`Ser
						{$join} JOIN `tbl_user_favourites`Fav ON ( Ser.seriesid = Fav.item AND Fav.uid = {$_SESSION['userID']} AND Fav.type='SE' AND Fav.bookmark = :bookmark ) ",
		];
		return $sql[$type];
	}
	
	public function saveBookFav($post, $params)
	{
		if ( isset($post['delete'] ) )
		{
			$this->libraryBookFavDelete($params);
			return TRUE;
		}

		if ( in_array($params['id'][0],["AU","RC","SE","ST"]) )
		{
			$insert = 
			[
				"uid"			=>	$_SESSION['userID'],
				"item"			=>	$params['id'][1],
				"type"			=>	$params['id'][0],
				"bookmark"		=>	(($params[0]=="bookmark") ? 1 : 0),
				"notify"		=>	(int)@isset($post['notify']),
				"visibility"	=>	(isset($post['visibility'])) ? (int)$post['visibility'] : 0,
				"comments"		=>	$post['comments'],
			];
			return $this->insertArray('tbl_user_favourites', $insert, TRUE);
		}
		return FALSE;
	}
}
