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
	}
	
	public function exec($cmds,$args=NULL,$ttl=0,$log=TRUE)
	{
		return $this->db->exec(str_replace("`tbl_", "`{$this->prefix}", $cmds), $args,$ttl,$log);
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
		return $this->exec($this->sqlTmp[$id]['sql'], $this->sqlTmp[$id]['param']);
	}
	
	protected function panelMenu($selected=FALSE, $admin=FALSE)
	{
		if ( $admin )
		{
			
		}
		else
		{
			if ( $selected )
			{
				
			}
			else
			{
				$sql = "SELECT M.label, M.link, M.icon, M.evaluate FROM `tbl_menu_userpanel`M WHERE M.child_of IS NULL;";
				$data = $this->exec($sql);
				foreach ( $data as $item )
				{
					$menu[$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"] ];
				}
			}
		}
		return $menu;
	}
	
	//protected function update()
}
