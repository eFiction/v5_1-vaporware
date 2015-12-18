<?php
namespace Controller;

class UserCP extends Base
{

	public function __construct()
	{
		$this->model = \Model\UserCP::instance();
		//$mapper = new \Model\News();
		//parent::__construct($mapper);
	}
	
	public function index(\Base $fw, $params)
	{
		
	}
}