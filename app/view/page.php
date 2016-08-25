<?php
namespace View;

class Page extends Base
{

	public static function load($page)
	{
		if($page = \Model\Page::instance()->load($page))
			return $page['content'];
		else return NULL;
	}


}
