<?php
namespace Model;

class AdminCP extends Base
{
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
				FROM `efi5_tag_groups`G 
				LEFT JOIN `efi5_tags`T ON ( G.tgid = T.tgid )
		

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

	public function saveTag(int $tid, $data)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->load(array('tid=?',$tid));
		$tag->copyfrom( [ "tgid" => $data['taggroup'], "label" => $data['label'], "description" => $data['description'] ]);
		$i = $tag->changed("tgid");
		$i += $tag->changed("label");
		$i += $tag->changed("description");
		/*
		$tag->tgid = $data['taggroup'];
		$tag->label = $data['label'];
		$tag->description = $data['description'];
		*/
		$tag->save();
		return $i;
	}
	
	public function tagGroups()
	{
		$sql = "SELECT TG.tgid as id, TG.description FROM `tbl_tag_groups`TG ORDER BY TG.description ASC";
		return $this->exec($sql);
	}

	
}