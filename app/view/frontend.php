<?php
namespace View;

class Frontend extends Base
{
/*
	public function __construct()
	{
		parent::__construct();
		//$this->iconset = Iconset::instance();
	}
*/
	/*
		Base render function wrapper
	*/
    public function finish()
	{
        /** @var \Base $f3 */
        $f3 = \Base::instance();
  		include('app/loader.php');

		if($this->data)
            $f3->mset($this->data);

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

		$body = preg_replace_callback(
								'/\{ICON:([\w-]+)\}/s',
								function ($icon)
								{
									return Iconset::instance()->{$icon[1]};
								}
								, $body
							);

		$f3->set('BODY', $body);
		
		return \Template::instance()->render('layout.html');
    }

	public function tagWork($tpl)
	{
		$expression = "/{(BLOCK|PAGE|LINK):([a-z][\w]*)([\.\w]*)}/is";

		if( preg_match($expression,$tpl,$match) )
		{
			// Array (     [0] => {BLOCK:menu.main}     [1] => BLOCK    [2] => menu    [3] => .main )
			if ( $match[1] == "BLOCK" AND isset( $this->modules[$match[2]] ) )
			{
				$call = $this->modules[$match[2]][0];//'\\'.$m[0].'\Block';
				// call or recall block module
				$qq = $this->modules[$match[2]][1];
				if ( isset($this->modules[$match[2]][2]) )
					$tpl = str_replace ( $match[0], $call::{$this->modules[$match[2]][1]}($match[3]), $tpl );
				else
					$tpl = str_replace ( $match[0], $call::instance()->{$this->modules[$match[2]][1]}($match[3]), $tpl );
			}
			elseif ( $match[1] == "PAGE" )
			{
				$page = \View\Page::load($match[2]);
				$tpl = str_replace ( $match[0], $page, $tpl );
			}
			else $tpl = str_replace ( $match[0], "*{$match[2]}*", $tpl );
			return $this->tagWork ( $tpl );
		}
		else return $tpl;
	}
	
	private function post_render($buffer)
	{
		$f3 = \Base::instance();
		$cfg = $f3->get('CONFIG');
		
		if ( isset($this->JS['head']) ) $f3->set( 'JS_HEAD', implode("\n", $this->JS['head']) );
		if ( isset($this->JS['body']) ) $f3->set( 'JS_BODY', implode("\n", $this->JS['body']) );

		$debug[] = $f3->get('DB')->log();
				$debug[] = "SESSION: ".print_r($_SESSION,TRUE);

		if($cfg->page_title_add=='slogan')
		{
			$f3->set('TITLE', $cfg->page_title.$cfg->page_title_separator.$cfg->page_slogan);
		}
		elseif($cfg->page_title_add=='path')
		{
			$f3->set('TITLE', implode($cfg->page_title_separator, array_merge([$cfg->page_title],$this->title) ) );
		}

		else $f3->set('TITLE', '');
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
		$f3->set('DEBUGLOG', implode("\n", $debug));
		return $buffer;
	}
	
}
