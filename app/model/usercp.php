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
				$allowed=[];
				// get associated author and curator data
				$authorData = $this->exec("SELECT U.uid, CONCAT(U.nickname, ' (',COUNT(DISTINCT SA.lid), ')') as label, IF(U.uid={$_SESSION['userID']},1,0) as curator
												FROM `tbl_users`U 
													LEFT JOIN `tbl_stories_authors`SA ON ( U.uid = SA.aid AND SA.type='M' ) 
												WHERE U.uid = {$_SESSION['userID']} OR U.curator = {$_SESSION['userID']} 
												GROUP BY U.uid
												ORDER BY curator DESC, label ASC");
				foreach ( $authorData as $aD )
				{
					$authors["AUTHORS"][$aD["uid"]] = $aD["label"];
					$allowed[$aD["uid"]] = TRUE;
				}
				\Base::instance()->set('allowed_authors', $allowed);
				$authors["ID"] = @$data['uid'];

				$sub = $this->panelMenu($selected, $authors);

				if ( isset($data['uid']) AND isset($authors["AUTHORS"][$data['uid']]) )
				{
					// create an empty array
					$status = [ 'id' => $data['uid'], -1 => 0, 0 => 0, 1 => 0 ];

					// get story count by completion status
					$authorData = $this->exec("SELECT S.completed, COUNT(DISTINCT S.sid) as count 
													FROM `tbl_stories`S
														INNER JOIN `tbl_stories_authors`SA ON (S.sid = SA.sid AND SA.aid = :aid AND SA.type='M') 
													GROUP BY S.completed", [ ":aid" => $data['uid'] ]);
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
		if ( $module == "library" )
		{
			$sql[]= "SET @bms  := (SELECT CONCAT_WS('//', IF(SUM(counter)>0,SUM(counter),0), GROUP_CONCAT(type,',',counter SEPARATOR '||')) FROM (SELECT SUM(1) as counter, F.type FROM `tbl_user_favourites`F WHERE F.uid={$_SESSION['userID']} AND F.bookmark=1 GROUP BY F.type) AS F1);";
			$sql[]= "SET @favs := (SELECT CONCAT_WS('//', IF(SUM(counter)>0,SUM(counter),0), GROUP_CONCAT(type,',',counter SEPARATOR '||')) FROM (SELECT SUM(1) as counter, F.type FROM `tbl_user_favourites`F WHERE F.uid={$_SESSION['userID']} AND F.bookmark=0 GROUP BY F.type) AS F1);";
			if(array_key_exists("recommendations", $this->config['optional_modules']))
			{
				$sql[]= "SET @recs := (SELECT COUNT(1) FROM `tbl_recommendations` WHERE `uid` = {$_SESSION['userID']});";
			}
			else $sql[]= "SET @recs := NULL";
			$sql[]= "SELECT @bms as bookmark,@favs as favourite,@recs as recommendation;";

			$data = $this->exec($sql)[0];
		}
		elseif ( $module == "feedback" )
		{
			$user = \User::instance();
			
			if ( NULL == $data = json_decode(@$user->feedback_cache,TRUE) )
			{
				$data = $this->userCacheRecount("feedback");
				$user->feedback_cache = json_encode($data);
				$user->save();
			}
			return (array)$data;
		}

		if ( isset($data) )
		{
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
		return NULL;
	}
	
	public function authorStoryAdd($data)
	{
		$newStory = new \DB\SQL\Mapper($this->db, $this->prefix."stories");
		$newStory->title		= $data['new_title'];
		$newStory->completed	= -1;
		$newStory->validated	= 11;
		$newStory->save();
		
		$newID = $newStory->_id;
		
		// add the story-author relation
		$newRelation = new \DB\SQL\Mapper($this->db, $this->prefix."stories_authors");
		$newRelation->sid	= $newID;
		$newRelation->aid	= $data['uid'];
		$newRelation->type	= 'M';
		$newRelation->save();
		
		// already counting as author? mainly for stats ...
		$editUser = new \DB\SQL\Mapper($this->db, $this->prefix."users");
		$editUser->load(array("uid=?",$data['uid']));
		if ( $editUser->groups < 4 )
			$editUser->groups += 4;
		$editUser->save();

		return $newID;
	}
	
	public function authorStoryLoadInfo($sid, $uid)
	{
		
		$data = $this->exec
		(
			"SELECT S.*, COUNT(DISTINCT Ch.chapid) as chapters
				FROM `tbl_stories`S
					INNER JOIN `tbl_stories_authors`A ON ( S.sid = A.sid AND A.type='M' AND A.aid = :aid )
					LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid)
				WHERE S.sid = :sid",
			[":sid" => $sid, ":aid" => $uid ]
		);
		if (sizeof($data)==1 AND $data[0]['sid']!="")
		{
			$data[0]['states']  = $this->storyStates();
			$data[0]['ratings'] = $this->exec("SELECT rid, rating, ratingwarning FROM `tbl_ratings`");
			return $data[0];
		}
		return FALSE;
	}
	
	public function loadChapterList($sid)
	{
		$data = $this->exec
		(
			"SELECT Ch.sid,Ch.chapid,Ch.title
				FROM `tbl_chapters`Ch
			WHERE Ch.sid = :sid ORDER BY Ch.inorder ASC",
			[":sid" => $sid ]
		);
		return $data;
		if (sizeof($data)>0) return $data;
		return FALSE;
	}

	public function storyEditPrePop(array $storyData)
	{
		$categories = json_decode($storyData['cache_categories']);
		if(sizeof($categories))
		{
			foreach ( $categories as $tmp ) $pre['cat'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
			$pre['cat'] = json_encode($pre['cat']);
		}
		else $pre['cat'] = '""';

		$tags = json_decode($storyData['cache_tags'],TRUE)['simple'];
		if(sizeof($tags)>0)
		{
			foreach ( $tags as $tmp ) $pre['tag'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
			$pre['tag'] = json_encode($pre['tag']);
		}
		else $pre['tag'] = '""';

		$characters = json_decode($storyData['cache_characters']);
		if(sizeof($characters))
		{
			foreach ( $characters as $tmp ) $pre['char'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
			$pre['char'] = json_encode($pre['char']);
		}
		else $pre['char'] = '""';
		
		$authors = $this->exec ( "SELECT U.uid as id, U.nickname as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'M' );", [ ":sid" => $storyData['sid'] ]);
		$pre['mainauth'] = json_encode($authors);

		$supauthors = $this->exec ( "SELECT U.uid as id, U.nickname as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'S' );", [ ":sid" => $storyData['sid'] ]);
		$pre['supauth'] = json_encode($supauthors);

		return $pre;
	}

	public function authorStoryStatus($sid)
	{
		$sql = "SELECT S.title, S.validated, S.completed
					FROM `tbl_stories`S 
						LEFT JOIN `tbl_stories_authors`A ON ( S.sid = A.sid AND A.type='M' )
						INNER JOIN `tbl_users`U ON ( A.aid = U.uid AND ( U.uid = {$_SESSION['userID']} OR U.curator = {$_SESSION['userID']} ) )
					WHERE S.sid=:sid";
		
		$data = $this->exec($sql, [":sid" => $sid] );
		return ( empty($data) ) ? NULL : $data[0];
	}
	
	public function authorStoryList($select,$author,$sort,$page)
	{
		$limit = 20;
		$pos = $page - 1;
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS S.sid,S.title, S.validated as story_validated, S.completed, Ch.validated as chapter_validated
					FROM `tbl_stories`S 
						LEFT JOIN `tbl_stories_authors`A ON ( S.sid = A.sid AND A.type='M' )
							INNER JOIN `tbl_users`U ON ( A.aid = U.uid AND ( U.uid = {$_SESSION['userID']} OR U.curator = {$_SESSION['userID']} ) )
						LEFT JOIN `tbl_chapters`Ch ON ( Ch.sid = S.sid )
					WHERE A.aid=:aid AND S.completed = ";

		switch ($select)
		{
			case "finished":
				$sql .= "1";
				break;
			case "unfinished":
				$sql .= "0";
				break;
			case "drafts":
				$sql .= "-1";
				break;
			default:
				return FALSE;
		}
		
		$sql .= " GROUP BY S.sid
				 ORDER BY chapter_validated ASC, {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit;
		
		$data = $this->exec($sql, [":aid" => $author] );

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/userCP/author/uid={$author}/{$select}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		
		return $data;
	}
	
	public function authorStoryChapterAdd($sid, $uid)
	{
		return parent::storyChapterAdd($sid, $uid);
	}
	
	public function authorStoryChapterLoad( $story, $chapter )
	{
		$data = $this->exec
		(
			"SELECT Ch.sid,Ch.chapid,Ch.inorder,Ch.title,Ch.notes,Ch.validated,Ch.rating
				FROM `tbl_chapters`Ch
			WHERE Ch.sid = :sid AND Ch.chapid = :chapter",
			[":sid" => $story, ":chapter" => $chapter ]
		);
		if (empty($data)) return FALSE;
		$data = $data[0];
		$data['chaptertext'] = parent::getChapter( $story, $data['inorder'], FALSE );
		
		return $data;
	}

	public function authorCuratorRemove($uid=NULL)
	{
		$this->exec("UPDATE `tbl_users`U set U.curator = NULL WHERE U.uid = :uid;", [ ":uid" => ($uid ?: $_SESSION['userID']) ]);
		return TRUE;
	}
	
	public function authorCuratorGet()
	{
		$data = $this->exec("SELECT C.nickname, C.uid
								FROM `tbl_users`U 
									INNER JOIN `tbl_users`C ON ( U.curator = C.uid AND U.uid = {$_SESSION['userID']} )
							");

		$return['self'] = ( sizeof($data) ) ? json_encode ( array ( "name"	=> $data[0]['nickname'], "id" => $data[0]['uid'] ) ) : "";
		
		$return['others'] = $this->exec("SELECT U.nickname, U.uid
											FROM `tbl_users`U
												INNER JOIN `tbl_users`C ON ( U.curator = C.uid AND C.uid = {$_SESSION['userID']} )
											");
		return $return;
	}
	
	public function authorCuratorSet($uid)
	{
		$data = $this->exec("SELECT U.uid FROM `tbl_users`U WHERE U.uid = :uid AND U.groups > 0;",[":uid" => $uid]);
		if(empty($data)) return FALSE;
		$this->exec("UPDATE `tbl_users`U set U.curator = {$data[0]['uid']} WHERE U.uid = {$_SESSION['userID']};");
		return TRUE;
	}
	
	public function feedbackHomeStats($data)
	{
		$chapters = $this->exec("SELECT COUNT(1) as count FROM `tbl_chapters`C INNER JOIN `tbl_stories_authors`SA ON ( C.sid = SA.sid ) WHERE SA.aid = {$_SESSION['userID']};")[0]['count'];
		$stats = 
		[
			"stories"				=> $data['st']['sum'],
			"storiesReviewedQ"		=> $data['st']['sum'] > 0 ? round(($data['rq']['sum']/$data['st']['sum']*100),1) : NULL,
			"storiesReviewedPie"	=> $data['st']['sum'] > 0 ? (int)($data['rq']['sum']/$data['st']['sum']*360) :NULL,
			"reviewsPerStory"		=> $data['rq']['sum'] > 0 ? round(($data['rr']['details']['ST']/$data['rq']['sum']),1) : NULL,
			"reviewsPerStoryTotal"	=> $data['st']['sum'] > 0 ? round(($data['rr']['details']['ST']/$data['st']['sum']),1) : NULL,
			"reviewsPerChapter"		=> $chapters > 0 ?			round(($data['rr']['details']['ST']/$chapters),1) : NULL,
		];
//		print_r($stats);
//		print_r($data);
		return $stats;
	}
	
	public function ajax($key, $data)
	{
		$bind = NULL;
		
		if ( $key == "messaging" )
		{
			if(isset($data['namestring']))
			{
				$ajax_sql = "SELECT U.nickname as name, U.uid as id from `tbl_users`U WHERE U.nickname LIKE :nickname AND U.groups > 0 AND U.uid != {$_SESSION['userID']} ORDER BY U.nickname ASC LIMIT 10";
				$bind = [ ":nickname" =>  "%{$data['namestring']}%" ];
			}
		}
		elseif ( $key == "stories" )
		{
			if(isset($data['author']))
			{
				$ajax_sql = "SELECT U.nickname as name, U.uid as id from `tbl_users`U WHERE U.nickname LIKE :nickname AND (U.groups&5) ORDER BY U.nickname ASC LIMIT 10";
				$bind = [ ":nickname" =>  "%{$data['author']}%" ];
			}
			elseif(isset($data['category']))
			{
				$ajax_sql = "SELECT category as name, cid as id from `tbl_categories`C WHERE C.category LIKE :category ORDER BY C.category ASC LIMIT 5";
				$bind = [ ":category" =>  "%{$data['category']}%" ];
			}
			elseif(isset($data['tag']))
			{
				$ajax_sql = "SELECT label as name, tid as id from `tbl_tags`T WHERE T.label LIKE :tag ORDER BY T.label ASC LIMIT 5";
				$bind = [ ":tag" =>  "%{$data['tag']}%" ];
			}
			elseif(isset($data['character']))
			{
				$ajax_sql = "SELECT Ch.charname as name, Ch.charid as id from `tbl_characters`Ch WHERE Ch.charname LIKE :charname ORDER BY Ch.charname ASC LIMIT 5";
				$bind = [ ":charname" =>  "%{$data['character']}%" ];
			}
			elseif(isset($data['chaptersort']))
			{
				if(sizeof($_SESSION['allowed_authors']))
				{
					$authors = $this->exec("SELECT rel.aid FROM `tbl_stories_authors`rel WHERE rel.sid = :sid AND rel.type='M' AND rel.aid IN (".implode(",",$_SESSION['allowed_authors']).")", [ ":sid" => $data['chaptersort']] );
					if(empty($authors)) return NULL;

					$chapters = new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
					if ( $this->config['chapter_data_location'] == "local" )
						$chaptersLocal = new \DB\SQL\Mapper(\storage::instance()->localChapterDB(), "chapters");

					foreach ( $data["neworder"] as $order => $id )
					{
						if ( is_numeric($order) && is_numeric($id) && is_numeric($data["chaptersort"]) )
						{
							$chapters->load(array('chapid = ? AND sid = ?',$id, $data['chaptersort']));
							$chapters->inorder = $order+1;
							$chapters->save();
							if ( $this->config['chapter_data_location'] == "local" )
							{
								$chaptersLocal->load(array('chapid = ? AND sid = ?',$id, $data['chaptersort']));
								$chaptersLocal->inorder = $order+1;
								$chaptersLocal->save();
							}
						}
					}
				}
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
	
	public function msgDelete($message)
	{
		$sql = "DELETE FROM `tbl_messaging` WHERE mid = :message AND ( ( sender = {$_SESSION['userID']} AND date_read IS NULL ) OR ( recipient = {$_SESSION['userID']} ) )";
		if ( 1 === $this->exec($sql, [ ":message" => $message ]) )
			return TRUE;
		
		$trouble = $this->exec("SELECT mid,date_read,sender,recipient FROM `tbl_messaging` WHERE mid=:message;", [ ":message" => $message ]);

		// message doesn't exist
		if ( sizeof($trouble)==0 )
			return "notfound";
		else $trouble = $trouble[0];
		
		// user is sender, but msg has been read
		if ( $trouble['sender']==$_SESSION['userID'] AND $trouble['date_read']>0 )
			return "msgread";
		// user is neither recipient nor sender
		if ( $trouble['recipient']!=$_SESSION['userID'] AND $trouble['sender']!=$_SESSION['userID'] )
			return "noaccess";
		// might not happen, but let's cover this
		return "unknown";
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
	
	public function listBookFav($page, $sort, $params)
	{
		$limit = 10;
		$pos = $page - 1;
		
		if ( in_array($params[1],["AU","RC","SE","ST"]) )
		{
			$sql = $this->sqlMaker("bookfav", $params[1]) . 
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
	
	public function loadBookFav($params)
	{
		// ?
		if ( empty($params['id'][0]) OR empty($params['id'][1]) ) return FALSE;

		if ( $params['id'][0]=="AU" )
		{
			$sql = $this->sqlMaker("bookfav", "AU", FALSE) . 
				"WHERE U.uid = :id ";
		}
		elseif ( $params['id'][0]=="SE" )
		{
			$sql = $this->sqlMaker("bookfav", "SE", FALSE) . 
				"WHERE Ser.seriesid = :id ";
		}
		elseif ( $params['id'][0]=="ST" )
		{
			$sql = $this->sqlMaker("bookfav", "ST", FALSE) . 
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
	
	public function listReviews($page, $sort, $params)
	{
		$limit = 10;
		$pos = $page - 1;

		if ( in_array($params[2],["RC","SE","ST"]) )
		{
			$sql = $this->sqlMaker("feedback".$params[1], $params[2]) . 
					( $params[1]=="written" ? "GROUP BY F.reference " : "") .
					"ORDER BY {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit;
					//echo $sql;exit;
		}
		else return FALSE;

		$data = $this->exec($sql);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/userCP/feedback/{$params[0]}/{$params[1]}/{$params[2]}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		return $data;
	}

	public function loadReview($params)
	{
		// ?
		if ( empty($params['id'][0]) OR empty($params['id'][1]) ) return FALSE;

		if ( $params[1] == "written")
		{
			if ( $params['id'][0] == "ST" )
			{
				$sql = $this->sqlMaker("feedback".$params[1], "ST", FALSE) . 
					"AND F.fid = :id ";
			}
		}
		else return FALSE;
		
		$data = $this->exec($sql,[ ":id"=>$params['id'][1] ]);

		if ( sizeof($data)==1 )
			return $data[0];
		return FALSE;
	}
	
	protected function sqlMaker($module, $type, $inner = TRUE)
	{
		$join = $inner ? "INNER" : "LEFT";
		$sql['bookfav'] =
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
			"RC"
				=>	"SELECT SQL_CALC_FOUND_ROWS 'RC' as type, Rec.recid as id, Rec.title as name, Rec.author as cache_authors, Fav.comments, Fav.visibility, Fav.notify, Fav.fid
						FROM `tbl_recommendations`Rec
						{$join} JOIN `tbl_user_favourites`Fav ON ( Rec.recid = Fav.item AND Fav.uid = {$_SESSION['userID']} AND Fav.type='RC' AND Fav.bookmark = :bookmark ) ",
		];

		$sql['feedbackwritten'] =
		[
			"ST"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, SA.sid as id, Ch.inorder as chapter, GROUP_CONCAT(DISTINCT U.nickname SEPARATOR ', ') as name, U.uid, F.text, S.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
						INNER JOIN `tbl_stories`S ON ( F.reference = S.sid )
							{$join} JOIN `tbl_stories_authors`SA ON ( S.sid = SA.sid )
							INNER JOIN `tbl_users`U ON ( U.uid = SA.aid )
						LEFT JOIN `tbl_chapters`Ch ON ( F.reference_sub = Ch.chapid )
						WHERE F.writer_uid = {$_SESSION['userID']} AND F.type='ST' ",
			"SE"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Ser.seriesid as id, GROUP_CONCAT(DISTINCT U.nickname SEPARATOR ', ') as name, U.uid, F.text, Ser.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
						INNER JOIN `tbl_series`Ser ON ( F.reference = Ser.seriesid )
							INNER JOIN `tbl_users`U ON ( U.uid = Ser.uid )
						WHERE F.writer_uid = {$_SESSION['userID']} AND F.type='SE' ",
			"RC"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Rec.recid as id, Rec.author as name, F.text, Rec.title, Rec.url, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
							INNER JOIN `tbl_recommendations`Rec ON ( F.reference = Rec.recid )
						WHERE F.writer_uid = {$_SESSION['userID']} AND F.type='ST' ",
		];

		$sql['feedbackreceived'] =
		[
			"ST"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, SA.sid as id, Ch.inorder as chapter, IF(F.writer_uid>0,U.nickname,F.writer_name) as name, U.uid, F.text, S.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
							LEFT JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
							INNER JOIN `tbl_stories_authors`SA ON ( F.reference = SA.sid AND SA.aid = {$_SESSION['userID']} )
							INNER JOIN `tbl_stories`S ON ( F.reference = S.sid )
								LEFT JOIN `tbl_chapters`Ch ON ( F.reference_sub = Ch.chapid )
						WHERE F.type='ST' ",
			"SE"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Ser.seriesid as id, IF(F.writer_uid>0,U.nickname,F.writer_name) as name, U.uid, F.text, Ser.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
							INNER JOIN `tbl_series`Ser ON ( F.reference = Ser.seriesid AND Ser.uid = {$_SESSION['userID']} )
							LEFT JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
						WHERE F.type='SE' ",
			"RC"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Rec.recid as id, IF(F.writer_uid>0,U.nickname,F.writer_name) as name, U.uid, F.text, Rec.title, Rec.url, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
							INNER JOIN `tbl_recommendations`Rec ON ( F.reference = Rec.recid AND Rec.uid = {$_SESSION['userID']} )
							LEFT JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
						WHERE F.type='RC' ",
		];
		if ( isset($sql[$module][$type]) )
			return $sql[$module][$type];
		else return NULL;
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
	
	public function settingsCheckPW($oldPW)
	{
		// Load a compatibility wrapper for PHP versions prior to 5.5.0
		if ( !function_exists("password_hash") ) include ( "app/inc/password_compat.php" );

		$password = $this->exec("SELECT U.password FROM `tbl_users` U where ( U.uid = {$_SESSION['userID']} )")[0]['password'];
		
		if ( password_verify ( $oldPW, $password ) )
			return TRUE;
		
		if( $password==md5($oldPW) )
		{
			$this->userChangePW( $_SESSION['userID'], $oldPW );
			return TRUE;
		}
		
		return FALSE;
	}
}
