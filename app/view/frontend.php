<?php
namespace View;

class Frontend extends Template
{
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
		
		if ( FALSE==\Config::getPublic('maintenance') OR $_SESSION['groups']&64 )
			$body =  $this->post_render(
										$this->tagWork(
												\Template::instance()->render('body.html')
										)
								);
		else 
			$body =  $this->post_render(
										$this->tagWork(
												\Template::instance()->render('body_maintenance.html')
										)
								);

		$body = preg_replace_callback(
								'/\{ICON:([\w-]+)(:|\!)?(.*?)\}/s',	// for use with forced visibility
								function ($icon)
								{
									return Iconset::parse($icon);
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
			/*
				$match = array
					[0] => {BLOCK:menu.main}
					[1] => BLOCK
					[2] => menu
					[3] => .main
			*/
			if ( $match[1] == "BLOCK" AND $match[2] == "HONEYPOT" )
			{
				$tpl = str_replace ( $match[0], $this->honeypot(), $tpl );
			}
			elseif ( $match[1] == "BLOCK" AND isset( $this->modules[$match[2]] ) )
			{
				$call = $this->modules[$match[2]][0];
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
		$this->f3->set( 'JS_HEAD', implode("\n", @$this->f3->JS['head']) );
		$this->f3->set( 'JS_BODY', implode("\n", @$this->f3->JS['body']) );


		if($this->config['page_title_add']=='slogan')
		{
			$this->f3->set('TITLE', $this->config['page_title'].$this->config['page_title_separator'].$this->config['page_slogan']);
		}
		elseif($this->config['page_title_add']=='path')
		{
			$this->f3->set('TITLE', implode($this->config['page_title_separator'], array_merge([$this->config['page_title']],$this->title) ) );
		}

		else $this->f3->set('TITLE', '');

		switch($this->config['debug'])
		{
			case 5:
				$debug[] = $this->f3->get('DB')->log();
			case 4:
				//$debug[] = "SQL queries: ".print_r($DB->history,TRUE);
				//$debug[] = "SQL analysis: ".print_r($DB->profiling(), TRUE);
			case 3:
				//$debug[] = "request: ".print_r($eFI->request,TRUE);
			case 2:
				$debug[] = "SESSION: ".print_r($_SESSION,TRUE);
				//$debug[] = "modules used: ".print_r($this->modules,TRUE);
				//$debug[] = "SQL types: ".print_r(array_filter($DB->history['count']),TRUE);
			case 1:
				//$runtime = microtime(true)-$timeStart;
				//$debug[] = "runtime : ".round($runtime*1000)." ms";
				//$debug[] = "SQL count: ".$DB->history['total'];
				//$debug[] = "SQL time: ".round($DB->history['duration']*1000)." ms (".round(100*$DB->history['duration']/$runtime)."%)";
				// for all levels above 0:
				$this->f3->set('DEBUGLOG', implode("\n", $debug));
				break;
		}
			//default:
				//$return = "";

		return $buffer;
	}
	
	private function honeypot()
	{
		$links = 
		[
			'<a href="http://efiction.org/credits.php"><!-- give_credits --></a>',
			'<a href="http://efiction.org/credits.php"><img src="give_credits.gif" height="1" width="1" border="0"></a>',
			'<a href="http://efiction.org/credits.php" style="display: none;">give_credits</a>',
			'<div style="display: none;"><a href="http://efiction.org/credits.php">give_credits</a></div>',
			'<a href="http://efiction.org/credits.php"></a>',
			'<!-- <a href="http://efiction.org/credits.php">give_credits</a> -->',
			'<div style="position: absolute; top: -250px; left: -250px;"><a href="http://efiction.org/credits.php">give_credits</a></div>',
			'<a href="http://efiction.org/credits.php"><span style="display: none;">give_credits</span></a>',
			'<a href="http://efiction.org/credits.php"><div style="height: 0px; width: 0px;"></div></a>'
		];
		return $links[array_rand($links)];
	}
}
