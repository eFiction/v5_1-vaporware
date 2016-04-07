<?php
namespace View;

class AdminCP extends Base
{

	public static function showMenu($menu="")
	{
		\Base::instance()->set('panel_menu', $menu);
		return \Template::instance()->render('menu.html');
	}
	
	public static function settingsFields($data,$target,$feedback)
	{
		\Base::instance()->set('form_target', $target);
		\Base::instance()->set('form_elements', $data);
		\Base::instance()->set('form_feedback', $feedback);
		$html = \Template::instance()->render('form_blocks.html');
		return $html;
	}

	public static function homeWelcome($v, $c)
	{
		\Base::instance()->set('script_versions', $v);
		\Base::instance()->set('versions_compare', $c);
		return \Template::instance()->render('home_welcome.html');
	}
	
	public static function listTags($data, $sort)
	{
		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('taglist', $data);
		return \Template::instance()->render('archive/list_tags.html');
	}

	public static function listTagGroups($data, $sort)
	{
		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('grouplist', $data);
		return \Template::instance()->render('archive/list_tag_groups.html');
	}
	
	public static function editTag($data)
	{
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('archive/edit_tag.html');
	}
}