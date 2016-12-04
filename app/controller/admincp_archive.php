<?php

namespace Controller;

class AdminCP_Archive extends AdminCP
{
	var $moduleBase = "archive";
	var $submodules = [ "submit", "featured", "characters", "tags", "categories" ];

	public function index(\Base $f3, $params, $feedback = [ NULL, NULL ] )
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Archive') );
		$f3->set('title_h1', $f3->get('LN__AdminMenu_Archive') );

		switch( $this->moduleInit(@$params['module']) )
		{
			case "submit":
				$this->submit($f3, $feedback);
				break;
			case "featured":
				$this->featured($f3, $params, $feedback);
				break;
			case "characters":
				$this->characters($f3, $params, $feedback);
				break;
			case "tags":
				$this->tagsIndex($f3, $params, $feedback);
				break;
			case "categories":
				$this->categories($f3, $params);
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
		
		if ( $params['module']=="tags" )
		{
			$data = $this->model->ajax("tags", $post);
		}
		elseif ( $params['module']=="featured" )
		{
			$data = $this->model->ajax("storySearch", $post);
		}
		echo json_encode($data);
		exit;
	}

	protected function home(\Base $f3, $feedback = [ NULL, NULL ])
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
	
	protected function submit(\Base $f3, $feedback = [ NULL, NULL ])
	{
		if ( isset($_POST['form_data']) )
		{
			$feedback = $this->model->saveKeys($f3->get('POST.form_data'));
		}
		//$this->response->addTitle( $f3->get('LN__AdminMenu_Archive') );
		$this->response->addTitle( $f3->get('LN__AdminMenu_Submission') );
		$data['Stories'] = $this->model->settingsFields('archive_submit');
		$data['Reviews'] = $this->model->settingsFields('archive_reviews');
		$this->buffer( \View\AdminCP::settingsFields($data, "archive/submit", $feedback) );
	}
	
	
	protected function featured(\Base $f3, $params, $feedback)
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

	protected function characters(\Base $f3, $params, $feedback)
	{
		$this->buffer( \View\Base::stub() );
	}
	
	protected function tagsIndex(\Base $f3, $params, $feedback)
	{
		//$p = [];
		$this->response->addTitle( $f3->get('LN__AdminMenu_Tags') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Tags') );

		$allowedSubs = $this->showMenuUpper("archive/tags");

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		
		if ( isset($params['groups']) )
			$this->tagsGroups($f3, $params);
		
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
			$this->tagsEdit($f3, $params);
	}
	
	protected function tagsEdit(\Base $f3, $params)
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
		$this->buffer ( \View\AdminCP::listTags($data, $sort) );
	}
	
	protected function tagsGroups(\Base $f3, $params)
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
		$this->buffer ( \View\AdminCP::listTagGroups($data, $sort) );
	}
	
	protected function categories(\Base $f3, $params)
	{
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		
		$this->response->addTitle( $f3->get('LN__AdminMenu_Categories') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Categories') );

		if ( isset($params['move']) )
		{
			$parent = $this->model->moveCategory( $params['move'][1], $params['move'][0] );
			\Model\Routines::instance()->cacheCategories($parent);
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
	
}
