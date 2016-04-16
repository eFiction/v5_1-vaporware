<?php
namespace Controller;

class AdminCP_Home extends AdminCP
{

	public function index(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Home') );
		$f3->set('title_h1', $f3->get('LN__AdminMenu_Home') );
		$this->showMenu("home");

		switch( @$params['module'] )
		{
			case "manual":
				$this->buffer( \View\Base::stub() );
				break;
			case "custompages":
				$this->custompages( $f3, $params );
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
	
	public function save(\Base $f3, $params)
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

	protected function custompages(\Base $f3, array $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_CustomPages') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_CustomPages') );

		if ( isset($params[2]) ) $params = $this->parametric($params[2]);

		if  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$changes = $this->model->saveCustompage($params['id'], $f3->get('POST.form_data') );
			}
			elseif ( isset($_POST['newPage']) )
			{
				$newID = $this->model->addCustompage( $f3->get('POST.newPage') );
				if ( $newID === FALSE )
					$f3->set('form_error', "__DuplicateLabel ".$f3->get('POST.newPage') );
				else
					$f3->reroute('/adminCP/home/custompages/id='.$newID, false);
			}
		}
		elseif ( isset($params['delete']) )
		{
			if ( $this->model->deleteCustompage( (int)$params['delete'] ) )
				$f3->reroute('/adminCP/home/custompages', false);
			else $f3->set('form_error', "__failedDelete");
		}
		
		if( isset ($params['id']) )
		{
			if(!isset($params['raw']))
			{
				\Registry::get('VIEW')->javascript( 'head', TRUE, "//cdn.tinymce.com/4/tinymce.min.js" );
				\Registry::get('VIEW')->javascript( 'head', TRUE, "editor.js" );
			}
			$data = $this->model->loadCustompage($params['id']);
			$data['raw'] = @$params['raw'];
			$data['errors'] = @$errors;
			$data['changes'] = @$changes;
			$this->buffer( \View\AdminCP::editCustompage($data) );
			return TRUE;
		}

		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );
		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
				"id"				=>	"id",
				"label"			=>	"label",
				"title"			=>	"title",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "label";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";
		
		$data = $this->model->listCustompages($page, $sort);

		$this->buffer ( \View\AdminCP::listCustompages($data, $sort) );
	}
	
}