<?php

namespace Controller;

class AdminCP_Archive extends AdminCP
{

	public function index(\Base $f3, $params, $feedback = [ NULL, NULL ] )
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Archive') );
		$f3->set('title_h1', $f3->get('LN__AdminMenu_Archive') );
		$this->showMenu("archive");

		switch( @$params['module'] )
		{
			case "featured":
				$this->featured($f3, $params, $feedback);
				break;
			case "tags":
				$this->tagsIndex($f3, $params, $feedback);
				break;
			case "categories":
				$this->categories($f3, $params);
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
	
	protected function featured(\Base $f3, $params, $feedback)
	{
		
	}
	
	protected function tagsIndex(\Base $f3, $params, $feedback)
	{
		$p = [];
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
		elseif ( isset($params['delete']) )
		{
			$this->model->deleteTag( (int)$params['delete'] );
			$f3->reroute('/adminCP/archive/tags/edit', false);
		}

		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );
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
		if ( isset($params[2]) ) $params = $this->parametric($params[2]);

		if  ( isset($_POST) AND sizeof($_POST)>0 )
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
		elseif ( isset($params['delete']) )
		{
			if ( $this->model->deleteTagGroup( (int)$params['delete'] ) )
				$f3->reroute('/adminCP/archive/tags/groups', false);
			else $f3->set('form_error', "__failedDelete");
		}

		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );
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
		if ( isset($params[2]) ) $params = $this->parametric($params[2]);
		
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

				// build category stats
				// \Model\Routines::instance()->cacheCategories($newID);
				// rebuild parent stats
				// \Model\Routines::instance()->cacheCategories($parent_cid);
			}
			// add category
			
			
			// re-align order
			//$parent = $this->model->moveCategory( $params['move'][1] );
			
			// determine parent_cid
			
		}
		elseif ( isset($params['delete']) )
		{
			$data = $this->model->loadCategory((int)$params['delete']);
			$data['stats'] = unserialize($data['stats']);

			if ( $data['stats']['sub']===NULL AND $data['stats']['count']==0 )
			{
				if ( FALSE === $this->model->deleteCategory( (int)$params['delete'] ) )
					$errors = '__failDeleteCategory: '.$data['category'];
				else
					$changes = '__deletedCategory: '.$data['category'];
			}
			else
			{
				$errors = '__failDeleteCategory: '.$data['category'];
			}
			//$this->categoriesDelete($params['delete']);
		}
		elseif  ( isset($_POST) AND sizeof($_POST)>0 )
		{
			if ( isset($_POST['form_data']) )
			{
				$changes = $this->model->saveCategory($params['id'], $f3->get('POST.form_data') );
			}
			
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
			$data['stats'] = unserialize($data['stats']);
			$data['errors'] = @$errors;
			$data['changes'] = @$changes;
			$this->buffer( \View\AdminCP::editCategory($data) );
			return TRUE;
		}

		\Registry::get('VIEW')->javascript( 'head', TRUE, "controlpanel.js.php?sub=confirmDelete" );
		
		$data = $this->model->categoriesListFlat();
		$feedback['errors'] = @$errors;
		$feedback['changes'] = @$changes;

		$this->buffer ( \View\AdminCP::listCategories($data, $feedback) );
	}
	
	protected function categoriesDelete($cid)
	{
		$data = $this->model->loadCategory($cid);
		$data['stats'] = unserialize($data['stats']);
		if ( $data['stats']['sub']==NULL )
		{
			// Has no sub-category
			$this->buffer ( "__CanDelete" );
		}
		else
		{
			// Still has sub-categories
			$this->buffer ( "__CannotDelete" );
		}
		$this->buffer ( "<br>".print_r($data,1) );
		//var_dump($data['stats']['sub']);
		//$parent = $this->model->moveCategory( NULL,NULL,1 );
		//  \Model\Routines::instance()->cacheCategories();
	}

}