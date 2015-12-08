<?php
namespace View;
class Redirect extends Base
{
	public static function inform($redirect)
	{
		//
		return  \Template::instance()->render('main/redirect.html', 'text/html', [ "BASE" => \Base::instance()->get('BASE'), "redirect" => $redirect ]);
	}
}
