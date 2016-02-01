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

	
}