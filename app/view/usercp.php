<?php
namespace View;

class UserCP extends Base
{

	public static function showMenu($menu="")
	{
		\Base::instance()->set('panel_menu', $menu);
		return \Template::instance()->render('usercp/menu.html');
	}
	
	public static function authorHome($data=[])
	{
		\Base::instance()->set('message', $data);
		return \Template::instance()->render('usercp/author/home.html');
	}

	public static function authorStoryList(array $data, array $sort, array $params)
	{
		//\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		\Base::instance()->set('storyEntries', $data);
		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('author', $params['uid']);
		\Base::instance()->set('select', $params[1]);
		return \Template::instance()->render('usercp/author/storyList.html');
	}
	
	public static function authorStoryAdd(array $data)
	{
		\Base::instance()->set('storyAdd', $data);
		
		return \Template::instance()->render('usercp/author/storyAdd.html');
	}

	public static function authorStoryMetaEdit(array $storyData, array $chapterList, array $prePop)
	{
		$storyData['storynotes'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['storynotes']);
		$storyData['summary'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $storyData['summary']);

		\Base::instance()->set('prePop', $prePop);
		\Base::instance()->set('data', $storyData);
		\Base::instance()->set('chapterList', $chapterList);
		
		return \Template::instance()->render('usercp/author/storyEditMeta.html');
	}

	public static function authorStoryChapterEdit(array $chapterData, array $chapterList, $editor="plain")
	{
		if ($editor=="visual")
		{
			\Registry::get('VIEW')->javascript( 'head', TRUE, "ckeditor/ckeditor.js" );
			$chapterData['editmode']	= "visual";
		}
		else
		{
			$chapterData['notes']		= preg_replace("/<br\\s*\\/>\\s*/i", "\n", $chapterData['notes']);
			$chapterData['chaptertext']	= html_entity_decode(preg_replace("/<br\\s*\\/>\\s*/i", "\n", $chapterData['chaptertext']));
			$chapterData['editmode']	= "plain";
		}

		\Base::instance()->set('data', $chapterData);
		\Base::instance()->set('chapterList', $chapterList);
		
		return \Template::instance()->render('usercp/author/storyEditChapter.html');
	}

	public static function authorCurator(array $data=[])
	{
		\Base::instance()->set('curator', $data);
		return \Template::instance()->render('usercp/author/curator.html');
	}

	public static function msgInOutbox($data, $select="inbox")
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
		$f3 = \Base::instance();
		
		if( isset($_SESSION['lastAction']) )
		{
			$f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$f3->set('messages', $data);
		$f3->set('WHICH', $select);
		$f3->set('PERSON_IS', $person_is);
		$f3->set('DATE_MEANS', $date_means);

		return \Template::instance()->render('usercp/messaging.inout.html');
	}

	public static function msgRead($data)
	{
		\Base::instance()->set('message', $data);
		\Base::instance()->set('forward',($data['sender_id']==$_SESSION['userID']) );
		
		return \Template::instance()->render('usercp/messaging.read.html');
	}

	public static function msgWrite($data)
	{
		\Base::instance()->set('write_data', $data);
		return \Template::instance()->render('usercp/messaging.write.html');
	}
	
	public static function shoutboxList($data)
	{
		//\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		if( isset($_SESSION['lastAction']) )
		{
			\Base::instance()->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}
		
		\Base::instance()->set('shouts', $data);
		return \Template::instance()->render('usercp/shoutbox.list.html');
	}
	
	public static function libraryBookFavEdit($data, $params)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "jquery.are-you-sure.js" );
		\Base::instance()->set('data', $data);
		\Base::instance()->set('block', $params[0]);
		\Base::instance()->set('returnpath', $params['returnpath']);
		\Base::instance()->set('saveError', @$params['error']);
		
		return \Template::instance()->render('usercp/library.editBookFav.html');
	}
	
	public static function libraryListBookFav(array $data, array $sort, array $extra)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		\Base::instance()->set('libraryEntries', $data);
		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('extra', $extra);
		return \Template::instance()->render('usercp/library.html');
	}
	
	public static function upperMenu(array $menu, array $counter, $path, $sub)
	{
		\Base::instance()->set('menu_upper', $menu);
		\Base::instance()->set('counter', $counter);
		\Base::instance()->set('sub', $sub);
		\Base::instance()->set('path', $path);

		return \Template::instance()->render('usercp/menu.upper.html');
	}
	
	public static function feedbackHome(array $data)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "piechart.js" );
		
		\Base::instance()->set('stats', $data);
		return \Template::instance()->render('usercp/feedback.home.html');
	}

	public static function feedbackListReviews(array $data, array $sort, array $extra)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );

		\Base::instance()->set('feedbackEntries', $data);
		\Base::instance()->set('sort', $sort);
		\Base::instance()->set('extra', $extra);
		return \Template::instance()->render('usercp/feedback.html');
	}

	public static function libraryFeedbackEdit($data, $params)
	{
		\Registry::get('VIEW')->javascript( 'head', TRUE, "jquery.are-you-sure.js" );
		\Base::instance()->set('data', $data);
		\Base::instance()->set('block', $params[0]);
		\Base::instance()->set('direction', $params[1]);
		\Base::instance()->set('returnpath', $params['returnpath']);
		\Base::instance()->set('saveError', @$params['error']);
		
		return \Template::instance()->render('usercp/feedback.edit.html');
	}
	
	public static function settingsChangePW($feedback)
	{
		\Base::instance()->set('feedback', $feedback);
		
		return \Template::instance()->render('usercp/changepw.html');
	}
	
	public static function settingsProfile($fields)
	{
		\Base::instance()->set('fields', $fields);
		
		return \Template::instance()->render('usercp/settings.profile.html');
	}
	
	public static function settingsPreferences($data)
	{
		\Base::instance()->set('data', $data);
		\Base::instance()->set('language_available', \Base::instance()->get('CONFIG.language_available'));
		
		return \Template::instance()->render('usercp/settings.preferences.html');
	}
	
}
