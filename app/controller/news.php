<?php
namespace Controller;

class News extends Base
{

	public function __construct()
	{
		$this->model = \Model\News::instance();
	}

	public function beforeroute()
	{
		parent::beforeroute();
		\Registry::get('VIEW')->addTitle( \Base::instance()->get('LN__News') );
	}
	
	public function blocks($select)
	{
		return "** NEWS **";
	}

}