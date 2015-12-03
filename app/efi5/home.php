<?php

class Home extends \Prefab {
	
	function shoutbox()
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "shoutbox.js.php?base=".\Base::instance()->get('BASE') );
		return \Template::instance()->render('sidebar/shoutbox.html');
	}

	public function calendar()
	{
		//$view = \Registry::get('VIEW');
		\Registry::get('VIEW')->javascript( 'body', TRUE, "calendar.js.php?base=".\Base::instance()->get('BASE') );
		
		$tmpHive = array ( 
				"ID"		=> "sb_cell_calendar",
				"TITLE"		=> "__Calendar",
				"CONTENT"	=> "Loading ...",
		);
		return \Template::instance()->render('sidebar/cell.html',NULL, $tmpHive);
	}
	
}