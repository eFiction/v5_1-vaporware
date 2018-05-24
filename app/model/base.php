<?php
namespace Model;

class Base extends \Prefab {

	// persistence settings
	protected $table, $db, $fieldConf, $sqlTmp, $menuCount;

	public function __construct()
	{
		$this->i = 0;
		$this->db = \Base::instance()->get('DB');
		$this->config = \Base::instance()->get('CONFIG');
		$this->prefix = $this->config['prefix'];
	}
	
	public function exec($cmds,$args=NULL,$ttl=0,$log=TRUE)
	{
		return $this->db->exec(str_replace("`tbl_", "`{$this->prefix}", $cmds), $args,$ttl,$log);
	}
	
	public function log()
	{
		return $this->db->log(TRUE);
	}

	public function newPasswordQuality($password1, $password2)
	{
		$this->password_regex = '/^(?=^.{'.$this->config['reg_min_password'].',}$)(?:.*?(?>((?(1)(?!))[a-z]+)|((?(2)(?!))[A-Z]+)|((?(3)(?!))[0-9]+)|((?(4)(?!))[^a-zA-Z0-9\s]+))){'.$this->config['reg_password_complexity'].'}.*$/s';		
		// Passwords match?
		if( $password1 == "" OR $password2 == "" )
			return "missing";

		if ( $password1 != $password2 )
			return "mismatch";

		// Passwords meets the criteria required?
		if ( preg_match( $this->password_regex, $password1, $matches) != 1 )
			return "criteria";
		
		return TRUE;
	}
	
	public function userChangePW($uid, $password)
	{
		// Load a compatibility wrapper for PHP versions prior to 5.5.0
		if ( !function_exists("password_hash") ) include ( "app/inc/password_compat.php" );

		$hash = password_hash( $password, PASSWORD_DEFAULT );
		$this->prepare("updateUser", "UPDATE `tbl_users`U SET U.password = :password WHERE U.uid = :uid");
		$this->bindValue("updateUser", "password", $hash, \PDO::PARAM_STR);
		$this->bindValue("updateUser", "uid",	 $uid,	 \PDO::PARAM_INT);
		$user = $this->execute("updateUser");
	}

	public function canAdmin($link)
	{
		if(NULL==\Base::instance()->get('canAdmin.'.$link))
		{
			if (empty($this->cAM)) $this->cAM = new \DB\SQL\Mapper($this->db,str_replace("tbl_", $this->prefix, 'tbl_menu_adminpanel'));
			$this->cAM->load( array('link=?',$link) );
			\Base::instance()->set('canAdmin.'.$link, ($this->cAM->requires & $_SESSION['groups']) );
		}
	}
	
	protected function prepare($id, $sql)
	{
		$this->sqlTmp[$id]['sql'] = $sql;
		$this->sqlTmp[$id]['param'] = [];
	}
	
	protected function bindValue($id, $label, $value, $type)
	{
		$this->sqlTmp[$id]['param'] = array_merge( $this->sqlTmp[$id]['param'], [ $label => $value ] );
	}
	
	protected function execute($id)
	{
		$data = $this->exec($this->sqlTmp[$id]['sql'], $this->sqlTmp[$id]['param']);
		unset($this->sqlTmp[$id]);
		return $data;
	}
	
	public function update($table, $data, $where)
	{
		$handle = new \DB\SQL\Mapper($this->db,str_replace("tbl_", $this->prefix, $table));
		$handle->load( $where );
		foreach( $data as $key => $value )
		{
			$handle->{$key} = $value;
		}
		$handle->save();
		unset($handle);
	}
	
