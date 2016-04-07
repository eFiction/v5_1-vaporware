<?php
namespace Controller;

class AdminCP_Archive extends AdminCP
{

	public function index(\Base $f3, $params, $feedback = [ NULL, NULL ] )
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Archive') );
		$this->showMenu("archive");

		switch( @$params['module'] )
		{
			case "featured":
				$this->buffer( \View\Base::stub() );
				break;
			case "tags":
				$this->tagsIndex($f3, $params, $feedback);
				break;
			case "categories":
				$this->buffer( \View\Base::stub() );
				break;
			default:
				$this->home();
		}
	}

	public function ajax(\Base $f3, $params)
	{
		$data = [];
		if ( empty($params['module']) ) return NULL;

		$post = $f3->get('POST');
		
		if ( $params['module']=="tags" )
		{
			$data = $this->model->ajax("tags", $post);
		}
		echo json_encode($data);
		exit;
	}

	protected function home()
	{
		$this->buffer( \View\Base::stub() );
	}
	
	protected function tagsIndex(\Base $f3, $params, $feedback)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Tags') );
		$this->showMenuUpper("archive/tags");

		if ( isset($params[2]) ) $p = $this->parametric($params[2]);
		
		if ( isset($p['groups']) )
			$this->tagsGroups($f3, $p);
		
		elseif ( isset($p['cloud']) )
		{
			if (isset($_POST['form_data']))
			{
				$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
			}
			$data['TagsCloud'] = $this->model->settingsFields('archive_tags_cloud');
			$this->buffer( \View\AdminCP::settingsFields($data, "archive/tags/cloud", $feedback ) );
		}
		else
			$this->tagsEdit($f3, $p);
	}
	
	protected function tagsEdit(\Base $f3, $params)
	{
		if ( isset($params[2]) ) $params = $this->parametric($params[2]);

		if  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$changes = $this->model->saveTag($params['id'], $_POST['form_data']);
			}
			elseif ( isset($_POST['tid']) ) $params['id'] = $f3->get('POST.tid');
		}
		
		if( isset ($params['id']) )
		{
			$data = $this->model->loadTag($params['id']);
			$data['groups'] = $this->model->tagGroups();
			$data['errors'] = @$errors;
			$data['changes'] = @$changes;
			$this->buffer( \View\AdminCP::editTag($data) );
		}
		elseif ( isset($params['delete']) )
		{
			$this->buffer("delete");
		}
		else
		{
			// page will always be an integer > 0
			$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

			// search/browse
			$allow_order = array (
				"id"		=>	"tid",
				"label"		=>	"label",
				"group"		=>	"G.description",
				"count"		=>	"count"
			);
	
			// sort order
			$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "label";
			$sort["order"]		= $allow_order[$sort["link"]];
			$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";
			
			$data = $this->model->tagsList($page, $sort);
			$this->buffer ( \View\AdminCP::listTags($data, $sort) );
		}
		//
	}
	
	protected function tagsGroups(\Base $f3, $params)
	{
		if ( isset($params[2]) ) $params = $this->parametric($params[2]);

		if  ( 1 == 0 )
		{
			
		}
		else
		{
			// page will always be an integer > 0
			$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

			// search/browse
			$allow_order = array (
				"id"		=>	"tid",
				"group"		=>	"G.description",
				"count"		=>	"count"
			);
	
			// sort order
			$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "group";
			$sort["order"]		= $allow_order[$sort["link"]];
			$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";
			
			$data = $this->model->tagGroupsList($page, $sort);
			$this->buffer ( \View\AdminCP::listTagGroups($data, $sort) );
		}
		//
	}
	
	protected function tagsCloud()
	{
		//
	}
	/*
	public function save(\Base $f3, $params)
	{
		if (empty($params['module']))
		{
			$f3->reroute('/adminCP/archive', false);
			exit;
		}
		$results = $this->model->saveKeys($f3->get('POST.form_data'));
		$this->index($f3, $params, $results);
	}*/
}