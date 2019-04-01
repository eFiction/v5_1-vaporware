<?php
namespace Model;

class AdminCP extends Controlpanel {

	protected $menu = [];
	protected $access = [];
	
	public function ajax($key, $data)
	{
		$bind = NULL;
		
		if ( $key == "search" )
		{
			if(isset($data['tagname']))
			{
				$ajax_sql = "SELECT T.label as name,T.tid as id FROM `tbl_tags`T WHERE T.label LIKE :label LIMIT 10";
				$bind = [ ":label" =>  "%{$data['tagname']}%" ];
			}
			elseif(isset($data['charname']))
			{
				$ajax_sql = "SELECT Ch.charname as name,Ch.charid as id FROM `tbl_characters`Ch WHERE Ch.charname LIKE :label LIMIT 10";
				$bind = [ ":label" =>  "%{$data['charname']}%" ];
			}
			elseif(isset($data['contestname']))
			{
				$ajax_sql = "SELECT C.title as name,C.conid as id FROM `tbl_contests`C WHERE C.title LIKE :label LIMIT 10";
				$bind = [ ":label" =>  "%{$data['contestname']}%" ];
			}
		}
		elseif ( $key == "editMeta" )
		{
			if(isset($data['category']))
			{
				$ajax_sql = "SELECT category as name, cid as id from `tbl_categories`C WHERE C.category LIKE :category AND C.locked = 0 ORDER BY C.category ASC LIMIT 5";
				$bind = [ ":category" =>  "%{$data['category']}%" ];
			}
			elseif(isset($data['author']))
			{
				$ajax_sql = "SELECT U.nickname as name, U.uid as id from `tbl_users`U WHERE U.nickname LIKE :nickname AND ( U.groups & 5 ) ORDER BY U.nickname ASC LIMIT 5";
				$bind = [ ":nickname" =>  "%{$data['author']}%" ];
			}
			elseif(isset($data['tag']))
			{
				$ajax_sql = "SELECT label as name, tid as id from `tbl_tags`T WHERE T.label LIKE :tag ORDER BY T.label ASC LIMIT 10";
				$bind = [ ":tag" =>  "%{$data['tag']}%" ];
			}
			elseif(isset($data['character']))
			{
				$ajax_sql = "SELECT Ch.charname as name, Ch.charid as id from `tbl_characters`Ch WHERE Ch.charname LIKE :charname ORDER BY Ch.charname ASC LIMIT 5";
				$bind = [ ":charname" =>  "%{$data['character']}%" ];
			}
		}
		elseif ( $key == "storySearch" )
		{
			if(isset($data['storyID']))
			{
				$ajax_sql = "SELECT S.title as name,S.sid as id from `tbl_stories`S WHERE S.title LIKE :story OR S.sid = :sid ORDER BY S.title ASC LIMIT 5";
				$bind = [ ":story" =>  "%{$data['storyID']}%", ":sid" =>  $data['storyID'] ];
			}
		}
		elseif ( $key == "userSearch" )
		{
			if(isset($data['userID']))
			{
				$ajax_sql = "SELECT U.nickname as name,U.uid as id from `tbl_users`U WHERE ( U.nickname LIKE :user OR U.uid = :uid ) AND U.uid > 0 ORDER BY U.nickname ASC LIMIT 5";
				$bind = [ ":user" =>  "%{$data['userID']}%", ":uid" =>  $data['userID'] ];
			}
		}
		elseif ( $key == "chaptersort" )
		{
			$chapters = new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
			foreach ( $data["neworder"] as $order => $id )
			{
				if ( is_numeric($order) && is_numeric($id) && is_numeric($data["story"]) )
				{
					$chapters->load(array('chapid = ? AND sid = ?',$id, $data['story']));
					$chapters->inorder = $order+1;
					$chapters->save();
				}
			}
		}
		elseif ( $key == "ratingsort" )
		{
			$chapters = new \DB\SQL\Mapper($this->db, $this->prefix.'ratings');
			foreach ( $data["neworder"] as $order => $id )
			{
				echo $order."#".$id."*";
				if ( is_numeric($order) && is_numeric($id) )
				{
					$chapters->load(array('rid = ?',$id));
					$chapters->inorder = $order+1;
					$chapters->save();
				}
			}
			exit;
		}

		if ( isset($ajax_sql) ) return $this->exec($ajax_sql, $bind);
		return NULL;
	}
	
	public function settingsFields($select)
	{
		$f3 = \Base::instance();
		
		$sql = "SELECT `name`, `value`, `form_type`, `can_edit`
					FROM `tbl_config` 
					WHERE 
						`admin_module` LIKE :module 
						AND `can_edit` > 0 
					ORDER BY `section_order` ASC";
		$data = $this->exec($sql,[ ":module" => $select ]);
		foreach ( $data as &$d )
		{
			list ( $d['comment'], $d['comment_small'] ) = array_merge ( explode("@SMALL@", $f3->get('LN__CFG_'.$d['name'])), array(FALSE) );

			$d['form_type'] = explode("//", $d['form_type']);
			$d['type'] = @array_shift($d['form_type']);
			if ($d['type']=="select")
			{
				array_walk( $d['form_type'],
                      function(&$v) { $v = @explode("=",$v); },  NULL);
			}
		}
		return [ "section" => $select, "fields" => $data];
	}
	
	public function saveKeys($data)
	{
		$affected=0;
		$sqlUpdate = "UPDATE `tbl_config` SET `value` = :value WHERE `name` = :key and `admin_module` = :section;";
		
		foreach ( $data as $section => $fields )
		{
			foreach($fields as $key => $value)
			{
				if ( $res = $this->exec($sqlUpdate,[ ":value" => $value, ":key" => $key, ":section" => $section ]) )
					$affected++;
			}
		}

		// Force re-caching right now
		\Cache::instance()->clear('config');
		\Base::instance()->set('CONFIG', \Config::instance()->load() );
		
		return [ $affected, FALSE ]; // prepare for error check
	}
	
	public function jsonPrepop($rawData)
	{
		if ( $rawData == NULL ) return "[]";
		foreach ( parent::cleanResult($rawData) as $tmp )
			$data[] = [ "id" => $tmp[0], "name" => $tmp[1] ];
		return json_encode( $data );	
	}

