<?php
namespace View;

class UserCP extends Base
{

	public static function showMenu($menu="")
	{
		return \Template::instance()->render
														('usercp/menu.html','text/html', 
															[
																"menu"	=> $menu,
																"BASE"		=> \Base::instance()->get('BASE')
															]
														);
	}

	public static function msgInOutbox($data, $select="inbox")
	{
		if ( $select == "outbox" )
		{
			$select = "__Outbox";
			$person_is = "__Recipient";
			$date_means = "__Sent";
		}
		else
		{
			$select = "__Inbox";
			$person_is = "__Sender";
			$date_means = "__Received";
		}
		return \Template::instance()->render
														('usercp/messaging.inout.html','text/html', 
															[
																"messages"	=> $data,
																"WHICH"		=> $select,
																"PERSON_IS"	=> $person_is,
																"DATE_MEANS"	=> $date_means,
																"BASE"			=> \Base::instance()->get('BASE')
															]
														);
	}

}