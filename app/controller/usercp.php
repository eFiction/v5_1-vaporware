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
		$this->response->addTitle( \Base::instance()->get('LN__UserCP') );
	}

	public function index(\Base $f3, $params)
	{
		
		$this->showMenu();
	}
	
	public function ajax(\Base $f3, $params)
	{
		if ( empty($params['module']) ) return NULL;
		
		$post = $f3->get('POST');
		
		if ( $params['module']=="messaging" )
		{
			$data = $this->model->ajax("messaging", $post);
		}
		echo json_encode($data);
		exit;
	}
	
	public function messaging(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__UserCP') );
		$this->response->addTitle( $f3->get('LN__PM_Messaging') );
		if ( isset($params[1]) )
		{
			$params = explode("/",$params[1]);
			$params[0] = explode(",",$params[0]);
		}
		
		switch ( $params[0][0] )
		{
			case "outbox":
				$this->msgOutbox($f3, $params);
				break;
			case "read":
				$this->msgRead($f3, $params);
				break;
			case "write":
				$this->msgWrite($f3, $params);
				break;
			default:
				$this->msgInbox($f3, $params);
		}
		$this->showMenu("messaging");
	}
	
	public function msgInbox(\Base $f3, $params)
	{
		$data = $this->model->msgInbox();
		$this->buffer ( \View\UserCP::msgInOutbox($data, "inbox") );
	}
	
	public function msgOutbox(\Base $f3, $params)
	{
		$data = $this->model->msgOutbox();
		$this->buffer ( \View\UserCP::msgInOutbox($data, "outbox") );
	}
	
	public function msgRead(\Base $f3, $params)
	{
		if ( $data = $this->model->msgRead($params[0][1]) )
		{
			$this->buffer ( \View\UserCP::msgRead($data) );
		}
		else $this->buffer( "*** No such message or access violation!");
	}
	
	public function msgWrite(\Base $f3, $params)
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