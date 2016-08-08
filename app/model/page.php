<?php
namespace Model;

class Page extends Base
{
    //protected $db = 'DB';
	public function load($page)
	{
		$data = $this->exec( "SELECT content, title FROM `tbl_textblocks`T WHERE T.label= :label ;" , array ( ":label" => $page ) );
		if(sizeof($data)==1) return $data[0];
		return FALSE;
	}
	
	
}