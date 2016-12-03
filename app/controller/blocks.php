<?php
namespace Controller;

class Blocks extends Base
{

	public function __construct()
	{
		$this->model = \Model\Blocks::instance();
	}

	public function shoutbox(\Base $f3, $params)
	{
		$params = $this->parametric( $params['*'] ); 

		if ( $params[0] == "load" )
		{
			$subs = explode(",",$params[1]);
			if ( isset($subs[1])  AND $subs[0]=="down" ) $offset = $subs[1] + \Config::getPublic('shoutbox_entries');
			elseif ( isset($subs[1])  AND $subs[0]=="up" )  $offset = max ( ($subs[1] - \Config::getPublic('shoutbox_entries')), 0);
			else $offset = 0;
			
			$data = $this->model->shoutboxLines($offset);
			$tpl = \View\Blocks::shoutboxLines($data);
			$this->buffer( array ( $tpl, "", $offset, 0 ) , "BODY", TRUE );
		}
		elseif ( $params[0] == "form" )
		{
			if($_SESSION['userID']!=0 || \Config::getPublic('shoutbox_guest') )
			{
				$form = \View\Blocks::shoutboxForm();
				$this->buffer( array ( "", $form, 0, 0 ) , "BODY", TRUE );
			}
			else
			{
				// Denied
				$this->buffer( array ( "", "Denied", 0, 0 ) , "BODY", TRUE );
			}
		}
		elseif ( $params[0] == "shout" )
		{
			/*
				note: even on error notes, the function has to return a non-FALSE value
				otherwise, the jQuery counterpart will assume a technical error
			*/
			
			if($_SESSION['userID']!=0 || \Config::getPublic('shoutbox_guest') )
			{
				// un-serialize the javascript serialized form data
				parse_str($f3->get('POST.data'),$data);
				/*
					$data = array
					(
						@name
						@message
						@captcha
					)
				*/
				if ( "" == $data['message'] = trim($data['message']) )
				{
					// Don't accept empty message
					$this->buffer( array ( "", $f3->get('LN__MessageEmpty'), 0, 2 ) , "BODY", TRUE );
				}
				elseif($_SESSION['userID'])
				{
					// Attempt to save data
					if ( 1 == $this->model->addShout($data, TRUE) )
						// tell the shoutbox to reload and go to top
						$this->buffer( array ( "", "", 0, 1 ) , "BODY", TRUE );
					// Drop error
					else
						$this->buffer( array ( "", "__saveError", 0, 2 ) , "BODY", TRUE );
				}
				else
				{
					if ( empty($_SESSION['captcha']) OR !password_verify(strtoupper($data['captcha']),$_SESSION['captcha']) )
					{
						// Drop error
						$this->buffer( array ( "", $f3->get('LN__CaptchaMismatch'), 0, 2 ) , "BODY", TRUE );
					}
					else
					{
						// Don't accept empty guest name
						if ( "" == $data['name'] = trim($data['name']) )
						{
							$this->buffer( array ( "", "__nameEmpty", 0, 2 ) , "BODY", TRUE );
						}
						elseif (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$data['message']))
						{
							$this->buffer( array ( "", "__guestURL", 0, 2 ) , "BODY", TRUE );
						}
						// Attempt to save data
						elseif ( 1 == $this->model->addShout($data) )
						{
							// destroy this session captcha
							unset($_SESSION['captcha']);
							// tell the shoutbox to reload and go to top
							$this->buffer( array ( "", "", 0, 1 ) , "BODY", TRUE );
						}
						// Drop error
						else
							$this->buffer( array ( "", "__saveError", 0, 2 ) , "BODY", TRUE );
					}
				}
			}
		}
	}

	public function calendar(\Base $f3, $params) {

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

		return \View\Blocks::pageMenu($main, $sub, isset($menuSelect[2]) );
	}
	
	public function categories()
	{
		$data = $this->model->categories();
		return \View\Blocks::categories($data);
	}
}
