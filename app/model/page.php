<?php
namespace Model;

class Page extends Base
{
    //protected $db = 'DB';
	public function load($page)
	{
		return $this->exec( "SELECT content FROM `tbl_textblocks`T WHERE T.label= :label ;" , array ( ":label" => $page ) );
	}
	
	
}