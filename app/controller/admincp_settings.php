<?php
namespace Controller;

class AdminCP_Settings extends AdminCP
{

	public function index(\Base $f3, $params, $feedback = [ NULL, NULL ] )
	{
		$data = NULL;
		$this->response->addTitle( $f3->get('LN__AdminMenu_Settings') );
		$this->showMenu("settings");

		switch ( @$params['module'] )
		{
			case "server":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Server') );
				$data['DateTime'] = $this->model->settingsFields('settings_datetime');
				$data['Server'] = $this->model->settingsFields('settings_server');
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
			default:
				$params['module'] = "home";
				$data['General'] = $this->model->settingsFields('settings_general');
		}
		if ($data) $this->buffer( \View\AdminCP::settingsFields($data, $params['module'], $feedback) );
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