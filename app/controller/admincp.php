<?php
namespace Controller;

class AdminCP extends Base
{
	public function __construct()
	{
		$this->model = \Model\AdminCP::instance();
		\Base::instance()->set('systempage', TRUE);
	}

	public function beforeroute()
	{
		$this->response = new \View\Backend();
		\Registry::set('VIEW',$this->response);
		
		$this->response->addTitle( \Base::instance()->get('LN__AdminCP') );
	}

	protected function showMenu($selected=FALSE)
	{
		$this->buffer
		( 
			\View\AdminCP::showMenu($this->model->showMenu($selected)), 
			"LEFT"
		);
	}
	
	public function catch(\Base $f3, $params)
	{
		$f3->reroute('/adminCP/home', false);
	}
}
