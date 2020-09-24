<?php
namespace Model;

class AdminCP extends Controlpanel {

	protected $menu = [];
	protected $access = [];
	
	public function ajax(string $key, array $data, array $params = [])
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
			elseif(isset($data['storyID']))
			{
				$ajax_sql = "SELECT S.title as name,S.sid as id from `tbl_stories`S WHERE S.title LIKE :story OR S.sid = :sid ORDER BY S.title ASC";
				$bind = [ ":story" =>  "%{$data['storyID']}%", ":sid" =>  $data['storyID'] ];
			}
			elseif(isset($data['collID']))
			{
				$ajax_sql = "SELECT C.title as name,C.collid as id from `tbl_collections`C WHERE C.title LIKE :collection OR C.collid = :collid ORDER BY C.title ASC";
				$bind = [ ":collection" =>  "%{$data['collID']}%", ":collid" =>  $data['collID'] ];
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
				$ajax_sql = "SELECT U.username as name, U.uid as id from `tbl_users`U WHERE U.username LIKE :username AND ( U.groups & 5 ) ORDER BY U.username ASC LIMIT 5";
				$bind = [ ":username" =>  "%{$data['author']}%" ];
			}
			elseif(isset($data['user']))
			{
				$ajax_sql = "SELECT U.username as name, U.uid as id from `tbl_users`U WHERE U.username LIKE :username AND ( U.groups & 1 ) ORDER BY U.username ASC LIMIT 5";
				$bind = [ ":username" =>  "%{$data['user']}%" ];
			}
			elseif(isset($data['tag']))
			{
				$ajax_sql = "SELECT label as name, tid as id from `tbl_tags`T WHERE T.label LIKE :tag ORDER BY T.label ASC LIMIT 10";
				$bind = [ ":tag" =>  "%{$data['tag']}%" ];
			}
			elseif(isset($data['character']))
			{
				if ( isset($params['categories']) )
				{
					$where = ( is_array($params['categories']) )
						? "FIND_IN_SET(C.cid, :categories)"
						: "C.cid = :categories";
					$bind = ( is_array($params['categories']) )
						? implode(",",$params['categories'])
						: $params['categories'];
					$c = [];
					$this->getCategories
					(
						$c,
						$this->exec("SELECT C.cid,C.parent_cid 
										FROM `tbl_categories`C
									WHERE {$where};", [ ":categories" => $bind ] )
					);
					
					if ( sizeof($c) ) $categories = " OR rCC.catid IN (".implode(",",$c).")";
				}
				
				$ajax_sql = "SELECT Ch.charname as name, Ch.charid as id
								FROM `tbl_characters`Ch 
								LEFT JOIN `tbl_character_categories`rCC ON ( Ch.charid = rCC.charid )
							WHERE Ch.charname LIKE :charname AND ( rCC.catid IS NULL ".($categories??"")." )
							GROUP BY id
							ORDER BY Ch.charname ASC LIMIT 5";

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
				$ajax_sql = "SELECT U.username as name,U.uid as id from `tbl_users`U WHERE ( U.username LIKE :user OR U.uid = :uid ) AND U.uid > 0 ORDER BY U.username ASC LIMIT 5";
				$bind = [ ":user" =>  "%{$data['userID']}%", ":uid" =>  $data['userID'] ];
			}
		}
		elseif ( $key == "ratingsort" )
		{
			$chapters = new \DB\SQL\Mapper($this->db, $this->prefix.'ratings');
			foreach ( $data["neworder"] as $order => $id )
			{
				if ( is_numeric($order) && is_numeric($id) )
				{
					$chapters->load(array('rid = ?',$id));
					$chapters->inorder = $order+1;
					$chapters->save();
				}
			}
			exit;
		}
		elseif ( $key == "storySort" )
		{
			// unified
			if(isset($data['collectionsort']))
				$this->ajaxCollectionItemsort($data);
			
			elseif(isset($data['chaptersort']))
				$this->ajaxStoryChaptersort($data);

		}

