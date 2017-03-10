<?php
namespace Controller;

class AdminCP_Members extends AdminCP
{
	var $moduleBase = "members";
	var $submodules = [ "search", "pending", "groups", "profile", "team" ];

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
			case "profile":
				$this->profile($f3, $params);
				break;
			case "team":
				$this->team($f3);
				break;
			case "home":
				$this->home($f3);
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
	}

	protected function home(\Base $f3, $feedback = [ NULL, NULL ])
	{
		if ( isset($_POST['form_data']) )
		{
			$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Members') );
		$data['General'] = $this->model->settingsFields('members_general');
		$this->buffer( \View\AdminCP::settingsFields($data, "members/home", $feedback) );
	}

	protected function profile(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Profile') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Profile') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		
		if ( isset($params['edit']) AND is_numeric($params['edit']) )
		{
			
			return TRUE;
		}
		
		// Get all available user fields
		$fields = $this->model->listUserFields();
		
		// Group array by field type
		foreach ( $fields as $field )
			$data[$field['field_type']][] = $field;
		
		$this->buffer ( \View\AdminCP::listUserFields( $data ) );
	}

	protected function team(\Base $f3)
	{
		$team = $this->model->listTeam();
		print_r($team);
		$this->buffer( \View\Base::stub() );
	}

	public function save(\Base $f3, $params)
	{
		
	}
}
