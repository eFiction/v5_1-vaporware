<?php
namespace Model;

class AdminCP extends Base {

	protected $menu = [];
	
	public function __construct()
	{
		parent::__construct();
		$this->menu = $this->panelMenu(FALSE,TRUE);
	}
	
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
				//echo "{$section} - {$key}: {$value}<br />";
				//if ( !$this->exec)
			}
		}
		if ( $affected ) $mapper->save();
		return [ $affected, FALSE ]; // prepare for error check
	}

	public function showMenu($selected=FALSE)
	{
		if ( $selected )
		{
			$this->menu[$selected]["sub"] = $this->panelMenu($selected,TRUE);
		}
		return $this->menu;
	}

	public function showMenuUpper($selected=FALSE)
	{
		if(!$selected) return NULL;
		$sql = "SELECT M.*
					FROM `tbl_menu_adminpanel`M
				WHERE `child_of` = :selected
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
	
	public function loadTag(int $tid)
	{
		$sql = "SELECT T.tid as id, T.tgid, T.label, T.description, T.count, G.description as groupname FROM `tbl_tags`T LEFT JOIN `tbl_tag_groups`G ON ( T.tgid=G.tgid) WHERE T.tid = :tid";
		$data = $this->exec($sql, [":tid" => $tid ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}

	public function loadTagGroup(int $tgid)
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

	public function saveTag(int $tid, $data)
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
	
	public function saveTagGroup(int $tgid, $data)
	{
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->load(array('tgid=?',$tgid));
		$taggroup->copyfrom( [ "label" => $data['label'], "description" => $data['description'] ]);

		$i = $taggroup->changed("label");
		$i += $taggroup->changed("description");

		$taggroup->save();
		return $i;
	}
	
	public function deleteTag(int $tid)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->load(array('tid=?',$tid));
		$tag->erase();
	}

	public function deleteTagGroup(int $tgid)
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
//		$sql = "SELECT cid, parent_cid, category, locked, leveldown, inorder FROM `tbl_categories`C ORDER BY C.parent_cid DESC, C.inorder ASC";
		$sql = "SELECT 
					C.cid, C.parent_cid, C.category, C.locked, C.leveldown, C.inorder, 
					COUNT(C1.cid) as counter 
				FROM `tbl_categories`C 
				INNER JOIN `tbl_categories`C1 ON C.parent_cid=C1.parent_cid 
				GROUP BY C.cid 
				ORDER BY C.parent_cid DESC, C.inorder ASC";
		$data = $this->exec($sql);
		
		if ( sizeof($data) == 0 ) return NULL;
		
		foreach ( $data as $item )
		{
			$temp[$item['parent_cid']][] = $item;
			if ( isset($temp[$item['cid']]) ) $temp[$item['parent_cid']] = array_merge ( $temp[$item['parent_cid']], $temp[$item['cid']]);
		}
		
		return $temp[0];
	}

	public function loadCategory(int $cid)
	{
		if ( $cid == 0 ) return NULL;
		$sql = "SELECT cid as id, parent_cid, category, description, image, locked, leveldown, inorder, stats FROM `tbl_categories`C WHERE C.cid = :cid";
		$data = $this->exec($sql, [":cid" => $cid ]);
		if (sizeof($data)==1) return $data[0];
		return FALSE;
	}

	public function loadCategoryPossibleParents(int $cid)
	{
		$sql = "SELECT C.cid, C.parent_cid, C.leveldown, C.category
					FROM `efi5_categories`C 
					INNER JOIN `efi5_categories`C2 ON ( ( C.parent_cid = C2.parent_cid OR C.cid = C2.parent_cid )AND C2.cid = :cid ) 
				WHERE C.cid != :cid2
				ORDER BY C.leveldown, C.inorder ASC ";
		
		$data = $this->exec($sql, [":cid" => $cid, ":cid2" => $cid ]);

		return $data;
	}

	public function saveCategory(int $cid, $data)
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
	
	public function addCategory( int $parent_cid, array $data=[] )
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
	
	public function moveCategory(int $catID=0, $direction=NULL, $parent=NULL)
	{
		if ( $parent === NULL )
		{
			$sql = "SELECT C.parent_cid 
						FROM `tbl_categories`C 
						WHERE C.`cid`= :catID";
			/*
			$sql = "SELECT C.cid, C.category, C.inorder 
						FROM `tbl_categories`C 
						INNER JOIN `tbl_categories`C2 ON ( C.parent_cid = C2.parent_cid AND C2.`cid`= :catID ) ORDER BY C.inorder ".(($direction=="up") ? "DESC" : "ASC");
			*/
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
					// echo "<br>cid: {$categories->cid} {$categories->category} new:".max(1,($i-1));
				else
					$categories->inorder = min($elements,($i+1));
					//echo "<br>cid: {$categories->cid} {$categories->category} new:".min($elements,($i+1));
				// remember the vacant spot
				$vacant = $i;
			}
			elseif ( $direction == "down" AND $i == $vacant+1 )
			{
				$categories->inorder = ($i-1);
				// echo "<br>cid: {$categories->cid} {$categories->category} new:".($i-1);
			}
			elseif ( $direction == "up" AND $i == $vacant-1 )
			{
				$categories->inorder = ($i+1);
				//echo "<br>cid: {$categories->cid} {$categories->category} new:".($i+1);
			}
			else 
				$categories->inorder = $i;
				//echo "<br>cid: {$categories->cid} {$categories->category} new:".$i;

			$categories->save();
			// moves forward even when the internal pointer is on last record
			$categories->next();
		}
//		print_r($data);
		return $parent;
	}

}