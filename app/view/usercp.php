<?php
namespace View;

class UserCP extends Base
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

	public function showMenu(array $menu=[]): string
	{
		$this->f3->set('panel_menu', $menu);
		return $this->render('usercp/menu.html');
	}

	public function upperMenu(array $menu, $counter, $path, $sub, $selected=NULL)
	{
		$this->f3->set('menu_upper', $menu);
		$this->f3->set('counter', $counter);
		$this->f3->set('sub', $sub);
		$this->f3->set('path', $path);
		$this->f3->set('selected', $selected);

		return $this->render('usercp/menu.upper.html');
	}

	public function start (array $stats)
	{
		$this->f3->set('stats', $stats);
		return $this->render('usercp/start.html');
	}

	public function authorHome($data=[])
	{
		$this->f3->set('message', $data);
		return $this->render('usercp/author/home.html');
	}

	public function authorStoryList(array $data, array $sort, array $params)
	{
		$this->f3->set('storyEntries', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('author', $params['uid']);
		$this->f3->set('select', $params[1]);
		return $this->render('usercp/author/story.list.html');
	}

	public function authorStoryAdd(array $data)
	{
		$this->f3->set('storyAdd', $data);

		return $this->render('usercp/author/story.add.html');
	}

	public function authorStoryHeaderEdit(array $storyData, array $chapterList, array $prePop)
	{
		$storyData['storynotes'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['storynotes']);
		$storyData['summary'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['summary']);

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('prePop', $prePop);
		$this->f3->set('data', $storyData);
		$this->f3->set('chapterList', $chapterList);

		return $this->render('usercp/author/story.editheader.html');
	}

	public function authorStoryChapterEdit(array $chapterData, array $chapterList)
	{
		if($chapterData['editor']=="visual")
		{
			// load TinyMCE and config
			$this->javascript( 'head', "tinymce/tinymce.min.js", TRUE );
			$this->javascript( 'head', "tinymce/tinymce.config.js", TRUE );
			// replace \n breaks with html breaks
			$chapterData['chaptertext'] = str_replace("\n", "<br/>", $chapterData['chaptertext']);
		}

		$this->f3->set('data', $chapterData);
		$this->f3->set('chapterList', $chapterList);

		return $this->render('usercp/author/story.editchapter.html');
	}

	public function authorCurator(array $data=[])
	{
		$this->f3->set('curator', $data);
		return $this->render('usercp/author/curator.html');
	}

	public function msgInOutbox($data, $select="inbox")
	{
		if ( $select == "outbox" )
		{
			$select = "Outbox";
			$person_is = "Recipient";
			$date_means = "Sent";
		}
		else
		{
			$select = "Inbox";
			$person_is = "Sender";
			$date_means = "Received";
		}

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('messages', $data);
		$this->f3->set('WHICH', $select);
		$this->f3->set('PERSON_IS', $person_is);
		$this->f3->set('DATE_MEANS', $date_means);

		return $this->render('usercp/messaging/inout.html');
	}

	public function msgRead($data)
	{
		$this->f3->set('message', $data);
		$this->f3->set('forward',($data['sender_id']==$_SESSION['userID']) );

		return $this->render('usercp/messaging/read.html');
	}

	public function msgWrite($data)
	{
		$this->f3->set('write_data', $data);
		return $this->render('usercp/messaging/write.html');
	}

	public function pollsList(array $data, array $sort)
	{
		$this->f3->set('polls', $data);
		$this->f3->set('sort', $sort);
		return $this->render('usercp/polls.list.html');
	}

	public function shoutboxList($data)
	{
		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('shouts', $data);
		return $this->render('usercp/shoutbox.list.html');
	}

	public function libraryBookFavList(array $data, array $sort, array $extra)
	{
		$this->javascript( 'head', "controlpanel.js.php?sub=confirmDelete", TRUE );

		$this->f3->set('libraryEntries', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('extra', $extra);
		return $this->render('usercp/library/bookFavList.html');
	}

	public function libraryBookFavEdit($data, $params)
	{
		$this->f3->set('data', $data);
		$this->f3->set('block', $params[0]);
		$this->f3->set('returnpath', $params['returnpath']);
		$this->f3->set('saveError', @$params['error']);

		return $this->render('usercp/library/bookFavEdit.html');
	}

	public function libraryCollectionsList(array $data, array $sort, string $module) : string
	{
		$this->f3->set('data', 		$data);
		$this->f3->set('module', 	$module);
		$this->f3->set('sort', $sort);

		return $this->render('usercp/library/collections.list.html');
	}

	public function collectionEdit(array $data, array $prePop, string $module, string $returnpath="" ) : string
	{
		if($data['editor']=="visual" AND $this->config['advanced_editor']==TRUE )
		{
			$this->javascript( 'head', "tinymce/tinymce.min.js", TRUE );
			$this->javascript( 'head', "tinymce/tinymce.config.js", TRUE );
		}
		$this->f3->set('module', 	$module);
		$this->f3->set('prePop', 	$prePop);
		$this->f3->set('data', 		$data);
		$this->f3->set('returnpath', $returnpath);

		return $this->render('usercp/library/collection.edit.html');
	}

	public function collectionItems(array $data, string $module, string $returnpath="" ) : string
	{
		$this->f3->set('data', 			$data);
		$this->f3->set('module', 		$module);
		$this->f3->set('returnpath',	$returnpath);

		return $this->render('usercp/library/collection.items.html');
	}

	public function feedbackHome(array $data)
	{
		$this->javascript( 'head', "piechart.js", TRUE );

		$this->f3->set('stats', $data);
		return $this->render('usercp/feedback.home.html');
	}

	public function feedbackListReviews(array $data, array $sort, array $extra)
	{
		$this->javascript( 'head', "controlpanel.js.php?sub=confirmDelete", TRUE );

		$this->f3->set('feedbackEntries', $data);
		$this->f3->set('sort', $sort);
		$this->f3->set('extra', $extra);

		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}
		return $this->render('usercp/feedback.html');
	}

	public function libraryFeedbackEdit($data, $params)
	{
		$this->f3->set('data', $data);
		$this->f3->set('block', $params[0]);
		$this->f3->set('direction', $params[1]);
		$this->f3->set('returnpath', $params['returnpath']);
		$this->f3->set('saveError', @$params['error']);

		return $this->render('usercp/feedback.edit.html');
	}

	public function recommendationList( array $data, array $sort ) : string
	{
		$this->javascript( 'head', "controlpanel.js.php?sub=confirmDelete", TRUE );
		$this->f3->set('data', $data);
		$this->f3->set('sort', $sort);

		return $this->render('usercp/library/recommendation.list.html');
	}

	public function recommendationEdit( array $data, array $prePop, string $returnpath="" ) : string
	{
		if($data['editor']=="visual" AND $this->config['advanced_editor']==TRUE )
		{
			$this->javascript( 'head', "tinymce/tinymce.min.js", TRUE );
			$this->javascript( 'head', "tinymce/tinymce.config.js", TRUE );
		}
		$this->f3->set('prePop', 	$prePop);
		$this->f3->set('data', 		$data);
		$this->f3->set('returnpath', $returnpath);

		return $this->render('usercp/library/recommendation.edit.html');
	}

	public function settingsChangePW($feedback)
	{
		$this->f3->set('feedback', $feedback);

		return $this->render('usercp/changepw.html');
	}

	public function settingsProfile($fields)
	{
		$this->f3->set('fields', $fields);

		return $this->render('usercp/settings.profile.html');
	}

	public function settingsPreferences($data)
	{
		$this->f3->set('data', $data);
		$this->f3->set('language_available', \Config::getPublic('language_available'));

		return $this->render('usercp/settings.preferences.html');
	}

}
