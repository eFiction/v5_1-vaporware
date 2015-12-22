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
				$this->msgRead($fw, $params);
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
		//print_r ( $data);
	}
	
	public function msgOutbox(\Base $fw, $params)
	{
		$data = $this->model->msgOutbox();
		$this->buffer ( \View\UserCP::msgInOutbox($data, "outbox") );
		//print_r ( $data);
	}
	
	public function msgRead(\Base $fw, $params)
	{
		$this->buffer( "Read Message!");
		//$data = $this->model->msgRead();
		//$this->buffer ( \View\UserCP::msgInbox($data) );
		//print_r ( $data);
	}
	
	public function msgWrite(\Base $fw, $params)
	{
		$this->buffer( "Write Message!");
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