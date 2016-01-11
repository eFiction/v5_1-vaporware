<?php
namespace Controller;

class AdminCP extends Base
{

	public function __construct()
	{
		$this->model = \Model\AdminCP::instance();
		//$mapper = new \Model\News();
		//parent::__construct($mapper);
		\Base::instance()->set('systempage', TRUE);
	}

	public function beforeroute()
	{
		$this->response = new \View\Backend();
		\Registry::set('VIEW',$this->response);
	}

	public function index(\Base $fw, $params)
	{
		$this->showMenu("home");
	}

	protected function showMenu($selected=FALSE)
	{
		$this->buffer
		( 
			\View\AdminCP::showMenu($this->model->showMenu($selected)), 
			"LEFT"
		);
	}

}