	public function checkAccess($link, $exists = FALSE)
	{
		if ( $exists ) return isset($this->access[$link]);
		return ( isset($this->access[$link]) AND (int)$this->access[$link]&(int)$_SESSION['groups'] );
	}

//	public function menuShow($selected=FALSE)
	public function menuShow($selected=FALSE)
	{
		$sql = "SELECT M.label, M.link, M.icon, M.child_of, M.link, M.evaluate, ";
		if ( !($_SESSION['groups']&128) )
		{
			$sql .= "M.requires 
					FROM `tbl_menu_adminpanel`M2
						LEFT JOIN `tbl_menu_adminpanel`M ON ( M2.link = M.child_of OR M2.link=M.link )
					WHERE M2.child_of IS NULL AND M2.active = 1
					ORDER BY M.child_of,M.order ASC ";
		}
		else
		{
			if ( $selected )
				$select = " OR `child_of` = :selected ";

			$sql .= "1 as requires
					FROM `tbl_menu_adminpanel`M 
					WHERE ( `child_of` IS NULL {$select})
					ORDER BY M.child_of,M.order ASC";
		}

		$data = $this->exec($sql, [":selected"=> $selected]);
		
		foreach ( $data as $item )
		{
			// fixed to return 0 if module disabled or not present
			if( empty($item['evaluate']) OR 1 == eval("return (isset(\$this->config{$item['evaluate']})) ? \$this->config{$item['evaluate']} : 0;") )
			{
				if ( isset($menu[$item['child_of']]) )
					$menu[$item['child_of']]['sub'][$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"], "requires" => $item["requires"] ];

				else $menu[$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"], "requires" => $item["requires"] ];

				$this->access[$item['link']] = $item["requires"];
			}
		}

		/**
			If menu is created for a moderator, traverse the menu and clear branches that can't be accessed
		**/
		if ( !($_SESSION['groups']&128) )
		{
			$menu = $this->menuSecure($menu);
			foreach ( $menu as $key => &$m )
				if ( $key != $selected ) unset ( $m['sub'] );
		}

		return $menu;
	}
	
//	public function menuShowUpper($selected=FALSE)
	public function menuShowUpper($selected=FALSE)
	{
		if(!$selected) return NULL;
		$sql = "SELECT M.*
					FROM `tbl_menu_adminpanel`M
				WHERE `child_of` = :selected AND M.requires <= {$_SESSION['groups']}
				ORDER BY M.order ASC";
		return $this->exec( $sql, [ ":selected" => $selected ] );
	}
	
	protected function menuSecure($menu)
	{
		foreach ( $menu as &$m )
		{
			if ( isset($m['sub']) ) $m['sub'] = $this->menuSecure($m['sub']);
			if ( !((int)$_SESSION['groups']&(int)$m['requires']) AND @sizeof($m['sub'])==0) $m = [];
		}
		return array_filter($menu);
	}

//	public function categoryAdd( int $parent_cid, array $data=[] )
	public function categoryAdd($parent_cid, array $data=[] )
	{
		// "clean" target category level
		$this->categoryMove(0, NULL, $parent_cid);

		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');

		// get number of elements with same parent
		$count = $categories->count(["parent_cid = ?", $parent_cid ]);

		// get parent level from siblings
		$categories->load(["cid = ?", $parent_cid ]);
		if ( $categories->dry() )	$leveldown = 0;
		else 						$leveldown = $categories->leveldown + 1;
		$categories->reset();
		
		$categories->category 		= $data['category'];
		$categories->description 	= $data['description'];
		$categories->locked 		= isset($data['locked'])?1:0;
		$categories->inorder		= $count;
		$categories->leveldown		= $leveldown;
		$categories->parent_cid		= $parent_cid;
		
		$categories->save();
		$newCategory = $categories->get('_id'); 
		
		// recount parent category
		if ( $parent_cid>0 )	$this->cacheCategories($parent_cid);
		$this->cacheCategories($categories->_id);

		$this->categoryMove(0, NULL, $parent_cid);

		return $categories->_id;
	}
	
	public function categories()
	{
		return $this->exec("SELECT Cat.cid as id, Cat.category FROM `tbl_categories`Cat ORDER BY Cat.category ASC;");
	}

	public function categoryListFlat()
	{
		$sql = "SELECT 
					C.cid, C.parent_cid, C.category, C.locked, C.leveldown, C.inorder, C.stats,
					COUNT(C1.cid) as counter 
				FROM `tbl_categories`C 
				INNER JOIN `tbl_categories`C1 ON C.parent_cid=C1.parent_cid 
				GROUP BY C.cid 
				ORDER BY C.leveldown DESC, C.inorder ASC";
		$data = $this->exec($sql);

		if ( sizeof($data) == 0 ) return NULL;
		
		foreach ( $data as $item )
		{
			$item['stats'] = json_decode($item['stats'],TRUE);
			$temp[$item['parent_cid']][] = $item;
			if ( isset($temp[$item['cid']]) ) $temp[$item['parent_cid']] = array_merge ( $temp[$item['parent_cid']], $temp[$item['cid']]);
		}
		return $temp[0];
	}

//	public function categoryLoad(int $cid)
	public function categoryLoad($cid)
	{
		if ( $cid == 0 ) return NULL;
		$sql = "SELECT cid as id, parent_cid, category, description, image, locked, leveldown, inorder, stats FROM `tbl_categories`C WHERE C.cid = :cid";
		$data = $this->exec($sql, [":cid" => $cid ]);
		if (sizeof($data)==1) return $data[0];
		return FALSE;
	}

//	public function categoryLoadPossibleParents(int $cid)
	public function categoryLoadPossibleParents($cid)
	{
		$sql = "SELECT C.cid, C.parent_cid, C.leveldown, C.category
					FROM `tbl_categories`C 
					INNER JOIN `tbl_categories`C2 ON ( ( C.parent_cid = C2.parent_cid OR C.cid = C2.parent_cid )AND C2.cid = :cid ) 
				WHERE C.cid != :cid2
				ORDER BY C.leveldown, C.inorder ASC ";
		
		$data = $this->exec($sql, [":cid" => $cid, ":cid2" => $cid ]);

		return $data;
	}

//	public function categorySave(int $cid, array $data)
	public function categorySave($cid, array $data)
	{
		$category=new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$category->load(array('cid=?',$cid));
		$parent_cid = $category->parent_cid;
		$category->copyfrom( 
			[ 
				"category" => $data['category'], 
				"description" => $data['description'],
				"locked" => isset($data['locked']) ? : 0,
				"parent_cid" => $data['parent_cid'], 
			]
		);

		$i = $category->changed("category");
		$i += $category->changed("description");
		$i += $category->changed("locked");
		if ( $category->changed("parent_cid") )
		{
			$i++;
			if ( $data['parent_cid']==0 )
				$new_level = 0;
			else
			{
				$parent=new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
				$parent->load(array('cid=?',$data['parent_cid']));
				$new_level = $parent->leveldown+1;
			}
			$this->categoryLevelAdjust($cid, $new_level);
			
			$category->save();

			$this->cacheCategories($parent_cid);
			$this->cacheCategories($data['parent_cid']);
		}
		else $category->save();

		return $i;
	}
	
//	protected function categoryLevelAdjust( int $cid, int $level)
	protected function categoryLevelAdjust($cid, $level)
	{
		$category=new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$category->load(array('cid=?',$cid));
		$category->leveldown = $level;
		$category->save();
		
		$subCategories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$subCategories->load(array('parent_cid=?',$cid));
		while ( !$subCategories->dry() )
		{
			$this->categoryLevelAdjust( $subCategories->cid, $level+1 );
			$subCategories->next();
		}
	}
	
//	public function categoryMove(int $catID=0, $direction=NULL, $parent=NULL)
	public function categoryMove($catID=0, $direction=NULL, $parent=NULL)
	{
		if ( $parent === NULL )
		{
			$sql = "SELECT C.parent_cid 
						FROM `tbl_categories`C 
						WHERE C.`cid`= :catID";

			$data = $this->exec ( $sql, [ ":catID" => $catID ] );
			if ( empty($data[0]) OR !is_numeric($data[0]['parent_cid']) ) return FALSE;
			$parent = $data[0]['parent_cid'];
		}
		
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$categories->load(
			["parent_cid = ?", $parent ],
			[
				'order' => "inorder ".(($direction=="up") ? "DESC" : "ASC"),
			]
		);
		$elements = $categories->count(["parent_cid = ?", $parent ]);

		// when moving elements upwards, we need to invert the entire logic
		if ( $direction == "up" ) $i = $elements + 1;
		else $i = 0;

		// init vacant spot
		$vacant = -1;
		
		while ( !$categories->dry() )
		{  // gets dry when we passed the last record
			if ( $direction == "up" ) $i--; 
			else $i++;
			if ( $categories->cid == $catID AND $direction!==NULL )
			{
				if ( $direction == "up" )
					$categories->inorder = max(1,($i-1));

				else
					$categories->inorder = min($elements,($i+1));

				// remember the vacant spot
				$vacant = $i;
			}
			elseif ( $direction == "down" AND $i == $vacant+1 )
				$categories->inorder = ($i-1);

			elseif ( $direction == "up" AND $i == $vacant-1 )
				$categories->inorder = ($i+1);

			else 
				$categories->inorder = $i;

			$categories->save();
			// moves forward even when the internal pointer is on last record
			$categories->next();
		}
		$this->cacheCategories($parent);
		return $parent;
	}
	
//	public function categoryDelete( int $cid )
	public function categoryDelete( $cid )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$delete->load( ["cid = ?", $cid ] );
		if ( 1 != $delete->count( ["cid = ?", $cid ] ) ) return FALSE;
		$stats = json_decode( $delete->stats, TRUE );
		if ( $stats['sub']===NULL AND $stats['count']==0 )
		{
			$parent = $delete->parent_cid;
			$delete->erase( ["cid = ?", $cid ] );
			$this->cacheCategories($parent);
			return TRUE;
		}
		else return FALSE;
	}
	
	public function characterList($page, $sort)
	{
		/*
		$tags = new \DB\SQL\Mapper($this->db, $this->prefix.'characters' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS Ch.charid, Ch.charname, Ch.count, Cat.category
				FROM `tbl_characters`Ch 
				LEFT JOIN `tbl_categories`Cat ON ( Ch.catid=Cat.cid )
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/characters/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}

	public function characterLoad(int $charid)
	{
		$sql = "SELECT Ch.charid as id, Ch.charname, Ch.biography, Ch.count, Cat.cid, Cat.category 
					FROM `tbl_characters`Ch
					LEFT JOIN `tbl_categories`Cat ON ( Ch.catid=Cat.cid )
					WHERE Ch.charid = :charid";
		$data = $this->exec($sql, [":charid" => $charid ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}
	
	public function contestsList(int $page, array $sort) : array
	{
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS
					C.conid, C.title, 
					UNIX_TIMESTAMP(C.date_open) as date_open, UNIX_TIMESTAMP(C.date_close) as date_close, UNIX_TIMESTAMP(C.vote_closed) as vote_closed, 
					C.cache_tags, C.cache_characters, 
					U.nickname, COUNT(R.lid) as count
				FROM `tbl_contests`C
					LEFT JOIN `tbl_users`U ON ( C.uid = U.uid )
					LEFT JOIN `tbl_contest_relations`R ON ( C.conid = R.conid AND R.type='ST' )
				GROUP BY C.conid
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/contests",
			$limit
		);

		return $data;
		
	}

	public function contestLoad(int $conid)
	{
		$sql = "SELECT C.conid as id, C.title, C.summary, C.concealed, C.date_open, C.date_close,
					GROUP_CONCAT(T.tid,',',T.label SEPARATOR '||') as tag_list,
					GROUP_CONCAT(Ch.charid,',',Ch.charname SEPARATOR '||') as character_list, 
					GROUP_CONCAT(Cat.cid,',',Cat.category SEPARATOR '||') as category_list, 
					U.uid, U.nickname
					FROM `tbl_contests`C
					LEFT JOIN `tbl_users`U ON ( C.uid=U.uid )
					LEFT JOIN `tbl_contest_relations`RelC ON ( C.conid=RelC.conid )
						LEFT JOIN `tbl_tags`T ON ( RelC.relid = T.tid AND RelC.type='T' )
						LEFT JOIN `tbl_characters`Ch ON ( RelC.relid = Ch.charid AND RelC.type='CH' )
						LEFT JOIN `tbl_categories`Cat ON ( RelC.relid = Cat.cid AND RelC.type='CA' )
					WHERE C.conid = :conid";
					/*
					--GROUP_CONCAT(S.sid,',',S.title SEPARATOR '||') as story_list,
					--LEFT JOIN `tbl_stories`S ON ( RelC.relid = S.sid AND RelC.type='ST' )
					*/

		$data = $this->exec($sql, [":conid" => $conid ]);
		if (sizeof($data)==1) 
		{
			$data[0]['date_open'] = ($data[0]['date_open']>0)
				? $this->timeToUser($data[0]['date_open'],  $this->config['date_format'])
				: "";
			$data[0]['date_close'] = ($data[0]['date_close']>0)
				? $this->timeToUser($data[0]['date_close'], $this->config['date_format'])
				: "";

			//$data[0]['story_list']		 = parent::cleanResult($data[0]['story_list']);
			$data[0]['pre']['tag']		 = $this->jsonPrepop($data[0]['tag_list']);
			$data[0]['pre']['character'] = $this->jsonPrepop($data[0]['character_list']);
			$data[0]['pre']['category']	 = $this->jsonPrepop($data[0]['category_list']);
			return $data[0];
		}
		return NULL;
	}

	public function contestLoadEntries($conid)
	{
		$sql = "SELECT S.sid, S.title
					FROM `tbl_contests`C
						LEFT JOIN `tbl_contest_relations`RelC ON ( C.conid = RelC.conid )
						INNER JOIN `tbl_stories`S ON ( S.sid = RelC.relid AND RelC.type='ST' )
					WHERE C.conid = :conid";

		$data = $this->exec($sql, [":conid" => $conid ]);
		
		return $data;
	}
	
	public function contestAdd($name)
	{
		$contest=new \DB\SQL\Mapper($this->db, $this->prefix.'contests');
		$contest->uid = $_SESSION['userID'];	// create under current user
		$contest->title = $name;
		$contest->concealed = 1;				// create hidden
		$contest->save();
		return $contest->get('_id');			// return new ID for edit form
	}
	
	public function contestSave($conid, array $data)
	{
		if( empty($data['title']) )
		{
			\Base::instance()->set('form_error', "__EmptyLabel");
			return FALSE;
		}
		$contest=new \DB\SQL\Mapper($this->db, $this->prefix.'contests');
		if ( $data['date_close'] < $data['date_open'] ) $data['date_close'] = $data['date_open'];

		$contest->load(array('conid=?',$conid));
		$contest->copyfrom( 
			[
				"title"			=> $data['title'],
				"concealed"		=> isset($data['concealed']) ? 1 : 0,
				"summary"		=> $data['summary'],
				"date_open"		=> empty($data['date_open']) ?
										NULL :
										\DateTime::createFromFormat($this->config['date_format'], $data['date_open'])->format('Y-m-d')." 00:00:00",
				"date_close"	=> empty($data['date_close']) ?
										NULL :
										\DateTime::createFromFormat($this->config['date_format'], $data['date_close'])->format('Y-m-d')." 00:00:00",
			]
		);

		$i  = $contest->changed("title");
		$i += $contest->changed("concealed");
		$i += $contest->changed("summary");
		$i += $contest->changed("date_open");
		$i += $contest->changed("date_close");
		
		$contest->save();
		
		// update relation table
		$this->contestRelation( $conid, $data['tag'], "T" );
		$this->contestRelation( $conid, $data['character'], "CH" );
		$this->contestRelation( $conid, $data['category'], "CA" );

		$this->rebuildContestCache($contest->conid);

		return $i;
	}
	
	private function contestRelation( $conid, $data, $type )
	{
		// Check tags:
		$data = explode(",",$data);
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'contest_relations');

		foreach ( $relations->find(array('`conid` = ? AND `type` = ?',$conid,$type)) as $X )
		{
			if ( FALSE === $temp = array_search($X['relid'], $data) )
			{
				// Excess relation, drop from table
				$relations->erase(['lid=?',$X['lid']]);
			}
			else unset($data[$temp]);
		}
		
		// Insert any tag IDs not already present
		if ( sizeof($data)>0 )
		{
			foreach ( $data as $temp )
			{
				if ( !empty($temp) )		// Fix adding empty entries
				{
					// Add relation to table
					$relations->reset();
					$relations->conid = $conid;
					$relations->relid = $temp;
					$relations->type = $type;
					$relations->save();
				}
			}
		}
		unset($relations);
	}

//	public function contestDelete(int $conid)
	public function contestDelete($conid)
	{
		$contest=new \DB\SQL\Mapper($this->db, $this->prefix.'contests');
		$contest->load(array('conid=?',$conid));
		$_SESSION['lastAction'] = [ "deleteResult" => $contest->erase() ];
	}

//	public function featuredList ( int $page, array $sort, string &$status )
	public function featuredList( $page, array $sort, &$status )
	{
		/*
		int status = 
			1: active
			2: past
			3: future
		*/

		/*
		active:
		SELECT * FROM `tbl_featured` WHERE status=1 OR ( start < NOW() AND end > NOW() )
		*/
		/*
		past:
		SELECT * FROM `tbl_featured` WHERE status=2 OR end < NOW()
		*/
		/*
		future:
		SELECT * FROM `tbl_featured` WHERE start > NOW()
		*/

		$limit = 20;
		$pos = $page - 1;
		
		switch( $status )
		{
			case "future":
				$join = "F.status=3 OR ( F.status IS NULL AND F.start > NOW() )";
				break;
			case "past":
				$join = "F.status=2 OR ( F.status IS NULL AND F.end < NOW() )";
				break;
			default:
				$status = "current";
				$join = "F.status=1 OR ( F.status IS NULL AND F.start < NOW() AND F.end > NOW() )";
		}

		$sql = str_replace
				(
					"%JOIN%",
					$join,
					"SELECT SQL_CALC_FOUND_ROWS S.title, S.sid, S.summary, S.cache_authors, S.cache_rating
						FROM `tbl_stories`S
						INNER JOIN `tbl_featured`F ON ( F.type='ST' AND F.id = S.sid AND %JOIN% )
					ORDER BY {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit
				);

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/featured/select={$status}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		
		return $data;
	}
	
//	public function featuredLoad ( int $sid )
	public function featuredLoad( $sid )
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS S.title, S.sid, S.summary, F.status, F.start, F.end, F.uid, U.nickname, S.cache_authors, S.cache_rating
					FROM `tbl_stories`S
						LEFT JOIN `tbl_featured`F ON ( F.type='ST' AND F.id = S.sid )
						LEFT JOIN `tbl_users`U ON ( F.uid = U.uid )
				WHERE S.sid = :sid";
		$data = $this->exec($sql, [":sid" => $sid ]);
		if (sizeof($data)==1)
		{
			$data[0]['cache_authors'] = json_decode($data[0]['cache_authors'], TRUE);
			return $data[0];
		}
		return NULL;
	}

//	public function featuredSave(int $sid, array $data)
	public function featuredSave($sid, array $data)
	{
		$i = NULL;
		$feature=new \DB\SQL\Mapper($this->db, $this->prefix.'featured');
		$feature->load(array('sid=?',$sid));
		$feature->copyfrom( 
			[ 
				"status"	=> $data['status'], 
				"sid"		=> $sid,
				"uid"		=> $_SESSION['userID']
			]
		);

		if ( TRUE === $feature->changed("status") )
		{
			if ( $data['status'] < 3 AND $feature->start === NULL )
				$feature->start = date('Y-m-d H:i:s');
			if ( $data['status'] == 1 AND $feature->end === NULL )
				$feature->end = date('Y-m-d H:i:s');
			$i = 1;
		}

		$feature->save();
		return $i;
	}
	
