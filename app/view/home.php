<?php
namespace View;

//class Page extends Base
class Home extends Base
{
	public function welcome()
	{
		return $this->render('main/welcome.html');
	}
	
	public function newsBlock($data)
	{
		$this->f3->set('newsItems', $data);
		return $this->render('home/news.block.html');
	}
	
	public function listNews($data)
	{
		$this->f3->set('newsItems', $data);
		return $this->render('home/news.listing.html');
	}
	
	public function showNews($data)
	{
		if ( $_SESSION['userID']==0 )
		{
			$this->javascript( 'body', FALSE, "$( document ).ready(function() {
																	getCaptchaImage();
																	$('#captchaBox').click(getCaptchaImage);
																}); " );
		}
		$this->f3->set('news', $data);
		return $this->render('home/news.single.html');
	}

	public function pollBlock($data)
	{
		$this->f3->set('pollItems', $data);
		return $this->render('home/poll.block.html');
	}
	
	public function pollSingle(array $data)
	{
		$this->f3->set('data', $data);

		if (current($data['cache'])>0)
			$this->f3->set('factor', $data['votes']/current($data['cache']));
		else $this->f3->set('factor', 0);

		return $this->render('home/poll.single.html');
	}
	
	public function pollArchive(array $polls): string
	{
		$this->f3->set('polls', $polls);
		
		return $this->render('home/poll.archive.html');
	}

	// this is a static wrapper called in the Frontend to work with {PAGE:xyz} includes
	public static function loadPage($page)
	{
		if($page = \Model\Home::instance()->loadPage($page))
			return $page['content'];
		else return NULL;
	}

}
