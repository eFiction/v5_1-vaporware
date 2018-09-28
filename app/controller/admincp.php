<?php
namespace Controller;

class AdminCP extends Base
{
	var $moduleBase = "home";
	//var $hasSub		= FALSE;

	public function __construct()
	{
		$this->model = \Model\AdminCP::instance();
		$this->template = new \View\AdminCP();
		\Base::instance()->set('systempage', TRUE);
	}

	public function beforeroute()
	{
		$this->response = new \View\Backend();
		\Registry::set('VIEW',$this->response);
		
		$this->response->addTitle( \Base::instance()->get('LN__AdminCP') );
	}

	protected function showMenu($selected=FALSE)
	{
		$menu = $this->model->showMenu($selected);
		$this->buffer
		( 
			\View\AdminCP::showMenu($menu), 
			"LEFT"
		);
		
		if ( isset($menu[$this->moduleBase]['sub']) AND sizeof($menu[$this->moduleBase]['sub'])>0 )
			\Base::instance()->set('accessSub', TRUE);
	}
	
	protected function moduleInit( array $allowed, $submodule )
	{
		$submodule = in_array ( @$submodule, $allowed ) ? $submodule : NULL;
		if ( $submodule )
			$s = "/{$submodule}";
		else
			$submodule = "home";

		if ( TRUE === $this->model->checkAccess($this->moduleBase.@$s) )
			return $submodule;
	}

	protected function showMenuUpper($selected=FALSE)
	{
		$menu = $this->model->showMenuUpper($selected);
		\Base::instance()->set('menu_upper', $menu);
		foreach ( $menu as $m ) $link[] = $m['link'];
		return $link;
	}
	
	public function fallback(\Base $f3, $params)
	{
		$f3->reroute('/adminCP/home', false);
	}
	