	public function logGetCount()
	{
		$count = [];
		$countSQL = "SELECT L.type, COUNT(L.id) as items FROM `tbl_log`L @WHERE@ GROUP BY L.type;";
		$data = $this->exec(str_replace("@WHERE@","",$countSQL));
		if ( sizeof($data) )
		{
			foreach ( $data as $dat )
				$count[$dat['type']]['all'] = $dat['items'];
		}
		$data = $this->exec(str_replace("@WHERE@","WHERE L.new=1",$countSQL));
		if ( sizeof($data) )
		{
			foreach ( $data as $dat )
				$count[$dat['type']]['new'] = $dat['items'];
		}
		return $count;
	}
	
	public function logGetData($sub=FALSE, $page, array $sort)
	{
		$limit = 50;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS U.uid, U.nickname, 
					L.uid as uid_reg, L.id, L.action, L.ip, UNIX_TIMESTAMP(L.timestamp) as timestamp, L.type, L.subtype, L.version, L.new
				FROM `tbl_log`L LEFT JOIN `tbl_users`U ON L.uid=U.uid ";

		if ( $sub )
			$sql .= "WHERE L.type = :sub ";

		$sql .= "ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		if ($sub)
			$data = $this->exec($sql, [":sub" => $sub]);
		else
			$data = $this->exec($sql);
		
		$geo = \Web\Geo::instance();

		foreach ( $data as &$item )
		{
			if ( $item['version']==0 )
			// eFiction 3 original, try to do some cleanup
			// tested against english and german log entries, considering the order of elements
			{
				if ( $item['type']=="AM" AND preg_match("/.+> (.*)./i", $item['action'], $matches) )
				// matching _LOG_RECALCREVIEWS / _LOG_CATCOUNTS, _LOG_OPTIMIZE, _LOG_BACKUP
				{
					$item['action'] = [ 'job'=>$matches[1] ];
				}
				elseif ( $item['type']=="DL" )
				{
					if ( preg_match("/.+\?sid=(\d*)'>(.*?)<\/.+\?uid=(\d*)'>(.*?)<\/.+\s(\d).*/i", $item['action'], $matches) )
					// matching _LOG_ADMIN_DEL_CHAPTER
					{
						$item['action'] = [ 
							'sid' => $matches[1], 'title' => $matches[2],
							'aid' => $matches[3], 'name' => $matches[4],
							'chapter' => $matches[5]
						];
						$item['subtype'] = 'c';
					}
					elseif ( preg_match("/(.*<\/a>)*+.*\'(.*)\'.*/i", $item['action'], $matches) )
					// matching _LOG_ADMIN_DEL_SERIES
					{
						$item['action'] = [ 'seriestitle' => $matches[2] ];
						$item['subtype'] = 's';
					}
					elseif ( preg_match("/.*?\?uid=(\d*)\'>(.*?)<.*\?sid=(\d*)\'>(.*?)<.*\?uid=(\d*)\'>(.*?)<.*\?seriesid=(\d*)\'>(.*?)<.*/i", $item['action'], $matches) )
					// matching _LOG_ADMIN_DEL_FROM_SERIES
					{
						$item['action'] = [
							'uid' => $matches[1], 'uname' => $matches[2],
							'sid' => $matches[3], 'sname' => $matches[4],
							'aid' => $matches[5], 'aname' => $matches[6],
							'seriesid' => $matches[7], 'sername' => $matches[8],
						];
						$item['subtype'] = 'f';
					}
					elseif ( preg_match("/.+\?sid=(\d*)'>(.*?)<\/.+\?uid=(\d*)'>([^<]*?)<\/a>.*/i", $item['action'], $matches) )
					// matching _LOG_ADMIN_DEL
					{
						$item['action'] = [ 'sid' => $matches[1], 'title' => $matches[2], 'aid' => $matches[3], 'author' => $matches[4] ];
					}
				}
				elseif ( $item['type']=="EB" )
				{
					if ( preg_match("/.+name[n]?\s(\w*).*?(\d+).*[in|to]\s(\w*).*/i", $item['action'], $matches) )
					// matching _NEWPEN for 'en' and 'de'
					{
						$item['action'] = [
							'uid'		=> $matches[2],
							'oldname'	=> $matches[1], 'newname'	=> $matches[3],
						];
					}
					$item['subtype'] = 'n';
				}
				elseif ( $item['type']=="ED" )
				{
					if ( preg_match("/.+\?sid=(\d*)'>(.*?)<\/.+\?uid=(\d*)'>(.*?)<\/.+\?uid=(\d*)'>(.*?)<\/.+/i", $item['action'], $matches) )
					// matching _LOG_ADMIN_EDIT_AUTHOR
					{
						$item['action'] = [ 
							'sid'     => $matches[1], 'title'    => $matches[2],
							'fromaid' => $matches[3], 'fromname' => $matches[4],
							'toaid'   => $matches[5], 'toname'   => $matches[6]
						];
						$item['subtype'] = 'a';
					}
					elseif ( preg_match("/.+\?sid=(\d*)'>(.*?)<\/.+\?uid=(\d*)'>(.*?)<\/.+(\d).*/i", $item['action'], $matches) )
					// matching _LOG_ADMIN_EDIT_CHAPTER
					{
						$item['action'] = [ 
							'sid' => $matches[1], 'title' => $matches[2],
							'aid' => $matches[3], 'author' => $matches[4],
							'chapter' => $matches[5]
						];
						$item['subtype'] = 'c';
					}
					elseif ( preg_match("/.+\?series[i]?d=(\d*)'>(.*?)<\/.+/i", $item['action'], $matches) )
					// matching _LOG_ADMIN_EDIT_SERIES
					{
						$item['action'] = [ 'seriesid' => $matches[1], 'title' => $matches[2] ];
						$item['subtype'] = 's';
					}
					elseif ( preg_match("/.+\?sid=(\d*)'>(.*?)<\/.+\?uid=(\d*)'>(.*?)<\/.+/i", $item['action'], $matches) )
					// matching _LOG_ADMIN_EDIT
					{
						$item['action'] = [
							'sid' => $matches[1], 'title' => $matches[2],
							'aid' => $matches[3], 'author' => $matches[4]
						];
					}
				}
				elseif ( $item['type']=="LP" AND preg_match("/.*\?uid=(\d*)\'>(.*)<.*:\s*(\w+).*/im", $item['action'], $matches) )
				// matching _LOG_LOST_PASSWORD
				{
					$item['action'] = [ 'uid'=>$matches[1], 'name'=>$matches[2], 'result'=>$matches[3] ];
				}
				elseif ( $item['type']=="RE" AND preg_match("/(.+?)\s*\(.*?'(.*)'.*?'.*?\?sid=(\d+).*/im", $item['action'], $matches) )
				// matching _LOG_REVIEW
				{
					$item['action'] = [ 'name'=>$matches[1], 'review'=>$matches[2], 'sid'=>$matches[3] ];
				}
				elseif ( $item['type']=="RG" AND preg_match('/(\w+[\s\w]*)\s+\((\d*)\).*/iU', $item['action'], $matches) )
				// matching _LOG_REGISTER & _LOG_ADMIN_REG
				{
					$item['action'] = [ 
							'name'=>$matches[1], 'uid'=>$matches[2], 
							'admin'=>($matches[2]!=$item['uid_reg'])
					];
				}
				elseif ( $item['type']=="VS" )
				{
					if ( preg_match("/.+\?sid=(\d+)\'>(.+?)<\/a> \(.+?(\d+).+\?uid=(\d+)\'>(.+?)</i", $item['action'], $matches) )
					// _LOG_VALIDATE_CHAPTER
					{
						$item['action'] = [
							'sid' 	 => $matches[1], 'title'  => $matches[2],
							'chapter'=> $matches[3],
							'aid' 	 => $matches[4], 'author' => $matches[5]
						];
						$item['subtype'] = 'c';
					}
					elseif ( preg_match("/.+\?sid=(\d+)\'>(.+?)<.*\?uid=(\d+)\'>(.+?)</i", $item['action'], $matches) )
					// _LOG_VALIDATE_STORY
					{
						$item['action'] = [
							'sid' 	 => $matches[1], 'title'  => $matches[2],
							'aid' 	 => $matches[3], 'author' => $matches[4]
						];
					}
					elseif ( preg_match("/.+\?sid=(\d+)\'>(.+?)<.*\/>(.*)/i", $item['action'], $matches) )
					// _LOG_REJECTION
					{
						$item['action'] = [
							'sid' 	 => $matches[1], 'title' => $matches[2],
							'reason' => $matches[3]
						];
						$item['subtype'] = 'r';
					}
				}
				
				/*
				
				define ("_LOG_BAD_LOGIN", "<a href='viewuser.php?uid=%2\$d'>%1\$s</a> hat ein falsches Passwort beim einloggen eingegeben.");
				define ("_LOG_BAD_LOGIN", "<a href='viewuser.php?uid=%2\$d'>%1\$s</a> entered a wrong password trying to log in.");

				define ("_LOG_EDIT_REVIEW", "<a href='viewuser.php?uid=%2\$d'>%1\$s</a> hat    <a href='reviews.php?reviewid=%4\$d'>ein Review</a> für '%3\$s' bearbeitet.");
				define ("_LOG_EDIT_REVIEW", "<a href='viewuser.php?uid=%2\$d'>%1\$s</a> edited <a href='reviews.php?reviewid=%4\$d'>a review  </a> for '%3\$s'.");
				
				AM
				("_LOG_RECALCREVIEWS", "<a href='viewuser.php?uid=%2\$d'>%1\$s</a> recalculated the reviews.");
				("_LOG_CATCOUNTS", "<a href='viewuser.php?uid=%2\$d'>%1\$s</a> recalculated the category counts.");
				("_LOG_OPTIMIZE", "<a href='viewuser.php?uid=%2\$d'>%1\$s</a> optimized the database tables.");
				("_LOG_BACKUP", "<a href='viewuser.php?uid=%2\$d'>%1\$s</a> backed up the database tables.");
				
				*/

				if ( is_array($item['action']) )
				{
					$item['ip'] = long2ip($item['ip']);
					$item['action']['origin'] =
					[
						$geo->location($item['ip'])['country_code'], 
						$geo->location($item['ip'])['country_name'], 
						$geo->location($item['ip'])['continent_code']
					];
					$this->logResaveData($item);
				}
			}
			elseif ( $item['version']==1 )
			{
				// eFiction 3 reworked
				$item['action'] = json_decode($item['action'], TRUE);
			}
			elseif ( $item['version']==2 )
			{
				// eFiction 5
				$item['action'] = json_decode($item['action'], TRUE);
			}
		}
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/home/logs/type=".($sub?"{$sub}/":"")."order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}
	
