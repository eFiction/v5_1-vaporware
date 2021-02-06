<?php
namespace Controller;

class Blocks extends Base
{

	public function __construct()
	{
		$this->config = \Config::instance();
		$this->model = \Model\Blocks::instance();
		$this->template = new \View\Blocks();
	}

	public function shoutbox(\Base $f3, array $params)//: void
	{
		$params = $this->parametric( $params['*'] );

		if ( $params[0] == "load" )
		{
			$subs = explode(",",$params[1]);
			if ( isset($subs[1])  AND $subs[0]=="down" ) $offset = $subs[1] + $this->config['shoutbox_entries'];
			elseif ( isset($subs[1])  AND $subs[0]=="up" )  $offset = max ( ($subs[1] - $this->config['shoutbox_entries']), 0);
			else $offset = 0;

			$data = $this->model->shoutboxLines($offset);
			$tpl = \View\Blocks::shoutboxLines($data);
			$this->jbuffer( array ( $tpl, "", $offset, 0 ) );
		}
		elseif ( $params[0] == "form" )
		{
			if($_SESSION['userID']!=0 || $this->config['shoutbox_guest'] )
			{
				$form = \View\Blocks::shoutboxForm();
				$this->jbuffer( array ( "", $form, 0, 0 ) );
			}
			else
			{
				// Denied
				$this->jbuffer( array ( "", "Denied", 0, 0 ) );
			}
		}
		elseif ( $params[0] == "shout" )
		{
			/*
				note: even on error notes, the function has to return a non-FALSE value
				otherwise, the jQuery counterpart will assume a technical error
			*/

			if($_SESSION['userID']!=0 || $this->config['shoutbox_guest'] )
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
					$this->jbuffer( array ( "", $f3->get('LN__MessageEmpty'), 0, 2 ) );
				}
				elseif($_SESSION['userID'])
				{
					// Attempt to save data
					if ( 1 == $this->model->addShout($data, TRUE) )
						// tell the shoutbox to reload and go to top
						$this->jbuffer( array ( "", "", 0, 1 ) );
					// Drop error
					else
						$this->jbuffer( array ( "", "__saveError", 0, 2 ) );
				}
				else
				{
					if ( empty($_SESSION['captcha']) OR !password_verify(strtoupper($data['captcha']),$_SESSION['captcha']) )
					{
						// Drop error
						$this->jbuffer( array ( "", $f3->get('LN__CaptchaMismatch'), 0, 2 ) );
					}
					else
					{
						// Don't accept empty guest name
						if ( "" == $data['name'] = trim($data['name']) )
						{
							$this->jbuffer( array ( "", "__nameEmpty", 0, 2 ) );
						}
						elseif (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$data['message']))
						{
							$this->jbuffer( array ( "", "__guestURL", 0, 2 ) );
						}
						// Attempt to save data
						elseif ( 1 == $this->model->addShout($data) )
						{
							// destroy this session captcha
							unset($_SESSION['captcha']);
							// tell the shoutbox to reload and go to top
							$this->jbuffer( array ( "", "", 0, 1 ) );
						}
						// Drop error
						else
							$this->jbuffer( array ( "", "__saveError", 0, 2 ) );
					}
				}
			}
		}
	}

	public function calendar(\Base $f3, array $params)//: void
	{
		$data = $this->model->ajaxCalendar($params);

		list($events, $c, $start) = $data;

		$day_count = date("t",mktime(0,0,0,$c['month'],1,$c['year']));
		$blanks_front = ( \Config::getPublic('monday_first_day') == 1 ) ? date('N',mktime(0,0,0,$c['month'],1,$c['year']))-1 : date('w',mktime(0,0,0,$c['month'],1,$c['year'])) ;
		$rows_required = intval ( ($day_count+$blanks_front+6) / 7 );
		$blanks_after = $rows_required*7 - $blanks_front - $day_count;

		$now 	 = array ( "month"	=> date("n"),
						 "year"		=> date("Y") );
		/*
			check if we have events on prior calendar sheets
		*/
		$back = ( ($c['year'] > $start['year']) || ($c['year']==$start['year'] && $c['month'] > $start['month']) )
			? date("Y-m",mktime(0,0,0,$c['month']-1,1,$c['year']))
			: FALSE;

		/*
				check if we have events on later calendar sheets
		*/
		$forward = ( ($c['year'] < $now['year']) || ($c['year']==$now['year'] && $c['month'] < $now['month']) )
		? date("Y-m",mktime(0,0,0,$c['month']+1,1,$c['year']))
		: FALSE;

		$today = ($c['year']==$now['year'] && $c['month']==$now['month']) ? FALSE : date("Y-m");

		// create empty leading cells
		for ( $i=1; $i <= $blanks_front; $i++ )
		{
			$cells[] = [ FALSE ];
		}

		// create days
		for ( $i=1; $i <= $day_count; $i++ )
		{
			$cells[] = array (
								"LINK"	=>	( isset($events[$i]) ) ? "{$c['year']}-{$c['month']}-{$i}" : FALSE,
								"I"			=>	$i,
							);
		}

		// create empty tailing cells
		for ( $i=1; $i <= $blanks_after; $i++ )
		{
			$cells[] = [ FALSE ];
		}

		$data = [
			"CELLS"		=>	$cells,
			"BACK"		=>	$back,
			"TODAY"		=>	$today,
			"FORWARD"	=>	$forward,
			"MONTH"		=>	$c['month'],
			"YEAR"		=>	$c['year'],
			"TITLE"		=>	mktime(0,0,0,$c['month'],1,$c['year']),
			"TITLELINK" =>	$events===FALSE ? FALSE : "{$c['year']}-{$c['month']}",
		];

		$this->template->calendar($data);
	}

	public function buildMenu(string $menuSelect): string
	{
		$pageSelect	= explode("/",\Base::instance()->get('PARAMS.currentpath'))[1];
		$menuSelect	= explode(".",$menuSelect);

		$data = $this->model->menuData($pageSelect);
		$main = $data['main'];
		$sub = empty($data['sub'])?FALSE:$data['sub'];

		return \View\Blocks::pageMenu($main, $sub, isset($menuSelect[1]) );
	}

	public function categories(): string
	{
		if ( NULL !== $data = $this->model->categories() )
			return \View\Blocks::categories($data);

		// Return empty if no data retrieved
		return "";
	}

	public function authorProfile(int $aid): string
	{
		return "Profile!".$aid;
	}

}
