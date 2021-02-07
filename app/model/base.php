<?php
namespace Model;

class Base extends \Prefab {

	// persistence settings
	protected $table, $db, $fieldConf, $sqlTmp, $menuCount;

	public function __construct()
	{
		$this->f3 = \Base::instance();
		$this->i = 0;
		$this->db = $this->f3->get('DB');
		$this->config = $this->f3->get('CONFIG');
		$this->prefix = ( $this->config == NULL ) ? \Config::getPublic('prefix') : $this->config['prefix'];
		$this->vprefix = "v_".$this->prefix;
	}

	public function exec($cmds,$args=NULL,$ttl=0,$log=TRUE)
	{
		$result = $this->db->exec(str_replace(["`tbl_", "`view_"], ["`{$this->prefix}", "`v_{$this->prefix}"], $cmds), $args,$ttl,$log);
		return $result;
	}

	public function log()
	{
		return $this->db->log(TRUE);
	}

	//foreign keys, currently not used
	public function getFKeys()
	{
		$sql = "SELECT CONSTRAINT_NAME as fk, TABLE_NAME as tbl
					FROM information_schema.TABLE_CONSTRAINTS
				WHERE
					information_schema.TABLE_CONSTRAINTS.CONSTRAINT_TYPE = 'FOREIGN KEY'
					AND information_schema.TABLE_CONSTRAINTS.TABLE_SCHEMA = '".$this->db->name()."'
					AND information_schema.TABLE_CONSTRAINTS.TABLE_NAME LIKE '{$this->prefix}%';";
		return $this->exec($sql);
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

	public function insertArray	(string $table, array $kvpair, bool $replace=FALSE) : int
	{
		$keys = array();
		$values = array();

		foreach( $kvpair as $key => $value )
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
			return 0;
		}
		return 0;
	}

	protected function timeToUser(string $dbTime, string $formatOut="Y-m-d H:i", bool $timestamp = FALSE): string
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

	public function storyData(array $replacements=[], array $bind=[])
	{
		$sql = $this->storySQL($replacements);
		$data = $this->exec($sql, $bind);

		if ( sizeof($data)>0 )
		{
			foreach ( $data as &$dat)
			{
				$favs = $this->cleanResult($dat['is_favourite']);
				$dat['is_favourite'] = [];
				if(!empty($favs))
				foreach ( $favs as $value )
					if ( isset($value[1]) ) $dat['is_favourite'][$value[0]] = $value[1];
			}
		}

		return $data;
	}

	public function storySQL(array $replacements=[])
	{
		$sql_StoryConstruct = "SELECT SQL_CALC_FOUND_ROWS
				S.sid, S.title, S.summary, S.storynotes, S.completed, S.wordcount, UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified,
				S.count,GROUP_CONCAT(Coll.collid,',',rCS.inorder,',',Coll.title ORDER BY Coll.title DESC SEPARATOR '||') as in_series @EXTRA@,
				".((isset($this->config['optional_modules']['contests']))?"GROUP_CONCAT(rSC.relid) as contests,":"")."
				GROUP_CONCAT(Fav.bookmark,',',Fav.fid SEPARATOR '||') as is_favourite,
				Edit.uid as can_edit,
				S.cache_authors, S.cache_tags, S.cache_characters, S.cache_categories, S.cache_rating, S.chapters, S.reviews,
				S.translation, S.trans_from, S.trans_to
			FROM `tbl_stories`S
				@JOIN@
			".((isset($this->config['optional_modules']['contests']))?"LEFT JOIN `tbl_contest_relations`rSC ON ( rSC.relid = S.sid AND rSC.type = 'story' )":"")."
				LEFT JOIN `tbl_collection_stories`rCS ON ( rCS.sid = S.sid )
					LEFT JOIN `tbl_collections`Coll ON ( Coll.collid=rCS.collid )
				LEFT JOIN `tbl_ratings`Ra ON ( Ra.rid = S.ratingid )
				LEFT JOIN `tbl_stories_authors`rSAE ON ( S.sid = rSAE.sid )
					LEFT JOIN `tbl_users`Edit ON ( ( rSAE.aid = Edit.uid ) AND ( ( Edit.uid = ".(int)$_SESSION['userID']." ) OR ( Edit.curator = ".(int)$_SESSION['userID']." ) ) )
				LEFT JOIN `tbl_user_favourites`Fav ON ( Fav.item = S.sid AND Fav. TYPE = 'ST' AND Fav.uid = ".(int)$_SESSION['userID'].")
			WHERE S.completed @COMPLETED@ 6 AND S.validated >= 30 @WHERE@
			GROUP BY S.sid
			@ORDER@
			@LIMIT@";

		// default replacements
		$replace =
		[
			"@EXTRA@"		=> "",
			"@JOIN@"		=> "",
			"@COMPLETED@"	=> ">=",
			"@WHERE@"		=> ($_SESSION['preferences']['ageconsent']==1)?"":"AND Ra.ratingwarning=0 ",
			"@ORDER@"		=> "",
			"@LIMIT@"		=> ""
		];

		// insert custom replacements
		foreach ( $replacements as $key => $value )
		{
			$replace["@{$key}@"] = $value;
		}
		return str_replace(array_keys($replace), array_values($replace), $sql_StoryConstruct);
	}

