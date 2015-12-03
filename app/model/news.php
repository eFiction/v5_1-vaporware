<?php
namespace Model;

class News extends Base
{
    //protected $db = 'DB';
	
	function ajaxCalendar($params)
	{
		//$date = isset($params[1]) ? (list($year,$month)=explode("-", $params[1])) : FALSE;
		
		$firstEvent = $this->exec("SELECT UNIX_TIMESTAMP(S.date) AS date FROM `tbl_stories`S ORDER BY S.date ASC LIMIT 0,1")[0];
		$start = array ( "month"	=> date("n",$firstEvent['date']),
									 "year"		=> date("Y",$firstEvent['date']) );
		
		$target=explode("-",$params[1]);

		if ( sizeof($target>1 ) )
		{
			if ( (($target[0]>2000) && ($target[0]<=date("Y"))) && (($target[1]>0) && ($target[1]<13)) )
			{
				$c['month'] = $target[1];
				$c['year']  = $target[0];
			}
		}

		if ( !isset($c) )
		{
			$last = \Base::instance()->get('SESSION.lastCalendar');
			if ( $last > "" )
			{
				$target=explode("-",$last);
				$c['month'] = $target[1];
				$c['year']  = $target[0];
			}
			else
			{
				$c['month'] = date("n");
				$c['year']  = date("Y");
			}
		}
		\Base::instance()->set('SESSION.lastCalendar', $c['year']."-".$c['month']);
		
		$this->prepare ( "queryEvents", "SELECT COUNT( S.title ) AS per_day, DAY( S.updated ) AS day_nr
												FROM `tbl_stories` S
												WHERE MONTH(S.updated) = :month AND YEAR(S.updated) = :year AND S.validated > 0
												GROUP BY DAY( S.updated ) " );
		$this->bindValue("queryEvents", ":month", $c['month'], \PDO::PARAM_INT);
		$this->bindValue("queryEvents", ":year",  $c['year'],	\PDO::PARAM_INT);
		$eventResults = $this->execute("queryEvents");

		if ( sizeof($eventResults) > 0 )
		{
			foreach ( $eventResults as $event )
			{
				$events[$event['day_nr']] = $event['day_nr'];
			}
		}
		else $events = FALSE;

		return [$events, $c, $start];
	}
}