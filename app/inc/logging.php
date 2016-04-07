<?php

class Logging extends \DB\SQL\Mapper
{
	
	public function __construct()
	{
		parent::__construct( \Base::instance()->get('DB'), \Config::instance()->prefix."log" );
	}

	/*
	static public function instance() {
		if (\Registry::exists('LOGGER'))
			$logger = \Registry::get('LOGGER');
		else {
			$logger = new self;
			\Registry::set('LOGGER',$logger);
		}
		return $logger;
	}
	*/
	
	static public function addEntry($type, $action, $uid = FALSE)
	{
		if (\Registry::exists('LOGGER'))
			$logger = \Registry::get('LOGGER');
		else {
			$logger = new self;
			\Registry::set('LOGGER',$logger);
		}
		/*
			eFiction 3 log types:
			"RG" => _NEWREG
			"ED" => _ADMINEDIT
			"DL" => _ADMINDELETE
			"VS" => _VALIDATESTORY
			"LP"=> _LOSTPASSWORD
			"BL" => _BADLOGIN
			"RE" => "Reviews"
			"AM" => "Admin Maintenance"
			"EB" => _EDITBIO
		*/

		// Force add entry
		$logger->reset();
		// Submitted data:
		$logger->type	 = $type;
		$logger->action	 = $action;
		// Use id of active user, unless specified
		$logger->uid	 = ( $uid ) ? $uid : $_SESSION['userID'];
		$logger->ip		 = $_SERVER['REMOTE_ADDR'];
		$logger->version = 2;
		// Add entry
		$test = $logger->save();
	}
}
