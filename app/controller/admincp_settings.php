<?php
namespace Controller;

class AdminCP_Settings extends AdminCP
{
	var $moduleBase = "settings";
	var $submodules = [ "server", "registration", "language", "layout" ];

	public function index(\Base $f3, $params, $feedback = [ NULL, NULL ] )
	{
		$data = NULL;
		$this->response->addTitle( $f3->get('LN__AdminMenu_Settings') );

		switch( $this->moduleInit(@$params['module']) )
		{
			case "server":
				$this->server($f3, $data);
				break;
			case "registration":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Registration') );
				$data['Registration'] = $this->model->settingsFields('settings_registration');
				$data['AntiSpam'] = $this->model->settingsFields('settings_registration_sfs');
				break;
			case "layout":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Layout') );
				$this->buffer( \View\Base::stub() );
				break;
			case "language":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Language') );
				$this->buffer( \View\Base::stub() );
				break;
			case "home":
				$params['module'] = "home";
				$data['General'] = $this->model->settingsFields('settings_general');
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
		if ($data) $this->buffer( \View\AdminCP::settingsFields($data, "settings/".$params['module'], $feedback) );
	}
	
/* 	protected function home(\Base $f3, &$data)
	{
		$params['module'] = "home";
		$data['General'] = $this->model->settingsFields('settings_general');
	}
 */	
	protected function server(\Base $f3, &$data)
	{
		if ( !$this->model->checkAccess("settings/server") )
		{
			$this->buffer( "__NoAccess" );
			return FALSE;
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Server') );
		$data['DateTime'] = $this->model->settingsFields('settings_datetime');
		$data['Server'] = $this->model->settingsFields('settings_server');
	}

	public function save(\Base $f3, $params)
	{
		if (empty($params['module']))
		{
			$f3->reroute('/adminCP/settings', false);
			exit;
		}
		$results = $this->model->saveKeys($f3->get('POST.form_data'));
		$this->index($f3, $params, $results);
	}

}