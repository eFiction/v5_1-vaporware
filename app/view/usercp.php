<?php
namespace View;

class UserCP extends Base
{

	public static function showMenu($menu)
	{
		return \Template::instance()->render
														('usercp/menu.html','text/html', 
															[
																"menu"	=> $menu,
																"BASE"		=> \Base::instance()->get('BASE')
															]
														);
	}

}