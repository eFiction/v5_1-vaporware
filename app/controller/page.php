<?php

namespace Controller;

class Page extends Base {

	public function __construct()
	{
		$this->model = \Model\Page::instance();
	}

	public function getMain(\Base $f3, array $params)//: void
	{
		if ( !empty($params['*']) )
		{
			if ( FALSE !== $page = $this->model->load($params['*'])  )  // 3.6
			{
				$this->response->addTitle( $page['title'] );
				$this->buffer ( $page['content'] );
			}
			// Workaround for ;returnpath not properly being handled by routes
			elseif ( 0 === strpos($params['*'],"logout"))
				Auth::instance()->logout($f3, $params['*']);
		}
		else
			$this->buffer ( \Template::instance()->render('main/welcome.html') );
	}
	
	public function maintenance(\Base $f3)//: void
	{
		$this->getMain($f3, ["*" => "maintenance"]);
	}
	
}
?>