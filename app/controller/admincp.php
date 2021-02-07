<?php
namespace Controller;

class AdminCP extends Base
{
	var $moduleBase = "home";
	var $feedback = [ NULL, NULL ];
	//var $hasSub		= FALSE;

	public function __construct()
	{
		$this->model = \Model\AdminCP::instance();
		$this->template = new \View\AdminCP();
		\Base::instance()->set('systempage', TRUE);
	}

	public function beforeroute(): void
	{
		$this->response = new \View\Backend();
		\Registry::set('VIEW',$this->response);

		$this->response->addTitle( \Base::instance()->get('LN__AdminCP') );
	}

	protected function menuShow($selected=FALSE, $module=""): void
	{
		$menu = $this->model->menuShow($selected,(string)$module);
		$this->buffer
		(
			$this->template->menuShow($menu),
			"LEFT"
		);

		if ( isset($menu[$this->moduleBase]['sub']) AND sizeof($menu[$this->moduleBase]['sub'])>0 )
			\Base::instance()->set('accessSub', TRUE);
	}

	protected function moduleInit( array $allowed, string $submodule=NULL ): string
	{
		if ( in_array ( $submodule, $allowed ) AND TRUE === $this->model->checkAccess($this->moduleBase."/{$submodule}") )
			return $submodule;
		else
			return "home";
	}

	protected function menuShowUpper(string $selected=NULL): array
	{
		$menu = $this->model->menuShowUpper($selected);
		\Base::instance()->set('menu_upper', $menu);
		foreach ( $menu as $m ) $link[] = $m['link'];
		return $link??[];
	}

	public function fallback(\Base $f3, array $params): void
	{
		$f3->reroute('/adminCP/home', false);
	}

	public function __archive(\Base $f3, array $params ): void
	{
		// declare module
		$this->moduleBase = "archive";
		// build menu and access list
		$this->menuShow($this->moduleBase, @$params['module']);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Archive') );
		$f3->set('title_h1', $f3->get('LN__AdminMenu_Archive') );

		switch( $this->moduleInit([ "submit", "contests", "characters", "tags", "categories", "ratings" ], @$params['module']) )
		{
			case "home":
				$this->archiveHome($f3);
				break;
			case "submit":
				$this->archiveSubmit($f3, $this->feedback);
				break;
			case "contests":
				$this->buffer( $this->archiveContests($f3, $params) );
				break;
			case "characters":
				$this->buffer( $this->archiveCharacters($f3, $params) );
				break;
			case "tags":
				$this->archiveTagsIndex($f3, $params, $this->feedback);
				break;
			case "categories":
				$this->archiveCategories($f3, $params);
				break;
			case "ratings":
				$this->archiveRatings($f3, $params);
				break;
			default:
				$this->buffer( $this->template->access() );
		}
	}

	public function archiveAjax(\Base $f3, array $params): void
	{
		$data = [];
		if ( empty($params['module']) ) return;

		$post = $f3->get('POST');

		if ( $params['module']=="search" )
			$data = $this->model->ajax("search", $post);

		elseif ( $params['module']=="editMeta" )
			$data = $this->model->ajax("editMeta", $post);

		elseif ( $params['module']=="ratingsort" )
		{
			$data = $this->model->ajax("ratingsort", $post);
		}

		echo json_encode($data);
		exit;
	}

