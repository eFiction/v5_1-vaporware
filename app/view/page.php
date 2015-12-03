<?php
namespace View;

class Page extends Base
{

	public static function load($page)
	{
		if($page = \Model\Page::instance()->load($page))
			return $page['content'];
	}

	public static function shoutboxLines($data)
	{
		return \Template::instance()->render('sidebar/shoutbox.inner.html','text/html', [ "lines" => $data, "BASE" => \Base::instance()->get('BASE') ]);
	}

	public static function shoutboxForm()
	{
		if ( $_SESSION['userID']==0 )
			return \Template::instance()->render('sidebar/shoutbox.inner.html','text/html', [ "formGuest" => TRUE, "BASE" => \Base::instance()->get('BASE') ]);
		else
			return \Template::instance()->render('sidebar/shoutbox.inner.html','text/html', [ "formMember" => TRUE, "BASE" => \Base::instance()->get('BASE') ]);
	}

}