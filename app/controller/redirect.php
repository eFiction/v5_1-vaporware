<?php
namespace Controller;

class Redirect extends Base
{

	public function __construct()
	{
		$this->template = new \View\Redirect();
	}
	
	public function filter (\Base $f3, array $params)
	{
		// This is only a visual move, but nevertheless
		if ( empty($params['a']) )
		{
			if ( isset($COOKIE['redirect_seen']) )
			{
				$params['a'] = $params['b'];
				$params['b'] = $params['c'];
			}
			else
			{
				$params['c'] = urldecode($params['c']);
				$f3->reroute("/redirect/{$params['b']}/{$params['c']}", false);
				exit;
			}
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
		elseif ( $params['a']=="browse" )
		{
			//print_r($old_data);
			// Browse is best handled by a search type
			$redirect = "/story/search";

			if ( isset($old_data['type']) AND $old_data['type']=="categories" )
			{
				if ( isset($old_data['catid']) && is_numeric($old_data['catid']) )
					$parameters[] = "category=".$old_data['catid'];
				
				/*
					Tags (former classes), type by type
				*/
				/*
				serious to-do
				- load tag_groups.label ( classtype name ) without characters
				- check $old_data[$label] and find in tags
				*/
				
				/* convert offset to page number */
				if ( isset($old_data['offset']) && is_numeric($old_data['offset']) )
				{
					$items = \Config::instance()->stories_per_page;
					$parameters[] = "page=".(int)($old_data['offset']/$items);
				}
			}
			elseif ( isset($old_data['type']) AND $old_data['type']=="class" )
			{
				if ( isset($old_data['classid']) && is_numeric($old_data['classid']) )
					$tags[] = $old_data['classid'];
				
			}
			elseif ( isset($old_data['type']) AND $old_data['type']=="characters" )
			{
				if ( isset($old_data['charid']) && is_numeric($old_data['charid']) )
				{
					$c = $old_data['charid'];
					// load tag with old character id from database
				}
				$tags[] = $old_data['charid'];
				
			}
			
			if(isset($tags)) $parameters[] = "tagIn=".implode(",",$tags);
			if(isset($parameters)) $redirect .= "/".implode(";",$parameters);
		}
		elseif ( $params['a']=="viewpage" )
		{
			$page = explode("=",$params['b']);
			$redirect = "/home/page/".@$page[1];
		}
		
		if ( isset($COOKIE['redirect_seen'] ) ) $f3->reroute($redirect, false);
		else $this->buffer( $this->template->inform($redirect) );
	}

}