	public function insertArray	($table, $kvpair, $replace=FALSE)
	{
		$keys = array();
		$values = array();

		while (list($key, $value) = each($kvpair))
		{
			$keys[] 	= $key;
			if ($value===NULL)
			{
				$values[] = "NULL";
				unset($kvpair[$key]);
			}
			elseif( $value==="NOW()" )
			{
				$values[] = "NOW()";
				unset($kvpair[$key]);
			}
			// ???
			elseif ( is_array($value) )
			{
				$values[] = "{$value[0]}( :{$key} )";
				$kvpair[$key] = $value[1];
			}
			else
			{
				$values[] = ":{$key}";
			}
		}

		if(sizeof($keys)>0)
		{
			$sql_query = (($replace===TRUE)?"REPLACE":"INSERT")." INTO `{$table}` (".implode(", ", $keys).") VALUES ( ".implode(", ", $values).")";

			$this->prepare("insertArray", $sql_query);

			foreach($kvpair as $key => $value)
			{
				if ( is_int($value) )
					$this->bindValue("insertArray", $key, $value, \PDO::PARAM_INT);
				else
					$this->bindValue("insertArray", $key, $value, \PDO::PARAM_STR);
			}

			if ( 1 <= $result = $this->execute("insertArray") )
				return (int)$this->db->lastInsertId();
			return FALSE;
		}
		return NULL;
	}
	
//	protected function timeToUser($dbTime, string $formatOut="Y-m-d H:i", bool $timestamp = FALSE)
	protected function timeToUser($dbTime, $formatOut="Y-m-d H:i", $timestamp = FALSE)
	{
		$date = new \DateTime($dbTime);
		$tz_server = timezone_name_get($date->getTimezone());

		if ( empty($tz_user) OR $tz_user == $tz_server )
			return $date->format($formatOut);
		
		$date->setTimezone( new \DateTimeZone($tz_user) );
		return $date->format($formatOut);
	}

	public function str_word_count_utf8($str) {
		return count(preg_split("/\p{L}[\p{L}\p{Mn}\p{Pd}'\x{2019}]{1,}/u",$str));
	}

//	protected function paginate(int $total, $route, int $limit=10)
	protected function paginate($total, $route, $limit=10)
	{
		/**
			Implementing parts of the

			Pagination class for the PHP Fat-Free Framework
			Copyright (c) 2012 by ikkez
			Christian Knuth <mail@ikkez.de>
			@version 1.4.1
			
			found at: https://github.com/ikkez/F3-Sugar/blob/master-v3/Pagination/pagination.php
		**/
		$f3 = \Base::instance();
		
		// Define a prefix
		$prefix = "/page=";
		
		// Get max page number
		$count = ceil($total/$limit);
		if ($count<2) return TRUE;
		// Current page should be at least 1, and $count at max
		$page = (int)min(max(1,$f3->get('paginate.page')),$count);

		// if the page number was too big, reroute to the highest page number
		if ( $f3->get('paginate.page') > $page )
		{
			$f3->reroute("{$route}{$prefix}{$page}", false);
			exit;
		}

		// really needed? must check
		$pos = (int)max(0,min($page-1,$count-1));
		
		// page link range, from config
		$range = $this->config['adjacent_paginations'];
		// build range link array
		$current_range = array( ($page-$range < 1 ? 1 : $page-$range),
            ($page+$range > $count ? $count : $page+$range));
        $rangeIDs = array();
        for($x = $current_range[0]; $x <= $current_range[1]; ++$x) {
            $rangeIDs[] = $x;
		}

		// add data to the global scope
		$f3->set('paginate',
		[
			'total' => $total, // Elements
			'limit' => $limit, // per page
			'count' => $count, // pages
			'pos'   => $pos, // current position
			'page'	=> $page,
			'route' => $route,
			'prefix' => $prefix,
			'firstPage' => ($page > 3) ? 1 : false,
			'lastPage'  => ( ($pos+3) < $count ) ? 1 : false,
			'rangePages' => $rangeIDs,
		]);
	}

	/*
	protected function storyStates($completed,$validated)
	{
		$f3 = \Base::instance();
		// Commented lines are for later versions, providing more options
		$state['completed'] =
		[
			0 => "deleted",
			1 => "draft",
			2 => "wip",
			3 => "completed",
		];
		
		$state['validated'] =
		[
			0 => "closed",
			1 => "moderationStatic",
			2 => "moderationPending",
			3 => "validated",
		];
		
		$state['reason'] =
		[
			0 => "none",
			1 => "user",
			2 => "moderator",
			3 => "admin",
			4 => "forcedRework",
			5 => "minorWorking",
			6 => "majorWorking",
			7 => "minorDone",
			8 => "majorDone",
			9 => "lockedOrphaned",
		];
		return $state;
		return
		[
			"completed" => [ $completed, 	$state['completed'][$completed] ],
			"validated" => [ $validated[1],	$state['validated'][$validated[1]] ],
			"reason" 	=> [ $validated[2],	$state['validated'][$validated[2]] ],
		];
	}
	*/
	
