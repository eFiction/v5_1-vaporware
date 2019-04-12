<?php

namespace Controller;

class Members extends Base {
	
	public function __construct()
	{
		$this->model = \Model\Members::instance();
		$this->template = new \View\Members();
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
			case "favourites":
				$data = $this->model->loadFavourites($user_data, $options);
				break;
			case "stories":
			default:
				$story_data = $this->model->memberStories($user_data, $options);
				$this->buffer( $this->template->stories($user_data, $story_data) );
		}

	}
	
	protected function listing()
	{
		return "Listing";
	}

/*
	public function index(\Base $f3, array $params)//: void
	{
		// reroute /u/Membername type links to uid
		if ( isset($params['membername']) AND ( 0 !== $uid = $this->model->uidByName($params['membername']) ) )
			$params['*'] = "id=".$uid;

		if ( isset($params['*']) )
			$params = $this->parametric($params['*']);
		else
			$params = [];

		if ( isset($params['id']) AND is_numeric($params['id']) )
			$data = $this->profile($params['id']);
		
		else
			$data = $this->listing($params);

		$this->buffer ($data);
	}

	public function profile(\Base $f3, array $params)//: void
	{
		print_r($params);exit;
	}

	protected function listing()
	{
		return "Listing";
	}
	
	protected function profile(int $uid) : string
	{
		// if the is no numeric id, fall back to listing
		if ( FALSE === $data = $this->model->profileData($uid) )
			 return $this->listing();
		 
		return $this->template->profile($data);
	}
*/
}
