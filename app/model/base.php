<?php
namespace Model;

class Base extends \Prefab {

	// persistence settings
	protected $table, $db, $fieldConf, $sqlTmp;

	public function __construct()
	{
		$this->i = 0;
		$this->db = \Base::instance()->get('DB');
		$this->prefix = \Config::instance()->prefix;
		$this->config = \Base::instance()->get('CONFIG');
	}
	
	public function exec($cmds,$args=NULL,$ttl=0,$log=TRUE)
	{
		return $this->db->exec(str_replace("`tbl_", "`{$this->prefix}", $cmds), $args,$ttl,$log);
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

	public function extendConfig()
	{
		$configDB = $this->exec("SELECT C.name, C.value FROM `tbl_config`C WHERE C.to_config_file=0 AND C.form_type != 'note';");
		foreach($configDB as $c)
		{
			$config[$c['name']] = $c['value'];
		}
		return $config;
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
//		preprint($kvpair);
		while (list($key, $value) = each($kvpair))
		{
			$keys[] 	= $key;
			if ($value===NULL)
			{
				$values[] = "NULL";
				unset($kvpair[$key]);
			}
			elseif( $value=="NOW()" )
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
				$this->bindValue("insertArray", $key, $value, \PDO::PARAM_STR);
			}
			if ( 1 == $result = $this->execute("insertArray") )
				return (int)$this->db->lastInsertId();
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

	protected function panelMenu($selected=FALSE)
	{
		$sql = "SELECT M.label, M.link, M.icon, M.evaluate FROM ";

		if ( $selected )
			$sql .= "`tbl_menu_userpanel`M WHERE M.child_of = :selected;";
		else
			$sql .= "`tbl_menu_userpanel`M WHERE M.child_of IS NULL;";

		$data = $this->exec($sql, [":selected"=> $selected]);
		foreach ( $data as $item )
		{
			$menu[$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"] ];
		}
		return $menu;
	}
	
	protected function storyStates()
	{
		$state['completed'] =
		[
			-2 => "__deleted",
			-1 => "__draft",
			 0 => "__wip",
			 1 => "__completed",
		];
		
		$state['validated'] =
		[
			 0 => "__pending",
			 1 => "__selfvalidated",
			 2 => "__admin",
		];
		return $state;
	}
	
	public function getChapter( $story, $chapter, $counting = TRUE )
	{
		$location = \Config::instance()->chapter_data_location;

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

	public function saveChapter( $chapterID, $chapterText )
	{
		$location = \Config::instance()->chapter_data_location;

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
					GROUP_CONCAT(DISTINCT tid,',',tag,',',description ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
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

		$this->update
		(
			'tbl_stories_blockcache',
			[
				'tagblock'			=> serialize($this->cleanResult($item['tagblock'])),
				'characterblock'	=> serialize($this->cleanResult($item['characterblock'])),
				'authorblock'		=> serialize($this->cleanResult($item['authorblock'])),
				'categoryblock'		=> serialize($this->cleanResult($item['categoryblock'])),
				'rating'			=> serialize(explode(",",$item['rating'])),
				'reviews'			=> $item['reviews'],
				'chapters'			=> $item['chapters'],
			],
			['sid=?',$sid]
		);

	}

	private static function cleanResult($messy)
	{
		$mess = explode("||",$messy);
		$mess = (array_unique($mess));
		foreach ( $mess as $element )
		{
			$elements[] = explode(",",$element );
		}
		return($elements);
	}
	
}
