<?php
namespace Controller;

class Redirect extends Base
{

	public function __construct()
	{
		
	}
	
	public function filter (\Base $f3, $params)
	{
		if ( empty($params['a']) )
		{
			if ( isset($COOKIE['redirect_seen']) )
			{
				$params['a'] = $params['b'];
				$params['b'] = $params['c'];
			}
			else $f3->reroute("/redirect/{$params['b']}/{$params['c']}", false);
		}

		$query = explode ( "&", $params['b'] );
		foreach ( $query as $q )
		{
			$item = explode("=", $q);
			$old_data[$item[0]] = $item[1];
		}

		// default: redirect to main page
		$redirect = "/";
		
		if ( $params['a']=="viewstory" )
		{
			if ( isset($old_data['sid']) && is_numeric($old_data['sid']) )
			{
				$redirect = "/story/read/".$old_data['sid'];
				if ( isset($old_data['chapter']) && is_numeric($old_data['chapter']) )
					$redirect .= ",".$old_data['chapter'];
			}
		}
		elseif ( $params['a']=="viewuser" )
		{
			if ( isset($old_data['uid']) && is_numeric($old_data['uid']) )
				$redirect = "/authors/".$old_data['uid'];
		}
		
		if ( isset($COOKIE['redirect_seen'] ) ) $f3->reroute($redirect, false);
		else $this->buffer( \View\Redirect::inform($redirect) );
	}

}