	public function getChapter( $story, $chapter, $counting = TRUE )
	{
		$location = $this->config['chapter_data_location'];

		if ( $location == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterLoad= @$db->exec('SELECT "chaptertext" FROM "chapters" WHERE "sid" = :sid AND "inorder" = :inorder', array(':sid' => $story, ':inorder' => $chapter ))[0];
		}
		else
		{
			$chapterLoad = $this->exec("SELECT C.chaptertext FROM `tbl_chapters`C WHERE C.sid=:sid AND C.inorder=:inorder", array(':sid' => $story, ':inorder' => $chapter ))[0];
		}
		if ( sizeof($chapterLoad)>0 ) $chapterText = $chapterLoad['chaptertext'];
		else return FALSE;
		
		if ( $counting AND \Base::instance()->get('SESSION')['userID'] > 0 )
		{
			$sql_tracker = "INSERT INTO `tbl_tracker` (sid,uid,last_chapter) VALUES (".(int)$story.", ".\Base::instance()->get('SESSION')['userID'].",".(int)$chapter.") 
											ON DUPLICATE KEY
											UPDATE last_read=NOW(),last_chapter=".(int)$chapter.";";
			$this->exec($sql_tracker);
		}
		return nl2br($chapterText);
	}

	public function storyChapterAdd($storyID, $userID=FALSE)
	{
		if ( $userID )
		{
			// coming from userCP, $userID is safe
			$countSQL = "SELECT COUNT(chapid) as chapters, U.uid
							FROM `tbl_stories`S
								LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid )
								INNER JOIN `tbl_stories_authors`SA ON ( SA.sid = S.sid AND SA.type='M' )
									INNER JOIN `new5_users`U ON ( ( U.uid=SA.aid ) AND ( U.uid={$userID} OR U.curator={$userID} ) )
						WHERE S.sid = :sid ";
			$countBind = [ ":sid" => $storyID ];
			
			$chapterCount = $this->exec($countSQL, $countBind);

			if ( empty($chapterCount) OR  $chapterCount[0]['uid']==NULL )
				return FALSE;

			// Get current chapter count and raise
			$chapterCount = $chapterCount[0]['chapters'] + 1;
		}
		else
		{
			if ( FALSE == $chapterCount = $this->exec("SELECT COUNT(chapid) as chapters FROM `tbl_chapters` WHERE `sid` = :sid ", [ ":sid" => $storyID ])[0]['chapters'] )
				return FALSE;

			// Get current chapter count and raise
			$chapterCount++;
		}

		$validated = 1;
		if ( $_SESSION['groups']&32 ) $validated = 2;
		if ( $_SESSION['groups']&128 ) $validated = 3;
		
		$kv = [
			'title'			=> \Base::instance()->get('LN__Chapter')." #{$chapterCount}",
			'inorder'		=> $chapterCount,
			//'notes'			=> '',
			//'workingtext'
			//'workingdate'
			//'endnotes'
			'validated'		=> "1".$validated,
			'wordcount'		=> 0,
			'rating'		=> "0", // allow rating later
			'sid'			=> $storyID,
		];

		$chapterID = $this->insertArray($this->prefix.'chapters', $kv );



