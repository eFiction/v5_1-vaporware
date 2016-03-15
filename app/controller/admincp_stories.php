<?php
namespace Controller;

class AdminCP_Stories extends AdminCP
{

	public function index(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Stories') );
		$this->showMenu("stories");

		switch( @$params['module'] )
		{
			case "pending":
				$this->buffer( \View\Base::stub() );
				break;
			case "edit":
				$this->buffer( \View\Base::stub() );
				break;
			case "add":
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