<?php
namespace Model;

class AdminCP extends Base {

	protected $menu = [];
	protected $access = [];
	
	public function ajax($key, $data)
	{
		$bind = NULL;
		
		if ( $key == "tags" )
		{
			if(isset($data['tagname']))
			{
				$ajax_sql = "SELECT T.label as name,T.tid as id FROM `tbl_tags`T WHERE T.label LIKE :label LIMIT 10";
				$bind = [ ":label" =>  "%{$data['tagname']}%" ];
			}
		}
		elseif ( $key == "editMeta" )
		{
			if(isset($data['category']))
			{
				$ajax_sql = "SELECT category as name, cid as id from `tbl_categories`C WHERE C.category LIKE :category ORDER BY C.category ASC LIMIT 5";
				$bind = [ ":category" =>  "%{$data['category']}%" ];
			}
			elseif(isset($data['author']))
			{
				$ajax_sql = "SELECT U.nickname as name, U.uid as id from `tbl_users`U WHERE U.nickname LIKE :nickname AND ( U.groups & 5 ) ORDER BY U.nickname ASC LIMIT 5";
				$bind = [ ":nickname" =>  "%{$data['author']}%" ];
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
		}
		elseif ( $key == "storySearch" )
		{
			if(isset($data['storyID']))
			{
				$ajax_sql = "SELECT S.title as name,S.sid as id from `tbl_stories`S WHERE S.title LIKE :story OR S.sid = :sid ORDER BY S.title ASC LIMIT 5";
				$bind = [ ":story" =>  "%{$data['storyID']}%", ":sid" =>  $data['storyID'] ];
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

		if ( isset($ajax_sql) ) return $this->exec($ajax_sql, $bind);
		return NULL;
	}
	
	public function settingsFields($select)
	{
		$sql = "SELECT `name`, `value`, `comment`, `form_type`
					FROM `tbl_config` 
					WHERE 
						`admin_module` LIKE :module 
						AND `can_edit` > 0 
					ORDER BY `section_order` ASC";
		$data = $this->exec($sql,[ ":module" => $select ]);
		foreach ( $data as &$d )
		{
			list ( $d['comment'], $d['comment_small'] ) = array_merge ( explode("@SMALL@", $d['comment']), array(FALSE) );
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
	
	public function listTeam()
	{
		$sql = "SELECT `uid`, `nickname`, `realname`, `groups` FROM `tbl_users` WHERE `groups` > 16";
		return $this->exec($sql);
	}
	
	public function listUserFields()
	{
		$sql = "SELECT `field_id`, `field_type`, `field_name`, `field_title`, `field_on` FROM `tbl_user_fields` ORDER BY `field_type` ASC";
		return $this->exec($sql);
	}
	
	public function saveKeys_file($data)
	{
		$affected=0;
		$sqlUpdate = "UPDATE `tbl_config` SET `value` = :value WHERE `name` = :key and `admin_module` = :section;";
		$sqlFile = "SELECT 1 from `tbl_config` WHERE `name`= :key and `admin_module`= :section and `to_config_file`=1";
		
		$mapper = \Config::instance();
		foreach ( $data as $section => $fields )
		{
			foreach($fields as $key => $value)
			{
				if ( $res = $this->exec($sqlUpdate,[ ":value" => $value, ":key" => $key, ":section" => $section ]) )
				{
					if ( $this->exec($sqlFile,[ ":key" => $key, ":section" => $section ]) )
					{
						/* experimental */
						if ( $value == "TRUE") $value = TRUE;
						elseif ( $value == "FALSE") $value = FALSE;
						
						$key = explode("__", $key);

						if ( isset($key[1]) )
						{	
							// nested key structures, like bb2__verbose -> bb2[verbose]
							if ( empty( $mapper->{$key[0]} ) ) $mapper->{$key[0]} = [];
							$mapper->{$key[0]}[$key[1]] = $value;
						}
						else
						{
							if ( NULL === $c = json_decode( $value ,TRUE ) )
								$mapper->{$key[0]} = $value;
							else
								$mapper->{$key[0]} = $c;
						}
					}
					$affected++;	
				}
			}
		}
		if ( $affected ) $mapper->save();
		return [ $affected, FALSE ]; // prepare for error check
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
		
		// re-build config cache field
		\Config::cache();

		return [ $affected, FALSE ]; // prepare for error check
	}
	
	public function checkAccess($link, $exists = FALSE)
	{
		if ( $exists ) return isset($this->access[$link]);
		return ( isset($this->access[$link]) AND (int)$this->access[$link]&(int)$_SESSION['groups'] );
	}

	public function showMenu($selected=FALSE)
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
			if ( isset($menu[$item['child_of']]) ) $menu[$item['child_of']]['sub'][$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"], "requires" => $item["requires"] ];
			else $menu[$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"], "requires" => $item["requires"] ];
			$this->access[$item['link']] = $item["requires"];
		}

		/**
			If menu is created for a moderator, traverse the menu and clear branches that can't be accessed
		**/
		if ( !($_SESSION['groups']&128) )
		{
			$menu = $this->secureMenu($menu);
			foreach ( $menu as $key => &$m )
				if ( $key != $selected ) unset ( $m['sub'] );
		}

		return $menu;
	}
	
	protected function secureMenu($menu)
	{
		
		foreach ( $menu as &$m )
		{
			if ( isset($m['sub']) ) $m['sub'] = $this->secureMenu($m['sub']);
//			if ( $m['requires'] == 2 AND @sizeof($m['sub'])==0) $m = [];
			if ( !((int)$_SESSION['groups']&(int)$m['requires']) AND @sizeof($m['sub'])==0) $m = [];
		}
		return array_filter($menu);
	}

	public function showMenuUpper($selected=FALSE)
	{
		if(!$selected) return NULL;
		$sql = "SELECT M.*
					FROM `tbl_menu_adminpanel`M
				WHERE `child_of` = :selected AND M.requires <= {$_SESSION['groups']}
				ORDER BY M.order ASC";
		return $this->exec( $sql, [ ":selected" => $selected ] );
	}
	
	public function tagsList($page, $sort)
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
	
//	public function loadTag(int $tid)
	public function loadTag($tid)
	{
		$sql = "SELECT T.tid as id, T.tgid, T.label, T.description, T.count, G.description as groupname FROM `tbl_tags`T LEFT JOIN `tbl_tag_groups`G ON ( T.tgid=G.tgid) WHERE T.tid = :tid";
		$data = $this->exec($sql, [":tid" => $tid ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}

//	public function loadTagGroup(int $tgid)
	public function loadTagGroup($tgid)
	{
		$sql = "SELECT TG.tgid as id, TG.label as label, TG.description FROM `tbl_tag_groups`TG WHERE TG.tgid = :tgid";
		$data = $this->exec($sql, [":tgid" => $tgid ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}
	
	public function addTag($name)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->label = $name;
		$tag->save();
		return $tag->get('_id');
	}

	public function addTagGroup($name)
	{
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->description = $name;
		$taggroup->save();
		return $taggroup->get('_id');
	}

//	public function saveTag(int $tid, array $data)
	public function saveTag($tid, array $data)
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
	
//	public function saveTagGroup(int $tgid, array $data)
	public function saveTagGroup($tgid, array $data)
	{
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->load(array('tgid=?',$tgid));
		$taggroup->copyfrom( [ "label" => $data['label'], "description" => $data['description'] ]);

		$i = $taggroup->changed("label");
		$i += $taggroup->changed("description");

		$taggroup->save();
		return $i;
	}
	
//	public function deleteTag(int $tid)
	public function deleteTag($tid)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->load(array('tid=?',$tid));
		$tag->erase();
	}

//	public function deleteTagGroup(int $tgid)
	public function deleteTagGroup($tgid)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		if($tag->count(array('tgid=?',$tgid)))
		{
			return FALSE;
		}
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->load(array('tgid=?',$tgid));
		$taggroup->erase();
		return TRUE;
	}

	public function tagGroups()
	{
		$sql = "SELECT TG.tgid as id, TG.description FROM `tbl_tag_groups`TG ORDER BY TG.description ASC";
		return $this->exec($sql);
	}
	
	public function categoriesListFlat()
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

//	public function loadCategory(int $cid)
	public function loadCategory($cid)
	{
		if ( $cid == 0 ) return NULL;
		$sql = "SELECT cid as id, parent_cid, category, description, image, locked, leveldown, inorder, stats FROM `tbl_categories`C WHERE C.cid = :cid";
		$data = $this->exec($sql, [":cid" => $cid ]);
		if (sizeof($data)==1) return $data[0];
		return FALSE;
	}

//	public function loadCategoryPossibleParents(int $cid)
	public function loadCategoryPossibleParents($cid)
	{
		$sql = "SELECT C.cid, C.parent_cid, C.leveldown, C.category
					FROM `tbl_categories`C 
					INNER JOIN `tbl_categories`C2 ON ( ( C.parent_cid = C2.parent_cid OR C.cid = C2.parent_cid )AND C2.cid = :cid ) 
				WHERE C.cid != :cid2
				ORDER BY C.leveldown, C.inorder ASC ";
		
		$data = $this->exec($sql, [":cid" => $cid, ":cid2" => $cid ]);

		return $data;
	}

//	public function saveCategory(int $cid, array $data)
	public function saveCategory($cid, array $data)
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
			$this->adjustCategoryLevel($cid, $new_level);
			
			$category->save();

			\Model\Routines::instance()->cacheCategories($parent_cid);
			\Model\Routines::instance()->cacheCategories($data['parent_cid']);
		}
		else $category->save();

		return $i;
	}
	
//	protected function adjustCategoryLevel( int $cid, int $level)
	protected function adjustCategoryLevel($cid, $level)
	{
		$category=new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$category->load(array('cid=?',$cid));
		$category->leveldown = $level;
		$category->save();
		
		$subCategories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$subCategories->load(array('parent_cid=?',$cid));
		while ( !$subCategories->dry() )
		{
			$this->adjustCategoryLevel( $subCategories->cid, $level+1 );
			$subCategories->next();
		}
	}
	
//	public function addCategory( int $parent_cid, array $data=[] )
	public function addCategory($parent_cid, array $data=[] )
	{
		// "clean" target category level
		$this->moveCategory(0, NULL, $parent_cid);

		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		// get number of elements with same parent
		$count = $categories->count(["parent_cid = ?", $parent_cid ]);
		$leveldown = $categories->load(["parent_cid = ?", $parent_cid ])->leveldown;
		$categories->reset();
		
		$categories->category 		= $data['category'];
		$categories->description 	= $data['description'];
		$categories->locked 		= $data['locked'];
		$categories->inorder		= $count;
		$categories->leveldown		= $leveldown;
		$categories->parent_cid		= $parent_cid;
		
		$categories->save();

		$this->moveCategory(0, NULL, $parent_cid);

		return $categories->_id;
	}
	
//	public function moveCategory(int $catID=0, $direction=NULL, $parent=NULL)
	public function moveCategory($catID=0, $direction=NULL, $parent=NULL)
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
		return $parent;
	}
	
//	public function deleteCategory( int $cid )
	public function deleteCategory( $cid )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$delete->load( ["cid = ?", $cid ] );
		if ( 1 != $delete->count( ["cid = ?", $cid ] ) ) return FALSE;
		$stats = json_decode( $delete->stats, TRUE );
		if ( $stats['sub']===NULL AND $stats['count']==0 )
		{
			$parent = $delete->parent_cid;
			$delete->erase( ["cid = ?", $cid ] );
			\Model\Routines::instance()->cacheCategories($parent);
			return TRUE;
		}
		else return FALSE;
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

		$data[0]['date_format_short'] = \Config::getPublic('date_format_short');
		$data[0]['datetime'] = $this->timeToUser($data[0]['datetime'], $data[0]['date_format_short']." H:i");

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

//	public function deleteNews( int $id )
	public function deleteNews( $id )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'news');
		if ( $delete->count( ["nid = ?", $id ] ) == 0 ) return FALSE;
		$delete->erase( ["nid = ?", $id ] );
		return TRUE;
	}

//	public function listStoryFeatured ( int $page, array $sort, string &$status )
	public function listStoryFeatured ( $page, array $sort, &$status )
	{
		/*
		int status = 
			1: active
			2: past
			3: future
		*/

		/*
		active:
		SELECT * FROM `tbl_stories_featured` WHERE status=1 OR ( start < NOW() AND end > NOW() )
		*/
		/*
		past:
		SELECT * FROM `tbl_stories_featured` WHERE status=2 OR end < NOW()
		*/
		/*
		future:
		SELECT * FROM `tbl_stories_featured` WHERE start > NOW()
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
	
//	public function loadFeatured ( int $sid )
	public function loadFeatured ( $sid )
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

//	public function saveFeatured(int $sid, array $data)
	public function saveFeatured($sid, array $data)
	{
		$i = NULL;
		$feature=new \DB\SQL\Mapper($this->db, $this->prefix.'stories_featured');
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
	
	//public function loadStoryInfo(int $sid)
	public function loadStoryInfo($sid)
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
			$data[0]['states']  = $this->storyStates();
			$data[0]['ratings'] = $this->exec("SELECT rid, rating, ratingwarning FROM `tbl_ratings`");
			return $data[0];
		}
		return FALSE;
	}
	
	public function storyEditPrePop(array $storyData)
	{
		$categories = json_decode($storyData['cache_categories']);
		foreach ( $categories as $tmp ) $pre['cat'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
		$pre['cat'] = json_encode($pre['cat']);

		$tags = json_decode($storyData['cache_tags']);
		foreach ( $tags as $tmp ) $pre['tag'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
		$pre['tag'] = json_encode($pre['tag']);

		$characters = json_decode($storyData['cache_characters']);
		foreach ( $characters as $tmp ) $pre['char'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
		$pre['char'] = json_encode($pre['char']);
		
		$authors = $this->exec ( "SELECT U.uid as id, U.nickname as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.ca = 0 );", [ ":sid" => $storyData['sid'] ]);
		$pre['auth'] = json_encode($authors);

		$coauthors = $this->exec ( "SELECT U.uid as id, U.nickname as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.ca = 1 );", [ ":sid" => $storyData['sid'] ]);
		$pre['coauth'] = json_encode($coauthors);

		return $pre;
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
		if (sizeof($data)>0) return $data;
		return FALSE;

	}
	
	public function loadStoryMapper($sid)
	{
		$story=new \DB\SQL\Mapper($this->db, $this->prefix.'stories');
		$story->load(array('sid=?',$sid));
		return $story;
	}
	
	public function getChapter( $story, $chapter, $counting = FALSE )
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
		$data['chaptertext'] = parent::getChapter( $story, $data['inorder'], $counting );
		
		return $data;
	}
	
	public function saveChapterChanges( $chapterID, array $post )
	{
		$chapter=new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
		$chapter->load(array('chapid=?',$chapterID));
		
		$chapter->title = $post['chapter_title'];
		$chapter->notes = $post['chapter_notes'];
		$chapter->save();
		
		// plain and visual return different newline representations, this will bring things to standard.
		$post['chapter_text'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $post['chapter_text']);
		
		$this->saveChapter($chapterID, $post['chapter_text']);
	}
	
	public function saveChapter( $chapterID, $chapterText )
	{
		return parent::saveChapter( $chapterID, $chapterText );
	}
	
	public function addChapter ( $storyID, $post )
	{
		$location = \Config::getPublic('chapter_data_location');
		
		// Get current chapter count and raise
		if ( FALSE == $chapterCount = @$this->exec("SELECT COUNT(chapid) as chapters FROM `tbl_chapters` WHERE `sid` = :sid ", [ ":sid" => $storyID ])[0]['chapters'] )
			return FALSE;
		$chapterCount++;
		
		$kv = [
			'title'			=> $post['chapter_title'],
			'inorder'		=> $chapterCount,
			'notes'			=> $post['chapter_notes'],
			//'workingtext'
			//'workingdate'
			//'endnotes'
			'validated'		=> "1",
			'wordcount'		=> $this->str_word_count_utf8($post['chapter_text']),
			'rating'		=> "0", // allow rating later
			'sid'			=> $storyID,
		];
		if ( $location != "local" )
			$kv['chaptertext'] = $post['chapter_text'];

		$chapterID = $this->insertArray($this->prefix.'chapters', $kv );
		
		if ( $location == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterAdd= @$db->exec('INSERT INTO "chapters" ("chapid","sid","inorder","chaptertext") VALUES ( :chapid, :sid, :inorder, :chaptertext )', 
								[
									':chapid' 		=> $chapterID,
									':sid' 			=> $storyID,
									':inorder' 		=> $chapterCount,
									':chaptertext'	=> $post['chapter_text'],
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

	public function saveStoryChanges(\DB\SQL\Mapper $current, array $post)
	{
		// Step one: save the plain data
		$current->title			= $post['story_title'];
		$current->summary		= str_replace("\n","<br />",$post['story_summary']);
		$current->storynotes	= str_replace("\n","<br />",$post['story_notes']);
		$current->ratingid		= $post['ratingid'];
		$current->completed		= $post['completed'];
		$current->validated 	= $post['validated'];
		$current->save();
		
		// Step two: check for changes in relation tables

		// Check tags:
		$post['tags'] = explode(",",$post['tags']);
		$tags = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_tags');

		foreach ( $tags->find(array('`sid` = ? AND `character` = ?',$current->sid,0)) as $X )
		{
			$temp=array_search($X['tid'], $post['tags']);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$tags->erase(['lid=?',$X['lid']]);
			}
			else unset($post['tags'][$temp]);
		}
		
		// Insert any tag IDs not already present
		if ( sizeof($post['tags'])>0 )
		{
			foreach ( $post['tags'] as $temp)
			{
				// Add relation to table
				$tags->reset();
				$tags->sid = $current->sid;
				$tags->tid = $temp;
				$tags->character = 0;
				$tags->save();
			}
		}
		unset($tags);
		
		// Check Characters:
		$post['characters'] = explode(",",$post['characters']);
		$characters = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_tags');

		foreach ( $characters->find(array('`sid` = ? AND `character` = ?',$current->sid,1)) as $X )
		{
			$temp=array_search($X['tid'], $post['characters']);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$characters->erase(['lid=?',$X['lid']]);
			}
			else unset($post['characters'][$temp]);
		}
		
		// Insert any character IDs not already present
		if ( sizeof($post['characters'])>0 )
		{
			foreach ( $post['characters'] as $temp)
			{
				// Add relation to table
				$characters->reset();
				$characters->sid = $current->sid;
				$characters->tid = $temp;
				$characters->character = 1;
				$characters->save();
			}
		}
		unset($characters);
		
		// Check Categories:
		$post['category'] = explode(",",$post['category']);
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_categories');

		foreach ( $categories->find(array('`sid` = ?',$current->sid)) as $X )
		{
			$temp=array_search($X['cid'], $post['category']);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$categories->erase(['lid=?',$X['lid']]);
			}
			else unset($post['category'][$temp]);
		}
		
		// Insert any character IDs not already present
		if ( sizeof($post['category'])>0 )
		{
			foreach ( $post['category'] as $temp)
			{
				// Add relation to table
				$categories->reset();
				$categories->sid = $current->sid;
				$categories->cid = $temp;
				$categories->save();
			}
		}
		unset($categories);
		
		// Author and co-Author preparation:
		$post['author'] = explode(",",$post['author']);		
		$post['coauthor'] = explode(",",$post['coauthor']);
		// remove co-authors, that are already in the author field
		$post['coauthor'] = array_diff($post['coauthor'], $post['author']);

		// Check Authors:
		$author = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_authors');

		foreach ( $author->find(array('`sid` = ? AND `ca` = ?',$current->sid,0)) as $X )
		{
			$temp=array_search($X['aid'], $post['author']);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$author->erase(['lid=?',$X['lid']]);
			}
			else unset($post['author'][$temp]);
		}

		// Insert any character IDs not already present
		if ( sizeof($post['author'])>0 )
		{
			foreach ( $post['author'] as $temp)
			{
				// Add relation to table
				$author->reset();
				$author->sid = $current->sid;
				$author->aid = $temp;
				$author->ca = 0;
				$author->save();
			}
		}
		unset($author);
		
		// Check co-Authors:
		$coauthor = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_authors');

		foreach ( $coauthor->find(array('`sid` = ? AND `ca` = ?',$current->sid,1)) as $X )
		{
			$temp=array_search($X['aid'], $post['coauthor']);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$coauthor->erase(['lid=?',$X['lid']]);
			}
			else unset($post['coauthor'][$temp]);
		}

		// Insert any character IDs not already present
		if ( sizeof($post['coauthor'])>0 )
		{
			foreach ( $post['coauthor'] as $temp)
			{
				// Add relation to table
				$coauthor->reset();
				$coauthor->sid = $current->sid;
				$coauthor->aid = $temp;
				$coauthor->ca = 1;
				$coauthor->save();
			}
		}
		unset($coauthor);
		
		$this->rebuildStoryCache($current->sid);
		
		return TRUE;
	}
	
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
		/*
		$sql = "SELECT S.id, S.message, IF(S.guest_name IS NULL,U.nickname,S.guest_name) AS author, S.uid, S.date, UNIX_TIMESTAMP(S.date) as timestamp
				FROM `tbl_shoutbox`S
				LEFT JOIN `tbl_users`U ON (S.uid=U.uid)
				WHERE `id` = :id";
		*/
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
		$sql = "SELECT `name`, `value`, `comment`, `form_type`
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
		$sql = "SELECT `name`, `value`, `comment`, `form_type`
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

}
