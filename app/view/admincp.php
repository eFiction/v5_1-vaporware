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

	public function homeWelcome($v, $c)
	{
		$this->f3->set('script_versions', $v);
		$this->f3->set('versions_compare', $c);
		return $this->render('home/welcome.html');
	}
	
	public function listTags($data, $sort)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		if( isset($_SESSION['deleteResult']) )
		{
			$this->f3->set('deleteResult',$_SESSION['deleteResult']);
			unset($_SESSION['deleteResult']);
		}

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
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
	
	public function ratingEdit(array $data)
	{
		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('data', $data);
		return $this->render('archive/rating_edit.html');
	}
	
	public function ratingDelete(array $data, array $ratings)
	{
		$this->f3->set('data', 		$data);
		$this->f3->set('ratings', 	$ratings);

		return $this->render('archive/rating_delete.html');
	}
	
	public function ratingList(array $data)
	{
		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('ratingList', $data);
		return $this->render('archive/rating_list.html');
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
			$this->javascriptjavascript( 'head', TRUE, "//cdn.tinymce.com/4/tinymce.min.js" );
			$this->javascriptjavascript( 'head', TRUE, "editor.js" );
		}
		$this->javascript( 'head', TRUE, "jquery.datetimepicker.js" );

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
		return \Template::instance()->render('archive/categories_list.html');
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
		return \Template::instance()->render('archive/categories_form.html');
	}
	
	public static function editCategory(array $data)
	{
		if(empty($data['job'])) $data['job'] = "id";
		\Base::instance()->set('data', $data);
		return \Template::instance()->render('archive/categories_form.html');
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

	public function listNews(array $data, array $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('newsEntries', $data);
		$this->f3->set('sort', $sort);
		return $this->render('home/list_news.html');
	}

	public function editNews(array $data, $returnpath)
	{
		if(!isset($data['raw']))
		{
			$this->javascript( 'head', TRUE, "//cdn.tinymce.com/4/tinymce.min.js" );
			$this->javascript( 'head', TRUE, "editor.js" );
		}
		$this->javascript( 'head', TRUE, "jquery.datetimepicker.js" );

		$this->f3->set('data', $data);
		$this->f3->set('format', $this->config['date_preset']." ".$this->config['time_preset']);
		$this->f3->set('returnpath', $returnpath);

		return $this->render('home/edit_news.html');
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
	
	public function storyAddForm( array $data = [] )
	{
		if ( sizeof($data)>0 )
		{
			$this->f3->set('storyTitle', $data['storyInfo']['title']);
			$this->f3->set('preAuthor', $data['preAuthor']);
			$this->f3->set('storyLink', $this->f3->format($this->f3->get('LN__StoryAddSimilar'), $this->f3->get('BASE')."/adminCP/stories/edit/story=".$data['storyInfo']['sid']));
		}
		else
		{
			$this->f3->set('storyTitle', '');
			$this->f3->set('preAuthor', '[]');
		}
		return $this->render('stories/add_story.html');
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

	public static function storyChapterEdit(array $chapterData, array $chapterList, $editor = "plain")
	{
		if ($editor == "plain")
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
	
	public function settingsDateTime()
	{
		return $this->render('settings/datetime_example.html');
	}
	
	public static function language(array $data, array $config)
	{
		\Base::instance()->set('data',			$data);
		\Base::instance()->set('config',		$config);
		
		return \Template::instance()->render('settings/language.html');
	}

	public function layout(array $data, array $config)
	{
		// Array ( [0] => Array ( [folder] => default [name] => eFiction 5 default [author] => Rainer "the sheep" [email] => papaschaf@hotmail.com [url] => efiction.org [active] => ) ) 
		$this->f3->set('data',		$data);
		$this->f3->set('config',	$config);
		
		return $this->render('settings/layout.html');
	}
	
	public function layoutIcons()
	{
		$icons = Iconset::instance()->_data;
		foreach ( $icons as $key => $value )
		{
			$data[] = "Key: {$key}, Icon: ".str_replace("@T@", "title='{$key}'", $value);
		}
		return "<br/>\n".implode("<br/>\n", $data);
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
