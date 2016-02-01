<?php
namespace Controller;

class AdminCP_Archive extends AdminCP
{

	public function index(\Base $fw, $params)
	{
		$this->response->addTitle( $fw->get('LN__AdminMenu_Archive') );
		$this->showMenu("archive");

		switch( @$params['module'] )
		{
			case "featured":
				$this->buffer( \View\Base::stub() );
				break;
			case "tags":
				$this->buffer( \View\Base::stub() );
				break;
			case "categories":
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
	
	public function save(\Base $fw, $params)
	{
		
	}
}