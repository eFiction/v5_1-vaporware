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
	
	public function beforeroute()
	{
		parent::beforeroute();
		\Registry::get('VIEW')->addTitle( \Base::instance()->get('LN__UserCP') );
	}

	public function index(\Base $fw, $params)
	{
		
		$this->showMenu();
	}
	
	public function messaging(\Base $fw, $params)
	{
		\Registry::get('VIEW')->addTitle( $fw->get('LN__UserCP') );
		\Registry::get('VIEW')->addTitle( $fw->get('LN__PM_Messaging') );
		if ( isset($params[1]) )
		{
			$params = explode("/",$params[1]);
			$params[0] = explode(",",$params[0]);
		}
		
		switch ( $params[0][0] )
		{
			case "outbox":
				$this->msgOutbox($fw, $params);
				break;
			case "read":
				$this->msgRead($fw, $params);
				break;
			case "write":
				$this->msgWrite($fw, $params);
				break;
			default:
				$this->msgInbox($fw, $params);
		}
		$this->showMenu("messaging");
	}
	
	public function msgInbox(\Base $fw, $params)
	{
		$data = $this->model->msgInbox();
		$this->buffer ( \View\UserCP::msgInOutbox($data, "inbox") );
	}
	
	public function msgOutbox(\Base $fw, $params)
	{
		$data = $this->model->msgOutbox();
		$this->buffer ( \View\UserCP::msgInOutbox($data, "outbox") );
	}
	
	public function msgRead(\Base $fw, $params)
	{
		if ( $data = $this->model->msgRead($params[0][1]) )
		{
			$this->buffer ( \View\UserCP::msgRead($data) );
		}
		else $this->buffer( "*** No such message or access violation!");
	}
	
	public function msgWrite(\Base $fw, $params)
	{
		if ( isset($params[0][1]) AND is_numeric($params[0][1]) )
			$data = $this->model->msgReply($params[0][1]);
		else $data = $this->model->msgReply();
		
		$this->buffer ( \View\UserCP::msgWrite($data) );
		//$this->buffer( "Write Message!");
		//$data = $this->model->msgRead();
		//$this->buffer ( \View\UserCP::msgInbox($data) );
		//print_r ( $data);
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