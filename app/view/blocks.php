<?php
namespace View;

class Blocks extends Base
{
	
	public static function pageMenu($main, $sub, $vertical = FALSE)
	{
		\Base::instance()->set('menuMain', $main);
		\Base::instance()->set('menuSub', $sub);
		
		if ( $vertical )
			return parent::render('blocks/menu.vert.html');
		
		else
			return parent::render('blocks/menu.html');
	}

	public static function shoutboxInit()
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "shoutbox.js" );
		return parent::render('blocks/shoutbox.html');
	}

	public static function shoutboxLines($data)
	{
		\Base::instance()->set('shoutboxLines', $data);
		return parent::render('blocks/shoutbox.inner.html');
	}

	public static function shoutboxForm()
	{
		if ( $_SESSION['userID']==0 )
			\Base::instance()->set('shoutboxGuest', TRUE);
		else
			\Base::instance()->set('shoutboxMember', TRUE);
		
		return parent::render('blocks/shoutbox.inner.html');
	}
	
	public static function calendarInit()
	{
		\Registry::get('VIEW')->javascript( 'body', TRUE, "calendar.js.php?base=".\Base::instance()->get('BASE') );
		
		$cell = array ( 
				"ID"		=> "sb_cell_calendar",
				"TITLE"		=> "__Calendar",
				"CONTENT"	=> "Loading ...",
		);
		\Base::instance()->set('cell', $cell);
		return parent::directrender('blocks/cell.html');
	}

	public function calendar($data)
	{
		\Base::instance()->set('data', $data);

		echo utf8_encode($this->directrender('blocks/calendar.html'));
		exit;
	}
	
	public static function categories($data)
	{
		
		$cell = array ( 
				"ID"		=> "sb_cell_categories",
				"TITLE"		=> \Base::instance()->get("LN__Categories"),
				"CONTENT"	=> $data,
		);
		\Base::instance()->set('cell', $cell);
		return parent::render('blocks/categories.html');
	}
}
