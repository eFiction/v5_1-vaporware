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
		
		if ($versions)
		{
			$version = unserialize($versions)['efiction5'];
			$this->buffer ( print_r($version,TRUE ) );
		}
		else $this->buffer ( "No version" );
		$this->buffer( \View\Base::stub() );
	}


}