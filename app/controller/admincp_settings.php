<?php
namespace Controller;

class AdminCP_Settings extends AdminCP
{
	var $moduleBase = "settings";
	var $submodules = [ "server", "registration", "security", "screening", "language", "layout" ];

	public function index(\Base $f3, $params, $feedback = [ NULL, NULL ] )
	{
		$data = NULL;
		$extra = NULL;
		$this->response->addTitle( $f3->get('LN__AdminMenu_Settings') );

		switch( $this->moduleInit(@$params['module']) )
		{
			case "server":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Server') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_Server') );
				$this->server($f3, $data);
				break;
			case "registration":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Registration') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_Registration') );
				$data['Registration'] = $this->model->settingsFields('settings_registration');
				$data['AntiSpam'] = $this->model->settingsFields('settings_registration_sfs');
				break;
			case "layout":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Layout') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_Layout') );
				$data['Layout'] = $this->model->settingsFields('settings_layout');
				$extra = $this->layout($f3, $params);
				break;
			case "security":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Security') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_Security') );
				break;
			case "screening":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Screening') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_Screening') );
				$data['BadBevaviour'] = $this->model->settingsFields('bad_behaviour');
				$data['BadBevaviour_Ext'] = $this->model->settingsFields('bad_behaviour_ext');
				$data['BadBevaviour_Rev'] = $this->model->settingsFields('bad_behaviour_rev');
				break;
			case "language":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Language') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_Language') );
				$data['Language'] = $this->model->settingsFields('settings_language');
				$extra = $this->language($f3, $params);
				break;
			case "home":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Home') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_Home') );
				$params['module'] = "home";
				$data['General'] = $this->model->settingsFields('settings_general');
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
		if ($data) $this->buffer( \View\AdminCP::settingsFields($data, "settings/".$params['module'], $feedback) );
		if ($extra)$this->buffer( $extra );
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
		$data['Mail'] = $this->model->settingsFields('settings_mail');
		$data['Maintenance'] = $this->model->settingsFields('settings_maintenance');
	}

	public function save(\Base $f3, $params)
	{
		if (empty($params['module']))
		{
			$f3->reroute('/adminCP/settings', false);
			exit;
		}
		if ( isset($_POST['form_data']) )
			// Save data from the generic created forms
			$results = $this->model->saveKeys($f3->get('POST.form_data'));
		else
			// Sava data from special forms (language, layout)
			$results = $this->saveData($f3, $params);
		
		$this->index($f3, $params, $results);
	}
	
	protected function saveData(\Base $f3, $params)
	{
		if ( $params['module'] == "language" )
			return $this->model->saveLanguage($f3->get('POST.form_special'));

		if ( $params['module'] == "layout" )
			return $this->model->saveLayout($f3->get('POST.form_special'));
	}

 	protected function language(\Base $f3, $params)
	{
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Language') );

		$languageConfig = $this->model->getLanguageConfig();

		$files = glob("./languages/*.xml");
		foreach ( $files as $file)
		{
			$data = (array)simplexml_load_file($file);
			$data['active'] = array_key_exists($data['locale'], $languageConfig['language_available']);
			$languageFiles[] = $data;
		}
		
		return \View\AdminCP::language($languageFiles, $languageConfig);
	}

 	protected function layout(\Base $f3, $params)
	{
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Layout') );
		
		$layoutConfig = $this->model->getLayoutConfig();
		
		// Folder list with ***x cleanup - anyone with a windows server, is this working?
		$entries = array_diff(scandir("./template/frontend"), array('..', '.'));
		foreach ( $entries as $entry )
		{
			if ( is_dir("./template/frontend/{$entry}") )
			{
				$data = (array)simplexml_load_file("./template/frontend/{$entry}/info.xml");
				$data['active'] = array_key_exists($data['folder'], $layoutConfig['layout_available']);
				$layoutFiles[] = $data;
			}
			print_r($data);
		}

		return \View\AdminCP::layout($layoutFiles, $layoutConfig);
	}
		
}
