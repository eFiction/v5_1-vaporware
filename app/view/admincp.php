<?php
namespace View;

class AdminCP extends Base
{
	public function __construct()
	{
		parent::__construct();

		if( isset($_SESSION['lastAction']) )
		{
			foreach( $_SESSION['lastAction'] as $key => $value )
				$this->f3->set($key,$value);
			unset($_SESSION['lastAction']);
		}
	}

	public function menuShow($menu="")
	{
		$this->f3->set('panel_menu', $menu);
		return $this->render('main/menu.html');
	}
	
	public function access()
	{
		return $this->render('main/access.html');
	}
	
	public function settingsFields($data,$target,$feedback)
	{
		$this->f3->set('form_target', $target);
		$this->f3->set('form_elements', $data);
		$this->f3->set('form_feedback', $feedback);
		// resolve() eval's the language injections
		$html = \Template::instance()->resolve($this->render('main/formblocks.html'));
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
		return $this->render('archive/category.list.html');
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
		return $this->render('archive/category.edit.html');
	}
	
	public function categoryEdit(array $data)
	{
		if(empty($data['job'])) $data['job'] = "id";
		$this->f3->set('data', $data);
		return $this->render('archive/category.edit.html');
	}

	public function characterList(array $data, array $categories, int $category, array $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('sort',			$sort);
		$this->f3->set('characterlist', $data);
		$this->f3->set('categories',	$categories);
		$this->f3->set('category',		$category);
		return $this->render('archive/character.list.html');
	}

	public function characterEdit(array $data, array $categories, string $returnpath)
	{
		$this->f3->set('data', 		 $data);
		$this->f3->set('categories', $categories);
		$this->f3->set('returnpath', $returnpath);
		return $this->render('archive/character.edit.html');
	}
	
	public function contestsList(array $data, array $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		//while ( list($key, $value) = each($data) )
		foreach ( $data as $key => $value )
			$this->dataProcess($data[$key], $key);

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('sort', $sort);
		$this->f3->set('contestlist', $data);

		return $this->render('archive/contest.list.html');
	}
	
	public function contestEdit(array $data, $returnpath)
	{
		if($data['editor']=="visual" AND $this->config['advanced_editor']==TRUE )
		{
			$this->javascript( 'head', TRUE, "tinymce/tinymce.min.js" );
			$this->javascript( 'head', TRUE, "tinymce/tinymce.config.js" );
			$data['description'] = nl2br($data['description']);
		}
		$this->javascript( 'head', TRUE, "jquery.datetimepicker.js" );

		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath);