		if ( isset($ajax_sql) ) return $this->exec($ajax_sql, $bind);
		return NULL;
	}
	
	protected function getCategories(array &$c, array $data)
	{
		foreach ( $data as $d )
		{
			// take note of this category
			$c[] = $d['cid'];
			// if a parent category exists, traverse the tree
			if ( $d['parent_cid'] > 0 ) 
				$this->getCategories($c, $this->exec("SELECT C.cid, C.parent_cid FROM `tbl_categories`C WHERE `cid` = {$d['parent_cid']};"));
		}
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

	//public function checkAccess($link, $exists = FALSE)
	public function checkAccess(string $link) :bool
	{
		//if ( $exists ) return isset($this->access[$link]);
		return ( isset($this->access[$link]) AND (int)$this->access[$link]&(int)$_SESSION['groups'] );
	}

	public function menuShow(string $selected, string $module="") :array
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
				{
					$menu[$item['child_of']]['sub'][$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"], "requires" => $item["requires"] ];
					if ( $item["link"] == "{$selected}/{$module}" )
					{
						$menu[$item['child_of']]['sub'][$item["link"]]["selected"] = 1;
						$has_selected = 1;
					}
				}

				else $menu[$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"], "requires" => $item["requires"] ];

				$this->access[$item['link']] = $item["requires"];
			}
		}
		if ( (empty($module) OR empty($has_selected)) and $selected ) $menu[$selected]["selected"] = 1;

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

	/**
	* Add a new category
	* rewrite 2020-09
	*
	* @param	int		$parent_cid		
	* @param	array	$data			
	*
	* @return	int						New category's ID
	*/
	public function categoryAdd( int $parent_cid, array $data ) : int
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

		// recount parent category, if applicable
		if ( $parent_cid>0 )
			$this->cacheCategories($parent_cid);
		
		// create the rather boring cache for the new category
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

	public function categoryLoad(int $cid)
	{
		if ( $cid == 0 ) return NULL;
		$sql = "SELECT cid as id, parent_cid, category, description, image, locked, leveldown, inorder, stats FROM `tbl_categories`C WHERE C.cid = :cid";
		$data = $this->exec($sql, [":cid" => $cid ]);
		if (sizeof($data)==1) return $data[0];
		return FALSE;
	}

	public function categoryLoadPossibleParents(int $cid)
	{
		$sql = "SELECT C.cid, C.parent_cid, C.leveldown, C.category
					FROM `tbl_categories`C 
					INNER JOIN `tbl_categories`C2 ON ( ( C.parent_cid = C2.parent_cid OR C.cid = C2.parent_cid )AND C2.cid = :cid ) 
				WHERE C.cid != :cid2
				ORDER BY C.leveldown, C.inorder ASC ";
		
		$data = $this->exec($sql, [":cid" => $cid, ":cid2" => $cid ]);

		return $data;
	}

	public function categorySave(int $cid, array $data)
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

		if ( $i )
		{
			// drop category cache for all stories that use this category
			$this->exec("UPDATE `tbl_stories`S
							INNER JOIN
							(
								SELECT rSC.sid
								FROM `tbl_stories_categories`rSC
								WHERE rSC.cid = :catID
							) AS C ON C.sid = S.sid
						SET S.cache_categories = NULL;", [":catID" => $cid]);
			
			$recache = TRUE;
		}

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

		// do we need to regenerate story cache?
		if ( isset($recache) )
		{
			$story=new \DB\SQL\Mapper($this->db, $this->prefix.'stories');

			$items = $this->exec("
				SELECT SELECT_OUTER.sid,
					GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock
					FROM
					(
						SELECT S.sid,
								Cat.cid, Cat.category
							FROM `tbl_stories` S
								LEFT JOIN `tbl_stories_categories`rSC ON ( rSC.sid = S.sid )
									LEFT JOIN `tbl_categories` Cat ON ( rSC.cid = Cat.cid )
							WHERE S.cache_categories IS NULL
					)AS SELECT_OUTER
				GROUP BY sid ORDER BY sid ASC;
			");

			foreach ( $items as $item )
			{
				$story->load(array('sid=?',$item['sid']));
				$story->cache_categories = json_encode($this->cleanResult($item['categoryblock']));
				$story->save();
			}
		}

		return $i;
	}
	
	protected function categoryLevelAdjust( int $cid, int $level)
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
	
	public function categoryMove(int $catID=0, $direction=NULL, $parent=NULL)
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
	
	public function categoryDelete( int $cid )
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
	
	public function characterList(int $page, array $sort, int $category)
	{
		/*
		$tags = new \DB\SQL\Mapper($this->db, $this->prefix.'characters' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		
		// Only global characters
		if ( $category == 0 )
		{
			$join = "";
			$url = "/category=0";
			$where = "WHERE rCC.catid IS NULL";
		}
		// limited by category
		elseif ( $category > 0 )
		{
			$join = "INNER JOIN `tbl_character_categories`rCC2 ON ( Ch.charid = rCC2.charid AND rCC2.catid = {$category} )";
			$url = "/category=".$category;
			$where = "";
		}
		// full list
		else
		{
			$where = $join = $url = "";
		}

		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS Ch.charid, Ch.charname, Ch.count, GROUP_CONCAT(Cat.category SEPARATOR ', ') as category
				FROM `tbl_characters`Ch 
                {$join}
                LEFT JOIN `tbl_character_categories`rCC ON ( Ch.charid = rCC.charid )
					LEFT JOIN `tbl_categories`Cat ON ( rCC.catid=Cat.cid )
				{$where}
                GROUP BY Ch.charid
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/characters{$url}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}

	public function characterCategories()
	{
		return $this->exec("SELECT Cat.cid as id, Cat.category as name, COUNT(DISTINCT rCC.charid) as counted
							FROM `tbl_categories`Cat 
								LEFT JOIN `tbl_character_categories`rCC ON ( Cat.cid = rCC.catid )
							GROUP BY Cat.cid
							HAVING counted > 0
							ORDER BY Cat.category ASC;");
	}

	public function characterLoad(int $charid): array
	{
		$sql = "SELECT Ch.charid as id, Ch.charname, Ch.biography, Ch.count, GROUP_CONCAT(rCC.catid) as categories
					FROM `tbl_characters`Ch
						LEFT JOIN `tbl_character_categories`rCC ON ( Ch.charid=rCC.charid )
					WHERE Ch.charid = :charid";
		$data = $this->exec($sql, [":charid" => $charid ]);
		if (sizeof($data)==1)
		{
			$data[0]['categories'] = explode(",",$data[0]['categories']);
			return $data[0];
		}
		return [];
	}
	
	public function characterAdd(string $name)
	{
		$character=new \DB\SQL\Mapper($this->db, $this->prefix.'characters');
		$character->charname = $name;
		$character->count = 0;
		$character->save();
		return $character->get('_id');
	}
	
	public function characterSave(int $charid, array $data)
	{
		$character=new \DB\SQL\Mapper($this->db, $this->prefix.'characters');
		$character->load(array('charid=?',$charid));
		$character->copyfrom( [ "charname" => $data['charname'], "biography" => $data['biography'] ]);
		$i =  $character->changed("charname");

		if ( $i )
		{
			// drop cache field for all stories that use this character
			$this->exec("UPDATE `tbl_stories`S
							INNER JOIN
							(
								SELECT rST.sid
								FROM `tbl_stories_tags`rST
								WHERE rST.tid = :tagID AND rST.character = 1
							) AS T ON T.sid = S.sid
						SET S.cache_characters = NULL;", [":tagID" => $charid]);
			
			$recache = TRUE;
		}

		$i += $character->changed("biography");
		$character->save();
		
		// open a db mapper to the relation table
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'character_categories');

		if (isset($data['categories']))
		{
			// Filter categories:
			$categories = array_filter($data['categories']);

			// check all existing links
			foreach ( $relations->find(array('`charid` = ?',$charid)) as $X )
			{
				$temp=array_search($X['catid'], $categories);
				if ( $temp===FALSE )
				// Excess relation, drop from table
				{
					$recounts[] = $X['catid'];
					$relations->erase(['lid=?',$X['lid']]);
				}
				// already in database
				else unset($categories[$temp]);
			}
			
			// Insert any character/category relations not already present
			if ( sizeof($categories)>0 )
			{
				foreach ( $categories as $temp)
				{
					// Add relation to table
					$relations->reset();
					$relations->charid = $charid;
					$relations->catid = $temp;
					$relations->save();
					$recounts[] = $temp;
				}
			}
			$i+=sizeof($recounts??[]);
		}
		else
		{
			// drop all relations for this character
			$i += $relations->erase(array('`charid` = ?',$charid));
		}
		
		// do we need to regenerate story cache?
		if ( isset($recache) )
		{
			$story=new \DB\SQL\Mapper($this->db, $this->prefix.'stories');

			$items = $this->exec("
				SELECT SELECT_OUTER.sid,
					GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock
					FROM
					(
						SELECT S.sid,
								Ch.charid, Ch.charname
							FROM `tbl_stories` S
								LEFT JOIN `tbl_stories_tags`rST ON ( rST.sid = S.sid )
									LEFT JOIN `tbl_characters` Ch ON ( Ch.charid = rST.tid AND rST.character = 1 )
							WHERE S.cache_characters IS NULL
					)AS SELECT_OUTER
				GROUP BY sid ORDER BY sid ASC;
			");

			foreach ( $items as $item )
			{
				$story->load(array('sid=?',$item['sid']));
				$story->cache_characters = json_encode($this->cleanResult($item['characterblock']));
				$story->save();
			}
		}

		return $i;
	}

	public function characterDelete(int $charid)
	{
		$character=new \DB\SQL\Mapper($this->db, $this->prefix.'characters');
		$character->load(array('charid=? and (count=0 OR count IS NULL)',$charid));
		
		$_SESSION['lastAction'] = [ "deleteResult" => $character->erase() ];
	}
	
	public function contestsList(int $page, array $sort) : array
	{
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS
					C.conid, C.title, 
					C.active, C.votable,
					UNIX_TIMESTAMP(C.date_open) as date_open, UNIX_TIMESTAMP(C.date_close) as date_close, UNIX_TIMESTAMP(C.vote_close) as vote_close, 
					C.cache_tags, C.cache_characters, 
					U.username, COUNT(R.lid) as count
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
		$sql = "SELECT C.conid as id, C.title, C.summary, C.description, C.concealed, C.date_open, C.date_close, C.vote_close,
					C.active, C.votable,
					GROUP_CONCAT(T.tid,',',T.label SEPARATOR '||') as tag_list,
					GROUP_CONCAT(Ch.charid,',',Ch.charname SEPARATOR '||') as character_list, 
					GROUP_CONCAT(Cat.cid,',',Cat.category SEPARATOR '||') as category_list, 
					U.uid, U.username
					FROM `tbl_contests`C
					LEFT JOIN `tbl_users`U ON ( C.uid=U.uid )
					LEFT JOIN `tbl_contest_relations`RelC ON ( C.conid=RelC.conid )
						LEFT JOIN `tbl_tags`T ON ( RelC.relid = T.tid AND RelC.type='T' )
						LEFT JOIN `tbl_characters`Ch ON ( RelC.relid = Ch.charid AND RelC.type='CH' )
						LEFT JOIN `tbl_categories`Cat ON ( RelC.relid = Cat.cid AND RelC.type='CA' )
					WHERE C.conid = :conid";

		$data = $this->exec($sql, [":conid" => $conid ]);
		if (sizeof($data)==1) 
		{
			$data[0]['date_open'] = ($data[0]['date_open']>0)
				? $this->timeToUser($data[0]['date_open'],  $this->config['date_format'])
				: "";
			$data[0]['date_close'] = ($data[0]['date_close']>0)
				? $this->timeToUser($data[0]['date_close'], $this->config['date_format'])
				: "";
			$data[0]['vote_close'] = ($data[0]['vote_close']>0)
				? $this->timeToUser($data[0]['vote_close'], $this->config['date_format'])
				: "";

			$data[0]['pre']['tag']		 = $this->jsonPrepop($data[0]['tag_list']);
			$data[0]['pre']['character'] = $this->jsonPrepop($data[0]['character_list']);
			$data[0]['pre']['category']	 = $this->jsonPrepop($data[0]['category_list']);
			return $data[0];
		}
		return NULL;
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
				"description"	=> preg_replace("/<br\\s*\\/>\\s*/i", "\n", $data['description']),
				"active"		=> $data['active'],
				"date_open"		=> empty($data['date_open']) ?
										NULL :
										\DateTime::createFromFormat($this->config['date_format'], $data['date_open'])->format('Y-m-d')." 00:00:00",
				"date_close"	=> empty($data['date_close']) ?
										NULL :
										\DateTime::createFromFormat($this->config['date_format'], $data['date_close'])->format('Y-m-d')." 00:00:00",
				"votable"		=> $data['votable'],
				"vote_close"	=> empty($data['vote_close']) ?
										NULL :
										\DateTime::createFromFormat($this->config['date_format'], $data['vote_close'])->format('Y-m-d')." 00:00:00",
			]
		);

		$i  = $contest->changed("title");
		$i += $contest->changed("concealed");
		$i += $contest->changed("summary");
		$i += $contest->changed("description");
		$i += $contest->changed("date_open");
		$i += $contest->changed("date_close");
		$i += $contest->changed("vote_close");
		
		$contest->save();
		
		// update relation table
		$this->contestRelation( $conid, $data['tag'], "T" );
		$this->contestRelation( $conid, $data['character'], "CH" );
		$this->contestRelation( $conid, $data['category'], "CA" );

		$this->rebuildContestCache($contest->conid);

		// drop contest block cache
		\Cache::instance()->clear('blockContestsCache');
		
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
	
	public function contestLoadEntries(int $conid, int $page, array $sort)
	{
		$limit = 10;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS 
				E.* FROM (
					SELECT 
						IF(S.sid IS NULL,Coll.collid,S.sid) as id,
						IF(S.title IS NULL,Coll.title,S.title) as title, 
						IF(S.cache_authors IS NULL,Coll.cache_authors,S.cache_authors) as cache_authors, 
						IF(S.validated IS NULL,'39',S.validated) as validated, 
						IF(S.completed IS NULL,'9',S.completed) as completed, 
						RelC.type, RelC.lid, Coll.ordered
						FROM `tbl_contest_relations`RelC
							LEFT JOIN `tbl_stories`S ON ( S.sid = RelC.relid AND RelC.type='ST' )
							LEFT JOIN `tbl_collections`Coll ON ( Coll.collid = RelC.relid AND RelC.type='CO' )
						WHERE RelC.conid = :conid AND ( RelC.type='ST' OR RelC.type='CO' )
					) as E
				GROUP BY id
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql, [":conid" => $conid ]);
		
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/contests/id={$conid}/entries/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		
		if ( sizeof($data)>0 )
		{
			foreach ($data as &$dat)
			{
				$dat['cache_authors'] = json_decode($dat['cache_authors']);
				foreach ($dat['cache_authors'] as $ca) $dat['authors'][] = $ca[1];
				$dat['authors'] = implode(", ",$dat['authors']);
			}
		}

		return $data;
	}

	public function contestEntryAdd(int $conID, int $entryID, string $type="S")// : void
	{
		if ( $type == "S" )
		{
			$stories = new \DB\SQL\Mapper($this->db, $this->prefix.'contest_relations');
			if ( 0 === $stories->count(array("conid=? AND relid=? AND type='ST'",$conID, $entryID)) )
			{
				$stories->reset();
				$stories->conid = $conID;
				$stories->relid = $entryID;
				$stories->type  = 'ST';
				$stories->save();
				$addResult = 1;
				// drop contest block cache
				\Cache::instance()->clear('blockContestsCache');
			}
			else $addResult = 0;
		}
		elseif ( $type == "C" )
		{
			$collections = new \DB\SQL\Mapper($this->db, $this->prefix.'contest_relations');
			if ( 0 === $collections->count(array("conid=? AND relid=? AND type='CO'",$conID, $entryID)) )
			{
				$collections->reset();
				$collections->conid = $conID;
				$collections->relid = $entryID;
				$collections->type  = 'CO';
				$collections->save();
				$addResult = 1;
				// drop contest block cache
				\Cache::instance()->clear('blockContestsCache');
			}
			else $addResult = 0;
		}
		$this->f3->set("addResult",$addResult);
	}

	public function contestEntryRemove(int $conID, int $linkID)
	{
		$link=new \DB\SQL\Mapper($this->db, $this->prefix.'contest_relations');
		$link->load(array("conid=? AND lid=?",$conID, $linkID));
		// show result after reload
		$_SESSION['lastAction'] = [ "deleteResult" => $link->erase() ];
		// drop contest block cache
		\Cache::instance()->clear('blockContestsCache');
	}

	public function contestDelete(int $conid)
	{
		$contest=new \DB\SQL\Mapper($this->db, $this->prefix.'contests');
		$contest->load(array('conid=?',$conid));
		$_SESSION['lastAction'] = [ "deleteResult" => $contest->erase() ];
		// drop contest block cache
		\Cache::instance()->clear('blockContestsCache');
	}

	public function featuredList ( int $page, array $sort, string &$status ): array
	{
		/*
		int status = 
			1: active
			2: past
			3: upcoming
		*/
		$limit = 20;
		$pos = $page - 1;
		
		switch( $status )
		{
			case "upcoming":
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
					"SELECT SQL_CALC_FOUND_ROWS S.title, S.sid, S.summary, S.cache_authors, S.cache_rating, 
							IF(F.start IS NULL OR STATUS IS NOT NULL,NULL,UNIX_TIMESTAMP(F.start)) as start, 
							IF(F.end IS NULL OR STATUS IS NOT NULL,NULL,UNIX_TIMESTAMP(F.end)) as end
						FROM `tbl_stories`S
						INNER JOIN `tbl_featured`F ON ( F.type='ST' AND F.id = S.sid AND (%JOIN%) )
					ORDER BY {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit
				);

		$data = $this->exec($sql);
		
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/stories/featured/select={$status}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		foreach ( $data as &$dat )
			$dat['cache_authors'] = json_decode($dat['cache_authors'], TRUE);
		return $data;
	}
	
	public function featuredLoad( int $sid )
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS S.title, S.sid, S.summary, F.status, F.start, F.end, F.uid, U.username, S.cache_authors, S.cache_rating
					FROM `tbl_stories`S
						LEFT JOIN `tbl_featured`F ON ( F.type='ST' AND F.id = S.sid )
						LEFT JOIN `tbl_users`U ON ( F.uid = U.uid )
				WHERE S.sid = :sid";
		$data = $this->exec($sql, [":sid" => $sid ]);
		if (sizeof($data)==1)
		{
			// going with the preset date/time versions to make sure the datetimepicker is happy
			if ( $data[0]['start'] != NULL ) $data[0]['start'] = $this->timeToUser($data[0]['start'], $this->config['date_preset']." ".$this->config['time_preset']);
			if ( $data[0]['end']   != NULL ) $data[0]['end']   = $this->timeToUser($data[0]['end'],   $this->config['date_preset']." ".$this->config['time_preset']);
			// unpack author cache
			$data[0]['cache_authors'] = json_decode($data[0]['cache_authors'], TRUE);
			return $data[0];
		}
		return NULL;
	}

	public function featuredSave( int $sid, array $data )
	{
		$feature=new \DB\SQL\Mapper($this->db, $this->prefix.'featured');
		$feature->load(array('id=? AND type="ST"',$sid));
		// copy form data, also used to create a new feature
		$feature->copyfrom( 
			[ 
				"status"	=> ($data['status']>0) ? $data['status'] : NULL, 
				"id"		=> $sid,
				"uid"		=> $_SESSION['userID']
			]
		);

		if ( NULL === $feature->status OR ""!=($data['start']??"") OR ""!=($data['end']??"") )
		//if ( NULL === $feature->status OR isset($data['start']) OR isset($data['end']) )
		{
			// we either have or require a start date, so let's make sure this is proper
			$start = ( ""==($data['start']??"") OR ( FALSE === $obj = \DateTime::createFromFormat($this->config['date_preset']." ".$this->config['time_preset'], $data['start']) ) )
				? date('Y-m-d H:i')
				: $obj->format('Y-m-d H:i');
			$feature->start = $start.":00";

			// same goes for the end date
			$end = ( ""==($data['end']??"") OR ( FALSE === $obj = \DateTime::createFromFormat($this->config['date_preset']." ".$this->config['time_preset'], $data['end']) ) )
				? date('Y-m-d H:i')
				: $obj->format('Y-m-d H:i');
			$feature->end = $end.":00";

			// if start is past end, make them the same
			if ( \DateTime::createFromFormat('Y-m-d H:i', $start)->format("U") > \DateTime::createFromFormat('Y-m-d H:i', $end)->format("U") )
				$feature->end = $feature->start;
		}
		// make note of data change, must occur before save()
		$_SESSION['lastAction'] = [ "saveResult" => (int)$feature->changed() ];

		// save date
		$feature->save();
	}
	
	public function recommendationDelete(int $recid)
	{
		// map the recommendation
		$recommendations=new \DB\SQL\Mapper($this->db, $this->prefix.'recommendations');
		
		// map all relations
		$relations=new \DB\SQL\Mapper($this->db, $this->prefix.'recommendation_relations');
		
		// map a possible feature tag
		$featured=new \DB\SQL\Mapper($this->db, $this->prefix.'featured');
		
		// delete all mapped entries and count the deletions
		$_SESSION['lastAction']['deleteDetails'] = 
		[ 
			$recommendations->erase(array('recid=?',$recid)), 
			$relations->erase(array('recid=?',$recid)), 
			$featured->erase(array("id=? AND type='RC'",$recid))
		];
		$_SESSION['lastAction']['deleteResult']  = [ array_sum($_SESSION['lastAction']['deleteDetails']) ];
	}

	public function logGetCount()
	{
		$count = [];
		$countSQL = "SELECT IF(L.type='','X',L.type)as type, COUNT(L.id) as items FROM `tbl_log`L @WHERE@ GROUP BY L.type;";
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
		// view: v_ACPlogData *todo*
		$sql = "SELECT SQL_CALC_FOUND_ROWS U.uid, U.username, 
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
	
	public function pollList(int $page, array $sort) : array
	{
		$limit = 15;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS P.poll_id as id, P.question, UNIX_TIMESTAMP(P.start_date) as start_date, UNIX_TIMESTAMP(P.end_date) as end_date,
					U.uid, U.username
				FROM `tbl_poll`P
					LEFT JOIN `tbl_users`U ON ( P.uid = U.uid )
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/home/polls/order={$sort['link']},{$sort['direction']}",
			$limit
		);

		return $data;
	}

	public function pollAdd(string $name): int
	{
		$poll=new \DB\SQL\Mapper($this->db, $this->prefix.'poll');
		$poll->question = $name;
		$poll->uid		= $_SESSION['userID'];
		$poll->options	= '[""]';
		$poll->save();
		return $poll->get('_id');
	}

	public function pollLoad(int $pollID): array
	{
		$sql = "SELECT P.poll_id as id, P.question, P.options, P.results, P.cache, P.start_date, P.end_date, P.open_voting,
					U.uid, U.username
				FROM `tbl_poll`P
					LEFT JOIN `tbl_poll_votes`V ON ( P.poll_id = V.poll_id )
					LEFT JOIN `tbl_users`U ON ( P.uid = U.uid )
				WHERE P.poll_id = :pollid
				GROUP BY V.option;";

		if ( NULL === $data = @$this->exec($sql, [":pollid" => $pollID])[0] )
			return [];

		// build a cache array
		if ( $data['cache']==NULL )
			$data['cache'] = $this->pollBuildCache($data['id']);
		// build the result array from the cache field
		else $data['cache'] = json_decode($data['cache'],TRUE);
		
		// each line is one option
		$data['options'] = implode("\n", json_decode($data['options'],TRUE)??[]);

		// going with the preset date/time versions to make sure the datetimepicker is happy
		$data['start_date'] = $data['start_date'] == ""
								? ""
								: $this->timeToUser($data['start_date'], $this->config['date_preset']." ".$this->config['time_preset']);
								
		$data['end_date'] = $data['end_date'] == ""
								? ""
								: $this->timeToUser($data['end_date'], $this->config['date_preset']." ".$this->config['time_preset']);
		
		return $data;
	}

	public function pollSave(int $id, array $data)
	{
		$poll=new \DB\SQL\Mapper($this->db, $this->prefix.'poll');
		$poll->load(array('poll_id=?',$id));

		$poll->copyfrom( 
			[ 
				"question"		=> $data['question'], 
				"start_date"	=> $data['start_date'] == ""
									? NULL
									: \DateTime::createFromFormat($this->config['date_preset']." ".$this->config['time_preset'], $data['start_date'])->format('Y-m-d H:i'),
				"end_date"		=> $data['end_date'] == ""
									? NULL
									: \DateTime::createFromFormat($this->config['date_preset']." ".$this->config['time_preset'], $data['end_date'])->format('Y-m-d H:i'),
				"options"		=> json_encode( explode("\n", $data['options']) ),
				"open_voting"	=> (int)isset( $data['open_voting'] ),
			]
		);

		$i  = $poll->changed("question");
		$i += $poll->changed("start_date");
		$i += $poll->changed("end_date");
		$i += $poll->changed("options");
		
		// drop cache if the options were edited
		if ( $poll->changed("options") )
			$poll->cache = NULL;
		
		$poll->save();

		return $i;
	}

	public function pollDelete(int $pollID)
	{
		$poll=new \DB\SQL\Mapper($this->db, $this->prefix.'poll');
		$erase = $poll->erase(array('poll_id=?',$pollID));

		$votes=new \DB\SQL\Mapper($this->db, $this->prefix.'poll_votes');
		if (0 < $votes->count(array('poll_id=?',$pollID)))
			$erase = $erase * $votes->erase(array('poll_id=?',$pollID));

		return $erase;
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
		$sqlAuthors = "SELECT U.uid as id, U.username as name FROM `tbl_users`U WHERE FIND_IN_SET(U.uid,:uid);";
		$authors = $this->exec($sqlAuthors,  [ ':uid' => $formData['new_author'] ] );
		
		return [ "storyInfo" => $similarExists[0], "preAuthor" => json_encode($authors) ];
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
		
		$i = $current->changed();

		$current->save();

		// Step two: check for changes in relation tables

		// Check tags:
		$i += $this->relationStoryTag( $current->sid, $post['tags'] );
		// Check Characters:
		$i += $this->relationStoryCharacter( $current->sid, $post['characters'] );
		// Check Categories:
		$i += $this->relationStoryCategories( $current->sid, $post['category'] );
		// Check Authors:
		$i += $this->relationStoryAuthor( $current->sid, $post['mainauthor'], $post['supauthor'] );

		$collection=new \DB\SQL\Mapper($this->db, $this->prefix.'collection_stories');
		$inSeries = $collection->find(array('sid=?',$current->sid));
		foreach ( $inSeries as $in )
		{
			// Rebuild collection/series cache based on new data
			$this->cacheCollections($in->seriesid);
		}

		// Rebuild story cache based on new data
		if ( $i ) $this->rebuildStoryCache($current->sid);
		return $i;
	}

	public function storyListPending( int $page, array $sort ) : array
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
                    GROUP_CONCAT(DISTINCT U.uid ORDER BY U.username ASC SEPARATOR ', ') as aid,
					GROUP_CONCAT(DISTINCT U.username ORDER BY U.username ASC SEPARATOR ', ') as authors
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
				$data['chapterList'] = $this->chapterLoadList($sid);
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

	public function loadStoryMapper($sid)
	{
		$story=new \DB\SQL\Mapper($this->db, $this->prefix.'stories');
		$story->load(array('sid=?',$sid));
		return $story;
	}
	
	public function tagAdd(string $name) : int
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->label = $name;
		$tag->tgid = 1;
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

	public function tagLoad(int $tid)
	{
		$sql = "SELECT T.tid as id, T.tgid, T.label, T.description, T.count, G.description as groupname FROM `tbl_tags`T LEFT JOIN `tbl_tag_groups`G ON ( T.tgid=G.tgid) WHERE T.tid = :tid";
		return $this->exec($sql, [":tid" => $tid ])[0]??NULL;
	}

	public function tagSave(int $tid, array $data)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->load(array('tid=?',$tid));
		$tag->copyfrom( [ "tgid" => $data['taggroup'], "label" => $data['label'], "description" => $data['description'] ]);

		//if ( TRUE === $i = $tag->changed("tgid") ) $this->tagGroupRecount();
		$i = $tag->changed("tgid");
		$i += $tag->changed("label");
		
		if ( $i )
		{
			// drop tag cache for all stories that use this tag
			$this->exec("UPDATE `tbl_stories`S
							INNER JOIN
							(
								SELECT rST.sid
								FROM `tbl_stories_tags`rST
								WHERE rST.tid = :tagID AND rST.character = 0
							) AS T ON T.sid = S.sid
						SET S.cache_tags = NULL;", [":tagID" => $tid]);
			
			$recache = TRUE;
		}
		
		$i += $tag->changed("description");
		$tag->save();

		// do we need to regenerate story cache?
		if ( isset($recache) )
		{
			$story=new \DB\SQL\Mapper($this->db, $this->prefix.'stories');

			$items = $this->exec("
				SELECT SELECT_OUTER.sid,
					GROUP_CONCAT(DISTINCT tid,',',tag,',',description,',',tgid ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock
					FROM
					(
						SELECT S.sid,
								TG.description,TG.order,TG.tgid,T.label as tag,T.tid
							FROM `tbl_stories` S
								LEFT JOIN `tbl_stories_tags`rST ON ( rST.sid = S.sid )
									LEFT JOIN `tbl_tags` T ON ( T.tid = rST.tid AND rST.character = 0 )
										LEFT JOIN `tbl_tag_groups` TG ON ( TG.tgid = T.tgid )
							WHERE S.cache_tags IS NULL
					)AS SELECT_OUTER
				GROUP BY sid ORDER BY sid ASC;
			");

			foreach ( $items as $item )
			{
				$tagblock['simple'] = $this->cleanResult($item['tagblock']);
				if($tagblock['simple']!==NULL) foreach($tagblock['simple'] as $t)
					$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];

				$story->load(array('sid=?',$item['sid']));
				$story->cache_tags = json_encode($tagblock);
				$story->save();
			}
		}

		return $i;
	}
	
	public function tagDelete(int $tid)
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
	
	public function tagGroupLoad(int $tgid)
	{
		$sql = "SELECT TG.tgid as id, TG.label as label, TG.description FROM `tbl_tag_groups`TG WHERE TG.tgid = :tgid";
		return $this->exec($sql, [":tgid" => $tgid ])[0]??NULL;
	}
	
	public function tagGroupSave(int $tgid, array $data)
	{
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->load(array('tgid=?',$tgid));
		$taggroup->copyfrom( [ "label" => $data['label'], "description" => $data['description'] ]);

		$i = $taggroup->changed("label");
		$i += $taggroup->changed("description");

		$taggroup->save();
		return $i;
	}
	
	public function tagGroupDelete(int $tgid)
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
		$sql = "SELECT SQL_CALC_FOUND_ROWS `uid`, `username`, `realname`, `groups` FROM `tbl_users` WHERE `groups` > 16 ORDER BY groups,username ASC";
		return $this->exec($sql);
	}
	
	public function listUserFields()
	{
		$sql = "SELECT `field_id`, `field_type`, `field_name`, `field_title`, `field_options`, `enabled` FROM `tbl_user_fields` ORDER BY `field_type` ASC";
		return $this->exec($sql);
	}
	
	public function memberAdd(array $member)
	{
		// load member list
		$members=new \DB\SQL\Mapper($this->db, $this->prefix.'users');
		// return an error if a login like this already exists
		if ( 1 == sizeof($members->find(array('login=?',$member['new_name']))) )
			return 0;
		if ( 1 == sizeof($members->find(array('username=?',$member['new_name']))) )
			return 0;
		
		$kv = [
			'login'		=> $member['new_name'],
			'username'	=> $member['new_name'],
			'email'		=> $member['new_mail'],
			'groups'	=> $member['new_group'],
		];
		$userID = $this->insertArray($this->prefix.'users', $kv );

		// Log successful user creation
		if ( $userID > 0 ) 
			\Logging::addEntry(
				"RG",
				json_encode([
					'name'		=> $member['new_name'],
					'uid'		=> $userID,
					'email'		=> $member['new_mail'],
					'reason'	=> '',
					'admin'		=> TRUE
				])
			);
		return $userID;
	}
	
	public function memberDataSave(int $uid, array $data)
	{
		$member=new \DB\SQL\Mapper($this->db, $this->prefix.'users');
		$member->load(array('uid=?',$uid));
		
		if(NULL === $member->uid) return FALSE;
		
		$member->copyfrom( 
			[ 
				"login"			=> $data['login'], 
				"username"		=> $data['username'],
				"realname"		=> $data['realname'],
				"email"			=> $data['email'],
				"registered"	=> \DateTime::createFromFormat($this->config['datetime_format'], $data['registered'])->format('Y-m-d H:i'),
			]
		);

		$i  = $member->changed("login");
		$i += $member->changed("username");
		$i += $member->changed("realname");
		$i += $member->changed("email");
		$i += $member->changed("registered");
		
		$member->save();
		
		return $i;
	}
	
	public function memberGroupSave(int $uid, array $groups)
	{
		$member=new \DB\SQL\Mapper($this->db, $this->prefix.'users');
		$member->load(array('uid=?',$uid));

		if(NULL === $member->uid) return FALSE;

		if(isset($groups[0]))
			$member->groups = 0;
		else
		{
			$g = 0;
			
			// user
			if ( isset($groups[1]) )	$g = $g | 1;
			// trusted user (includes user)
			if ( isset($groups[2]) )	$g = $g | 3;
			
			// author
			if ( isset($groups[4]) )	$g = $g | 4;
			// trusted author (includes author)
			if ( isset($groups[8]) )	$g = $g | 12;
			
			// lector (includes trusted user)
			if ( isset($groups[16]) )	$g = $g | 19;
			// moderator (includes trusted lector)
			if ( isset($groups[32]) )	$g = $g | 51;
			// super-moderator (includes all below)
			if ( isset($groups[64]) )	$g = $g | 127;
			// admin (includes all below)
			if ( isset($groups[128]) )	$g = $g | 255;
			
			/*
			Session mask (bit-wise)

			- admin			   128
			- super mod			64
			- story mod 		32
			- lector			16
			- author (trusted)	 8
			- author (regular)	 4
			- user (trusted)	 2
			- user (active)		 1
			- guest/banned		 0
			*/			
			$member->groups = $g;
			
		}
	
		$member->save();
		return $member->changed("groups");
	}
	
	public function listUsers($page, array $sort, $search=NULL)
	{
		$limit = 20;
		$pos = $page - 1;
		$bind = [];

		$sql = "SELECT SQL_CALC_FOUND_ROWS `uid`, `username`, `login`, `email`, UNIX_TIMESTAMP(registered) as registered, `groups`
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
							or `username` LIKE :term2
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
		$sql = "SELECT uid, login, username, realname, email, UNIX_TIMESTAMP(registered) as registered, groups, curator, about
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

	public function loadCustompage(int $id)
	{
		$sql = "SELECT TB.* FROM `tbl_textblocks`TB WHERE TB.id = :id";
		return $this->exec($sql, [":id" => $id ])[0]??NULL;
	}

	public function saveCustompage(int $id, array $data)
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
	
	public function addCustompage( string $label )
	{
		$textblock=new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		$conflicts = (int)$textblock->count(array('label=?',$label));
		if($conflicts>0) return FALSE;
		$textblock->reset();
		$textblock->label = $label;
		$textblock->save();
		return $textblock->_id;
	}

	public function deleteCustompage( int $id )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		if ( $delete->count( ["id = ?", $id ] ) == 0 ) return FALSE;
		$delete->erase( ["id = ?", $id ] );
		return TRUE;
	}

	public function newsList(int $page, array $sort)
	{
		/*
		$tags = new \DB\SQL\Mapper($this->db, $this->prefix.'tags' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS N.nid, N.headline, U.username AS author, DATE(N.datetime) as date, UNIX_TIMESTAMP(N.datetime) as timestamp
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

	public function newsLoad(int $id)
	{
		$sql = "SELECT N.nid as id, N.headline, N.newstext, N.datetime, UNIX_TIMESTAMP(N.datetime) as timestamp FROM `tbl_news`N WHERE `nid` = :nid";
		if ( NULL === $data = @$this->exec($sql, [":nid" => $id ])[0] )
			return [];

		// going with the preset date/time versions to make sure the datetimepicker is happy
		$data['datetime'] = $data['datetime'] == ""
								? ""
								: $this->timeToUser($data['datetime'], $this->config['date_preset']." ".$this->config['time_preset']);

		return $data;
	}

	public function newsAdd( string $headline )
	{
		$news=new \DB\SQL\Mapper($this->db, $this->prefix.'news');
		$news->uid = $_SESSION['userID'];
		$news->headline = $headline;
		$news->datetime = NULL;
		$news->save();
		return $news->_id;
	}

	public function newsSave(int $id, array $data)
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
				"datetime"	=> $data['datetime'] == ""
									? NULL
									: \DateTime::createFromFormat($this->config['datetime']." ".$this->config['time_preset'], $data['datetime'])->format('Y-m-d H:i'),
			]
		);

		$i  = $news->changed("headline");
		$i += $news->changed("newstext");
		$i += $news->changed("datetime");
		
		$news->save();

		return $i;
	}
	
	public function newsDelete( int $id )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'news');
		return $delete->erase( ["nid = ?", $id ] );
	}

	public function shoutList(int $page, array $sort)
	{
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS S.id, S.message, IF(S.guest_name IS NULL,U.username,S.guest_name) AS author, S.uid, DATE(S.date) as date, UNIX_TIMESTAMP(S.date) as timestamp
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
	
	public function shoutDelete(int $id)
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'shoutbox');
		if ( $delete->count( ["id = ?", $id ] ) == 0 ) return FALSE;
		$delete->erase( ["id = ?", $id ] );
		return TRUE;
	}

	public function shoutLoad(int $id)
	{
		$sql = "SELECT S.id, S.message FROM `tbl_shoutbox`S WHERE `id` = :id";
		$data = $this->exec($sql, [":id" => $id ]);
		if (sizeof($data)!=1) 
			return NULL;

		return $data[0];
	}

	public function shoutSave(int $id, array $data)
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
	
	public function maintenanceRecountChapters()
	{
		return $this->exec
		("UPDATE `tbl_stories`S
			SET chapters = 
				( SELECT COUNT(DISTINCT chapid) as chapters
					FROM `tbl_chapters`C WHERE C.sid = S.sid AND C.validated >= 30
				);"
		);
	}

	// former recount function, now a maintenance tool
	public function maintenanceRecountCategories()
	{
		// clear stats of all categories
		$sql = "UPDATE `tbl_categories` SET `stats` = NULL";
		$this->exec($sql);
		
		// mySQL runs on a pretty tight CONCAT limit, better make some room here ...
		$this->exec("SET SESSION group_concat_max_len = 1000000;");
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories' );
		
		// start with lowest level and work up all the way to the root
		do
		{
			$sql = "SELECT C.cid, C.category, COUNT(DISTINCT S.sid) as counted, C.parent_cid as parent, C.leveldown, 
						GROUP_CONCAT(DISTINCT C1.category SEPARATOR '||' ) as sub_categories, 
						GROUP_CONCAT(DISTINCT C1.stats SEPARATOR '||' ) as sub_stats
				FROM `tbl_categories`C 
					INNER JOIN (SELECT leveldown FROM `tbl_categories` WHERE `stats` = '' ORDER BY leveldown DESC LIMIT 0,1) c2 ON ( C.leveldown = c2.leveldown )
					LEFT JOIN `tbl_stories_categories`SC ON ( C.cid = SC.cid )
					LEFT JOIN `tbl_stories`S ON ( S.sid = SC.sid )
					LEFT JOIN `tbl_categories`C1 ON ( C.cid = C1.parent_cid )
				GROUP BY C.cid";
				
			// process all categories of that level
			do {
				$items = $this->exec( $sql );
				$change = FALSE;
				foreach ( $items as $item)
				{
					if ( $item['sub_categories']==NULL ) $sub = NULL;
					else
					{
						$sub_categories = explode("||", $item['sub_categories']);
						$sub_stats = explode("||", $item['sub_stats']);
						$sub_stats = array_map("json_decode", $sub_stats);

						foreach( $sub_categories as $key => $value )
						{
							if ($sub_stats[$key]!=NULL)
							{
								$item['counted'] += $sub_stats[$key]->count;
								$sub[] =
								[ 
									'id' 	=> $sub_stats[$key]->cid,
									'count' => $sub_stats[$key]->count,
									'name'	=> $value,
								];
							}
						}
					}
					$stats = json_encode([ "count" => (int)$item['counted'], "cid" => $item['cid'], "sub" => $sub ]);
					unset($sub);
					
					$categories->load(array('cid=?',$item['cid']));
					$categories->stats = $stats;
					$categories->save();
					
					$change = ($change) ? : $categories->changed();
				}
			} while ( $change != FALSE );
		} while ( $items[0]['leveldown'] > 0 );
	}

}
