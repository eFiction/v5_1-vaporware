<?php
namespace Controller;

class UserCP extends Base
{

	public function __construct()
	{
		$this->model = \Model\UserCP::instance();
		//$mapper = new \Model\News();
		//parent::__construct($mapper);
		\Base::instance()->set('systempage', TRUE);
	}
	
	public function index(\Base $fw, $params)
	{
		
		$this->showMenu();
	}
	
	public function messaging(\Base $fw, $params)
	{
		$this->buffer( "Message!");
		$this->showMenu();
	}
	
	protected function showMenu($selected=FALSE)
	{
		$this->buffer
		( 
			\View\UserCP::showMenu($this->model->showMenu($selected)), 
			"LEFT"
		);
	}
}