	protected function logResaveData($item)
	{
		$this->update(
				'tbl_log',
				[
					'action' 	=> json_encode($item['action']),
					'version'	=> 1,
					'subtype'	=> isset($item['subtype'])? $item['subtype'] : NULL,
/*					'origin'	=> json_encode(
									[
										$geo->location($item['ip'])['country_code'], 
										$geo->location($item['ip'])['country_name'], 
										$geo->location($item['ip'])['continent_code']
									]
					)	*/
				],
				"id = {$item['id']}"
		);
	}
	
	public function ratingAdd($rating)
	{
		$ratings=new \DB\SQL\Mapper($this->db, $this->prefix.'ratings');
		$ratings->load(null,['order'=>'inorder DESC']);
		$inorder = $ratings->inorder + 1;
		
		$ratings->reset();
		
		$ratings->rating	= $rating;
		$ratings->inorder	= $inorder;

		$ratings->save();
		return $ratings->_id;
	}
	
	public function ratingList()
	{
		$sql = "SELECT R.rid, R.inorder, R.rating, R.rating_age, R.ratingwarning, COUNT(S.sid) as counter
					FROM `tbl_ratings`R
						LEFT JOIN `tbl_stories`S ON ( R.rid = S.ratingid )
					GROUP BY R.rid
					ORDER BY inorder ASC;";
		$data = $this->exec($sql);
		return $data;
	}
	
