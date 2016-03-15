<?php
namespace Controller;

class AdminCP_Archive extends AdminCP
{

	public function index(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Archive') );
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
	
	public function save(\Base $f3, $params)
	{
		
	}
}