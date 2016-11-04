<?php
/**
	SQL-based user-data wrapper
 **/

class User extends \DB\SQL\Mapper {
	
	public function __construct()
	{
		$db = \Base::instance()->get('DB');
		parent::__construct($db,\Config::instance()->prefix.'users');
		$this->load( [
						'uid = :uid and groups > 0',
						':uid'=> $_SESSION['userID']
					] );
	}
	
	static public function instance() {
		if (\Registry::exists('USER'))
			$user = \Registry::get('USER');
		else {
			$user = new self;
			\Registry::set('USER',$user);
		}
		return $user;
	}	
}