	public function ratingLoad( $rid )
	{
		$sql = "SELECT R.rid, R.inorder, R.rating, R.rating_age, R.rating_image, R.ratingwarning, R.warningtext, COUNT(S.sid) as counter
					FROM `tbl_ratings`R
						LEFT JOIN `tbl_stories`S ON ( R.rid = S.ratingid )
					WHERE R.rid = :rid
					GROUP BY R.rid;";
		$data = $this->exec($sql, [ ":rid" => $rid ]);
		
		if (sizeof($data)!=1) 
			return NULL;

		return $data[0];	
	}
	
	public function ratingSave($rid, array $data)
	{
		$rating=new \DB\SQL\Mapper($this->db, $this->prefix.'ratings');
		$rating->load(array('rid=?',$rid));
		$rating->copyfrom( 
			[
				"rating" 		=> $data['rating'],
				"rating_age"	=> ($data['rating_age']=="") ? NULL : $data['rating_age'],
				//"rating_image"	=> empty($data['rating_image']) ? NULL : $data['rating_image'],
				"ratingwarning"	=> (int)isset($data['ratingwarning']),
				"warningtext"	=> $data['warningtext']
			]
		);

		$i  = $rating->changed("rating");
		//$i += $rating->changed("rating_image");
		$i += $rating->changed("ratingwarning");

		// If any of the cache-contained values has changed, update story table cache
		if ( $i > 0 )
		$this->exec
		(
			"UPDATE `tbl_stories` SET `cache_rating` = '{$this->ratingCache($rid)}' WHERE `tbl_stories`.`ratingid` = :rid;",
			[ ":rid" => $rid ]
		);

		$i += $rating->changed("rating_age");
		$i += $rating->changed("warningtext");
		
		$_SESSION['lastAction'] = [ "changes" => $i ];

		$rating->save();
		return TRUE;
	}
	
	public function ratingDelete($oldID, $newID)
	{
		
		$this->exec
		(
			"UPDATE `tbl_stories` SET `ratingid` = :new, `cache_rating` = '{$this->ratingCache($newID)}' WHERE `tbl_stories`.`ratingid` = :old;",
			[ ":old" => $oldID, ":new" => $newID ]
		);

		$rating=new \DB\SQL\Mapper($this->db, $this->prefix.'ratings');
		$moved = $rating->erase(array('rid=?',$oldID));
		
		$_SESSION['lastAction'] = [ "moved" => $moved ];
		
		return TRUE;
	}
	
	protected function ratingCache( $rid )
	{
		$data = $this->exec("SELECT CONCAT_WS(',',`rid`,`rating`,`ratingwarning`,`rating_image`) as rating
						FROM `tbl_ratings`
						WHERE `tbl_ratings`.`rid` = :new;",
					[ ":new" => $rid ]
		);
		
		if ( isset($data[0]['rating']) )
			return json_encode(explode(",",$data[0]['rating']));
		else
			return "[]";
	}
	
	public function seriesAdd()
	{
		
	}
	
	public function seriesList(int $page, array $sort, string $module) : array
	{
		$limit = 20;
		$pos = $page - 1;
		
		$sql = str_replace
				(
					"%TYPE%",
					($module=="collections")?"C":"S",
					"SELECT Ser.title, Ser.cache_authors,
								COUNT(DISTINCT rSerS.sid) as stories
							FROM `tbl_series`Ser
								LEFT JOIN `tbl_series_stories`rSerS ON ( Ser.seriesid = rSerS.seriesid )
							WHERE Ser.type = '%TYPE%'
							GROUP BY Ser.seriesid
							ORDER BY {$sort['order']} {$sort['direction']}
							LIMIT ".(max(0,$pos*$limit)).",".$limit
				);
		
		$data = $this->exec( $sql );
		
		return $data;
	}

	public function storyAdd(array $data)
	{
		$newStory = new \DB\SQL\Mapper($this->db, $this->prefix."stories");
		$newStory->title		= $data['new_title'];
		$newStory->completed	= 1;
		$newStory->validated	= ($_SESSION['groups']&128) ? 33 : 32;
		$newStory->date			= date('Y-m-d H:i:s');
		$newStory->updated		= $newStory->date;
		$newStory->save();
		
		$newID = $newStory->_id;
		
		// add initial chapter to the story
		if ( FALSE === $this->storyChapterAdd($newID, NULL, $newStory->date) )
		{
			
		}
		
		$new_authors = explode(",",$data['new_author']);
		foreach ( $new_authors as $new_author )
		{
			// add the story-author relation
			$newRelation = new \DB\SQL\Mapper($this->db, $this->prefix."stories_authors");
			$newRelation->sid	= $newID;
			$newRelation->aid	= $new_author;
			$newRelation->type	= 'M';
			$newRelation->save();
			
			// already counting as author? mainly for stats ...
			$editUser = new \DB\SQL\Mapper($this->db, $this->prefix."users");
			$editUser->load(array("uid=?",$new_author));
			if ( $editUser->groups < 4 )
				$editUser->groups += 4;
			$editUser->save();
		}
		
		$this->rebuildStoryCache($newID);

		return $newID;
	}