	protected function archiveHome(\Base $f3, array $feedback = [ NULL, NULL ]): void
	{
		if ( isset($_POST['form_data']) )
		{
			$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Archive') );
		$data['General'] = $this->model->settingsFields('archive_general');
		$data['Intro'] = $this->model->settingsFields('archive_intro');
		$data['Authors'] = $this->model->settingsFields('archive_authors');
		$data['Ebook'] = $this->model->settingsFields('archive_ebook');
		$this->buffer( $this->template->settingsFields($data, "archive/home", $feedback) );
	}

	protected function archiveSubmit(\Base $f3, array $feedback = [ NULL, NULL ]): void
	{
		if ( isset($_POST['form_data']) )
		{
			$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Submission') );
		$data['Stories'] = $this->model->settingsFields('archive_submit');
		$data['Images'] = $this->model->settingsFields('archive_images');
		$data['Reviews'] = $this->model->settingsFields('archive_reviews');
		$this->buffer( $this->template->settingsFields($data, "archive/submit", $feedback) );
	}

	protected function archiveContests(\Base $f3, array $params): string
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Contests') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Contests') );
		$f3->set('wiki', 'Archive:Contests');

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset($params['delete']) )
		{
			$this->model->contestDelete( (int)$params['delete'] );
			$f3->reroute('/adminCP/archive/contests', false);
			exit;
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			// save changes to an existing contest
			if ( isset($_POST['form_data']) )
			{
				$f3->set
				(
					'saveResult',
					$this->model->contestSave
					(
						$params['id'],
						$f3->get('POST.form_data')
					)
				);
			}

			// add a story to an existing contest
			elseif ( isset($_POST['entry_story']) AND 0 < (int)($_POST['entry_story']) )
				$this->model->contestEntryAdd($params['id'], $f3->get('POST.entry_story'), "S" );

			// add a collection or series to an existing contest
			elseif ( isset($_POST['entry_collection']) AND 0 < (int)($_POST['entry_collection']) )
				$this->model->contestEntryAdd($params['id'], $f3->get('POST.entry_collection'), "C" );

			// create a new contest
			elseif ( isset($_POST['newContest']) )
			{
				if ( NULL !== $newID = $this->model->contestAdd( $f3->get('POST.newContest') ) )
				{
					$f3->reroute('/adminCP/archive/contests/id='.$newID, false);
					exit;
				}
				// Error handling
			}
			elseif ( isset($_POST['conid']) ) $params['id'] = $f3->get('POST.conid');
		}

		if( isset ($params['id']) AND 0 < (int)$params['id'] )
		{
			// Load contest data
			$data = $this->model->contestLoad($params['id']);

			// Edit or add contest entries
			if( isset($params['entries']) )
			{
				return $this->archiveContestsEntries($f3, $params, $data);
			}
			// Edit contest data
			else
			{
				$data['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
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

	protected function archiveContestsEntries(\Base $f3, array $params, array $data)
	{
		if ( isset($params['remove']) )
		{
			$this->model->contestEntryRemove( (int)$params['id'], (int)$params['remove'] );
			$url = "/adminCP/archive/contests/id=".(int)$params['id']."/entries";
			if ( isset($params['order']) ) $url .= "/order=".implode(",",$params['order']);
			if ( isset($params['page']) )  $url .= "/page=".$params['page'];
			$f3->reroute($url, false);
			exit;

		}

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
			"id"		=>	"lid",
			"title"		=>	"title",
			"validated"	=>	"validated",
			"completed"	=>	"completed",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";
		// sort icons
		$sort['data']['id'] = 	 ( $sort['direction']=="desc" OR $sort['link']!='id' ) ? "asc" : "desc";
		$sort['data']['title'] = ( $sort['direction']=="desc" OR $sort['link']!='title' ) ? "asc" : "desc";


		$data['stories'] = $this->model->contestLoadEntries($params['id'], $page, $sort);
		return $this->template->contestEntries($data, $sort, @$params['returnpath']);
	}

	protected function archiveCharacters(\Base $f3, array $params): string
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Characters') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Characters') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		$category = (int)($params['category']??-1);

		if ( isset($params['delete']) )
		{
			$this->model->characterDelete( (int)$params['delete'] );
			$f3->reroute('/adminCP/archive/characters', false);
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$f3->set
				(
					'form_changes',
					$this->model->characterSave
					(
						$params['id'],
						$f3->get('POST.form_data')
					)
				);
			}
			elseif ( isset($_POST['newCharacter']) )
			{
				$newID = $this->model->characterAdd( $f3->get('POST.newCharacter') );
				$f3->reroute('/adminCP/archive/characters/id='.$newID, false);
			}
			elseif ( isset($_POST['charid']) ) $params['id'] = $f3->get('POST.charid');
		}

		if( isset ($params['id']) )
		{
			$data = $this->model->characterLoad($params['id']);
			$categories = $this->model->categoryListFlat();
			return $this->template->characterEdit($data, $categories, @$params['returnpath']);
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

		return $this->template->characterList
				(
					$this->model->characterList($page, $sort, $category),
					$this->model->characterCategories(),
					$category,
					$sort
				);
	}

	protected function archiveTagsIndex(\Base $f3, array $params, $feedback): void
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Tags') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Tags') );

		$allowedSubs = $this->menuShowUpper("archive/tags");

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
			$this->buffer( $this->template->settingsFields($data, "archive/tags/cloud", $feedback ) );
		}
		else
			$this->archiveTagsEdit($f3, $params);
	}

	protected function archiveTagsEdit(\Base $f3, $params): void
	{

		if ( isset($params['delete']) )
		{
			$this->model->tagDelete( (int)$params['delete'] );
			$f3->reroute('/adminCP/archive/tags/edit', false);
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$f3->set
				(
					'form_changes',
					$this->model->tagSave
					(
						$params['id'],
						$f3->get('POST.form_data')
					)
				);
			}
			elseif ( isset($_POST['newTag']) )
			{
				$newID = $this->model->tagAdd( $f3->get('POST.newTag') );
				$f3->reroute('/adminCP/archive/tags/edit/id='.$newID, false);
			}
			elseif ( isset($_POST['tid']) ) $params['id'] = $f3->get('POST.tid');
		}

		if( isset ($params['id']) AND is_numeric($params['id']) )
		{
			if ( $data = $this->model->tagLoad($params['id']) )
			{
				$data['groups'] = $this->model->tagGroups();
				$this->buffer( $this->template->tagEdit($data, @$params['returnpath']) );
				return;
			}
			else
			{
				// show load error
			}
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
		// sort icons
		$sort['data']['id']    = ( $sort['direction']=="desc" OR $sort['link']!='id' )    ? "asc" : "desc";
		$sort['data']['label'] = ( $sort['direction']=="desc" OR $sort['link']!='label' ) ? "asc" : "desc";
		$sort['data']['group'] = ( $sort['direction']=="desc" OR $sort['link']!='group' ) ? "asc" : "desc";
		$sort['data']['count'] = ( $sort['direction']=="desc" OR $sort['link']!='count' ) ? "asc" : "desc";

		$data = $this->model->tagList($page, $sort);
		$this->buffer ( $this->template->tagList($data, $sort) );
	}

	protected function archiveTagsGroups(\Base $f3, array $params): void
	{
		//$segment = "archive/tags/groups";
		//if(!$this->model->checkAccess($segment)) return FALSE;

		if ( isset($params['delete']) )
		{
			if ( $this->model->tagGroupDelete( (int)$params['delete'] ) )
				$f3->reroute('/adminCP/archive/tags/groups', false);
			else $f3->set('form_error', "__failedDelete");
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$changes = $this->model->tagGroupSave($params['id'], $f3->get('POST.form_data') );
			}
			elseif ( isset($_POST['newTagGroup']) )
			{
				$newID = $this->model->tagGroupAdd( $f3->get('POST.newTagGroup') );
				$f3->reroute('/adminCP/archive/tags/groups/id='.$newID, false);
			}
		}

		if( isset ($params['id']) )
		{
			if ($data = $this->model->tagGroupLoad($params['id']))
			{
				$data['errors'] = @$errors;
				$data['changes'] = @$changes;
				$this->buffer( $this->template->tagGroupEdit($data) );
				return;
			}
			else
			{
				// show load error
			}
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
		$this->buffer ( $this->template->tagGroupList($data, $sort) );
	}

	protected function archiveCategories(\Base $f3, array $params): void
	{
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		$this->response->addTitle( $f3->get('LN__AdminMenu_Categories') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Categories') );

		if ( isset($params['move']) )
		{
			$parent = $this->model->categoryMove( $params['move'][1], $params['move'][0] );
		}
		elseif ( isset($params['add']) )
		{
			$parent_cid = (isset($params['add']) AND $params['add']!==TRUE) ? (int)$params['add'] : 0;

			if ( isset($_POST['form_data']) )
				$newID = $this->model->categoryAdd( $parent_cid, $f3->get('POST.form_data') );

			if ( empty($newID) )
			{
				// Attempted to add category, but failed
				if ( @$newID === FALSE )
					$errors = '__failAddCategory';

				$parent_info = $this->model->categoryLoad($parent_cid);
				// Non-existent category, go back to overview
				if ( $parent_info === FALSE ) $f3->reroute('/adminCP/archive/categories', false);

				// Form
				$data = [
					'errors'	=> @$errors,
					'changes'	=> @$changes,
					'id'		=> $parent_cid,
					'info'		=> @$parent_info,
				];
				$this->buffer( $this->template->categoryAdd( $f3, $data ) );

				// Leave function without creating further forms or mishap
				return;
			}
			else
			{
				$f3->set('changes', 1);
			}

		}
		elseif ( isset($params['delete']) )
		{
			$data = $this->model->categoryLoad((int)$params['delete']);
			if ( isset($data['category']) )
			{
				$data['stats'] = json_decode($data['stats'],TRUE);

				if ( $data['stats']['sub']===NULL AND $data['stats']['count']==0 )
				{
					if ( FALSE === $this->model->categoryDelete( (int)$params['delete'] ) )
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
			{
				$f3->set
				(
					'form_changes',
					$this->model->categorySave
					(
						$params['id'],
						$f3->get('POST.form_data')
					)
				);
			}
		}

		if ( isset($params['id']) )
		{
			$data = $this->model->categoryLoad($params['id']);
			$data['move'] = $this->model->categoryLoadPossibleParents($params['id']);
			if ( $data['leveldown'] > 1 )
			{
				$parent = $this->model->categoryLoad($data['move'][0]['parent_cid']);
				$data['move'] = array_merge([ [ "cid" => $parent['id'], "parent_cid" => $parent['parent_cid'], "leveldown" => $parent['leveldown']-1, "category" => $parent['category']." (one level up)" ] ], $data['move'] );
			}
			$data['move'] = array_merge([ [ "cid" => 0, "parent_cid" => 0, "leveldown" => -1, "category" => $f3->get('LN__ACP_MainCategory')] ], $data['move'] );
			$data['stats'] = json_decode($data['stats'],TRUE);
			$data['errors'] = @$errors;
			$this->buffer( $this->template->categoryEdit($data) );
			return;
		}

		$data = $this->model->categoryListFlat();
		$feedback['errors'] = @$errors;
		$feedback['changes'] = @$changes;

		$this->buffer ( $this->template->categoryList($data, $feedback) );
	}

	protected function archiveRatings(\Base $f3, array $params): void
	{
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		$this->response->addTitle( $f3->get('LN__AdminMenu_Ratings') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Ratings') );

		if ( isset($params['delete']) )
		{
			$data = $this->model->ratingLoad($params['delete']);
			$allRatings = $this->model->ratingList();
			$this->buffer( $this->template->ratingDelete($data, $allRatings) );
			return;
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$changes = $this->model->ratingSave($params['id'], $f3->get('POST.form_data') );
			}
			elseif ( isset($_POST['newRating']) )
			{
				$newID = $this->model->ratingAdd( $f3->get('POST.newRating') );
				$f3->reroute('/adminCP/archive/ratings/id='.$newID, false);
				exit;
			}
			elseif ( isset($_POST['moveTo']) )
			{
				$this->model->ratingDelete( $params['id'], $f3->get('POST.moveTo') );
				$f3->reroute('/adminCP/archive/ratings', false);
				exit;
			}
		}

		if( isset ($params['id']) )
		{
			$data = $this->model->ratingLoad($params['id']);
			$this->buffer( $this->template->ratingEdit($data) );
			return;
		}

		$data = $this->model->ratingList();
		$this->buffer ( $this->template->ratingList($data) );
	}

	public function __home(\Base $f3, array $params): void
	{
		// declare module
		$this->moduleBase = "home";
		// build menu and access list
		$this->menuShow($this->moduleBase, @$params['module']);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Home') );
		$f3->set('title_h1', $f3->get('LN__AdminMenu_Home') );

		switch( $this->moduleInit([ "custompages", "logs", "manual", "news", "shoutbox", "polls", "maintenance" ], @$params['module']) )
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
			case "news":
				$this->homeNews( $f3, $params );
				break;
			case "shoutbox":
				$this->homeShoutbox( $f3, $params );
				break;
			case "polls":
				$this->buffer( $this->homePolls( $f3, $params ) );
				break;
			case "maintenance":
				$this->buffer( $this->homeMaintenance( $f3, $params ) );
				break;
			default:
				$this->buffer( $this->template->access() );
		}
	}

	protected function homeIndex(\Base $f3): void
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

	protected function homeManual(\Base $f3): void
	{
		$this->buffer( "<a href='http://efiction.org/wiki/Main_Page'>http://efiction.org/wiki/Main_Page</a>" );
	}

	protected function homeCustompages(\Base $f3, array $params): void
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_CustomPages') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_CustomPages') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset($params['delete']) )
		{
			if ( $this->model->deleteCustompage( (int)$params['delete'] ) )
			{
				$f3->reroute('/adminCP/home/custompages', false);
				exit;
			}
			else $f3->set('form_error', "__failedDelete");
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$f3->set
				(
					'form_changes',
					$this->model->saveCustompage
					(
						$params['id'],
						$f3->get('POST.form_data')
					)
				);
			}
			elseif ( isset($_POST['newPage']) )
			{
				if ( FALSE === $newID = $this->model->addCustompage( $f3->get('POST.newPage') ) )
					$f3->set('form_error', [ $f3->get('LN__DuplicateLabel'), $f3->get('POST.newPage') ] );
				else
				{
					$f3->reroute('/adminCP/home/custompages/id='.$newID, false);
					exit;
				}
			}
		}

		if( isset ($params['id']) )
		{
			if ( NULL !== $data = $this->model->loadCustompage($params['id']) )
			{
				$data['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
				$this->buffer( $this->template->custompageEdit($data, @$params['returnpath']) );
				return;
			}
			else $f3->set('form_error', "__failedLoad");
		}

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
		// sort icons
		$sort['data']['id'] = 	 ( $sort['direction']=="desc" OR $sort['link']!='id' ) ? "asc" : "desc";
		$sort['data']['label'] = ( $sort['direction']=="desc" OR $sort['link']!='label' ) ? "asc" : "desc";
		$sort['data']['title'] = ( $sort['direction']=="desc" OR $sort['link']!='title' ) ? "asc" : "desc";

		$data = $this->model->listCustompages($page, $sort);

		$this->buffer ( $this->template->custompageList($data, $sort) );
	}

	protected function homeLogs(\Base $f3, array $params): void
	{
		if ( !$this->model->checkAccess("home/logs") )
		{
			$this->buffer( "__NoAccess" );
			return;
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Logs') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Logs') );

		$menuCount = $this->model->logGetCount();

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		$sub = isset($params['type'])?$params['type']:FALSE;

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


		$this->buffer
		(
			$this->template->logList
			(
				$this->model->logGetData($sub, $page, $sort),
				$menuCount,
				[],
				$sub
			)
		);
	}

	protected function homePolls(\Base $f3, array $params): string
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Polls') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Polls') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset($params['delete']) )
		{
			if ( $this->model->pollDelete( (int)$params['delete'] ) )
			{
				$_SESSION['lastAction'] = [ "deleteResult" => 1 ];
				$f3->reroute('/adminCP/home/polls', false);
				exit;
			}
			else $f3->set('deleteResult', 0);
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$f3->set
				(
					'saveResult',
					$this->model->pollSave
					(
						$params['id'],
						$f3->get('POST.form_data')
					)
				);
			}
			elseif ( isset($_POST['newPoll']) )
			{
				// form sent with empty field
				if ( empty($_POST['newPoll']) )
				{
					$f3->set ( 'addResult', 0 );
					$f3->set ( 'addReason', $f3->get('LN__Error_PollNoQuestion') );
				}
				// save data and reroute to edit form
				elseif ( FALSE !== $newID = $this->model->pollAdd( $f3->get('POST.newPoll') ) )
				{
					$_SESSION['lastAction'] = [ "addResult" => 1 ];
					$f3->reroute('/adminCP/home/polls/id='.$newID, false);
					exit;
				}
				else
				{
					$f3->set ( 'addResult', 0 );
					//$f3->set ( 'addReason', $f3->get('LN__') );
				}
			}
		}

		if( isset ($params['id']) )
		{
			$data = $this->model->pollLoad((int)$params['id']);
			if ( sizeof($data)==0 ) $f3->reroute('/adminCP/home/polls', false);
			return $this->template->pollEdit($data, @$params['returnpath']);
		}

		// search/browse
		$allow_order = array (
				"id"		=>	"id",
				"open"		=>	"start_date",
				"close"		=>	"end_date",
		);

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";
		// sort icons
		$sort['data']['id'] = 	 ( $sort['direction']=="desc" OR $sort['link']!='id' ) ? "asc" : "desc";
		$sort['data']['open'] =  ( $sort['direction']=="desc" OR $sort['link']!='open' ) ? "asc" : "desc";
		$sort['data']['close'] = ( $sort['direction']=="desc" OR $sort['link']!='close' ) ? "asc" : "desc";

		return $this->template->pollList
				(
					$this->model->pollList($page, $sort),
					$sort
				);
		}

	protected function homeShoutbox(\Base $f3, array $params): void
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
			if ( $this->model->shoutDelete( (int)$params['delete'] ) )
			{
				$_SESSION['lastAction'] = [ "deleteResult" => 1 ];
				$f3->reroute("/adminCP/home/shoutbox/order={$sort['order']},{$sort['direction']}/page={$page}", false);
				exit;
			}
			else $f3->set('deleteResult', 0);
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$f3->set
				(
					'saveResult',
					$this->model->shoutSave
					(
						$params['id'],
						$f3->get('POST.form_data')
					)
				);
			}
		}

		if( isset ($params['id']) AND $f3->get('saveResult')=="" )
		{
			if ( NULL !== $data = $this->model->shoutLoad($params['id']) )
			{
				$data['raw'] = $params['raw'] ?? NULL;
				$this->buffer( $this->template->shoutEdit($data, $sort, $page) );
				return;
			}
			else $f3->set('loadResult', 0);
		}

		$this->buffer
		(
			$this->template->shoutList
			(
				$this->model->shoutList($page, $sort),
				$sort
			)
		);
	}

	protected function homeMaintenance(\Base $f3, array $params): string
	{
		$this->model->maintenanceRecountCategories();
		return "";
	}

	protected function homeNews(\Base $f3, array $params): void
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_News') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_News') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset($params['delete']) )
		{
			if ( $this->model->newsDelete( (int)$params['delete'] ) )
			{
				$_SESSION['lastAction'] = [ "deleteResult" => 1 ];
				$f3->reroute('/adminCP/home/news', false);
				exit;
			}
			else $f3->set('deleteResult', 0);
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$f3->set
				(
					'saveResult',
					$this->model->newsSave
					(
						$params['id'],
						$f3->get('POST.form_data')
					)
				);
			}
			elseif ( isset($_POST['newHeadline']) )
			{
				// form sent with empty field
				if ( empty($_POST['newHeadline']) )
				{
					$f3->set ( 'addResult', 0 );
					$f3->set ( 'addReason', $f3->get('LN__Error_NewsNoHeadline') );
				}
				// save data and reroute to edit form
				elseif ( FALSE !== $newID = $this->model->newsAdd( $f3->get('POST.newHeadline') ) )
				{
					$_SESSION['lastAction'] = [ "addResult" => 1 ];
					$f3->reroute('/adminCP/home/news/id='.$newID, false);
					exit;
				}
				// recover from DB error
				else
				{
					$f3->set ( 'addResult', 0 );
					//$f3->set ( 'addReason', $f3->get('LN__') );
				}
			}
		}

		if( isset ($params['id']) )
		{
			if ( NULL !== $data = $this->model->newsLoad($params['id']) )
			{
				$data['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
				$this->buffer( $this->template->newsEdit($data, @$params['returnpath']) );
				return;
			}
			else $f3->set('loadResult', 0);
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
		// sort icons
		$sort['data']['id'] =	  ( $sort['direction']=="desc" OR $sort['link']!='id' ) ? "asc" : "desc";
		$sort['data']['date'] =   ( $sort['direction']=="desc" OR $sort['link']!='date' ) ? "asc" : "desc";
		$sort['data']['title'] =  ( $sort['direction']=="desc" OR $sort['link']!='title' ) ? "asc" : "desc";
		$sort['data']['author'] = ( $sort['direction']=="desc" OR $sort['link']!='author' ) ? "asc" : "desc";

		$this->buffer
		(
			$this->template->newsList
			(
				$this->model->newsList($page, $sort),
				$sort
			)
		);
	}

	public function __members(\Base $f3, array $params)
	{
		// declare module
		$this->moduleBase = "members";
		// build menu and access list
		$this->menuShow($this->moduleBase, @$params['module']);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Members') );

		switch( $this->moduleInit([ "add", "edit", "pending", "groups", "profile", "team" ], @$params['module']) )
		{
			case "add":
				$this->buffer( $this->membersAdd($f3, $params) );
				break;
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
				$this->buffer( $this->template->access() );
		}
	}

	public function membersAjax(\Base $f3, array $params): void
	{
		$data = [];
		if ( empty($params['module']) ) return;

		$post = $f3->get('POST');

		if ( $params['module']=="search" )
			$data = $this->model->ajax("userSearch", $post);

		echo json_encode($data);
		exit;
	}

	protected function membersAdd(\Base $f3, array $params): string
	{
		if ( isset($_POST) )
		{
			$post = $f3->get('POST');
			if ( !empty($post['new_name']) )
				$f3->set("addResult", $this->model->memberAdd($post) );
		}
		return $this->template->userAddForm();
	}

	protected function membersEdit(\Base $f3, array $params): string
	{
		if( isset($params['*']) ) $params = $this->parametric($params['*']);

		if( empty($params['uid']) OR !is_numeric($params['uid']) )
			return $this->membersEditSearchForm($f3, $params);

		elseif( isset($_POST) AND sizeof($_POST)>0 )
		{
			if(isset($_POST['data']))
			{
				$data = $f3->get('POST.data');
				$i = $this->model->memberDataSave($params['uid'], $data);

				if(isset($_POST['group']) AND sizeof($_POST['group'])>0)
					$this->model->memberGroupSave($params['uid'], $f3->get('POST.group'));
			}
		}

		if( FALSE === $memberdata = $this->model->loadUser($params['uid']) )
			return "__failed";

		return $this->template->userEdit($memberdata, $params['returnpath']);
	}

	protected function membersEditSearchForm(\Base $f3, array $params): string
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
				"name"		=>	"username",
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
		return $this->template->userEditList($data, $sort, $search);
	}

	protected function membersHome(\Base $f3, array $feedback = [ NULL, NULL ]): void
	{
		if ( isset($_POST['form_data']) )
		{
			$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Members') );
		$data['General'] = $this->model->settingsFields('members_general');
		$this->buffer( $this->template->settingsFields($data, "members/home", $feedback) );
	}

	protected function membersTeam(\Base $f3): void
	{
		$team = $this->model->listTeam();
		$this->buffer( $this->template->userTeamList($team) );
	}

	protected function membersProfile(\Base $f3, array $params)
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

		$this->buffer ( $this->template->userFieldsList( $data ) );
	}

	public function __modules(\Base $f3, array $params)
	{
		// declare module
		$this->moduleBase = "modules";
		// build menu and access list
		$this->menuShow($this->moduleBase, @$params['module']);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Modules') );

	}

	public function __settings(\Base $f3, array $params ): void
	{
		$data = [];
		// declare module
		$this->moduleBase = "settings";
		// build menu and access list
		$this->menuShow($this->moduleBase, @$params['module']);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Settings') );
		// implement a Cache killswitch:
		if(@$params['module']=="cachedrop") \Cache::instance()->clear('config');

		switch( $this->moduleInit([ "modules", "datetime", "server", "registration", "security", "screening", "language", "layout" ], @$params['module']) )
		{
			case "modules":
				$this->response->addTitle( $f3->get('LN__AdminMenu_Modules') );
				$f3->set('title_h3', $f3->get('LN__AdminMenu_Modules') );
				$modules['shoutbox'] = $this->model->settingsFields('modules_shoutbox');
				$extra = $this->settingsModules( $f3, $params, $modules );
				break;
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
				$this->buffer( $this->template->access() );
		}
		if (sizeof($data)) $this->buffer( $this->template->settingsFields($data, "settings/".$params['module'], $this->feedback) );
		if (isset($extra)) $this->buffer( $extra );
	}

	public function __settingsSave(\Base $f3, array $params): void
	{
		if (empty($params['module']))
		{
			$f3->reroute('/adminCP/settings', false);
			exit;
		}

		if ( isset($_POST['form_data']) )
			// Save data from the generic created forms
			$this->feedback = $this->model->saveKeys($f3->get('POST.form_data'));
		else
			// Sava data from special forms (language, layout)
			$this->feedback = $this->settingsSaveLLData($f3, $params);

		$this->__settings($f3, $params);
	}

	protected function settingsModules(\Base $f3, array $params, array $modules): void
	{
		// sweep params for a selected module
		$params = $this->parametric($params['*']??"");
		// get the active modules configuration
		$activeModules = \Config::getPublic('optional_modules');

		// check if an active module is selected *todo*
		if ( @$activeModules[$params[0]] )
		{
			$this->buffer (print_r($params, 1));
			$this->buffer (print_r($activeModules, 1));
		}

		//\Base::instance()->set('menu_upper', $activeModules);
		// create setting fields for the optional modules
		foreach ( $activeModules as $active => $status )
		{
			if ( isset($modules[$active]) )
			{
				$this->buffer( $this->template->settingsFields([ "Shoutbox" => $modules[$active]], "settings/modules", $this->feedback) );
			}
		}
	}

	protected function settingsDateTime(\Base $f3, array $params): string
	{
		return $this->template->settingsDateTime();
	}

 	protected function settingsLanguage(\Base $f3, array $params) : string
	{
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Language') );

		$languageConfig = $this->model->getLanguageConfig();

		$files = glob("./languages/*.xml"); // */
		foreach ( $files as $file)
		{
			$data = (array)simplexml_load_file($file);
			$data['active'] = array_key_exists($data['locale'], $languageConfig['language_available']);
			$languageFiles[] = $data;
		}

		return $this->template->language($languageFiles, $languageConfig);
	}

 	protected function settingsLayout(\Base $f3, array $params): string
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

	protected function settingsSaveLLData(\Base $f3, array $params): array
	{
		if ( $params['module'] == "language" )
			return $this->model->saveLanguage($f3->get('POST.form_special'));

		if ( $params['module'] == "layout" )
			return $this->model->saveLayout($f3->get('POST.form_special'));
	}

	protected function settingsServer(\Base $f3, array &$data): void
	{
		if ( !$this->model->checkAccess("settings/server") )
		{
			$this->buffer( "__NoAccess" );
			return;
		}
		$this->response->addTitle( $f3->get('LN__AdminMenu_Server') );
		$data['Mail'] = $this->model->settingsFields('settings_mail');
		$data['Maintenance'] = $this->model->settingsFields('settings_maintenance');
		$data['Report'] = $this->model->settingsFields('settings_report');
	}

	public function __stories(\Base $f3, array $params)
	{
		// declare module
		$this->moduleBase = "stories";
		// build menu and access list
		$this->menuShow($this->moduleBase, @$params['module']);
		// add module title
		$this->response->addTitle( $f3->get('LN__AdminMenu_Stories') );

		switch( $this->moduleInit([ "pending", "edit", "add", "featured", "recommendations", "series", "collections" ], @$params['module']) )
		{
			case "pending":
				$this->buffer( $this->storiesPending($f3, $params) );
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
			case "featured":
				$this->storiesFeatured($f3, $params);
				break;
			case "recommendations":
				$this->storiesRecommendations($f3, $params);
				break;
			case "series":
			case "collections":
				$this->storiesCollections($f3, $params);
				break;
			default:
				$this->buffer( $this->template->access() );
		}
	}

	public function storiesAjax(\Base $f3, array $params): void
	{
		$data = [];
		if ( empty($params['module']) ) return;

		$post = $f3->get('POST');

		if ( $params['module']=="search" )
			$data = $this->model->ajax("storySearch", $post);

		elseif ( $params['module']=="editMeta" )
			$data = $this->model->ajax("editMeta", $post, $this->parametric($params['*']??""));

		elseif ( $params['module']=="featured" )
			$data = $this->model->ajax("storySearch", $post);

		elseif ( $params['module']=="sort" )
		{
			$data = $this->model->ajax("storySort", $post);
		}

		echo json_encode($data);
		exit;
	}

	protected function storiesPending(\Base $f3, array $params): string
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Stories_Pending') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Stories_Pending') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset($params['validate']) )
		{
			if ( isset($params['story']) AND NULL!==$situation=$this->model->storyLoadPending($params['story']) )
			// need a story id, it must be valid and not blocked by chapters that are not up for validation yet
			{
				if ( $situation['state']=='storyOnly' )
				// only need to validate the story, don't care if any chapter is added to the query
				{
					// change story validation status
					if ( 1 == $this->model->storyValidatePending($situation['story']['sid']) )
					{
						// make note of the success
						$_SESSION['lastAction'] = [ 'storyValidation' => 'success' ];
						// nothing else to do, reroute to the pending list
						if ( isset($params['returnpath']) )	$f3->reroute($params['returnpath'], false);
						else								$f3->reroute("/adminCP/stories/pending", false);
						exit;
					}
					else
					{
						// make note of an error event
						$_SESSION['lastAction'] = [ 'storyValidation' => 'dberror' ];
						// return to story
						$f3->reroute("/adminCP/stories/pending/story={$situation['story']['sid']};returnpath={$params['returnpath']}", false);
						exit;
					}
				}
				elseif ( isset($params['chapter']) AND $situation['story']['chapid']==$params['chapter'] )
				// either chapterFirst or chapterOnly, so all we need is a chapter number and it must be tagged 'first'
				{
					// validate chapter-story association
					if ( 1 == $this->model->storyValidatePending($situation['story']['sid'], $situation['story']['chapid']) )
					{
						// make note of the success
						$_SESSION['lastAction'] = [ 'chapterValidation' => 'success' ];
					}
					else
					{
						// make note of an error event
						$_SESSION['lastAction'] = [ 'chapterValidation' => 'dberror' ];
					}
					// return to story, if the story should require no further moderation this will lead to the overview
					$f3->reroute("/adminCP/stories/pending/story={$situation['story']['sid']};returnpath={$params['returnpath']}", false);
					exit;
				}
			}
			else
			{
				if ( isset($params['returnpath']) )	$f3->reroute($params['returnpath'], false);
				else								$f3->reroute("/adminCP/stories/pending", false);
				exit;
			}
		}

		if( isset ($params['story']) )
		{
			if ( NULL !== $data = $this->model->storyLoadPending($params['story']) )
			// We have a story that requires validation, let's get to it
			{
				if ( isset($params['chapter']) AND $data['story']['chapid']==$params['chapter'] )
				// we have a chapter selected and it is marked as first one to be validated
				{
					// Load chapter text
					$chapterText = $this->model->getChapterText( $data['story']['sid'], $data['story']['chap_inorder'], FALSE );
					return $this->template->storyValidateChapter($data['story'], $chapterText, $params['returnpath']);
				}

				// no chapter selected, let's look at the story overview
				return $this->template->storyValidatePending($data, $params['returnpath']);
			}
			else
			{
				if ( isset($params['returnpath']) )	$f3->reroute($params['returnpath'], false);
				else								$f3->reroute("/adminCP/stories/pending", false);
				exit;
			}
		}

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

		return $this->template->storyListPending
				(
					$this->model->storyListPending
					(
						$page,
						$sort
					),
					$sort
				);
	}

	protected function storiesEdit(\Base $f3, array $params): void
	{
		if ( isset($params['*']) )
			$params = $this->parametric($params['*']);

		if ( empty($params['story']) )
		{
			// Select story form
			$this->buffer( $this->template->storySearch() );
			return;
		}
		elseif ( [] !== $storyInfo = $this->model->storyLoadInfo((int)$params['story']) )
		{
			// save data
			if (isset($_POST) and sizeof($_POST)>0 )
			{
				// so we want to delete something
				if( isset($params['delete']) )
				{
					// let's assume this will work
					$reroute['base']  = "/adminCP/stories/edit";
					$reroute['story'] = "/story={$params['story']}";
					if ( isset($params['chapter']) ) $reroute['chapter'] = "/chapter=".$params['chapter'];
					$reroute['editor'] = "/editor=".$params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
					if ( isset($params['returnpath']) ) $reroute['returnpath'] = ";returnpath=".$params['returnpath'];

					if ( ""!=$f3->get('POST.confirm_delete') )
					{
						// delete a chapter
						if ( isset($params['chapter']) )
						{
							// when deleting a chapter, always return to chapter overview
							if ( 0 == $i = $this->model->chapterDelete( $params['story'], $params['chapter'] ) )
								$_SESSION['lastAction']['delete_error'] = TRUE;
							else
							{
								$_SESSION['lastAction']['delete_success'] = TRUE;
								unset($reroute['chapter']);
							}
						}
						// delete the whole story
						else
						{
							if ( 0 == $i = $this->model->storyDelete( $params['story'] ) )
								$_SESSION['lastAction']['delete_error'] = TRUE;
							// since the story is now gone, we must fall back to the story listing
							else
							{
								$_SESSION['lastAction']['delete_success'] = TRUE;
								if (isset($params['returnpath']))
									$reroute = [$params['returnpath']];
								else
									$reroute = $reroute['base'];
							}
						}
					}
					// but it seems we are not really sure ...
					else
					{
						$_SESSION['lastAction']['delete_confirm'] = TRUE;
					}
					$f3->reroute(implode("",$reroute),FALSE);
					exit;
				}
				else
				{
					if ( isset($params['chapter']) )
					{
						if ( 0 < $i = $this->model->chapterSave($params['chapter'], $f3->get('POST.form'), 'A') )
							$f3->set('save_success', $i);
					}
					else
					{

						if ( 0 < $i = $this->model->storySaveChanges($params['story'], $f3->get('POST.form')) )
							$_SESSION['lastAction']['save_success'] = $i;
						$f3->reroute("/adminCP/stories/edit/story={$storyInfo['sid']};returnpath=".$params['returnpath'], false);
						exit;
					}
				}
			}

			// Chapter list is always needed, load after POST to catch chapter name changes
			$chapterList = $this->model->chapterLoadList($storyInfo['sid']);

			if ( isset($params['chapter']) )
			{
				if ( $params['chapter']=="new" )
				{
					$newChapterID = $this->model->chapterAdd($storyInfo['sid']);
					$reroute = "/adminCP/stories/edit/story={$storyInfo['sid']}/chapter={$newChapterID}"; //;returnpath=".$params['returnpath'];
					$f3->reroute($reroute, false);
					exit;
				}
				// make sure this chapter actually exists for this story
				elseif ( [] !== $chapterInfo = $this->model->chapterLoad($storyInfo['sid'],(int)$params['chapter']) )
				{
					// abusing $chapterData to carry a few more details
					$chapterInfo['storytitle'] = $storyInfo['title'];
					// figure out if we want a visual editor
					$chapterInfo['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");

					$this->buffer( $this->template->storyChapterEdit($chapterInfo,$chapterList) );
				}
				else
				{
					$f3->reroute('/adminCP/stories/edit/story='.$storyInfo['sid'], false);
					exit;
				}
			}
			else
			{
				$storyInfo['returnpath'] = $params['returnpath'];
				// figure out if we want a visual editor
				$storyInfo['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");

				$chapterList = $this->model->chapterLoadList($storyInfo['sid']);
				$prePopulate = $this->model->storyEditPrePop($storyInfo);
				$this->buffer( $this->template->storyMetaEdit($storyInfo,$chapterList,$prePopulate) );
			}
		}
		else
		{
			$_SESSION['lastAction']['load_error'] = TRUE;
			$f3->reroute("adminCP/stories/edit", false);
		}
	}

	protected function storiesAdd(\Base $f3, array $params): void
	{
		if ( isset($_POST['form']) && !empty($_POST['form']['new_title']) )
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

	protected function storiesHome(\Base $f3, array $params)
	{
		$this->buffer( \View\Base::stub() );
	}

	protected function storiesFeatured(\Base $f3, array $params): void
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Featured') );
		//$allowedSubs = $this->menuShowUpper("stories/featured");
		$this->menuShowUpper("stories/featured");

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset( $_POST['sid'] ) )
			$params['sid'] = (int)$_POST['sid'];

		if (isset($_POST['form_data']))
			$this->model->featuredSave($params['sid'], $f3->get('POST.form_data') );

		// delete through the dialog box
		if( isset($params['delete']) AND ( $_POST['confirm_delete'] ?? FALSE ) )
		{
			$this->model->featuredDelete((int)$params['delete']);
			$f3->reroute($params['returnpath']==""?"/adminCP/stories/featured":$params['returnpath'], false);
			exit;
		}

		if( isset ($params['sid']) )
		{
			// load the selected feature
			if ( $data = $this->model->featuredLoad($params['sid']) )
				// show the edit template if data was returned
				$this->buffer( $this->template->featuredEdit($data, @$params['returnpath']) );
			// seems we hit a blank, let's try again
			else
			{
				$f3->reroute($params['returnpath']==""?"/adminCP/stories/featured":$params['returnpath'], false);
				exit;
			}
		}
		else
		{
			$select = $params['select'] ?? "current";

			$allow_order = array (
				"id"		=>	"S.sid",
				"title"		=>	"S.title",
			);

			// sort order
			$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "title";
			$sort["order"]		= $allow_order[$sort["link"]];
			$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";

			$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

			$data = $this->model->featuredList($page, $sort, $select);
			$this->buffer( $this->template->featuredList($data, $sort, $select) );

			return;
		}

	}

	protected function storiesCollections(\Base $f3, array $params)
	{
		if ( $params['module']=="collections" )
		{
			$this->response->addTitle( $f3->get('LN__AdminMenu_Collections') );
			$f3->set('title_h3', $f3->get('LN__AdminMenu_Collections') );
			$module = "collections";
		}
		else
		{
			$this->response->addTitle( $f3->get('LN__AdminMenu_Series') );
			$f3->set('title_h3', $f3->get('LN__AdminMenu_Series') );
			$module = "series";
		}

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		// so we want to delete from inside the edit form
		if( isset($params['delete']) )
		{
			// default return point
			$reroute = empty($params['returnpath']) ? "/adminCP/stories/{$module}" : $params['returnpath'];

			if ( ""!=$f3->get('POST.confirm_delete') )
			{
				if ( 0 == $i = $this->model->collectionDelete( $params['id'] ) )
				{
					$_SESSION['lastAction']['delete_error'] = TRUE;
				}
				else
				{
					$_SESSION['lastAction']['delete_success'] = $i;
				}
			}
			// but it seems we are not really sure ...
			else
			{
				$reroute  = "/adminCP/stories/{$module}/id={$params['id']}";
				if ( isset($params['returnpath']) ) $reroute .= ";returnpath=".$params['returnpath'];
				$_SESSION['lastAction']['delete_confirm'] = TRUE;
			}
			$f3->reroute($reroute,FALSE);
			exit;
		}

		if (isset($_POST['form_data']))
		{
			if ( 0 < $i = $this->model->collectionSave($params['id'], $f3->get('POST.form_data') ) )
				$f3->set('save_success', $i);

			if ( isset($_POST['form_data']['changetype']) )
			{
				$reroute = "/adminCP/stories/{$module}";
				foreach($params as $key => $param)
				{
					if ($key!="returnpath")
						$reroute .= "/{$key}={$param}";
				}
				$f3->reroute($reroute,FALSE);
				exit;
			}
		}
		elseif (isset($_POST['new_data']))
		{
			$params['id'] = $this->model->collectionAdd($f3->get('POST.new_data') );
		}
		elseif (isset($_POST['story-add']))
		{
			$this->model->collectionItemsAdd($params['id'], $f3->get('POST.story-add') );
		}

		if( isset ($params['id']) )
		{
			// edit the elements of the collection/series
			if ( isset ($params['items']) AND NULL !== $data = $this->model->collectionLoadItems($params['id']) )
			{
				$data['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
				$this->buffer( $this->template->collectionItems($data, $module, @$params['returnpath']) );
				return;
			}
			// edit the collection/series
			elseif ( [] !== $data = $this->model->collectionLoad($params['id']) )
			{
				$data['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
				$this->buffer( $this->template->collectionEdit($data, $this->model->storyEditPrePop($data), $module, @$params['returnpath']) );
				return;
			}
			else $f3->set('load_error', TRUE);
		}

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
				"id"		=>	"Coll.collid",
				"date"		=>	"date",
				"title"		=>	"title",
				"author"	=>	"author",
				"stories"	=>  "stories"
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";

		$this->buffer
		(
			$this->template->collectionsList
			(
				$this->model->collectionsList($page, $sort, $module),
				$sort,
				$module
			)
		);
	}

	protected function storiesRecommendations(\Base $f3, array $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Recommendations') );
		$this->menuShowUpper("stories/recommendations");

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		// so we want to delete from inside the edit form
		if( isset($params['delete']) )
		{
			// default return point
			$reroute = empty($params['returnpath']) ? "/adminCP/stories/recommendations" : $params['returnpath'];

			if ( ""!=$f3->get('POST.confirm_delete') )
			{
				if ( 0 == $i = $this->model->recommendationDelete( $params['id'] ) )
				{
					$_SESSION['lastAction']['delete_error'] = TRUE;
				}
				else
				{
					$_SESSION['lastAction']['delete_success'] = $i;
				}
			}
			// but it seems we are not really sure ...
			else
			{
				$reroute = "/adminCP/stories/recommendations/id={$params['id']}";
				if ( isset($params['returnpath']) ) $reroute .= ";returnpath=".$params['returnpath'];
				$_SESSION['lastAction']['delete_confirm'] = TRUE;
			}
			$f3->reroute($reroute,FALSE);
			exit;
		}

		elseif (isset($_POST['form_data']))
		{
			if ( 0 < $i = $this->model->recommendationSave($params['id'], $f3->get('POST.form_data') ) )
				$f3->set('save_success', $i);
		}
		elseif (isset($_POST['new_data']))
		{
			$params['id'] = $this->model->recommendationAdd($f3->get('POST.new_data') );
		}

		if( isset ($params['id']) )
		{
			if ( [] !== $data = $this->model->recommendationLoad($params['id']) )
			{
				// despite 0 not being an official code, AO3 will reply this way when a story is no longer found.
				if ( @$data['lookup']['http_code']===0 )
				{
					$f3->set('lookup_error', 0);
				}

				// server has found something
				elseif ( @$data['lookup']['http_code']==200 )
					$f3->set('lookup_success', 1);
				// server has found something but it's not the plain 'OK' code
				elseif ( @$data['lookup']['http_code']>200 AND @$data['lookup']['http_code']<300 )
					$f3->set('lookup_success', 0);

				// Server replies with a permanent moved status
				elseif ( @$data['lookup']['http_code']==301 OR @$data['lookup']['http_code']==308 )
				{
					// if the only difference is a change from http to https, silently alter the value and inform the user.
					if ( str_replace("http:", "https:", $data['url']) == @$data['lookup']['redirect_url'] OR
							"https://".$data['url'] == $data['lookup']['redirect_url'] )
					{
						$data['url'] = $data['lookup']['redirect_url'];
						$f3->set('lookup_moved', 1);
					}
					else
					{
						$f3->set('lookup_moved', 0);
					}
				}

				elseif ( @$data['lookup']['http_code']>=400 )
				{
					$f3->set('lookup_error', 0);
				}

				$data['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
				$this->buffer ( $this->template->recommendationEdit($data, $this->model->storyEditPrePop($data), @$params['returnpath']) );
				return;
			}
			else
			{
				$reroute = isset($params['returnpath']) ? $params['returnpath'] : "/adminCP/stories/recommendations";
				$f3->reroute($reroute,FALSE);
				exit;
			}
		}

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
				"id"		=>	"Rec.recid",
				"title"		=>	"title",
				"author"	=>	"maintainer",
				"date"		=>	"date",
				"rating"	=>	"R.inorder",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";

		$this->buffer
		(
			$this->template->recommendationList
			(
				$this->model->recommendationList($page, $sort),
				$sort
			)
		);
	}

}
