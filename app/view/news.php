<?php

namespace View;

class News extends Base
{

	public static function block($data)
	{
		\Base::instance()->set('newsItems', $data);
		return \Template::instance()->render('news/block.box.html');
	}
	
	public static function listNews($data)
	{
		\Base::instance()->set('newsItems', $data);
		return \Template::instance()->render('news/listing.html');
	}
	
	public static function showNews($data)
	{
		if ( $_SESSION['userID']==0 )
		{
			\Registry::get('VIEW')->javascript( 'body', FALSE, "$( document ).ready(function() {
																	getCaptchaImage();
																	$('#captchaBox').click(getCaptchaImage);
																}); " );
		}
		\Base::instance()->set('news', $data);
		return \Template::instance()->render('news/single.html');
	}
	
}
