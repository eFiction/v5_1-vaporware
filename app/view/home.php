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
			$this->javascript( 'body', "$( document ).ready(function() {
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

	public function pollAll(array $pollData, array $count, bool $closed, int $selected=0 ): string
	{
		$this->f3->set('polls', $pollData);
		$this->f3->set('count', $count);
		$this->f3->set('closed', $closed);
		$this->f3->set('selected', $selected);

		return $this->render('home/poll.outer.html');
	}

	// this is a static wrapper called in the Frontend to work with {PAGE:xyz} includes
	public static function loadPage($page)
	{
		if($page = \Model\Home::instance()->loadPage($page))
			return $page['content'];
		else return NULL;
	}

}
