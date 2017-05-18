<?php
namespace Controller;

/*
	This class offers routines that can be triggered based on certain events
*/

class Routines extends Base {

	public function __construct()
	{
		$this->model = \Model\Routines::instance();
		$this->config = \Base::instance()->get('CONFIG');
	}

	public function notification($type, $id)
	{
		
	}
}