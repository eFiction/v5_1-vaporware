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
		return \Template::instance()->render('home/welcome.html');
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

	public static function editTagGroup($data)
	{
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('archive/edit_tag_group.html');
	}

	public static function listCategories($data, $feedback)
	{
		\Base::instance()->set('categories', $data);
		\Base::instance()->set('feedback', $feedback);
		return \Template::instance()->render('archive/list_categories.html');
	}
	
	public static function addCategory( \Base $f3, array $data )
	{
		$data = array_merge (
		[
			"job"			=> "add",
			"category"		=> $f3->get('POST.form_data.category'),
			"description"	=> $f3->get('POST.form_data.description'),
			"locked"		=> TRUE,
		], $data );
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('archive/form_category.html');
	}
	
	public static function editCategory(array $data)
	{
		if(empty($data['job'])) $data['job'] = "id";
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('archive/form_category.html');
	}

	public static function listCustompages(array $data, array $sort)
	{
		\Base::instance()->set('pages', $data);
		\Base::instance()->set('sort', $sort);
		return \Template::instance()->render('home/list_custompages.html');
	}

	public static function editCustompage(array $data)
	{
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('home/edit_custompage.html');
	}


}