	public function __archive(\Base $f3, $params, $feedback = [ NULL, NULL ] )
	{
		// declare module
		$this->moduleBase = "archive";
		// build menu and access list
		$this->showMenu($this->moduleBase);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Archive') );
		//$f3->set('title_h1', $f3->get('LN__AdminMenu_Archive') );

		switch( $this->moduleInit([ "submit", "featured", "contests", "characters", "tags", "categories" ], @$params['module']) )
		{
			case "home":
				$this->archiveHome($f3);
				break;
			case "submit":
				$this->archiveSubmit($f3, $feedback);
				break;
			case "featured":
				$this->archiveFeatured($f3, $params, $feedback);
				break;
			case "contests":
				$this->buffer( $this->archiveContests($f3, $params, $feedback) );
				break;
			case "characters":
				$this->buffer( $this->archiveCharacters($f3, $params, $feedback) );
				break;
			case "tags":
				$this->archiveTagsIndex($f3, $params, $feedback);
				break;
			case "categories":
				$this->archiveCategories($f3, $params);
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
	}
	
	public function archiveAjax(\Base $f3, $params)
	{
		$data = [];
		if ( empty($params['module']) ) return NULL;

		$post = $f3->get('POST');
		
		if ( $params['module']=="search" )
			$data = $this->model->ajax("search", $post);

		elseif ( $params['module']=="editMeta" )
			$data = $this->model->ajax("editMeta", $post);

		elseif ( $params['module']=="featured" )
		{
			$data = $this->model->ajax("storySearch", $post);
		}
		echo json_encode($data);
		exit;
	}

	protected function archiveHome(\Base $f3, $feedback = [ NULL, NULL ])
	{
		if ( isset($_POST['form_data']) )
		{
			$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Archive') );
		$data['General'] = $this->model->settingsFields('archive_general');
		$data['Intro'] = $this->model->settingsFields('archive_intro');
		$this->buffer( \View\AdminCP::settingsFields($data, "archive/home", $feedback) );
	}
	
	protected function archiveSubmit(\Base $f3, $feedback = [ NULL, NULL ])
	{
		if ( isset($_POST['form_data']) )
		{
			$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Submission') );
		$data['Stories'] = $this->model->settingsFields('archive_submit');
		$data['Images'] = $this->model->settingsFields('archive_images');
		$data['Reviews'] = $this->model->settingsFields('archive_reviews');
		$this->buffer( \View\AdminCP::settingsFields($data, "archive/submit", $feedback) );
	}
	
	protected function archiveFeatured(\Base $f3, $params, $feedback)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Featured') );
		$allowedSubs = $this->showMenuUpper("archive/featured");
		
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset( $_POST['sid'] ) )
		{
			$params['sid'] = (int)$_POST['sid'];
		}

		if ( isset ($params['select']) )
		{
			$allow_order = array (
				"id"		=>	"S.sid",
				"title"		=>	"S.title",
			);

			// sort order
			$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "title";
			$sort["order"]		= $allow_order[$sort["link"]];
			$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";
			
			$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

			$data = $this->model->listStoryFeatured($page, $sort, $params['select']);
			$this->buffer( \View\AdminCP::listFeatured($data, $sort, $params['select']) );
			
			return TRUE;
		}
		elseif (isset($_POST['form_data']))
		{
			$this->buffer( print_r($f3->get('POST.form_data'),1) );
			$changes = $this->model->saveFeatured($params['sid'], $f3->get('POST.form_data') );
		}

		if( isset ($params['sid']) )
		{
			$data = $this->model->loadFeatured($params['sid']);
			$data['errors'] = @$errors;
			$data['changes'] = @$changes;
			$this->buffer( \View\AdminCP::editFeatured($data) );
			// return TRUE;
		}
		else
		{
			$this->buffer( \View\Base::stub() );
		}
	}
	
	protected function archiveContests(\Base $f3, $params, $feedback)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Contests') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Contests') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset($params['delete']) )
		{
			$this->model->contestDelete( (int)$params['delete'] );
			$f3->reroute('/adminCP/archive/contests', false);
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				if ( FALSE === $changes = $this->model->contestSave($params['id'], $f3->get('POST.form_data') ) )
					$errors = $f3->get('form_error');
			}
			elseif ( isset($_POST['entries_data']) )
			{
				//if ( FALSE === $changes = $this->model->contestSave($params['id'], $f3->get('POST.form_data') ) )
				//	$errors = $f3->get('form_error');
			}
			elseif ( isset($_POST['newContest']) )
			{
				if ( NULL !== $newID = $this->model->contestAdd( $f3->get('POST.newContest') ) )
					$f3->reroute('/adminCP/archive/contests/id='.$newID, false);
				// Error handling
			}
			elseif ( isset($_POST['charid']) ) $params['id'] = $f3->get('POST.charid');
		}

		if( isset ($params['id']) )
		{
			// Load contest data
			$data = $this->model->contestLoad($params['id']);

			// Edit or add contest entries
			if( isset($params['entries']) )
			{
				$data['stories'] = $this->model->contestLoadEntries($params['id']);
				return $this->template->contestEntries($data, @$params['returnpath']);
			}
			// Edit contest data
			else
			{
				//$data['categories'] = $this->model->getCategories();
				//$data['tags']
				$data['raw'] = @$params['raw'];
				$data['errors'] = @$errors;
				$data['changes'] = @$changes;
				return $this->template->contestEdit($data, @$params['returnpath']);
			}
		}

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
			"id"		=>	"conid",
			"name"		=>	"title",
			"open"		=>	"date_open",
			"close"		=>	"date_close",
			"count"		=>	"count",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";
		
		return $this->template->contestsList($this->model->contestsList($page, $sort), $sort);
	}

	protected function archiveCharacters(\Base $f3, $params, $feedback)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Characters') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Characters') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset($params['delete']) )
		{
			$this->model->deleteCharacter( (int)$params['delete'] );
			$f3->reroute('/adminCP/archive/characters', false);
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$changes = $this->model->saveCharacter($params['id'], $f3->get('POST.form_data') );
			}
			elseif ( isset($_POST['newCharacter']) )
			{
				$newID = $this->model->addCharacter( $f3->get('POST.newCharacter') );
				$f3->reroute('/adminCP/archive/characters/id='.$newID, false);
			}
			elseif ( isset($_POST['charid']) ) $params['id'] = $f3->get('POST.charid');
		}
		
		if( isset ($params['id']) )
		{
			$data = $this->model->loadCharacter($params['id']);
			$data['categories'] = $this->model->getCategories();
			$data['errors'] = @$errors;
			$data['changes'] = @$changes;
			return $this->template->editCharacter($data, @$params['returnpath']);
		}

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
			"id"		=>	"charid",
			"name"		=>	"charname",
			"count"		=>	"count"
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "name";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";
		
		$data = $this->model->charactersList($page, $sort);
		return $this->template->listCharacters($data, $sort);
	}
	
	protected function archiveTagsIndex(\Base $f3, $params, $feedback)
	{
		//$p = [];
		$this->response->addTitle( $f3->get('LN__AdminMenu_Tags') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Tags') );

		$allowedSubs = $this->showMenuUpper("archive/tags");

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		
		if ( isset($params['groups']) )
			$this->archiveTagsGroups($f3, $params);
		
		elseif ( isset($params['cloud']) )
		{
			if (isset($_POST['form_data']))
			{
				$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
			}
			$data['Settings'] = $this->model->settingsFields('archive_tags_cloud');
			$this->buffer( \View\AdminCP::settingsFields($data, "archive/tags/cloud", $feedback ) );
		}
		else
			$this->archiveTagsEdit($f3, $params);
	}
	
	protected function archiveTagsEdit(\Base $f3, $params)
	{

		if ( isset($params['delete']) )
		{
			$this->model->deleteTag( (int)$params['delete'] );
			$f3->reroute('/adminCP/archive/tags/edit', false);
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$changes = $this->model->saveTag($params['id'], $f3->get('POST.form_data') );
			}
			elseif ( isset($_POST['newTag']) )
			{
				$newID = $this->model->addTag( $f3->get('POST.newTag') );
				$f3->reroute('/adminCP/archive/tags/edit/id='.$newID, false);
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
			return TRUE;
		}

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
		$this->buffer ( $this->template->listTags($data, $sort) );
	}
	
	protected function archiveTagsGroups(\Base $f3, $params)
	{
		//$segment = "archive/tags/groups";
		//if(!$this->model->checkAccess($segment)) return FALSE;
		
		if ( isset($params['delete']) )
		{
			if ( $this->model->deleteTagGroup( (int)$params['delete'] ) )
				$f3->reroute('/adminCP/archive/tags/groups', false);
			else $f3->set('form_error', "__failedDelete");
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$changes = $this->model->saveTagGroup($params['id'], $f3->get('POST.form_data') );
			}
			elseif ( isset($_POST['newTagGroup']) )
			{
				$newID = $this->model->addTagGroup( $f3->get('POST.newTagGroup') );
				$f3->reroute('/adminCP/archive/tags/groups/id='.$newID, false);
			}
		}

		if( isset ($params['id']) )
		{
			$data = $this->model->loadTagGroup($params['id']);
			$data['errors'] = @$errors;
			$data['changes'] = @$changes;
			$this->buffer( \View\AdminCP::editTagGroup($data) );
			return TRUE;
		}

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
		$this->buffer ( $this->template->listTagGroups($data, $sort) );
	}
	
	protected function archiveCategories(\Base $f3, $params)
	{
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		
		$this->response->addTitle( $f3->get('LN__AdminMenu_Categories') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Categories') );

		if ( isset($params['move']) )
		{
			$parent = $this->model->moveCategory( $params['move'][1], $params['move'][0] );
		}
		elseif ( isset($params['add']) )
		{
			$parent_cid = (isset($params['add']) AND $params['add']!==TRUE) ? (int)$params['add'] : 0;

			if ( isset($_POST['form_data']) )
				$newID = $this->model->addCategory( $parent_cid, $f3->get('POST.form_data') );

			if ( empty($newID) )
			{
				// Attempted to add category, but failed
				if ( @$newID === FALSE )
					$errors = '__failAddCategory';
				
				$parent_info = $this->model->loadCategory($parent_cid);
				// Non-existent category, go back to overview
				if ( $parent_info === FALSE ) $f3->reroute('/adminCP/archive/categories', false);

				// Form
				$data = [
					'errors'	=> @$errors,
					'changes'	=> @$changes,
					'id'		=> $parent_cid,
					'info'		=> @$parent_info,
				];
				$this->buffer( \View\AdminCP::addCategory( $f3, $data ) );
				
				// Leave function without creating further forms or mishap
				return TRUE;
			}
			else
			{
				$f3->set('changes', 1);
			}
			
		}
		elseif ( isset($params['delete']) )
		{
			$data = $this->model->loadCategory((int)$params['delete']);
			if ( isset($data['category']) )
			{
				$data['stats'] = json_decode($data['stats'],TRUE);

				if ( $data['stats']['sub']===NULL AND $data['stats']['count']==0 )
				{
					if ( FALSE === $this->model->deleteCategory( (int)$params['delete'] ) )
						$errors = $f3->get('ACP_Categories_Error_DBError', $data['category']);
					else
						$changes = $f3->get('ACP_Categories_Success_Deleted', $data['category']);
				}
				else
					$errors = $f3->get('ACP_Categories_Error_notEmpty', $data['category']);
			}
			else
				$errors = $f3->get('ACP_Categories_Error_badID');
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
				$changes = $this->model->saveCategory($params['id'], $f3->get('POST.form_data') );
		}

		if ( isset($params['id']) )
		{
			$data = $this->model->loadCategory($params['id']);
			$data['move'] = $this->model->loadCategoryPossibleParents($params['id']);
			if ( $data['leveldown'] > 1 )
			{
				$parent = $this->model->loadCategory($data['move'][0]['parent_cid']);
				$data['move'] = array_merge([ [ "cid" => $parent['id'], "parent_cid" => $parent['parent_cid'], "leveldown" => $parent['leveldown']-1, "category" => $parent['category']." (one level up)" ] ], $data['move'] );
			}
			$data['move'] = array_merge([ [ "cid" => 0, "parent_cid" => 0, "leveldown" => -1, "category" => "__Category_MainCategory"] ], $data['move'] );
			$data['stats'] = json_decode($data['stats'],TRUE);
			$data['errors'] = @$errors;
			$data['changes'] = @$changes;
			$this->buffer( \View\AdminCP::editCategory($data) );
			return TRUE;
		}

		$data = $this->model->categoriesListFlat();
		$feedback['errors'] = @$errors;
		$feedback['changes'] = @$changes;

		$this->buffer ( \View\AdminCP::listCategories($data, $feedback) );
	}

	public function __home(\Base $f3, $params)
	{
		// declare module
		$this->moduleBase = "home";
		// build menu and access list
		$this->showMenu($this->moduleBase);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Home') );

//		$f3->set('title_h1', $f3->get('LN__AdminMenu_Home') );

		switch( $this->moduleInit([ "manual", "custompages", "news", "modules", "logs", "shoutbox" ], @$params['module']) )
		{
			case "custompages":
				$this->homeCustompages( $f3, $params );
				break;
			case "home":
				$this->homeIndex($f3);
				break;
			case "logs":
				$this->homeLogs( $f3, $params );
				break;
			case "manual":
				$this->homeManual( $f3, $params );
				break;
			case "modules":
				$this->buffer( \View\Base::stub() );
				break;
			case "news":
				$this->homeNews( $f3, $params );
				break;
			case "shoutbox":
				$this->homeShoutbox( $f3, $params );
				break;
			case "stories":
				$this->homeStories($f3, $params);
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
	}
	
	protected function homeIndex(\Base $f3)
	{
		// silently attempt to get version information
		$ch = @curl_init("https://efiction.org/version.php");
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
		
		$this->buffer( $this->template->homeWelcome($version, $compare) );
	}

	protected function homeManual(\Base $f3)
	{
		$this->buffer( "<a href='http://efiction.org/wiki/Main_Page'>http://efiction.org/wiki/Main_Page</a>" );
	}
	
	protected function homeCustompages(\Base $f3, array $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_CustomPages') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_CustomPages') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

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

	protected function homeLogs(\Base $f3, array $params)
	{
		if ( !$this->model->checkAccess("home/logs") )
		{
			$this->buffer( "__NoAccess" );
			return FALSE;
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Logs') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Logs') );
		
		$menuCount = $this->model->logGetCount();

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		$sub = isset($params['module'])?FALSE:$params['0'];

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
				"date"		=>	"timestamp",
				"user"		=>	"username",
				"author"	=>	"author",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "date";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";

		$data = $this->model->logGetData($sub, $page, $sort);
		
		
		$this->buffer( \View\AdminCP::listLog($data, $menuCount, [], $sub ) );
		//$this->buffer( "<pre>".print_r($data,TRUE)."</pre>" );
	}
	
	protected function homeShoutbox(\Base $f3, array $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Shoutbox') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Shoutbox') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

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
	
	protected function homeNews(\Base $f3, array $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_News') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_News') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

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
				$changes = $this->model->saveNews($params['id'], $f3->get('POST.form_data') );
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
				$this->buffer( $this->template->editNews($data, @$params['returnpath']) );
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

		$this->buffer ( $this->template->listNews($data, $sort) );
	}

	public function __members(\Base $f3, $params)
	{
		// declare module
		$this->moduleBase = "members";
		// build menu and access list
		$this->showMenu($this->moduleBase);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Members') );

		switch( $this->moduleInit([ "edit", "pending", "groups", "profile", "team" ], @$params['module']) )
		{
			case "edit":
				$this->buffer( $this->membersEdit($f3, $params) );
				break;
			case "pending":
				$this->buffer( \View\Base::stub() );
				break;
			case "groups":
				$this->buffer( \View\Base::stub() );
				break;
			case "profile":
				$this->membersProfile($f3, $params);
				break;
			case "team":
				$this->membersTeam($f3);
				break;
			case "home":
				$this->membersHome($f3);
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
	}

	public function membersAjax(\Base $f3, $params)
	{
		$data = [];
		if ( empty($params['module']) ) return NULL;

		$post = $f3->get('POST');
		
		if ( $params['module']=="search" )
			$data = $this->model->ajax("userSearch", $post);

		echo json_encode($data);
		exit;
	}

	protected function membersEdit(\Base $f3, $params)
	{
		if( isset($_POST) ) $post = $f3->get('POST');
		if( isset($params['*']) ) $params = $this->parametric($params['*']);
		
		if( empty($params['uid']) OR !is_numeric($params['uid']) )
			return $this->membersEditSearchForm($f3, $params);
		
		if( FALSE === $memberdata = $this->model->loadUser($params['uid']) )
			return "__failed";
		
		return $this->template->userEdit($memberdata, $params['returnpath']);
	}
			
	protected function membersEditSearchForm(\Base $f3, $params)
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

	protected function membersHome(\Base $f3, $feedback = [ NULL, NULL ])
	{
		if ( isset($_POST['form_data']) )
		{
			$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Members') );
		$data['General'] = $this->model->settingsFields('members_general');
		$this->buffer( \View\AdminCP::settingsFields($data, "members/home", $feedback) );
	}

	protected function membersTeam(\Base $f3)
	{
		$team = $this->model->listTeam();
		$this->buffer( $this->template->userListTeam($team) );
	}

	protected function membersProfile(\Base $f3, $params)
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

	public function __settings(\Base $f3, $params, $feedback = [ NULL, NULL ] )
	{
		// declare module
		$this->moduleBase = "settings";
		// build menu and access list
		$this->showMenu($this->moduleBase);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Settings') );

		switch( $this->moduleInit([ "datetime", "server", "registration", "security", "screening", "language", "layout" ], @$params['module']) )
		{
			case "datetime":
				$this->response->addTitle( $f3->get('LN__AdminMenu_DateTime') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_DateTime') );
				$data['DateTime'] = $this->model->settingsFields('settings_datetime');
				$extra = $this->settingsDateTime($f3, $params);
				break;
			case "server":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Server') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_Server') );
				$this->settingsServer($f3, $data);
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
				$extra = $this->settingsLayout($f3, $params);
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
				$extra = $this->settingsLanguage($f3, $params);
				break;
			case "home":
				$this->response->addTitle( $f3->get('LN__AdminMenu_General') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_General') );
				$params['module'] = "home";
				$data['General'] = $this->model->settingsFields('settings_general');
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
		if (isset($data))  $this->buffer( \View\AdminCP::settingsFields($data, "settings/".$params['module'], $feedback) );
		if (isset($extra)) $this->buffer( $extra );
	}

	public function __settingsSave(\Base $f3, $params)
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
			$results = $this->settingsSaveLLData($f3, $params);
		
		$this->__settings($f3, $params, $results);
	}

	protected function settingsDateTime(\Base $f3, $params)
	{
		return $this->template->settingsDateTime();
	}

 	protected function settingsLanguage(\Base $f3, $params)
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

 	protected function settingsLayout(\Base $f3, $params)
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
				$data['active'] = array_key_exists($entry, $layoutConfig['layout_available']);
				$data['folder'] = $entry;
				$layoutFiles[] = $data;
			}
		}
		
		$iconset = $this->template->layoutIcons();

		return $this->template->layout($layoutFiles, $layoutConfig).$iconset;
	}

	protected function settingsSaveLLData(\Base $f3, $params)
	{
		if ( $params['module'] == "language" )
			return $this->model->saveLanguage($f3->get('POST.form_special'));

		if ( $params['module'] == "layout" )
			return $this->model->saveLayout($f3->get('POST.form_special'));
	}

	protected function settingsServer(\Base $f3, &$data)
	{
		if ( !$this->model->checkAccess("settings/server") )
		{
			$this->buffer( "__NoAccess" );
			return FALSE;
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Server') );
		//$data['DateTime'] = $this->model->settingsFields('settings_datetime');
		$data['Mail'] = $this->model->settingsFields('settings_mail');
		$data['Maintenance'] = $this->model->settingsFields('settings_maintenance');
		$data['Report'] = $this->model->settingsFields('settings_report');
	}

	public function __stories(\Base $f3, $params)
	{
		// declare module
		$this->moduleBase = "stories";
		// build menu and access list
		$this->showMenu($this->moduleBase);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Stories') );

		switch( $this->moduleInit([ "pending", "edit", "add" ], @$params['module']) )
		{
			case "pending":
				$this->storiesPending($f3, $params);
				break;
			case "edit":
				$this->storiesEdit($f3, $params);
				break;
			case "add":
				$this->storiesAdd($f3, $params);
				break;
			case "home":
				$this->storiesHome($f3, $params);
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
	}
	
	public function storiesAjax(\Base $f3, $params)
	{
		$data = [];
		if ( empty($params['module']) ) return NULL;

		$post = $f3->get('POST');
		
		if ( $params['module']=="search" )
			$data = $this->model->ajax("storySearch", $post);

		elseif ( $params['module']=="editMeta" )
			$data = $this->model->ajax("editMeta", $post);
		
		elseif ( $params['module']=="chaptersort" )
		{
			//if ( isset($params[2]) ) $params = $this->parametric($params[2]); // 3.6
			$data = $this->model->ajax("chaptersort", $post);
		}
		
		echo json_encode($data);
		exit;
	}

	protected function storiesPending(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Pending') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Pending') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		// search/browse
		$allow_order = array (
				"id"		=>	"sid",
				"date"		=>	"timestamp",
				"title"		=>	"title",
		);

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";

		$data = $this->model->getPendingStories($page, $sort);
		$this->buffer( $this->template->listPendingStories($data, $sort) );
	}
	
	protected function storiesEdit(\Base $f3, $params)
	{
		if ( isset($params['*']) )
			$params = $this->parametric($params['*']);

		if ( empty($params['story']) )
		{
			// Select story form
			$this->buffer( $this->template->searchStoryForm() );
			return TRUE;
		}
		elseif ( FALSE !== $storyInfo = $this->model->loadStoryInfo((int)$params['story']) )
		{
			// save data
			if (isset($_POST) and sizeof($_POST)>0 )
			{
				$post = $f3->get('POST');
				$current = $this->model->loadStoryMapper($params['story']);
				
				if ( isset($params['chapter']) )
				{
					$this->model->saveChapterChanges($params['chapter'], $post['form']);
					$f3->reroute("/adminCP/stories/edit/story={$storyInfo['sid']};chapter={$params['chapter']}", false);
					exit;
				}
				else
				{
					$this->model->storySaveChanges($current, $post['form']);
					$f3->reroute('/adminCP/stories/edit/story='.$storyInfo['sid'], false);
					exit;
				}
			}

			// Chapter list is always needed, load after POST to catch chapter name changes
			$chapterList = $this->model->loadChapterList($storyInfo['sid']);


			if ( isset($params['chapter']) )
			{
				if ( $params['chapter']=="new" )
				{
					$newChapterID = $this->model->addChapter($storyInfo['sid']);
					$reroute = "/adminCP/stories/edit/story={$storyInfo['sid']}/chapter={$newChapterID}"; //;returnpath=".$params['returnpath'];
					$f3->reroute($reroute, false);
					exit;
				}
				
				$chapterInfo = $this->model->loadChapter($storyInfo['sid'],(int)$params['chapter']);
				// abusing $chapterData to carry a few more details
				$chapterInfo['storytitle'] = $storyInfo['title'];
				
				if ( isset($params['plain']) ) $editor = "plain";
				elseif ( isset($params['visual']) ) $editor = "visual";
				else
				{
					if (empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0)
						$editor = "plain";
					else
						$editor = "visual";
				}

				$this->buffer( \View\AdminCP::storyChapterEdit($chapterInfo,$chapterList,$editor) );


				//$storyInfo['returnpath'] = $params['returnpath'];
				//$this->buffer ( $this->editChapter($f3, $params, $storyInfo) );
			}
			else
			{
				$storyInfo['returnpath'] = $params['returnpath'];
				$chapterList = $this->model->loadChapterList($storyInfo['sid']);
				$prePopulate = $this->model->storyEditPrePop($storyInfo);
				$this->buffer( $this->template->storyMetaEdit($storyInfo,$chapterList,$prePopulate) );
			}
		}
		else
		{
			$this->buffer ( "__Error" );
		}
	}
	
	protected function storiesAdd(\Base $f3, $params)
	{
		if ( isset($_POST['form']) )
		{
			$post = $f3->get('POST.form');
			if ( ( NULL === $data = $this->model->storyAddCheck($post) ) OR isset($post['confirmInsert']) )
			{
				// insert story to database
				if ( NULL !== $storyID = $this->model->storyAdd($post) )
				{
					$f3->reroute("/adminCP/stories/edit/story={$storyID}", false);
					exit;
				}
			}
			else $this->buffer( $this->template->storyAddForm($data) );
		}
		else $this->buffer( $this->template->storyAddForm() );
	}
	
	protected function storiesHome(\Base $f3, $params)
	{
		$this->buffer( \View\Base::stub() );
	}
}

