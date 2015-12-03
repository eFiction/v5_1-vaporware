<?php
namespace Controller;

class News extends Base
{

	public function __construct()
	{
		$this->model = \Model\News::instance();
		//$mapper = new \Model\News();
		//parent::__construct($mapper);
	}

	public function calendar(\Base $f3, $params) {

		$data = $this->model->ajaxCalendar($params);
		
		echo \View\News::calendar($data);
		exit;
	}
}