<?php

namespace Controller;

class Page extends Base {

	public function __construct()
	{
		$this->model = \Model\Page::instance();
	}

	public function getMain(\Base $f3, $params)
	{
		if ( empty($params['*']) OR FALSE === $page = $this->model->load($params['*'])  )  // 3.6
			$this->buffer ( \Template::instance()->render('main/welcome.html') );
		else
		{
			$this->response->addTitle( $page['title'] );
			$this->buffer ( $page['content'] );
		}
	}
	
}
?>