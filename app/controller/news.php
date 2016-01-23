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
		$select = explode(".",$select);
		$items = (isset($select[2]) AND $select[2]<=3) ? $select[2] : 3;
		
		$data = $this->model->loadOverview($items);
		return \View\News::block($data);//"** NEWS **".$items;
		
	}

}