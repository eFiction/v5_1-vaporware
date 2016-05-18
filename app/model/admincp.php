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
				

				
				//	$queries[] = "UPDATE `tbl_chapters` SET inorder = ".($order+1)." WHERE `chapid` = {$id} AND `sid` = {$data["story"]};";
//			echo "{$id} is now ".($order+1)."<br>";
/*			$sql = str_replace (
								array ( "@ORDER@", "@ID@" ),
								array ( ($order+1), $id ),
								"UPDATE `tbl_chapters` SET inorder = @ORDER@ WHERE `chapid` = @ID@;"
								);
			$eFI->db_query ( $sql );*/
		//}
		//$DB->multiQuery($queries);

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
	
	public function saveKeys($data)
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
						$mapper->{$key} = $value;
					}
					$affected++;	
				}
			}
		}
		if ( $affected ) $mapper->save();
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
		CREATE OR REPLACE VIEW efi5_list_tag_groups AS SELECT G.tgid,G.description,COUNT(T.tid) as `count`
				FROM `tbl_tag_groups`G 
				LEFT JOIN `tbl_tags`T ON ( G.tgid = T.tgid )
		

		$tags = new \DB\SQL\Mapper($this->db, 'efi5_list_tag_groups' );
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
			$item['stats'] = unserialize($item['stats']);
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
					FROM `efi5_categories`C 
					INNER JOIN `efi5_categories`C2 ON ( ( C.parent_cid = C2.parent_cid OR C.cid = C2.parent_cid )AND C2.cid = :cid ) 
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
		$stats = unserialize( $delete->stats );
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

		$data[0]['date_format_short'] = \Config::instance()->date_format_short;
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
		SELECT * FROM `efi5_stories_featured` WHERE status=1 OR ( start < NOW() AND end > NOW() )
		*/
		/*
		past:
		SELECT * FROM `efi5_stories_featured` WHERE status=2 OR end < NOW()
		*/
		/*
		future:
		SELECT * FROM `efi5_stories_featured` WHERE start > NOW()
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
					"SELECT SQL_CALC_FOUND_ROWS S.title, S.sid, S.summary, Cache.authorblock, Cache.rating
						FROM `tbl_stories`S
						INNER JOIN `tbl_stories_featured`F ON ( F.sid = S.sid AND %JOIN% )
						INNER JOIN `tbl_stories_blockcache`Cache ON ( S.sid = Cache.sid  )
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
		$sql = "SELECT SQL_CALC_FOUND_ROWS S.title, S.sid, S.summary, F.status, F.start, F.end, F.uid, U.nickname, Cache.authorblock, Cache.rating
					FROM `tbl_stories`S
						LEFT JOIN `tbl_stories_featured`F ON ( F.sid = S.sid )
						LEFT JOIN `tbl_users`U ON ( F.uid = U.uid )
						INNER JOIN `tbl_stories_blockcache`Cache ON ( S.sid = Cache.sid  )
				WHERE S.sid = :sid";
		$data = $this->exec($sql, [":sid" => $sid ]);
		if (sizeof($data)==1)
		{
			$data[0]['authorblock'] = unserialize($data[0]['authorblock']);
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
			"SELECT S.*, Cache.*, COUNT(DISTINCT Ch.chapid) as chapters
				FROM `tbl_stories`S
					INNER JOIN `tbl_stories_blockcache`Cache ON ( S.sid = Cache.sid )
					LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid)
				WHERE S.sid = :sid",
			[":sid" => $sid ]
		);
		if (sizeof($data)==1) return $data[0];
		return FALSE;
	}
	
	public function storyEditPrePop(array $storyData)
	{
		$categories = unserialize($storyData['categoryblock']);
		foreach ( $categories as $tmp ) $pre['cat'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
		$pre['cat'] = json_encode($pre['cat']);

		$tags = unserialize($storyData['tagblock']);
		foreach ( $tags as $tmp ) $pre['tag'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
		$pre['tag'] = json_encode($pre['tag']);

		$characters = unserialize($storyData['characterblock']);
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
	
}
