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
		// resolve() eval's the language injections
		$html = \Template::instance()->resolve(\Template::instance()->render('form_blocks.html'));
		return $html;
	}

	public static function homeWelcome($v, $c)
	{
		\Base::instance()->set('script_versions', $v);
		\Base::instance()->set('versions_compare', $c);
		return \Template::instance()->render('home/welcome.html');
	}
	
	public function listTags($data, $sort)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		if( isset($_SESSION['deleteResult']) )
		{
			$this->f3->set('deleteResult',$_SESSION['deleteResult']);
			unset($_SESSION['deleteResult']);
		}

		$this->f3->set('sort', $sort);
		$this->f3->set('taglist', $data);
		return $this->render('archive/list_tags.html');
	}

	public function listTagGroups(array $data, array $sort)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('sort', $sort);
		$this->f3->set('grouplist', $data);
		return $this->render('archive/list_tag_groups.html');
	}
	
	public static function editTag(array $data)
	{
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('archive/edit_tag.html');
	}

	public static function editTagGroup(array $data)
	{
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('archive/edit_tag_group.html');
	}
	
	public function listCharacters(array $data, array $sort)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('sort', $sort);
		$this->f3->set('characterlist', $data);
		return $this->render('archive/list_characters.html');
	}

//	public function editCharacter(array $data, str $returnpath)
	public function editCharacter(array $data, $returnpath)
	{
		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath);

		return $this->render('archive/edit_character.html');
	}
	
	public function contestsList(array $data, array $sort)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		while ( list($key, $value) = each($data) )
			$this->dataProcess($data[$key], $key);

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('sort', $sort);
		$this->f3->set('contestlist', $data);

		return \Template::instance()->render('archive/contest_list.html');
	}
	
	public function contestEdit(array $data, $returnpath)
	{
		if(!isset($data['raw']))
		{
			\Registry::get('VIEW')->javascript( 'head', TRUE, "//cdn.tinymce.com/4/tinymce.min.js" );
			\Registry::get('VIEW')->javascript( 'head', TRUE, "editor.js" );
		}
		\Registry::get('VIEW')->javascript( 'head', TRUE, "jquery.datetimepicker.js" );

		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath);

		return \Template::instance()->render('archive/contest_edit.html');
	}

	public function contestEntries(array $data, $returnpath)
	{
		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath);
		
		return \Template::instance()->render('archive/contest_entries.html');
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
	
//	public static function listFeatured(array $data, array $sort, string $select)
	public static function listFeatured(array $data, array $sort, $select)
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

	public function searchStoryForm()
	{
		return $this->render('stories/search.html');
	}
	
	public function storyMetaEdit(array $storyData, array $chapterList, array $prePop)
	{
		$storyData['storynotes'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['storynotes']);
		$storyData['summary'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['summary']);

		$this->f3->set('prePop', $prePop);
		$this->f3->set('data', $storyData);
		$this->f3->set('chapterList', $chapterList);
		
		return $this->render('stories/edit_meta.html');
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
	
	public function listPendingStories(array $data, array $sort)
	{
		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);
		return $this->render('stories/pending.html');
	}
	
	public function listUserFields(array $fieldData)
	{
		\Base::instance()->set('data', $fieldData);
		return $this->render('members/list_fields.html');
	}
	
	public function userListTeam($teamData)
	{
		\Base::instance()->set('data', $teamData);
		return $this->render('members/list_team.html');
	}
	
	public function userSearchList(array $data, array $sort, $search)
	{
		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('search', $search);
		return $this->render('members/searchlist.html');
	}

	public function userEdit(array $data, $returnpath)
	{
		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath);
		return $this->render('members/edit_member.html');
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
	
	public static function language(array $data, array $config)
	{
		\Base::instance()->set('data',			$data);
		\Base::instance()->set('config',		$config);
		
		return \Template::instance()->render('settings/language.html');
	}

	public static function layout(array $data, array $config)
	{
		// Array ( [0] => Array ( [folder] => default [name] => eFiction 5 default [author] => Rainer "the sheep" [email] => papaschaf@hotmail.com [url] => efiction.org [active] => ) ) 
		\Base::instance()->set('data',			$data);
		\Base::instance()->set('config',		$config);
		
		return \Template::instance()->render('settings/layout.html');
	}

	public static function listLog(array $data, array $menu, array $sort, $sub=FALSE)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		\Base::instance()->set('logEntries', $data);
		\Base::instance()->set('logMenu', $menu);
		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('sub', $sub);
		return \Template::instance()->render('home/list_log.html');
	}

}
