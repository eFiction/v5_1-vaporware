<?php
namespace Controller;

class AdminCP_Home extends AdminCP
{

	public function index(\Base $fw, $params)
	{
		$this->response->addTitle( $fw->get('LN__AdminMenu_Home') );
		$this->showMenu("home");

		switch( @$params['module'] )
		{
			case "manual":
				$this->buffer( \View\Base::stub() );
				break;
			case "custompages":
				$this->buffer( \View\Base::stub() );
				break;
			case "news":
				$this->buffer( \View\Base::stub() );
				break;
			case "modules":
				$this->buffer( \View\Base::stub() );
				break;
			default:
				$this->home();
		}
	}
	
	public function save(\Base $fw, $params)
	{
		
	}

	protected function home()
	{
		// silently attempt to get version information
		$ch = @curl_init("http://efiction.org/version.php");
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$versions = @curl_exec($ch);
		@curl_close($ch);
		
		$compare['base'] = \Base::instance()->get('APP_VERSION');

		if ($versions)
		{
			$version = @unserialize($versions)['efiction5'];
			if ( @$version['dev'] ) $compare['dev'] = version_compare ( $version['dev'], $compare['base'] );
			if ( @$version['stable'] ) $compare['stable'] = version_compare ( $version['stable'], $compare['base'] );
		}
		else $version = FALSE;
		
		$this->buffer( \View\AdminCP::homeWelcome($version, $compare) );
	}


}