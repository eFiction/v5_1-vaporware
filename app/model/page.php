<?php
namespace Model;

class Page extends Base
{
    //protected $db = 'DB';
	public function load($page)
	{
		return $this->exec( "SELECT content FROM `tbl_textblocks`T WHERE T.label= :label ;" , array ( ":label" => $page ) );
	}
	
	public function shoutboxLines($offset)
	{
		$shoutSQL = "SELECT B.id, B.uid, IF(B.uid=0,B.guest_name,U.nickname) as name, B.message, UNIX_TIMESTAMP(B.date) as date
									FROM `tbl_shoutbox`B 
									LEFT JOIN `tbl_users`U ON ( U.uid = B.uid )
								ORDER BY date DESC
								LIMIT :offset,5" ;

		return $this->exec($shoutSQL,[ ":offset" => $offset]);
	}
	
}