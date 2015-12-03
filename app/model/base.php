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
	
	protected function exec($cmds,$args=NULL,$ttl=0,$log=TRUE)
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
	
	//protected function update()
}