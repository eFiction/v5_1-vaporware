<?php
namespace View;

class Frontend extends Base
{

	/*
		Base render function wrapper
	*/
    public function render()
	{
        /** @var \Base $f3 */
        $fw = \Base::instance();
  		include('app/efi5/loader.php');

		if($this->data)
            $fw->mset($this->data);

		/*
			3-step processing of the inner page content:
			- render page
			- process tags
			- prepare data for outer page rendering
		*/
		$body =  $this->post_render(
										$this->tagWork(
												\Template::instance()->render('body.html')
										)
								);
		$fw->set('BODY', $body);
		
		return \Template::instance()->render('layout.html');
    }

	public function tagWork($tpl)
	{
		//$fw = \Base::instance();
		$expression = "/{(BLOCK|PAGE|LINK):([a-z][\w].+?)}/is";

		if( preg_match($expression,$tpl,$match) )
		{
			// Array ( [0] => {BLOCK:home.navbar} [1] => BLOCK [2] => home.navbar )
			if ( $match[1] == "BLOCK" AND isset( $this->modules[$match[2]] ) )
			{
				$call = $this->modules[$match[2]][0];//'\\'.$m[0].'\Block';
				// call or recall block module
				$tpl = str_replace ( $match[0], $call::instance()->{$this->modules[$match[2]][1]}(), $tpl );
			}
			elseif ( $match[1] == "PAGE" )
			{
				$page = \View\Page::load($match[2]);
				$tpl = str_replace ( $match[0], $page, $tpl );
			}
			else $tpl = str_replace ( $match[0], "", $tpl );
			return $this->tagWork ( $tpl );
		}
		else return $tpl;
	}
	
	private function post_render($buffer)
	{
		$fw = \Base::instance();
		
		if ( isset($this->JS['head']) ) $fw->set( 'JS_HEAD', implode("\n", $this->JS['head']) );
		if ( isset($this->JS['body']) ) $fw->set( 'JS_BODY', implode("\n", $this->JS['body']) );

		$debug[] = $fw->get('DB')->log();
				$debug[] = "SESSION: ".print_r($_SESSION,TRUE);
		/*
		switch($eFI->config['show_debug'])
		{
			case 5:
				$debug[] = "SESSION: ".print_r($_SESSION,TRUE);
			case 4:
				$debug[] = "SQL queries: ".print_r($DB->history,TRUE);
				$debug[] = "SQL analysis: ".print_r($DB->profiling(), TRUE);
			case 3:
				$debug[] = "request: ".print_r($eFI->request,TRUE);
			case 2:
				$debug[] = "modules used: ".print_r($this->modules,TRUE);
				$debug[] = "SQL types: ".print_r(array_filter($DB->history['count']),TRUE);
			case 1:
				$runtime = microtime(true)-$timeStart;
				$debug[] = "runtime : ".round($runtime*1000)." ms";
				$debug[] = "SQL count: ".$DB->history['total'];
				$debug[] = "SQL time: ".round($DB->history['duration']*1000)." ms (".round(100*$DB->history['duration']/$runtime)."%)";
				// for all levels above 0:
				$return = str_replace("{DEBUG}",html_entity_decode	( implode("\n",$debug) ),$this->blocks['main']['debug']);
				break;
			default:
				$return = "";
		*/
		$fw->set('DEBUGLOG', implode("\n", $debug));
		return $buffer;
	}
	
}