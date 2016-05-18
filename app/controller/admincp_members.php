<?php
namespace Controller;

class AdminCP_Members extends AdminCP
{
	var $moduleBase = "members";
	var $submodules = [ "search", "pending", "groups" ];

	public function index(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Members') );

		switch( $this->moduleInit(@$params['module']) )
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
			case "home":
				$this->home($f3);
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
	}

	protected function home(\Base $f3)
	{
		if ( !$this->model->checkAccess("members") )
		{
			$this->buffer( "__NoAccess" );
			return FALSE;
		}
		$this->buffer( \View\Base::stub() );
	}

	public function save(\Base $f3, $params)
	{
		
	}
}