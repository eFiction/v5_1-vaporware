<?php
namespace Controller;

class AdminCP_Home extends AdminCP
{
	var $moduleBase = "home";
	var $submodules = [ "manual", "custompages", "news", "modules", "shoutbox" ];

	public function index(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Home') );
		$f3->set('title_h1', $f3->get('LN__AdminMenu_Home') );

		switch( $this->moduleInit(@$params['module']) )
		{
			case "manual":
				$this->buffer( \View\Base::stub() );
				break;
			case "custompages":
				$this->custompages( $f3, $params );
				break;
			case "news":
				$this->news( $f3, $params );
				break;
			case "modules":
				$this->buffer( \View\Base::stub() );
				break;
			case "shoutbox":
				$this->shoutbox( $f3, $params );
				break;
			case "home":
				$this->home($f3);
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
	}
	
	public function save(\Base $f3, $params)
	{
		
	}

	protected function home(\Base $f3)
	{
		// silently attempt to get version information
		$ch = @curl_init("http://efiction.org/version.php");
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$versions = @curl_exec($ch);
		@curl_close($ch);
		
		$compare['base'] = $f3->get('APP_VERSION');

		if ($versions)
		{
			$version = @json_decode($versions,TRUE)['efiction5'];
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

		if ( isset($params['delete']) )
		{
			if ( $this->model->deleteCustompage( (int)$params['delete'] ) )
				$f3->reroute('/adminCP/home/custompages', false);
			else $f3->set('form_error', "__failedDelete");
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
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
		
		if( isset ($params['id']) )
		{
			if ( NULL !== $data = $this->model->loadCustompage($params['id']) )
			{
				$data['raw'] = @$params['raw'];
				$data['errors'] = @$errors;
				$data['changes'] = @$changes;
				$this->buffer( \View\AdminCP::editCustompage($data) );
				return TRUE;
			}
			else $f3->set('form_error', "__failedLoad");
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

	protected function shoutbox(\Base $f3, array $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Shoutbox') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Shoutbox') );

		if ( isset($params[2]) ) $params = $this->parametric($params[2]);

		// search/browse
		$allow_order = array (
				"id"		=>	"id",
				"date"		=>	"date",
				"message"	=>	"message",
				"author"	=>	"author",
		);

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";

		if ( isset($params['delete']) )
		{
			if ( $this->model->deleteShout( (int)$params['delete'] ) )
				$f3->reroute("/adminCP/home/shoutbox/order={$sort['order']},{$sort['direction']}/page={$page}", false);
			else $f3->set('form_error', "__failedDelete");
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$changes = $this->model->saveShout($params['id'], $f3->get('POST.form_data') );
			}
		}

		if( isset ($params['id']) AND !isset($changes) )
		{
			if ( NULL !== $data = $this->model->loadShoutbox($params['id']) )
			{
				$data['raw'] = @$params['raw'];
				$data['errors'] = @$errors;
				$data['changes'] = @$changes;
				$this->buffer( \View\AdminCP::editShout($data, $sort, $page) );
				return TRUE;
			}
			else $f3->set('form_error', "__failedLoad");
		}
		
		$data = $this->model->listShoutbox($page, $sort);
		$changes = [ @$params['id'], @$changes ];

		$this->buffer ( \View\AdminCP::listShoutbox($data, $sort, $changes) );
	}
	
	protected function news(\Base $f3, array $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_News') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_News') );

		if ( isset($params[2]) ) $params = $this->parametric($params[2]);

		if ( isset($params['delete']) )
		{
			if ( $this->model->deleteNews( (int)$params['delete'] ) )
				$f3->reroute('/adminCP/home/news', false);
			else $f3->set('form_error', "__failedDelete");
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				//$changes = $this->model->saveCustompage($params['id'], $f3->get('POST.form_data') );
			}
			elseif ( isset($_POST['newHeadline']) )
			{
				$newID = $this->model->addNews( $f3->get('POST.newHeadline') );
				if ( $newID !== FALSE )
					$f3->reroute('/adminCP/home/news/id='.$newID, false);
			}
		}
		
		if( isset ($params['id']) )
		{
			if ( NULL !== $data = $this->model->loadNews($params['id']) )
			{
				$data['raw'] = @$params['raw'];
				$data['errors'] = @$errors;
				$data['changes'] = @$changes;
				$this->buffer( \View\AdminCP::editNews($data) );
				return TRUE;
			}
			else $f3->set('form_error', "__failedLoad");
		}

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
				"id"		=>	"nid",
				"date"		=>	"date",
				"title"		=>	"headline",
				"author"	=>	"author",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "date";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";
		
		$data = $this->model->listNews($page, $sort);

		$this->buffer ( \View\AdminCP::listNews($data, $sort) );
	}
	
}
