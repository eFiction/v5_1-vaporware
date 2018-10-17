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

//	public function index(\Base $f3, array $params): void
	public function index(\Base $f3, array $params)
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

}