		if ( "local" == $this->config['chapter_data_location'] )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterAdd= @$db->exec('INSERT INTO "chapters" ("chapid","sid","inorder","chaptertext") VALUES ( :chapid, :sid, :inorder, :chaptertext )', 
								[
									':chapid' 		=> $chapterID,
									':sid' 			=> $storyID,
									':inorder' 		=> $chapterCount,
									':chaptertext'	=> '',
								]
			);
		}

		/*
		
		
		
		*/
		// ???
		$this->rebuildStoryCache($storyID);
		
		return $chapterID;
	}

	public function saveChapter( $chapterID, $chapterText )
	{
		$location = $this->config['chapter_data_location'];

		if ( $location == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterSave= @$db->exec('UPDATE "chapters" SET "chaptertext" = :chaptertext WHERE "chapid" = :chapid', array(':chapid' => $chapterID, ':chaptertext' => $chapterText ));
		}
		else
		{
			$chapterSave = $this->exec('UPDATE `tbl_chapters` SET `chaptertext` = :chaptertext WHERE `chapid` = :chapid', array(':chapid' => $chapterID, ':chaptertext' => $chapterText ));
		}

		return $chapterSave;
	}

	public function rebuildStoryCache($sid)
	{
		$sql = "SELECT SELECT_OUTER.sid,
					GROUP_CONCAT(DISTINCT tid,',',tag,',',description,',',tgid ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
					GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
					GROUP_CONCAT(DISTINCT uid,',',nickname ORDER BY nickname ASC SEPARATOR '||' ) as authorblock,
					GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
					GROUP_CONCAT(DISTINCT ratingid,',',rating_name,',',rating_image SEPARATOR '||' ) as rating,
					COUNT(DISTINCT fid) AS reviews,
					COUNT(DISTINCT chapid) AS chapters
					FROM
					(
						SELECT S.sid,C.chapid,UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified,
								F.fid,
								S.ratingid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image,
								U.uid, U.nickname,
								Cat.cid, Cat.category,
								TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
								Ch.charid, Ch.charname
							FROM `tbl_stories` S
								LEFT JOIN `tbl_ratings` Ra ON ( Ra.rid = S.ratingid )
								LEFT JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid )
									LEFT JOIN `tbl_users` U ON ( rSA.aid = U.uid )
								LEFT JOIN `tbl_stories_tags`rST ON ( rST.sid = S.sid )
									LEFT JOIN `tbl_tags` T ON ( T.tid = rST.tid AND rST.character = 0 )
										LEFT JOIN `tbl_tag_groups` TG ON ( TG.tgid = T.tgid )
									LEFT JOIN `tbl_characters` Ch ON ( Ch.charid = rST.tid AND rST.character = 1 )
								LEFT JOIN `tbl_stories_categories`rSC ON ( rSC.sid = S.sid )
									LEFT JOIN `tbl_categories` Cat ON ( rSC.cid = Cat.cid )
								LEFT JOIN `tbl_chapters` C ON ( C.sid = S.sid )
								LEFT JOIN `tbl_feedback` F ON ( F.reference = S.sid AND F.type='ST' )
							WHERE S.sid = :sid
					)AS SELECT_OUTER
				GROUP BY sid ORDER BY sid ASC";
		
		$item = $this->exec($sql, ['sid' => $sid] );
		
		if ( empty($item) ) return FALSE;
		
		$item = $item[0];

		$tagblock['simple'] = $this->cleanResult($item['tagblock']);
		if($tagblock['simple']!==NULL) foreach($tagblock['simple'] as $t)
			$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];

		$this->update
		(
			'tbl_stories',
			[
				'cache_tags'		=> json_encode($tagblock),
				'cache_characters'	=> json_encode($this->cleanResult($item['characterblock'])),
				'cache_authors'		=> json_encode($this->cleanResult($item['authorblock'])),
				'cache_categories'	=> json_encode($this->cleanResult($item['categoryblock'])),
				'cache_rating'		=> json_encode(explode(",",$item['rating'])),
				'reviews'			=> $item['reviews'],
				'chapters'			=> $item['chapters'],
			],
			['sid=?',$sid]
		);
	}
	
	public function rebuildContestCache($conid)
	{
		$sql = "SELECT SELECT_OUTER.conid,
					GROUP_CONCAT(DISTINCT tid,',',tag,',',description,',',tgid ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
					GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
					GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
					GROUP_CONCAT(DISTINCT sid,',',title ORDER BY title ASC SEPARATOR '||' ) as storyblock
					FROM
					(
						SELECT C.conid, 
								TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
								Cat.cid, Cat.category,
								S.sid, S.title,
								Ch.charid, Ch.charname
							FROM `tbl_contests`C
								LEFT JOIN `tbl_contest_relations`rC ON ( rC.conid = C.conid )
									LEFT JOIN `tbl_tags`T ON ( T.tid = rC.relid AND rC.type = 'T' )
										LEFT JOIN `tbl_tag_groups`TG ON ( TG.tgid = T.tgid )
									LEFT JOIN `tbl_characters`Ch ON ( Ch.charid = rC.relid AND rC.type = 'CH' )
									LEFT JOIN `tbl_categories`Cat ON ( rC.relid = Cat.cid AND rC.type = 'CA' )
									LEFT JOIN `tbl_stories`S ON ( rC.relid = S.sid AND rC.type = 'ST' )
							WHERE C.conid = :conid
					)AS SELECT_OUTER
				GROUP BY conid ORDER BY conid ASC";
		
		$item = $this->exec($sql, ['conid' => $conid] );
		
		if ( empty($item) ) return FALSE;
		
		$item = $item[0];

		$tagblock['simple'] = $this->cleanResult($item['tagblock']);
		if($tagblock['simple']!==NULL) foreach($tagblock['simple'] as $t)
			$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];

		$this->update
		(
			'tbl_contests',
			[
				'cache_tags'		=> json_encode($tagblock),
				'cache_characters'	=> json_encode($this->cleanResult($item['characterblock'])),
				'cache_categories'	=> json_encode($this->cleanResult($item['categoryblock'])),
				'cache_stories'		=> json_encode($this->cleanResult($item['storyblock'])),
			],
			['conid=?',$conid]
		);
	}

	protected static function cleanResult($messy)
	{
		if ( empty($messy) ) return NULL;
		$mess = explode("||",$messy);
		$mess = (array_unique($mess));
		foreach ( $mess as $element )
		{
			$elements[] = explode(",",$element );
		}
		return($elements);
	}
	
	// http://stackoverflow.com/questions/2915748/convert-a-series-of-parent-child-relationships-into-a-hierarchical-tree/2915920#2915920
	protected function parseTree($tree, $root = null)
	{
		$return = array();
		# Traverse the tree and search for direct children of the root
		foreach($tree as $child => $parent) {
			# A direct child is found
			if($parent == $root) {
				# Remove item from tree (we don't need to traverse this again)
				unset($tree[$child]);
				# Append the child into result array and parse its children
				$return[] = array(
					'name' => $child,
					'children' => $this->parseTree($tree, $child)
				);
			}
		}
		return empty($return) ? null : $return;    
	}
	
	/**
		This function refreshes the user`s cache for feedback and library count on demand
	**/
	public function userCacheRecount($module="")
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
			$sql[]= "SET @rw  := (SELECT CONCAT_WS('//', IF(SUM(counter)>0,SUM(counter),0), GROUP_CONCAT(type,',',counter SEPARATOR '||')) FROM (SELECT SUM(1) as counter, F.type FROM `tbl_feedback`F WHERE F.writer_uid={$_SESSION['userID']} AND F.type IN ('RC','SE','ST') GROUP BY F.type) AS F1);";
			if(array_key_exists("recommendations", $this->config['optional_modules']))
			{
				$sql[]= "SET @rr  := (SELECT CONCAT_WS('//', SUM(SE+ST+RC), GROUP_CONCAT(type,',',IF(ST=0,IF(SE=0,RC,SE),ST) SEPARATOR '||')) FROM 
							(SELECT F.type, COUNT(SA.lid) as ST, COUNT(Ser.seriesid) as SE, COUNT(Rec.recid) as RC
								FROM `tbl_feedback`F
									LEFT JOIN `tbl_stories_authors`SA ON ( F.reference = SA.sid AND F.type='ST' AND SA.aid = {$_SESSION['userID']} )
									LEFT JOIN `tbl_recommendations`Rec ON ( F.reference = Rec.recid AND F.type = 'RC' AND Rec.uid = {$_SESSION['userID']} )
									LEFT JOIN `tbl_series`Ser ON ( F.reference = Ser.seriesid AND F.type = 'SE' AND Ser.uid = {$_SESSION['userID']}	)
							WHERE F.type IN ('RC','SE','ST') GROUP BY F.type) as F1)";
			}
			else
			{
				$sql[]= "SET @rr  := (SELECT CONCAT_WS('//', SUM(SE+ST), GROUP_CONCAT(type,',',IF(ST=0,SE,ST) SEPARATOR '||')) FROM 
							(SELECT F.type, COUNT(SA.lid) as ST, COUNT(Ser.seriesid) as SE
								FROM `tbl_feedback`F
									LEFT JOIN `tbl_stories_authors`SA ON ( F.reference = SA.sid AND F.type='ST' AND SA.aid = {$_SESSION['userID']} )
									LEFT JOIN `tbl_series`Ser ON ( F.reference = Ser.seriesid AND F.type = 'SE' AND Ser.uid = {$_SESSION['userID']} )
							WHERE F.type IN ('RC','SE','ST') GROUP BY F.type) as F1)";
			}
			$sql[]= "SET @rq := (SELECT COUNT(DISTINCT SA.sid) FROM `tbl_feedback`F INNER JOIN `tbl_stories_authors`SA ON ( F.reference = SA.sid AND F.type='ST' AND SA.aid = {$_SESSION['userID']}) )";
			$sql[]= "SET @st := (SELECT COUNT(1) FROM `tbl_stories_authors` WHERE `aid` = {$_SESSION['userID']} )";

			$sql[]= "SET @cw  := (SELECT CONCAT_WS('//', IF(SUM(counter)>0,SUM(counter),0), GROUP_CONCAT(type,',',counter SEPARATOR '||')) FROM (SELECT SUM(1) as counter, F.type FROM `tbl_feedback`F WHERE F.writer_uid={$_SESSION['userID']} AND F.type IN ('N','C') GROUP BY F.type) AS F1);";
			$sql[]= "SET @cr  := (SELECT CONCAT_WS('//', SUM(C+N), GROUP_CONCAT(type,',',IF(C=0,N,C) SEPARATOR '||')) FROM 
							(SELECT F.type, COUNT(F0.fid) as C, COUNT(N.nid) as N
								FROM `tbl_feedback`F
									LEFT JOIN `tbl_feedback`F0 ON ( F.reference_sub = F0.reference AND F.type='C' AND F0.writer_uid = {$_SESSION['userID']} )
									LEFT JOIN `tbl_news`N ON ( F.reference = N.nid AND F.type = 'N' AND N.uid = {$_SESSION['userID']} )
							WHERE F.type IN ('C','N') GROUP BY F.type) as F1)";

			$sql[]= "SELECT @rw as rw, @rr as rr, @rq as rq, @st as st, @cw as cw, @cr as cr;";

			$data = $this->exec($sql)[0];
		}
		elseif ( $module == "messaging" )
		{
			// we should not get here without a userID in session, but if we do, better bail out before zeeee error strikes
			if(empty($_SESSION['userID'])) return NULL;
			
			$sql[]= "SET @inbox  := (SELECT COUNT(1) FROM `tbl_messaging`M WHERE M.recipient = {$_SESSION['userID']});";
			$sql[]= "SET @unread := (SELECT COUNT(1) FROM `tbl_messaging`M WHERE M.recipient = {$_SESSION['userID']} AND M.date_read IS NULL);";
			$sql[]= "SET @outbox := (SELECT COUNT(1) FROM `tbl_messaging`M WHERE M.sender = {$_SESSION['userID']});";
			
			if(array_key_exists("shoutbox", $this->config['optional_modules']))
			{
				$sql[]= "SET @shoutbox := (SELECT COUNT(1) FROM `tbl_shoutbox` WHERE `uid` = {$_SESSION['userID']});";
			}
			else $sql[]= "SET @shoutbox := NULL";
			
			$sql[]= "SELECT @inbox as inbox, @unread as unread, @outbox as outbox, @shoutbox as shoutbox;";

			$data = $this->exec($sql)[0];
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

	
}

