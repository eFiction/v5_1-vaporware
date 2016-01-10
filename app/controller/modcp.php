<?php
namespace Controller;

class ModCP extends Base
{

	public function __construct()
	{
		$this->model = \Model\ModCP::instance();
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
		
		$this->showMenu();
	}

	protected function showMenu($selected=FALSE)
	{
		$this->buffer
		( 
			\View\ModCP::showMenu($this->model->showMenu($selected)), 
			"LEFT"
		);
	}

}