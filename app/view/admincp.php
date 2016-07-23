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
		$html = \Template::instance()->resolve(\Template::instance()->render('form_blocks.html'));
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
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('taglist', $data);
		return \Template::instance()->render('archive/list_tags.html');
	}

	public static function listTagGroups($data, $sort)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

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
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

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
		if(!isset($data['raw']))
		{
			\Registry::get('VIEW')->javascript( 'head', TRUE, "//cdn.tinymce.com/4/tinymce.min.js" );
			\Registry::get('VIEW')->javascript( 'head', TRUE, "editor.js" );
		}
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('home/edit_custompage.html');
	}

	public static function listNews(array $data, array $sort)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		\Base::instance()->set('newsEntries', $data);
		\Base::instance()->set('sort', $sort);
		return \Template::instance()->render('home/list_news.html');
	}

	public static function editNews(array $data)
	{
		if(!isset($data['raw']))
		{
			\Registry::get('VIEW')->javascript( 'head', TRUE, "//cdn.tinymce.com/4/tinymce.min.js" );
			\Registry::get('VIEW')->javascript( 'head', TRUE, "editor.js" );
		}
		\Registry::get('VIEW')->javascript( 'head', TRUE, "jquery.datetimepicker.js" );

		\Base::instance()->set('data', $data);
		return \Template::instance()->render('home/edit_news.html');
	}
	
	public static function listFeatured(array $data, array $sort, string $select)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		\Base::instance()->set('featured', $data);
		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('select', $select);
		return \Template::instance()->render('archive/list_featured.html');
	}

	public static function editFeatured(array $data)
	{
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('archive/edit_featured.html');
	}

	public static function searchStoryForm()
	{
		return \Template::instance()->render('stories/search.html');
	}
	
	public static function storyMetaEdit(array $storyData, array $chapterList, array $prePop)
	{
		$storyData['storynotes'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['storynotes']);
		$storyData['summary'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['summary']);

		\Base::instance()->set('prePop', $prePop);
		\Base::instance()->set('data', $storyData);
		\Base::instance()->set('chapterList', $chapterList);
		
		return \Template::instance()->render('stories/edit_meta.html');
	}

	public static function storyChapterEdit(array $chapterData, array $chapterList, $plain = FALSE)
	{
		if ($plain)
		{
			$chapterData['notes']		= preg_replace("/<br\\s*\\/>\\s*/i", "\n", $chapterData['notes']);
			$chapterData['chaptertext']	= html_entity_decode(preg_replace("/<br\\s*\\/>\\s*/i", "\n", $chapterData['chaptertext']));
			$chapterData['editmode']	= "plain";
		}
		else
		{
			\Registry::get('VIEW')->javascript( 'head', TRUE, "ckeditor/ckeditor.js" );
			$chapterData['editmode']	= "visual";
		}

		\Base::instance()->set('data', $chapterData);
		\Base::instance()->set('chapterList', $chapterList);

		return \Template::instance()->render('stories/edit_chapter.html');
	}
	
	public static function listUserFields(array $fieldData)
	{
		\Base::instance()->set('data', $fieldData);
		return \Template::instance()->render('members/list_fields.html');
	}

	public static function listShoutbox(array $data, array $sort, array $changes)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		\Base::instance()->set('shoutEntries', $data);
		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('changes', $changes);
		return \Template::instance()->render('home/list_shoutbox.html');
	}
	
	public static function editShout(array $data, array $sort, $page)
	{
		\Base::instance()->set('data', $data);
		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('page', $page);
		
		return \Template::instance()->render('home/edit_shout.html');
	}
	
	public static function language(array $data, array $config, array $feedback)
	{
		\Base::instance()->set('data',			$data);
		\Base::instance()->set('config',		$config);
		\Base::instance()->set('form_feedback', $feedback);
		
		return \Template::instance()->render('settings/languages.html');
	}

}