	public function storyAddCheck(array $formData)
	{
		$sql = "SELECT S.sid, S.title 
					FROM `tbl_stories`S 
						INNER JOIN `tbl_stories_authors`A ON ( S.sid = A.sid ) 
					WHERE S.title LIKE :title and A.aid IN(:aid)";
		$similarExists = $this->exec($sql,  [ ':title' => $formData['new_title'], ':aid' => $formData['new_author'] ] );
		
		// no hits, seems to be a new story
		if ( sizeof($similarExists) == 0 ) return NULL;
		
		// might be an existing title up for dual entry, let's notify the mod/admin
		$sqlAuthors = "SELECT U.uid as id, U.nickname as name FROM `tbl_users`U WHERE FIND_IN_SET(U.uid,:uid);";
		$authors = $this->exec($sqlAuthors,  [ ':uid' => $formData['new_author'] ] );
		
		return [ "storyInfo" => $similarExists[0], "preAuthor" => json_encode($authors) ];
	}

//	public function storyLoadInfo(int $sid)
	public function storyLoadInfo($sid)
	{
		$data = $this->exec
		(
			"SELECT S.*, COUNT(DISTINCT Ch.chapid) as chapters
				FROM `tbl_stories`S
					LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid)
				WHERE S.sid = :sid",
			[":sid" => $sid ]
		);
		if (sizeof($data)==1)
		{
			$data[0]['ratings'] = $this->exec("SELECT rid, rating, ratingwarning FROM `tbl_ratings`");
			return $data[0];
		}
		return FALSE;
	}

	public function storySaveChanges(\DB\SQL\Mapper $current, array $post)
	{
		// remember old validation status
		$oldValidated = $current->validated;
		
		// Step one: save the plain data
		$current->title			= $post['story_title'];
		$current->summary		= str_replace("\n","<br />",$post['story_summary']);
		$current->storynotes	= str_replace("\n","<br />",$post['story_notes']);
		$current->ratingid		= @$post['ratingid'];	// Quick fix for when no rating is created
		$current->completed		= $post['completed'];
		$current->validated 	= $post['validated'].$post['valreason'];
		
		if ( $current->changed("validated") )
		{
			if ( $post['validated'] == 3 AND substr($oldValidated,0,1)!=3 )
			// story got validated
			\Logging::addEntry('VS', $current->sid);
		}
		
		$current->save();

		// Step two: check for changes in relation tables
		

		// Check tags:
		$this->storyRelationTag( $current->sid, $post['tags'] );
		// Check Characters:
		$this->storyRelationTag( $current->sid, $post['characters'], 1 );
		// Check Categories:
		$this->storyRelationCategories( $current->sid, $post['category'] );
		// Check Authors:
		$this->storyRelationAuthor( $current->sid, $post['mainauthor'], $post['supauthor'] );

		// Rebuild story cache based on new data
		$this->rebuildStoryCache($current->sid);
		
		return TRUE;
	}

	//public function storyListPending(int $page, array $sort)
	public function storyListPending($page, array $sort)
	{
		$limit = 20;
		$pos = $page - 1;
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS
					S.sid, S.title, IF(S.validated >= 20 AND S.validated <= 30,1,0) as pStory,
					COUNT(DISTINCT Ch.chapid) as pChapters,
					IF(
						Ch4.chapid IS NULL
						AND
						(
							Ch2.chapid<MIN(Ch.chapid) 
							OR (COUNT(Ch.chapid)=0 AND Ch3.chapid IS NOT NULL)
						)
					,1,0) as blocked,
					UNIX_TIMESTAMP(IF(Ch.chapid IS NULL,S.updated,Ch.created)) as lastdate,
                    GROUP_CONCAT(DISTINCT U.uid ORDER BY U.nickname ASC SEPARATOR ', ') as aid,
					GROUP_CONCAT(DISTINCT U.nickname ORDER BY U.nickname ASC SEPARATOR ', ') as authors
				FROM `tbl_stories`S
					LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid AND Ch.validated >= 20 AND Ch.validated <= 30 )
						LEFT JOIN `tbl_chapters`Ch2 ON
						( 
							(Ch.validated >= 20 AND Ch.validated <= 30) 
							AND (Ch2.validated >= 10 AND Ch2.validated <= 20) 
							AND Ch.sid = Ch2.sid 
							AND Ch2.inorder < Ch.inorder
						)
					LEFT JOIN `tbl_chapters`Ch3 ON ( S.sid = Ch3.sid AND Ch3.validated >= 10 AND Ch3.validated <= 20 )
					LEFT JOIN `tbl_chapters`Ch4 ON ( S.sid = Ch4.sid AND Ch4.validated >= 30 AND S.validated <= 30 )
					LEFT JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid )
						LEFT JOIN `tbl_users`U ON ( rSA.aid = U.uid )
				WHERE Ch.chapid IS NOT NULL OR ( S.validated >= 20 AND S.validated <= 30 )
				GROUP BY S.sid
				ORDER BY blocked ASC, pChapters DESC, lastdate ASC
				LIMIT ".(max(0,$pos*$limit)).",".$limit;
				
		$data = $this->exec($sql);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/stories/pending/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}

	public function storyLoadPending($sid)
	{
		$jobs = $this->exec("SELECT
						S.sid, S.title, S.completed, S.storynotes, S.summary, S.translation, S.trans_from, S.trans_to,
						IF(S.validated >= 20 AND S.validated <= 30,1,0) as pStory, 
						IF(
							Ch4.chapid IS NULL
							AND
							(
								Ch2.chapid<MIN(Ch.chapid) 
								OR (COUNT(Ch.chapid)=0 AND Ch3.chapid IS NOT NULL)
							)
						,1,0) as blocked,
						S.cache_authors, S.cache_tags, S.cache_characters, S.cache_categories, S.cache_rating, 
						Ch.chapid, Ch.title as chap_title, UNIX_TIMESTAMP(Ch.created) as chap_lastdate, Ch.inorder as chap_inorder
					FROM `tbl_stories`S
						LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid AND Ch.validated >= 20 AND Ch.validated <= 30 )
							LEFT JOIN `tbl_chapters`Ch2 ON
							( 
								(Ch.validated >= 20 AND Ch.validated <= 30) 
								AND (Ch2.validated >= 10 AND Ch2.validated <= 20) 
								AND Ch.sid = Ch2.sid 
								AND Ch2.inorder < Ch.inorder
							)
						LEFT JOIN `tbl_chapters`Ch3 ON ( S.sid = Ch3.sid AND Ch3.validated >= 10 AND Ch3.validated <= 20 )
						LEFT JOIN `tbl_chapters`Ch4 ON ( S.sid = Ch4.sid AND Ch4.validated >= 30 AND S.validated <= 30 )
					WHERE S.sid = :sid AND ((S.validated >= 20 AND S.validated <= 30 ) OR Ch.chapid IS NOT NULL)
					GROUP BY Ch.chapid
					HAVING blocked = 0
					ORDER BY Ch.inorder ASC;",
				[":sid"=>$sid]
		);
		
		if ( sizeof($jobs)>0 )
		{
			$data = 
			[
				"story"			=> $jobs[0],
				"chapterList"	=> [],
			];
			
			if ( $jobs[0]['chapid']!==NULL )
			// Only build a chapter list if there are chapters that require validation
			{
				$data['chapterList'] = $this->loadChapterList($sid);
				$first  = 0;
				$closed = 0;
				
				foreach ( $data['chapterList'] as $key => &$chapter )
				{
					if ( $chapter['validated'] >= 20 AND $chapter['validated'] <= 29 AND $closed == 0 )
					// chapter requires validation and the queue has not yet been closed
					{
						if ( $first==0 )
						// first job
						{
							$first = 1;
							$chapter['first'] = TRUE;
						}
						// remember the last chapter that requires validation
						$last = $key;
						$chapter['active'] = 1;
					}
					elseif ( $chapter['validated'] <= 19 )
					// encountering the first non-mod chapter, we close the queue
					{
						$closed = 1;
					}
				}
				// tag the last chapter that can be validated
				$data['chapterList'][$last]['last'] = TRUE;
				
			}

			if ( sizeof($jobs)==1 AND $jobs[0]['chapid']==NULL )
				$data['state'] = 'storyOnly';
			elseif ( $jobs[0]['pStory']==0 AND $jobs[0]['chapid']!=NULL )
				$data['state'] = 'chapterOnly';
			else
				$data['state'] = 'chapterFirst';

			return ($data);
		}
		return NULL;
	}
	
	public function storyValidatePending($sid, $chapid = FALSE)
	{
		if 		( $_SESSION['groups']&128 ) $validated = 33;
		elseif 	( $_SESSION['groups']&64 )	$validated = 32;
		else								$validated = 32;
		
		if ( $chapid )
		{
			// Try to set validation on chapter
			if ( 1 == $result = $this->exec("UPDATE `tbl_chapters` SET `validated`=:validated WHERE `chapid`=:chapid;",
						[
							":validated"	=> $validated,
							":chapid"		=> $chapid
						])
			)
			// Log success
			\Logging::addEntry(['VS','c'], [$sid,$chapid]);
		}
		else
		{
			// Try to set story to validated
			if ( 1 == $result = $this->exec("UPDATE `tbl_stories` SET `validated`=:validated WHERE `sid`=:sid;",
						[
							":validated"	=> $validated,
							":sid"			=> $sid
						])
			)
			// Log success
			\Logging::addEntry('VS', $sid);
		}
		return $result;
	}

