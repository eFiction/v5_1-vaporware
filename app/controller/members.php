<?php

namespace Controller;

class Members extends Base {
	
	public function __construct()
	{
		$this->model = \Model\Members::instance();
		$this->template = new \View\Members();
		$this->f3 = \Base::instance();
	}

	public function beforeroute()
	{
		parent::beforeroute();
		$this->template->addTitle( \Base::instance()->get('LN__AS_Members') );
	}

	public function index(\Base $f3, array $params)//: void
	{
		if ( isset($_POST['form_data']) )
		{
			//*todo*
			
		}
		
		else
			$data = $this->listing($params);

		$this->buffer ($data);
	}
	
	public function profile(\Base $f3, array $params)//: void
	{
		// load user
		$user_data = $this->model->memberData($params['user']);

		// if the model returned no data, go to the memberlist
		if ( sizeof($user_data) == 0 )
		{
			$f3->reroute("/members", false);
			exit;
		}

		// still in the game, let's check for extra options
		$options = ( isset($params['*']) ) ? $this->parametric($params['*']) : [];
		// check what to do

		switch ( @$params['selection'] )
		{
			case "profile":
				$this->buffer ( $this->template->profile($user_data) );
				break;
			case "bookmarks":
			case "favourites":
				$this->memberBookFav( $params['selection'], $user_data, $options );
				break;
			case "series":
			case "collections":
				$collections_data = $this->model->memberCollections($user_data, $params['selection'], $options);
				$this->buffer ( $this->template->collections($user_data, $params['selection'], $collections_data) );
				break;
			case "stories":
			default:
				$story_data = $this->model->memberStories($user_data, $options);
				$this->buffer( $this->template->stories($user_data, $story_data) );
		}

	}
	
	protected function memberBookFav( string $selection, array $user_data, array $params )
	{
		// get the page
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// load data from the proper model
		$data = ( $selection == "bookmarks" ) ?
			$this->model->loadBookmarks($user_data, $params, $page)
			:
			$this->model->loadFavourites($user_data, $params, $page);

		if ( sizeof($data) == 0 )
		{
			// return to member page on empty data
			$this->f3->reroute("/members/".$user_data['username'], false);
			exit;
		}


		$this->buffer ( $this->template->listBookFav( $user_data, $data ) );
	}
	
	protected function listing()
	{
		return "Listing";
	}

}
