<?php

namespace View;

class News extends Base
{

	public static function block($data)
	{
		\Base::instance()->set('newsItems', $data);
		return \Template::instance()->render('news/block.box.html');
	}
	
}
