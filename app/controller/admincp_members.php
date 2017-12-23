<?php
namespace Controller;

class AdminCP_Members extends AdminCP
{
	var $moduleBase = "members";
	//var $submodules = [ "search", "edit", "pending", "groups", "profile", "team" ];
	var $submodules = [ "edit", "pending", "groups", "profile", "team" ];

	public function index(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Members') );

		switch( $this->moduleInit(@$params['module']) )
		{
			case "edit":
				$this->buffer( $this->edit($f3, $params) );
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

	public function ajax(\Base $f3, $params)
	{
		$data = [];
		if ( empty($params['module']) ) return NULL;

		$post = $f3->get('POST');
		
		if ( $params['module']=="search" )
			$data = $this->model->ajax("userSearch", $post);

		echo json_encode($data);
		exit;
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
		
		$this->buffer ( $this->template->listUserFields( $data ) );
	}

	protected function team(\Base $f3)
	{
		$team = $this->model->listTeam();
		$this->buffer( $this->template->userListTeam($team) );
	}
	
	protected function edit(\Base $f3, $params)
	{
		if( isset($_POST) ) $post = $f3->get('POST');
		if( isset($params['*']) ) $params = $this->parametric($params['*']);
		
		if( empty($params['uid']) OR !is_numeric($params['uid']) )
			return $this->editSearchForm($f3, $params);
		
		if( FALSE === $memberdata = $this->model->loadUser($params['uid']) )
			return "__failed";
		
		return $this->template->userEdit($memberdata, $params['returnpath']);
		//return print_r($memberdata,1);
		
	}
			
	protected function editSearchForm(\Base $f3, $params)
	{
		if(!empty($params['term']))
		{
			$search['term'] = $params['term'];
			$search['follow'][] = "term={$params['term']}";
		}
		else $search['term'] = NULL;
		
		if(isset($params['fromlevel']))
		{
			if(isset($params['tolevel']) AND $params['fromlevel']>$params['tolevel'] )
				$search['fromlevel'] = (int)$params['tolevel'];
			else
				$search['fromlevel'] = (int)$params['fromlevel'];
			$search['follow'][] = "fromlevel={$search['fromlevel']}";
		}
		else $search['fromlevel'] = NULL;
		
		if(isset($params['tolevel']))
		{
			$search['tolevel'] = (int)$params['tolevel'];
			$search['follow'][] = "tolevel={$search['tolevel']}";
		}
		else $search['tolevel'] = NULL;
		
		$search['follow'] = (isset($search['follow'])) ? implode("/",$search['follow'])."/" : "";
		
		// search/browse
		$allow_order = array (
				"id"		=>	"uid",
				"name"		=>	"nickname",
				"date"		=>	"registered",
				"email"		=>	"email",
		);

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "date";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";
		
		$data = $this->model->listUsers($page, $sort, $search);
		return $this->template->userSearchList($data, $sort, $search);
	}

	public function save(\Base $f3, $params)
	{
		
	}
}
