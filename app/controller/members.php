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

	public function index(\Base $f3, array $params)
	{
		// reroute /u/Membername type links to uid
		if ( isset($params['membername']) AND ( 0 !== $uid = $this->model->uidByName($params['membername']) ) )
			$params = [
				"*"			=>	"id=".$uid,
				"action"	=>	"profile",
			];

		switch(@$params['action'])
		{
			case 'profile':
				$data = $this->profile($params['*']);
				break;
			case 'listing':
			default:
				$data = $this->listing($params);
		}
		$this->buffer ($data);
	}

	protected function listing()
	{
		return "Listing";
	}
	
//	protected function profile(string $params)
	protected function profile($params)
	{
		// prepare parameters
		$params = $this->parametric($params);
		// if the is no numeric id, fall back to listing
		if ( FALSE === $data = $this->model->profileData(@$params['id']) )
			 return $this->listing();
		 
		return $this->template->profile($data);
	}

}
