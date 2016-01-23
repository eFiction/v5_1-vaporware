<?php
namespace Controller;

class Blocks extends Base
{

	public function __construct()
	{
		$this->model = \Model\Blocks::instance();
		//$mapper = new \Model\News();
		//parent::__construct($mapper);
	}

	public function shoutbox(\Base $fw, $params)
	{
		//$this->model = \Model\Page::instance();
		if ( $params['action'] == "load" )
		{
			$subs = explode(",",$params['sub']);
			if ( isset($subs[1])  AND $subs[0]=="down" ) $offset = $subs[1] + \Base::instance()->get('CONFIG')['shoutbox_entries'];
			elseif ( isset($subs[1])  AND $subs[0]=="up" )  $offset = max ( ($subs[1] - \Base::instance()->get('CONFIG')['shoutbox_entries']), 0);
			else $offset = 0;
			
			$data = $this->model->shoutboxLines($offset);
			$tpl = \View\Blocks::shoutboxLines($data);
			echo json_encode ( array ( $tpl, "", $offset, 0 ) );
		}
		if ( $params['action'] == "form" )
		{
			if($_SESSION['userID']!=0 || \Base::instance()->get('CONFIG')['shoutbox_guest'] )
			//if( \Base::instance()->get('CONFIG')['shoutbox_guest'] )
			{
				$form = \View\Blocks::shoutboxForm();
				echo json_encode ( array ( "", $form, 0, 0 ) );
			}
			else
			{
				// Denied
				echo json_encode ( array ( "", "Denied", 0, 0 ) );
			}
		}
		exit;		
	}

	public function calendar(\Base $fw, $params) {

		$data = $this->model->ajaxCalendar($params);
		
		echo \View\Blocks::calendar($data);
		exit;
	}
	
	public function buildMenu($menuSelect)
	{
		$pageSelect	= explode("/",\Base::instance()->get('PARAMS.0'))[1];
		$menuSelect	= explode(".",$menuSelect);
		
		$data = $this->model->menuData($pageSelect);
		$main = $data['main'];
		$sub = empty($data['sub'])?FALSE:$data['sub'];
		//print_r($data);
		return \View\Blocks::pageMenu($main, $sub, isset($menuSelect[2]) );//$menuSelect.$pageSelect;
	}
	
}