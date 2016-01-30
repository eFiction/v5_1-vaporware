<?php
namespace Controller;

class AdminCP extends Base
{

	public function __construct()
	{
		$this->model = \Model\AdminCP::instance();
		//$mapper = new \Model\News();
		//parent::__construct($mapper);
		\Base::instance()->set('systempage', TRUE);
	}

	public function beforeroute()
	{
		$this->response = new \View\Backend();
		\Registry::set('VIEW',$this->response);
		
		$this->response->addTitle( \Base::instance()->get('LN__AdminCP') );
	}

	public function index(\Base $fw, $params)
	{
		$this->response->addTitle( $fw->get('LN__AdminMenu_Home') );
		$this->showMenu("home");
	}

	public function settings(\Base $fw, $params)
	{
		$this->response->addTitle( $fw->get('LN__AdminMenu_Settings') );
		$this->showMenu("settings");
		$params = @$params[1] ?: "home";

		switch ( @$params )
		{
			case "server":
				$this->response->addTitle( $fw->get('LN__AdminMenu_Server') );
				$data['DateTime'] = $this->model->settingsFields('settings_datetime');
				$data['Server'] = $this->model->settingsFields('settings_server');
				break;
			case "registration":
				$this->response->addTitle( $fw->get('LN__AdminMenu_Registration') );
				$data['Registration'] = $this->model->settingsFields('settings_registration');
				$data['AntiSpam'] = $this->model->settingsFields('settings_registration_sfs');
				break;
			case "layout":
				$this->response->addTitle( $fw->get('LN__AdminMenu_Layout') );
				break;
			case "language":
				$this->response->addTitle( $fw->get('LN__AdminMenu_Language') );
				break;
			default:
				$data=[];
		}
	$this->buffer( \View\AdminCP::settingsFields($data, $params) );
	}

	public function settingsSave(\Base $fw, $params)
	{
		//$this->showMenu("home");
		$fw->reroute('/adminCP/settings', false);
	}

	protected function showMenu($selected=FALSE)
	{
		$this->buffer
		( 
			\View\AdminCP::showMenu($this->model->showMenu($selected)), 
			"LEFT"
		);
	}

}