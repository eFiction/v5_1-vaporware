<?php
namespace View;

class News extends Base
{

	public static function calendar($data)
	{
		list($events, $c, $start) = $data;
		
		$day_count = date("t",mktime(0,0,0,$c['month'],1,$c['year']));
		$blanks_front = ( \Base::instance()->get('CONFIG')["monday_first_day"] == 1 ) ? date('N',mktime(0,0,0,$c['month'],1,$c['year']))-1 : date('w',mktime(0,0,0,$c['month'],1,$c['year'])) ;
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
			"cells" => $cells,
			"BACK"		=>	$back,
			"TODAY"		=>	$today,
			"FORWARD"	=>	$forward,
			"TITLE"		=>	date("__F Y",mktime(0,0,0,$c['month'],1,$c['year'])),
		];
		return \Template::instance()->render('sidebar/calendar.html','text/html', $data);
	}
}