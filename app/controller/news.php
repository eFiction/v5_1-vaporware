<?php
namespace Controller;

class News extends Base
{

	public function __construct()
	{
		$this->model = \Model\News::instance();
	}

	public function beforeroute()
	{
		parent::beforeroute();
		\Registry::get('VIEW')->addTitle( \Base::instance()->get('LN__News') );
	}
	
	public function blocks(string $select): string
	{
		$select = explode(".",$select);
		$items = (isset($select[2]) AND $select[2]<=3) ? $select[2] : 3;
		
		$data = $this->model->loadOverview($items);
		return \View\News::block($data);//"** NEWS **".$items;
		
	}
	
//	public function index(\Base $f3, array $params): void
	public function index(\Base $f3, array $params)
	{
		$this->model->canAdmin('home/news');
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset ( $params['id']) AND (int)$params['id'] > 0 )
		{
			if ( $data = $this->model->loadNews($params['id']) )
			{
				$this->buffer( \View\News::showNews($data) );
				return TRUE;
			}
		}

		// Show all news, currently set to show 5 items per page
		// Happens if no id provided ot ID did not reurn a news entry
		$data = $this->model->listNews(5);
		$this->buffer( \View\News::listNews($data) );
	}

//	public function save(\Base $f3, array $params): void
	public function save(\Base $f3, array $params)
	{
		$params = $this->parametric($params['*']);
		if($_SESSION['userID']!=0 || \Config::getPublic('allow_guest_comment_news') )
		{
			$errors = [];
			$data = $f3->get('POST.comment');

			// Obviously, there should be some text ...
			if ( "" == $data['text'] = trim($data['text']) )
				$errors[]= 'MessageEmpty';

			if ( $_SESSION['userID'] )
			{
				if ( empty($errors) AND $this->model->saveComment($params['id'], $data, TRUE) )
					$f3->reroute('news/id='.$params['id'], false);
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
					$f3->reroute('news/id='.$params['id'], false);
				}
			}
			// If no data was saved, we end up here, so we show the page again and it will display the errors
			$f3->set('formError', $errors);
			$this->index($f3, $params);
		}
	}

}
