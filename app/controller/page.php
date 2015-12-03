<?php
/**
    post.php
    
    The contents of this file are subject to the terms of the GNU General
    Public License Version 3.0. You may not use this file except in
    compliance with the license. Any of the license terms and conditions
    can be waived if you get permission from the copyright holder.
    
    Copyright (c) 2015 ~ ikkez
    Christian Knuth <ikkez0n3@gmail.com>
 
        @version 0.2.0
 **/
namespace Controller;

class Page extends Base {

	public function getMain(\Base $fw, $params)
	{
		$this->buffer ( \Template::instance()->render('main/welcome.html') );
	}
	
	public function shoutbox(\Base $fw, $params)
	{
		$this->model = \Model\Page::instance();
		if ( $params['action'] == "load" )
		{
			$subs = explode(",",$params['sub']);
			if ( isset($subs[1])  AND $subs[0]=="down" ) $offset = $subs[1] + \Base::instance()->get('CONFIG')['shoutbox_entries'];
			elseif ( isset($subs[1])  AND $subs[0]=="up" )  $offset = max ( ($subs[1] - \Base::instance()->get('CONFIG')['shoutbox_entries']), 0);
			else $offset = 0;
			
			$data = $this->model->shoutboxLines($offset);
			$tpl = \View\Page::shoutboxLines($data);
			echo json_encode ( array ( $tpl, "", $offset, 0 ) );
		}
		if ( $params['action'] == "form" )
		{
			if($_SESSION['userID']!=0 || \Base::instance()->get('CONFIG')['shoutbox_guest'] )
			//if( \Base::instance()->get('CONFIG')['shoutbox_guest'] )
			{
				$form = \View\Page::shoutboxForm();
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
}
?>