	public function collectionsListBase(array $userData = [], bool $ordered = FALSE): array
	{
		$limit = 5;
		$pos = (int)$this->f3->get('paginate.page') - 1;

		if ( sizeof($userData) )
		{
			$whereUser = "C.uid={$userData['uid']} AND ";
			if ( $userData['visibility'] < 2 )
				$status = "('F','P','A')";
			else
				$status = "('P','A')";
		}
		else
		{
			$whereUser = "";
			$status = "('P','A')";
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS
					C.collid, C.parent_collection, C.title, C.summary, C.open, C.max_rating,
					COUNT(DISTINCT rCS.sid) as stories, C.chapters, C.wordcount, C.reviews,
					C.cache_authors, C.cache_tags, C.cache_characters, C.cache_categories,
					GROUP_CONCAT(Fav.bookmark,',',Fav.fid SEPARATOR '||') as is_favourite,
					C2.title as parent_title
				FROM `tbl_collections`C
					LEFT JOIN `tbl_collections`C2 ON ( C.parent_collection = C2.collid )
					LEFT JOIN `tbl_collection_stories`rCS ON ( C.collid = rCS.collid AND rCS.confirmed = 1 )
					LEFT JOIN `tbl_user_favourites`Fav ON ( Fav.item = C.collid AND Fav.type IN ('CO','SE') AND Fav.uid = ".(int)$_SESSION['userID'].")
				WHERE {$whereUser} C.ordered=".(int)$ordered." AND C.chapters>0 AND C.status IN {$status}
				GROUP BY C.collid
				LIMIT ".(max(0,$pos*$limit)).",".$limit;
		return [ $sql, $limit ];
	}

	protected function paginate(int $total, string $route, int $limit=10)
	{
		/**
		*	Implementing parts of the

		*	Pagination class for the PHP Fat-Free Framework
		*	Copyright (c) 2012 by ikkez
		*	Christian Knuth <mail@ikkez.de>
		*	@version 1.4.1

		*	found at: https://github.com/ikkez/F3-Sugar/blob/master-v3/Pagination/pagination.php
		**/
		$f3 = \Base::instance();

		// Define a prefix
		$prefix = "/page=";

		// Get max page number
		if ( 2 > $count = ceil($total/$limit) )
			return;
		// Current page should be at least 1, and $count at max
		$page = (int)min(max(1,$f3->get('paginate.page')),$count);

		// if the page number was too big, reroute to the highest page number
		if ( $f3->get('paginate.page') > $page )
		{
			$f3->reroute("{$route}{$prefix}{$page}", false);
			exit;
		}

		// page link range, from config
		$range = $this->config['adjacent_paginations'];
		// set up page range
		$first_page = $page-$range < 1 ? 1 : $page-$range;
		$last_page	= $page+$range > $count ? $count : $page+$range;

		// add data to the global scope
		$f3->set('paginate',
		[
			'total' => $total,	// Elements
			'limit' => $limit,	// per page
			'count' => $count,	// pages
			'page'	=> $page,
			'route' => $route,
			'prefix' => $prefix,
			'firstPage' => $first_page,
			'lastPage'  => $last_page,
		]);
	}

	/**
	* (re)build poll cache
	* 2021-01-10 re-write
	*
	* @param	int							$pollID 	selected poll
	* @param	\DB\SQL\Mapper	$poll 		mapper to this poll
	* @param	\DB\SQL\Mapper	$ballots 	mapper to the votes
	*
	* @return array						cache data
	*/
	public function pollBuildCache(int $pollID, \DB\SQL\Mapper $poll = NULL, \DB\SQL\Mapper $ballots = NULL): ?array
	{
		// Open a new poll mapper if required
		if ( !$poll )
		{
			$poll = new \DB\SQL\Mapper($this->db, $this->prefix."poll");
			$poll->load(["poll_id = ?",$pollID]);
		}

		// attempt a safe retreat when there is no such poll
		if ( NULL === $poll->poll_id )
			return NULL;

		// where there are no options, there is no poll
		if ( "" == $options = json_decode($poll->options, TRUE) )
		{
			// empty poll
			$poll->cache = json_encode(array());
			$poll->save();
			return array();
		}

		if ( $poll->results == NULL )
		// new style poll
		{
			// grab _all_ votes from the stack
			$votes = $ballots ?: new \DB\SQL\Mapper($this->db, $this->prefix."poll_votes");
			// count their votes, not very afficient but with a limited data set, it should do
			foreach ($options as $key => $option)
				$cache_array[$option] = $votes->count('poll_id='.$pollID.' AND option='.($key+1));
			// save JSON
			$poll->votes = $votes->count('poll_id='.$pollID);
		}
		else
		// old style poll. this should have been done during upgrade, but just to be on the shaved side of things
		{
			$results = json_decode($poll->results, TRUE);
			foreach ( $results as $key => $value )
				$cache_array[$options[$key]] = $value;
			$poll->votes = array_sum($results);
		}

		// numeric sorting, disabled
		//arsort( $cache_array, SORT_NUMERIC  );
		$poll->cache = json_encode($cache_array);
		$poll->save();
		return $cache_array;
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
			2 => "abandoned",
			3 => "adoption"
			4 => "paused",
			5 => "help",
			6 => "wip",
			9 => "completed",
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
			**4 => "forcedRework",
			**5 => "minorWorking",
			**6 => "majorWorking",
			**7 => "minorDone",
			**8 => "majorDone",
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

	/**
	* Load the actual chapter text
	* rewrite 2020-09
	*
	* @param	int		$storyID	Story ID
	* @param	int		$chapterID	Chapter ID (changed from inorder)
	* @param	bool	$counting	Are we counting this as a read or do we need the contents for an editing mask
	*
	* @return	string				Result or empty
	*/
	public function getChapterText( int $storyID, int $chapterID, bool $counting = TRUE ) : string
	{
		if ( $this->config['chapter_data_location'] == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			@$chapterLoad= $db->exec('SELECT "chaptertext" FROM "chapters" WHERE "sid" = :storyID AND "chapid" = :chapterID', array(':storyID' => $storyID, ':chapterID' => $chapterID ))[0];
		}
		else
		{
			$chapterLoad = $this->exec("SELECT C.chaptertext FROM `tbl_chapters`C WHERE C.sid=:storyID AND C.chapid=:chapterID", array(':storyID' => $storyID, ':chapterID' => $chapterID ))[0];
		}

		if ( empty($chapterLoad['chaptertext']) )
			return "";

		if ( $counting AND \Base::instance()->get('SESSION')['userID'] > 0 )
		{
			$sql_tracker = "INSERT INTO `tbl_tracker` (sid,uid,last_chapter) VALUES (".(int)$storyID.", ".\Base::instance()->get('SESSION')['userID'].",".(int)$chapterID.")
											ON DUPLICATE KEY
											UPDATE last_read=NOW(),last_chapter=".(int)$chapterID.";";
			$this->exec($sql_tracker);
		}
		return nl2br($chapterLoad['chaptertext']);
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
	* Process SQL data fields
	* rewrite 2021-02, moving from \View to \Model
	*	for use with 'array_map([$this,'dataProcess'], $data);'
	*
	* @param	array		$item			Array containing story data (or similar)
	*
	* @return	array							Array with processed content
	*/
	protected function dataProcess(array $item): array
	{
		if (isset($item['modified']))		$item['modified']	= ($item['modified'] > ($item['published'] + (24*60*60) ) ) ?
																			date(\Config::getPublic('date_format'),$item['modified']) :
																			NULL;
		if (isset($item['published']))				$item['published']				= date(\Config::getPublic('date_format'),$item['published']);
		if (isset($item['wordcount'])) 				$item['wordcount']				= number_format($item['wordcount'], 0, '','.');
		if (isset($item['count'])) 						$item['count']						= number_format($item['count'], 0, '','.');
		if (isset($item['cache_categories'])) $item['cache_categories']	= json_decode($item['cache_categories'],TRUE);
		if (isset($item['cache_rating'])) 		$item['cache_rating']			= json_decode($item['cache_rating'],TRUE);
		if (isset($item['max_rating'])) 			$item['max_rating']				= json_decode($item['max_rating'],TRUE);
		if (isset($item['cache_tags'])) 			$item['cache_tags']				= json_decode($item['cache_tags'],TRUE);
		if (isset($item['cache_characters'])) $item['cache_characters']	= json_decode($item['cache_characters'],TRUE);
		if (isset($item['cache_stories']))		$item['cache_stories']		= json_decode($item['cache_stories'],TRUE);
		if (isset($item['cache_authors']))
			if ( NULL !== $item['authors'] 	= 	$item['cache_authors'] 		= json_decode($item['cache_authors'],TRUE) )
				array_walk($item['authors'], function (&$v, $k){ $v = $v[1];} );
		// build a combined tag/character array
		$item['all_tags'] 			= array_merge( $item['cache_tags']['simple']??[], $item['cache_characters']??[] );
		//								$item['number']		= isset($item['inorder']) ? "{$item['inorder']}&nbsp;" : "";
		return $item;
	}

	/**
	*	This function refreshes the user`s cache for feedback and library count on demand
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
				$sql[]= "SET @rr  := (SELECT CONCAT_WS('//', SUM(SC+ST+RC), GROUP_CONCAT(type,',',IF(ST=0,IF(SC=0,RC,SC),ST) SEPARATOR '||')) FROM
							(SELECT F.type, COUNT(SA.lid) as ST, COUNT(C.collid) as SC, COUNT(Rec.recid) as RC
								FROM `tbl_feedback`F
									LEFT JOIN `tbl_stories_authors`SA ON ( F.reference = SA.sid AND F.type='ST' AND SA.aid = {$_SESSION['userID']} )
									LEFT JOIN `tbl_recommendations`Rec ON ( F.reference = Rec.recid AND F.type = 'RC' AND Rec.uid = {$_SESSION['userID']} )
									LEFT JOIN `tbl_collections`C ON ( F.reference = C.collid AND F.type = 'SC' AND C.uid = {$_SESSION['userID']}	)
							WHERE F.type IN ('RC','SC','ST') GROUP BY F.type) as F1)";
			}
			else
			{
				$sql[]= "SET @rr  := (SELECT CONCAT_WS('//', SUM(SC+ST), GROUP_CONCAT(type,',',IF(ST=0,SC,ST) SEPARATOR '||')) FROM
							(SELECT F.type, COUNT(SA.lid) as ST, COUNT(C.collid) as SC
								FROM `tbl_feedback`F
									LEFT JOIN `tbl_stories_authors`SA ON ( F.reference = SA.sid AND F.type='ST' AND SA.aid = {$_SESSION['userID']} )
									LEFT JOIN `tbl_collections`C ON ( F.reference = C.collid AND F.type = 'SC' AND C.uid = {$_SESSION['userID']} )
							WHERE F.type IN ('RC','SC','ST') GROUP BY F.type) as F1)";
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

			$sql[]= "SET @inbox  := (SELECT COUNT(1) FROM `tbl_messaging`M WHERE M.recipient = {$_SESSION['userID']} AND M.sent IS NULL);";
			$sql[]= "SET @unread := (SELECT COUNT(1) FROM `tbl_messaging`M WHERE M.recipient = {$_SESSION['userID']} AND M.sent IS NULL AND M.date_read IS NULL);";
			$sql[]= "SET @outbox := (SELECT COUNT(1) FROM `tbl_messaging`M WHERE M.sender = {$_SESSION['userID']} AND M.sent IS NOT NULL);";

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

	public function userPreferencesReBuild( array &$pref ) : void
	{
		$pref = array_merge (
					[
						"ageconsent"	=> 0,
						"useEditor"		=> 1,
						"sortNew"		=> 1,
						"showTOC"		=> 1,
						"language"		=> $this->f3->get('CONFIG.language_default'),
						"layout"		=> "default",
						"hideTags"		=> NULL,
					],
					$pref ?: []
				);

		$mapper = new \DB\SQL\Mapper( $this->db, $this->prefix."users" );
		$mapper->load(['uid = ?', $_SESSION['userID'] ]);

		$mapper->preferences = json_encode
							([
								"ageconsent"	=> $pref['ageconsent'],
								"useEditor"		=> $pref['useEditor'],
								"sortNew"		=> $pref['sortNew'],
								"showTOC"		=> $pref['showTOC'],
								"language"		=> $pref['language'],
								"layout"		=> $pref['layout'],
								"hideTags"		=> $pref['hideTags'],
							]);
		$mapper->save();
	}

}