//	public function tagAdd(string $name) : int
	public function tagAdd($name)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->label = $name;
		$tag->save();
		return $tag->get('_id');
	}

	public function tagList($page, $sort)
	{
		/*
		$tags = new \DB\SQL\Mapper($this->db, $this->prefix.'tags' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS T.tid,T.tgid,T.label,T.count,G.description as `group`
				FROM `tbl_tags`T 
				LEFT JOIN `tbl_tag_groups`G ON ( T.tgid=G.tgid)
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/tags/edit/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}

//	public function tagLoad(int $tid)
	public function tagLoad($tid)
	{
		$sql = "SELECT T.tid as id, T.tgid, T.label, T.description, T.count, G.description as groupname FROM `tbl_tags`T LEFT JOIN `tbl_tag_groups`G ON ( T.tgid=G.tgid) WHERE T.tid = :tid";
		$data = $this->exec($sql, [":tid" => $tid ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}

//	public function tagSave(int $tid, array $data)
	public function tagSave($tid, array $data)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->load(array('tid=?',$tid));
		$tag->copyfrom( [ "tgid" => $data['taggroup'], "label" => $data['label'], "description" => $data['description'] ]);

		//if ( TRUE === $i = $tag->changed("tgid") ) $this->tagGroupRecount();
		$i = $tag->changed("tgid");
		$i += $tag->changed("label");
		$i += $tag->changed("description");

		$tag->save();
		return $i;
	}
	
//	public function tagDelete(int $tid)
	public function tagDelete($tid)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->load(array('tid=?',$tid));
		$_SESSION['lastAction'] = [ "deleteResult" => $tag->erase() ];
	}

//	public function tagGroupAdd(string $name)
	public function tagGroupAdd($name)
	{
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->description = $name;
		$taggroup->save();
		return $taggroup->get('_id');
	}

	public function tagGroups()
	{
		return $this->exec("SELECT TG.tgid as id, TG.description FROM `tbl_tag_groups`TG ORDER BY TG.description ASC");
	}

	public function tagGroupsList($page, $sort)
	{
		/*
		CREATE OR REPLACE VIEW tbl_list_tag_groups AS SELECT G.tgid,G.description,COUNT(T.tid) as `count`
				FROM `tbl_tag_groups`G 
				LEFT JOIN `tbl_tags`T ON ( G.tgid = T.tgid )
		

		$tags = new \DB\SQL\Mapper($this->db, 'tbl_list_tag_groups' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS G.tgid,G.description,COUNT(T.tid) as `count`
				FROM `tbl_tag_groups`G 
				LEFT JOIN `tbl_tags`T ON ( G.tgid = T.tgid )
				GROUP BY G.tgid
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/tags/edit/",
			$limit
		);

		return $data;
	}
	
//	public function tagGroupLoad(int $tgid)
	public function tagGroupLoad($tgid)
	{
		$sql = "SELECT TG.tgid as id, TG.label as label, TG.description FROM `tbl_tag_groups`TG WHERE TG.tgid = :tgid";
		$data = $this->exec($sql, [":tgid" => $tgid ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}
	
//	public function tagGroupSave(int $tgid, array $data)
	public function tagGroupSave($tgid, array $data)
	{
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->load(array('tgid=?',$tgid));
		$taggroup->copyfrom( [ "label" => $data['label'], "description" => $data['description'] ]);

		$i = $taggroup->changed("label");
		$i += $taggroup->changed("description");

		$taggroup->save();
		return $i;
	}
	
//	public function tagGroupDelete(int $tgid)
	public function tagGroupDelete($tgid)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		if($tag->count(array('tgid=?',$tgid)))
		{
			$_SESSION['lastAction'] = [ "deleteResult" => 0 ];
			return FALSE;
		}
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->load(array('tgid=?',$tgid));
		$_SESSION['lastAction'] = [ "deleteResult" => $taggroup->erase() ];
		return TRUE;
	}

	public function listTeam()
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS `uid`, `nickname`, `realname`, `groups` FROM `tbl_users` WHERE `groups` > 16 ORDER BY groups,nickname ASC";
		return $this->exec($sql);
	}
	
	public function listUserFields()
	{
		$sql = "SELECT `field_id`, `field_type`, `field_name`, `field_title`, `field_options`, `enabled` FROM `tbl_user_fields` ORDER BY `field_type` ASC";
		return $this->exec($sql);
	}
	
	public function listUsers($page, array $sort, $search=NULL)
	{
		$limit = 20;
		$pos = $page - 1;
		$bind = [];

		$sql = "SELECT SQL_CALC_FOUND_ROWS `uid`, `nickname`, `login`, `email`, UNIX_TIMESTAMP(registered) as registered, `groups`
					FROM `tbl_users`
					WHERE `uid`>0";

		if ( $search['fromlevel'] )
			$sql .= " AND `groups` >= POW(2,{$search['fromlevel']})";
		else 
			$sql .= " AND `groups` >= 1";
		
		if ( $search['tolevel'] )
			$sql .=	" AND groups < POW(2,".($search['tolevel']+1).") ";

		if($search['term'])
		{
			$sql .="	AND (`login` LIKE :term1
							or `nickname` LIKE :term2
							or `realname` LIKE :term3
							or `email` LIKE :term4
							or `about` LIKE :term5) ";
			$bind = [ ":term1" => "%{$search['term']}%", ":term2" => "%{$search['term']}%", ":term3" => "%{$search['term']}%", ":term4" => "%{$search['term']}%", ":term5" => "%{$search['term']}%" ];
		}

		$sql .= " ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql,$bind);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/members/edit/{$search['follow']}order={$sort['link']},{$sort['direction']}",
			$limit
		);
		return $data;
	}
	
	//public function loadUser(int $uid)
	public function loadUser($uid)
	{
		$sql = "SELECT uid, login, nickname, realname, email, UNIX_TIMESTAMP(registered) as registered, groups, curator, about
					FROM `tbl_users`
					WHERE uid = :uid;";
		$data = $this->exec( $sql, [ ":uid" => $uid ] );
		
		if ( sizeof($data)==1 ) $user = $data[0];
		else return FALSE;
		
		$sql = "SELECT F.field_id as id, F.field_title as title, F.field_type as type, F.field_options as options, I.info
					FROM `tbl_user_fields`F
					LEFT JOIN `tbl_user_info`I ON ( F.field_id = I.field AND I.uid = :uid )
					WHERE F.enabled = 1
					ORDER BY F.field_type, F.field_order";
		$user['fields'] = $this->exec( $sql, [ ":uid" => $uid ] );
		
		foreach ( $user['fields'] as &$field )
		{
			if ( $field['type']==2 )
				$field['options'] = json_decode($field['options'],TRUE);
		}
		
		return $user;
	}
	
//	public function addTag(string $name) : int
	public function addCharacter($name)
	{
		$character=new \DB\SQL\Mapper($this->db, $this->prefix.'characters');
		$character->charname = $name;
		$character->save();
		return $character->get('_id');
	}
	
//	public function saveCharacter(int $charid, array $data)
	public function saveCharacter($charid, array $data)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'characters');
		$tag->load(array('charid=?',$charid));
		$tag->copyfrom( [ "catid" => $data['catid'], "charname" => $data['charname'], "biography" => $data['biography'] ]);

		//if ( TRUE === $i = $tag->changed("tgid") ) $this->tagGroupRecount();
		$i = $tag->changed("catid");
		$i += $tag->changed("charname");
		$i += $tag->changed("biography");

		$tag->save();
		return $i;
	}

//	public function deleteCharacter(int $tid)
	public function deleteCharacter($charid)
	{
		$character=new \DB\SQL\Mapper($this->db, $this->prefix.'characters');
		$character->load(array('charid=? and count=0',$charid));
		
		$_SESSION['lastAction'] = [ "deleteResult" => $character->erase() ];
	}
	
//	public function listCustompages(int $page, array $sort)
	public function listCustompages($page, array $sort)
	{
		$limit = 10;
		$textblocks = new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		$data = $textblocks->paginate(
					$page-1,
					$limit,
					NULL,
					array('order'=>"{$sort['order']} {$sort['direction']}")
		);

		$this->paginate(
			$data['total'],	//$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/home/custompages/order={$sort['link']},{$sort['direction']}",
			$limit
		);

		return $data['subset'];
	}

//	public function loadCustompage(int $id)
	public function loadCustompage($id)
	{
		$sql = "SELECT TB.* FROM `tbl_textblocks`TB WHERE TB.id = :id";
		$data = $this->exec($sql, [":id" => $id ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}

//	public function saveCustompage(int $id, array $data)
	public function saveCustompage($id, array $data)
	{
		if( empty($data['label']) )
		{
			\Base::instance()->set('form_error', "__EmptyLabel");
			return FALSE;
		}
		$textblock=new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		if( $textblock->count(array('id!=? AND label=?',$id,$data['label'])) > 0 )
		{
			\Base::instance()->set('form_error', "__DuplicateLabel");
			return FALSE;
		}
		$textblock->load(array('id=?',$id));
		$textblock->copyfrom( 
			[ 
				"label"		=> $data['label'], 
				"title"		=> $data['title'],
				"content"	=> $data['content'],
				"as_page"	=> isset($data['page']) ? : 0,
			]
		);

		$i  = $textblock->changed("label");
		$i += $textblock->changed("title");
		$i += $textblock->changed("content");
		$i += $textblock->changed("as_page");
		
		$textblock->save();

		return $i;
	}
	
//	public function addCustompage( string $label )
	public function addCustompage( $label )
	{
		$textblock=new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		$conflicts = (int)$textblock->count(array('label=?',$label));
		if($conflicts>0) return FALSE;
		$textblock->reset();
		$textblock->label = $label;
		$textblock->save();
		return $textblock->_id;
	}

//	public function deleteCustompage( int $id )
	public function deleteCustompage( $id )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		if ( $delete->count( ["id = ?", $id ] ) == 0 ) return FALSE;
		$delete->erase( ["id = ?", $id ] );
		return TRUE;
	}

//	public function listNews(int $page, array $sort)
	public function listNews($page, array $sort)
	{
		/*
		$tags = new \DB\SQL\Mapper($this->db, $this->prefix.'tags' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS N.nid, N.headline, U.nickname AS author, DATE(N.datetime) as date, UNIX_TIMESTAMP(N.datetime) as timestamp
				FROM `tbl_news`N
				LEFT JOIN `tbl_users`U ON (N.uid=U.uid)
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/home/news/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}

//	public function loadNews(int $id)
	public function loadNews($id)
	{
		$sql = "SELECT N.nid as id, N.headline, N.newstext, N.datetime, UNIX_TIMESTAMP(N.datetime) as timestamp FROM `tbl_news`N WHERE `nid` = :nid";
		$data = $this->exec($sql, [":nid" => $id ]);
		if (sizeof($data)!=1) 
			return NULL;

		// going with the preset date/time versions to make sure the datetimepicker is happy
		$data[0]['datetime'] = $this->timeToUser($data[0]['datetime'], $this->config['date_preset']." ".$this->config['time_preset']);

		return $data[0];
	}

//	public function addNews( string $headline )
	public function addNews( $headline )
	{
		$news=new \DB\SQL\Mapper($this->db, $this->prefix.'news');
		$news->uid = $_SESSION['userID'];
		$news->headline = $headline;
		$news->datetime = NULL;
		$news->save();
		return $news->_id;
	}

//	public function saveNews(int $id, array $data)
	public function saveNews($id, array $data)
	{
		if( empty($data['headline']) )
		{
			\Base::instance()->set('form_error', "__EmptyLabel");
			return FALSE;
		}
		$news=new \DB\SQL\Mapper($this->db, $this->prefix.'news');
		if( $news->count(array('nid!=? AND headline=?',$id,$data['headline'])) > 0 )
		{
			\Base::instance()->set('form_error', "__DuplicateLabel");
			return FALSE;
		}
		$news->load(array('nid=?',$id));

		$news->copyfrom( 
			[ 
				"headline"	=> $data['headline'], 
				"newstext"	=> $data['newstext'],
				"datetime"	=> \DateTime::createFromFormat($this->config['date_preset']." ".$this->config['time_preset'], $data['datetime'])->format('Y-m-d H:i'),
			]
		);

		$i  = $news->changed("headline");
		$i += $news->changed("newstext");
		$i += $news->changed("datetime");
		
		$news->save();

		return $i;
	}
	
//	public function deleteNews( int $id )
	public function deleteNews( $id )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'news');
		if ( $delete->count( ["nid = ?", $id ] ) == 0 ) return FALSE;
		$delete->erase( ["nid = ?", $id ] );
		return TRUE;
	}

/*	
	public function loadChapterList($sid)
	moved to parent
*/
	
	public function loadStoryMapper($sid)
	{
		$story=new \DB\SQL\Mapper($this->db, $this->prefix.'stories');
		$story->load(array('sid=?',$sid));
		return $story;
	}
	
	//public function saveChapterChanges( int $chapterID, array $post )
	public function saveChapterChanges( $chapterID, array $post )
	{
		$chapter=new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
		$chapter->load(array('chapid=?',$chapterID));
		
		// remember old validation status
		$oldValidated = $chapter->validated;

		$chapter->title 		= $post['chapter_title'];
		$chapter->notes 		= $post['chapter_notes'];
		$chapter->validated 	= $post['validated'].$post['valreason'];

		if ( $chapter->changed("validated") )
		{
			if ( $post['validated'] == 3 AND substr($oldValidated,0,1)!=3 )
			// story got validated
			\Logging::addEntry(['VS','c'], [ $chapter->sid, $chapter->inorder] );
		}

		$chapter->save();
		
		// plain and visual return different newline representations, this will bring things to standard.
		$post['chapter_text'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $post['chapter_text']);
		parent::saveChapter($chapterID, $post['chapter_text']);
	}
	
	//public function addChapter ( int $storyID )
	public function addChapter ( $storyID )
	{
		$location = $this->config['chapter_data_location'];
		
		// Get current chapter count and raise
		if ( FALSE === $chapterCount = @$this->exec("SELECT COUNT(chapid) as chapters FROM `tbl_chapters` WHERE `sid` = :sid ", [ ":sid" => $storyID ])[0]['chapters'] )
			return FALSE;
		$chapterCount++;
		
		$kv = [
			'title'			=> \Base::instance()->get('LN__Chapter')." #{$chapterCount}",
			'inorder'		=> $chapterCount,
			'validated'		=> "1".($_SESSION['groups']&128)?"3":"2",
			'wordcount'		=> 0,
			'rating'		=> "0", // allow rating later
			'sid'			=> $storyID,
		];

		$chapterID = $this->insertArray($this->prefix.'chapters', $kv );

		if ( $location == "local" )
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
		
		$this->rebuildStoryCache($storyID);
		
		return $chapterID;
	}

	public function addChapterText( $chapterData )
	{
		return parent::addChapterText( $chapterData );
	}
	
	//public function listShoutbox(int $page, array $sort)
	public function listShoutbox($page, array $sort)
	{
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS S.id, S.message, IF(S.guest_name IS NULL,U.nickname,S.guest_name) AS author, S.uid, DATE(S.date) as date, UNIX_TIMESTAMP(S.date) as timestamp
				FROM `tbl_shoutbox`S
				LEFT JOIN `tbl_users`U ON (S.uid=U.uid)
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/home/shoutbox/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}
	
	public function deleteShout($id)
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'shoutbox');
		if ( $delete->count( ["id = ?", $id ] ) == 0 ) return FALSE;
		$delete->erase( ["id = ?", $id ] );
		return TRUE;
	}

	public function loadShoutbox($id)
	{
		$sql = "SELECT S.id, S.message FROM `tbl_shoutbox`S WHERE `id` = :id";
		$data = $this->exec($sql, [":id" => $id ]);
		if (sizeof($data)!=1) 
			return NULL;

		return $data[0];
	}

	public function saveShout($id, array $data)
	{
		$shout=new \DB\SQL\Mapper($this->db, $this->prefix.'shoutbox');
		$shout->load(array('id=?',$id));
		$shout->copyfrom( [ "message" => $data['message'] ]);

		$i = $shout->changed("message");

		$shout->save();
		return $i;
	}
	
	public function getLanguageConfig()
	{
		$sql = "SELECT `name`, `value`, `form_type`
					FROM `tbl_config` 
					WHERE 
						`admin_module` LIKE 'settings_language_file'";
		$data = $this->exec($sql);
		
		foreach ( $data as $dat )
			$config[$dat['name']] = $dat['value'];

		$config['language_available'] = json_decode($config['language_available'], TRUE);
		if ( !is_array($config['language_available']) ) $config['language_available'] = [];

		return $config;
	}
	
	public function saveLanguage($data)
	{
		$default = $data['lang_default'];
		unset($data['lang_default']);

		foreach ( $data as $key => &$dat )
		{
			if ( $key == $default ) $dat['available'] = TRUE;
			if ( $dat['available'] == TRUE ) $available[$key] = $dat['localname'];
		}
		
		$post["settings_language_file"] =
			[
				"language_available" 	=> json_encode($available),
				"language_default"		=> $default,
			];
		return $this->saveKeys($post);
	}

	public function getLayoutConfig()
	{
		$sql = "SELECT `name`, `value`, `form_type`
					FROM `tbl_config` 
					WHERE 
						`admin_module` LIKE 'settings_layout_file'";
		$data = $this->exec($sql);
		
		foreach ( $data as $dat )
			$config[$dat['name']] = $dat['value'];

		$config['layout_available'] = json_decode($config['layout_available'],TRUE);
		if ( !is_array($config['layout_available']) ) $config['layout_available'] = [];

		return $config;
	}
	
	public function saveLayout($data)
	{
		$default = $data['lay_default'];
		unset($data['lay_default']);
		
		foreach ( $data as $key => &$dat )
		{
			if ( $key == $default ) $dat['available'] = TRUE;
			if ( $dat['available'] == TRUE ) $available[$key] = $dat['name'];
		}
		
		$post["settings_layout_file"] =
			[
				"layout_available" 	=> json_encode($available),
				"layout_default"	=> $default,
			];
			
		return $this->saveKeys($post);
	}
	
	public function deleteOrphanRelations()
	{
		// remove orphaned story-author relations
		$sql = "DELETE FROM `tbl_stories_authors`
					WHERE NOT EXISTS (SELECT * FROM `tbl_stories` WHERE `tbl_stories_authors`.`sid` = `tbl_stories`.`sid`);";
		// remove orphaned story-category relations
		$sql = "DELETE FROM `tbl_stories_categories`
					WHERE NOT EXISTS (SELECT * FROM `tbl_stories` WHERE `tbl_stories_categories`.`sid` = `tbl_stories`.`sid`);";
		// remove orphaned story-tag relations
		$sql = "DELETE FROM `tbl_stories_tags`
					WHERE NOT EXISTS (SELECT * FROM `tbl_stories` WHERE `tbl_stories_tags`.`sid` = `tbl_stories`.`sid`);";
		
	}

}
