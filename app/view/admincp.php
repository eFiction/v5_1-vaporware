<?php
namespace View;

class AdminCP extends Base
{

	public static function showMenu($menu="")
	{
		\Base::instance()->set('panel_menu', $menu);
		return \Template::instance()->render('menu.html');
	}
	
	public function settingsFields($data,$target,$feedback)
	{
		$this->f3->set('form_target', $target);
		$this->f3->set('form_elements', $data);
		$this->f3->set('form_feedback', $feedback);
		// resolve() eval's the language injections
		$html = \Template::instance()->resolve($this->render('form_blocks.html'));
		return $html;
	}

	public function settingsDateTime()
	{
		return $this->render('settings/datetime_example.html');
	}

	public function categoryList($data, $feedback)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('categories', $data);
		$this->f3->set('feedback', $feedback);
		return $this->render('archive/category_list.html');
	}
	
	public function categoryAdd( \Base $f3, array $data )
	{
		$data = array_merge (
		[
			"job"			=> "add",
			"category"		=> $f3->get('POST.form_data.category'),
			"description"	=> $f3->get('POST.form_data.description'),
			"locked"		=> TRUE,
		], $data );
		$this->f3->set('data', $data);
		return $this->render('archive/category_form.html');
	}
	
	public function categoryEdit(array $data)
	{
		if(empty($data['job'])) $data['job'] = "id";
		$this->f3->set('data', $data);
		return $this->render('archive/category_form.html');
	}

	public function characterList(array $data, array $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('sort', $sort);
		$this->f3->set('characterlist', $data);
		return $this->render('archive/character_list.html');
	}

//	public function characterEdit(array $data, str $returnpath)
	public function characterEdit(array $data, $returnpath)
	{
		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath);

		return $this->render('archive/character_edit.html');
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

		return $this->render('archive/contest_list.html');
	}
	
	public function contestEdit(array $data, $returnpath)
	{
		if(!isset($data['raw']))
		{
			$this->javascript( 'head', TRUE, "//cdn.tinymce.com/4/tinymce.min.js" );
			$this->javascript( 'head', TRUE, "editor.js" );
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

	public function custompageList(array $data, array $sort)
	{
		$this->f3->set('pages', $data);
		$this->f3->set('sort', $sort);
		return $this->render('home/custompage_list.html');
	}

	public function custompageEdit(array $data)
	{
		if(!isset($data['raw']))
		{
			$this->javascript( 'head', TRUE, "//cdn.tinymce.com/4/tinymce.min.js" );
			$this->javascript( 'head', TRUE, "editor.js" );
		}
		$this->f3->set('data', $data);
		return $this->render('home/custompage_edit.html');
	}

//	public function featuredList(array $data, array $sort, string $select)
	public function featuredList(array $data, array $sort, $select)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('featured', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('select', $select);
		return $this->render('archive/featured_list.html');
	}

	public function featuredEdit(array $data)
	{
		$this->f3->set('data', $data);
		return $this->render('archive/featured_edit.html');
	}

	public function homeWelcome($v, $c)
	{
		$this->f3->set('script_versions', $v);
		$this->f3->set('versions_compare', $c);
		return $this->render('home/welcome.html');
	}

	public function language(array $data, array $config)
	{
		$this->f3->set('data',	$data);
		$this->f3->set('config',$config);
		
		return $this->render('settings/language.html');
	}

	public function layout(array $data, array $config)
	{
		$this->f3->set('data',	$data);
		$this->f3->set('config',$config);
		
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

	public function logList(array $data, array $menu, array $sort, $sub=FALSE)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('logEntries', $data);
		$this->f3->set('logMenu', $menu);
		$this->f3->set('sort', $sort);
		$this->f3->set('sub', $sub);
		return $this->render('home/log_list.html');
	}
	
	public function logView()
	{
		
	}

	public function newsEdit(array $data, $returnpath)
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

		return $this->render('home/news_edit.html');
	}
	
	public function newsList(array $data, array $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('newsEntries', $data);
		$this->f3->set('sort', $sort);
		return $this->render('home/news_list.html');
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
	
	public function shoutEdit(array $data, array $sort, $page)
	{
		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('page', $page);
		
		return $this->render('home/shout_edit.html');
	}
	
	public function shoutList(array $data, array $sort, array $changes)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('shoutEntries', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('changes', $changes);
		return $this->render('home/shout_list.html');
	}
	
	public function storySearch()
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

	public function storyListPending(array $data, array $sort)
	{
		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);
		return $this->render('stories/pending.html');
	}
	
	public function tagList($data, $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

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
		return $this->render('archive/tag_list.html');
	}

	public function tagGroupList(array $data, array $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('sort', $sort);
		$this->f3->set('grouplist', $data);
		return $this->render('archive/tagGroup_list.html');
	}
	
	public function tagEdit(array $data)
	{
		$this->f3->set('data', $data);
		return $this->render('archive/tag_edit.html');
	}

	public function tagGroupEdit(array $data)
	{
		$this->f3->set('data', $data);
		return $this->render('archive/tagGroup_edit.html');
	}
	
	public function userFieldsEdit()
	{

	}
	
	public function userFieldsList(array $fieldData)
	{
		$this->f3->set('data', $fieldData);
		return $this->render('members/list_fields.html');
	}
	
	public function userTeamList($teamData)
	{
		$this->f3->set('data', $teamData);
		return $this->render('members/list_team.html');
	}
	
	public function userEditList(array $data, array $sort, $search)
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

}
