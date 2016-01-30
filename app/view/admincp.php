<?php
namespace View;

class AdminCP extends Base
{

	public static function showMenu($menu="")
	{
		\Base::instance()->set('panel_menu', $menu);
		return \Template::instance()->render('menu.html');
	}
	
	public static function settingsFields($data,$target)
	{
			\Base::instance()->set('form_target', $target);
			\Base::instance()->set('form_elements', $data);
			$html = \Template::instance()->render('form_blocks.html');
			//print_r($form_fields);
		return $html;
	}

	
	
}