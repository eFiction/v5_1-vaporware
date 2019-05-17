<?php

namespace Controller;

class Home extends Base {

	public function __construct()
	{
		$this->model = \Model\Home::instance();
		$this->template = new \View\Home();
	}
	
	public function index(\Base $f3, array $params)//: void
	{
		switch( @$params['module'] )
		{
			case "news":
				$this->buffer( $this->news($f3, $params) );
				break;
			case "polls":
				$this->poll($f3, $params);
				break;
			default:
				$this->buffer( $this->page($f3, $params) );
		}		
	}
	
	// maintenance wrapper
	public function maintenance(\Base $f3)//: void
	{
		$this->page($f3, ['page' => "maintenance"]);
	}

	public function news(\Base $f3, array $params)//: void
	{
		$this->response->addTitle( \Base::instance()->get('LN__News') );
		
		$this->model->canAdmin('home/news');
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if  ( isset($_POST) AND sizeof($_POST)>0 AND "" == $f3->get('formError') )
		{
			$this->newsSave($f3, $params);
		}
		if ( isset ( $params['id']) AND (int)$params['id'] > 0 )
		{
			if ( $data = $this->model->loadNews($params['id']) )
			{
				return $this->template->showNews($data);
			}
		}

		// Show all news, currently set to show 5 items per page
		// Happens if no id provided ot ID did not reurn a news entry
		$data = $this->model->listNews(5);
		return $this->template->listNews($data);
	}

	public function newsSave(\Base $f3, array $params)//: void
	{
		//$params = $this->parametric($params['*']);
		if($_SESSION['userID']!=0 OR \Config::getPublic('allow_guest_comment_news') )
		{
			$errors = [];
			$data = $f3->get('POST.comment');

			// Obviously, there should be some text ...
			if ( "" == $data['text'] = trim($data['text']) )
				$errors[]= 'MessageEmpty';

			if ( $_SESSION['userID'] )
			{
				if ( empty($errors) AND $this->model->saveComment($params['id'], $data, TRUE) )
					$f3->reroute('/home/news/id='.$params['id'], false);
				else $errors[] = "CannotSave";
			}
			else
			{
				// Check if captcha is initialized and matches user entry
				if ( empty($_SESSION['captcha']) OR !password_verify(strtoupper($data['captcha']),$_SESSION['captcha']) )
					$errors[]= 'CaptchaMismatch';

				// Guest can't post with an empty name
				if ( "" == $data['name'] = trim($data['name']) )
					$errors[]= 'GuestNameEmpty';

				// guest can't post URL (reg ex is not perfect, but it's a start)
				if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$data['text']))
					$errors[]= 'GuestURL';

				if ( empty($errors) AND 1 == $this->model->saveComment($params['id'], $data) )
				{
					// destroy this session captcha
					unset($_SESSION['captcha']);
					$f3->reroute('/home/news/id='.$params['id'], false);
				}
			}
			// If no data was saved, we end up here, so we show the page again and it will display the errors
			$f3->set('formError', $errors);
			$this->news($f3, $params);
		}
	}

	public function blocks(string $select): string
	{
		$select = explode(".",$select);

		// bad request? no problem, here's your nothing
		if ( empty($select[0]) )
			return "";
		
		elseif ( $select[0]=="news" )
		{
			$items = min(($select[1]??1),3);
			
			$data = $this->model->loadNewsOverview($items);
			return $this->template->newsBlock($data);
		}
		elseif ( $select[0]=="poll" )
		{
			$data = $this->model->loadPolls();
			return $this->template->pollBlock($data);
		}
	}

	public function page(\Base $f3, array $params)//: void
	{
		// did we ask for a page and can it be loaded?
		if ( isset($params['*']) AND ( [] !== $page = $this->model->loadPage($params['*'])) )
		{
				$this->response->addTitle( $page['title'] );
				return $page['content'];
		}
		// Workaround for ;returnpath not properly being handled by routes
		elseif ( 0 === strpos($params['*']??"","logout"))
			Auth::instance()->logout($f3, $params);
		// Show welcome page instead
		else
			return $this->template->render('main/welcome.html');
	}

	public function poll(\Base $f3, array $params)//: void
	{
		
	}
	
}
?>
