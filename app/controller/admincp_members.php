<?php
namespace Controller;

class AdminCP_Members extends AdminCP
{

	public function index(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Members') );
		$this->showMenu("members");

		switch( @$params['module'] )
		{
			case "search":
				$this->buffer( \View\Base::stub() );
				break;
			case "pending":
				$this->buffer( \View\Base::stub() );
				break;
			case "groups":
				$this->buffer( \View\Base::stub() );
				break;
			default:
				$this->home();
		}
	}

	protected function home()
	{
		$this->buffer( \View\Base::stub() );
	}
	
	public function save(\Base $f3, $params)
	{
		
	}
}