		return \Template::instance()->render('archive/contest.edit.html');
	}

	public function contestEntries(array $data, array $sort, string $returnpath): string
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('returnpath', $returnpath);
		
		return \Template::instance()->render('archive/contest.entries.html');
	}

	public function custompageList(array $data, array $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('pages', $data);
		$this->f3->set('sort', $sort);
		return $this->render('home/custompage.list.html');
	}

	public function custompageEdit(array $data, string $returnpath)
	{
		if($data['editor']=="visual" AND $this->config['advanced_editor']==TRUE )
		{
			$this->javascript( 'head', TRUE, "tinymce/tinymce.min.js" );
			$this->javascript( 'head', TRUE, "tinymce/tinymce.config.js" );
		}

		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath);
		return $this->render('home/custompage.edit.html');
	}

	public function featuredList(array $data, array $sort, string $select)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('featured', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('select', $select);
		return $this->render('stories/featured.list.html');
	}

	public function featuredEdit(array $data, string $returnpath): string
	{
		$this->javascript( 'head', TRUE, "jquery.datetimepicker.js" );

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('data', $data);
		$this->f3->set('format', $this->config['date_preset']." ".$this->config['time_preset']);
		$this->f3->set('returnpath', $returnpath);

		return $this->render('stories/featured.edit.html');
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

	public function newsEdit(array $data, string $returnpath)
	{
		if($data['editor']=="visual" AND $this->config['advanced_editor']==TRUE )
		{
			$this->javascript( 'head', TRUE, "tinymce/tinymce.min.js" );
			$this->javascript( 'head', TRUE, "tinymce/tinymce.config.js" );
		}
		$this->javascript( 'head', TRUE, "jquery.datetimepicker.js" );

		$this->f3->set('data', $data);
		$this->f3->set('format', $this->config['date_preset']." ".$this->config['time_preset']);
		$this->f3->set('returnpath', $returnpath);

		return $this->render('home/news.edit.html');
	}
	
	public function newsList(array $data, array $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('newsEntries', $data);
		$this->f3->set('sort', $sort);
		return $this->render('home/news.list.html');
	}

	public function ratingEdit(array $data)
	{
		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('data', $data);
		return $this->render('archive/rating.edit.html');
	}
	
	public function ratingDelete(array $data, array $ratings)
	{
		$this->f3->set('data', 		$data);
		$this->f3->set('ratings', 	$ratings);

		return $this->render('archive/rating.delete.html');
	}
	
	public function ratingList(array $data)
	{
		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('ratingList', $data);
		return $this->render('archive/rating.list.html');
	}
	
	public function collectionsList(array $data, array $sort, string $module) : string
	{
		//while ( list($key, $value) = each($data) )
		foreach ( $data as $key => $value )
			$this->dataProcess($data[$key], $key);

		$this->f3->set('data', 		$data);
		$this->f3->set('module', 	$module);
		$this->f3->set('sort', $sort);
		
		return $this->render('stories/collections.list.html');
	}
	
	public function collectionEdit(array $data, array $prePop, string $module, string $returnpath="" )
	{
		if($data['editor']=="visual" AND $this->config['advanced_editor']==TRUE )
		{
			$this->javascript( 'head', TRUE, "tinymce/tinymce.min.js" );
			$this->javascript( 'head', TRUE, "tinymce/tinymce.config.js" );
		}
		$this->dataProcess($data);
		$this->f3->set('module', 	$module);
		$this->f3->set('prePop', 	$prePop);
		$this->f3->set('data', 		$data);
		$this->f3->set('returnpath', $returnpath);

		return $this->render('stories/collection.edit.html');
	}
	
	public function collectionItems(array $data, string $module, string $returnpath="" ) : string
	{
		$this->f3->set('data', 			$data);
		$this->f3->set('module', 		$module);
		$this->f3->set('returnpath',	$returnpath);

		return $this->render('stories/collection.items.html');
	}

	public function pollList(array $data, array $sort) : string
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);

		return $this->render('home/poll.list.html');
	}
	
	public function pollEdit(array $data, string $returnpath)
	{
		$this->javascript( 'head', TRUE, "jquery.datetimepicker.js" );

		$this->f3->set('data', 		 $data);
		$this->f3->set('format', $this->config['date_preset']." ".$this->config['time_preset']);
		$this->f3->set('returnpath', $returnpath);

		return $this->render('home/poll.edit.html');
	}

	public function recommendationList( array $data, array $sort ) : string
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );
		$this->f3->set('data', 		 $data);
		$this->f3->set('sort', $sort);
		
		return $this->render('stories/recommendation.list.html');
	}

	public function recommendationEdit( array $data, array $prePop, string $returnpath="" ) : string
	{
		if($data['editor']=="visual" AND $this->config['advanced_editor']==TRUE )
		{
			$this->javascript( 'head', TRUE, "tinymce/tinymce.min.js" );
			$this->javascript( 'head', TRUE, "tinymce/tinymce.config.js" );
		}
		$this->dataProcess($data);
		$this->f3->set('prePop', 	$prePop);
		$this->f3->set('data', 		$data);
		$this->f3->set('returnpath', $returnpath);
		
		return $this->render('stories/recommendation.edit.html');
	}

	public function shoutEdit(array $data, array $sort, $page)
	{
		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('page', $page);
		
		return $this->render('home/shout.edit.html');
	}
	
	public function shoutList(array $data, array $sort)
	{
		$this->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		$this->f3->set('shoutEntries', $data);
		$this->f3->set('sort', $sort);
		return $this->render('home/shout.list.html');
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
		return $this->render('stories/story.add.html');
	}
	
	public function storyMetaEdit(array $storyData, array $chapterList, array $prePop)
	{
		$storyData['storynotes'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['storynotes']);
		$storyData['summary'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['summary']);

		$this->f3->set('prePop', $prePop);
		$this->f3->set('data', $storyData);
		$this->f3->set('chapterList', $chapterList);
		
		return $this->render('stories/edit.header.html');
	}

	public function storyChapterEdit(array $chapterData, array $chapterList): string
	{
		if($chapterData['editor']=="visual" AND $this->config['advanced_editor']==TRUE )
		{
			$this->javascript( 'head', TRUE, "tinymce/tinymce.min.js" );
			$this->javascript( 'head', TRUE, "tinymce/tinymce.config.js" );
		}

		$this->f3->set('data', $chapterData);
		$this->f3->set('chapterList', $chapterList);

		return $this->render('stories/edit.chapter.html');
	}

	public function storyListPending(array $data, array $sort)
	{
		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);
		return $this->render('stories/pending.list.html');
	}
	
	public function storyValidatePending(array $data, $returnpath)
	{
		$data['story']['cache_authors'] 	= json_decode($data['story']['cache_authors'],TRUE);
		$data['story']['cache_tags'] 		= json_decode($data['story']['cache_tags'],TRUE);
		$data['story']['cache_characters']	= json_decode($data['story']['cache_characters'],TRUE);
		$data['story']['cache_categories'] 	= json_decode($data['story']['cache_categories'],TRUE);
		$data['story']['cache_rating'] 		= json_decode($data['story']['cache_rating'],TRUE);

		$this->f3->set('data', 		 $data);
		$this->f3->set('returnpath', $returnpath);
		return $this->render('stories/pending.view.html');
	}
	
	public function storyValidateChapter(array $data, $chapterText, $returnpath)
	{
		$data['cache_authors'] 	= json_decode($data['cache_authors'],TRUE);
		
		$this->f3->set('data', 			$data);
		$this->f3->set('chapterText', 	$chapterText);
		$this->f3->set('returnpath',	$returnpath);
		return $this->render('stories/pending.chapter.html');
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
		return $this->render('archive/tag.list.html');
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
		return $this->render('archive/tagGroup.list.html');
	}
	
	public function tagEdit(array $data, string $returnpath)
	{
		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath);
		return $this->render('archive/tag.edit.html');
	}

	public function tagGroupEdit(array $data)
	{
		$this->f3->set('data', $data);
		return $this->render('archive/tagGroup.edit.html');
	}
	
	public function userFieldsEdit()
	{

	}
	
	public function userFieldsList(array $fieldData)
	{
		$this->f3->set('data', $fieldData);
		return $this->render('members/fields.list.html');
	}
	
	public function userTeamList($teamData)
	{
		$this->f3->set('data', $teamData);
		return $this->render('members/team.list.html');
	}
	
	public function userAddForm() //(array $post, int $result)
	{
		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		return $this->render('members/member.add.html');
	}
	
	public function userEditList(array $data, array $sort, $search)
	{
		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('search', $search);
		return $this->render('members/member.search.html');
	}

	public function userEdit(array $data, $returnpath): string
	{
		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath);
		return $this->render('members/member.edit.html');
	}

}
