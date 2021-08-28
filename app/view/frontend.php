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

		$this->f3->set('SITENAME', $this->config['page_title']);
    $this->f3->set('BREADCRUMBS', $this->title);
		/*
			3-step processing of the inner page content:
			- render page
			- process tags
			- prepare data for outer page rendering
		*/

		if ( FALSE==\Config::getPublic('maintenance') OR $_SESSION['groups']&64 )
			$body =  $this->post_render(
										$this->tagWork(
												\Template::instance()->render('main/body.html')
										)
								);
		else
			$body =  $this->post_render(
										$this->tagWork(
												\Template::instance()->render('main/body.maintenance.html')
										)
								);

		$body = preg_replace_callback(
								'/\{ICON:([\w-]+)(:|\!|#)?(.*?)\}/s',	// allowed seperators: ':' (normal), '!', '#' (id)
								function ($icon)
								{
									return Iconset::parse($icon);
								},
								$body
							);

		$f3->set('BODY', $body);

		return \Template::instance()->render('index.html');
    }

	public function tagWork($tpl)
	{
		$expression = "/{(BLOCK|PAGE|LINK):([a-z][\w]*)(?:\.([\.\w]*))*}/is";

		if( preg_match($expression,$tpl,$match) )
		{
			/*
				$match = array
					[0] => {BLOCK:menu.main.1}
					[1] => BLOCK
					[2] => menu
					[3] => main.1
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
					$tpl = str_replace ( $match[0], $call::{$this->modules[$match[2]][1]}(@$match[3]), $tpl );
				else
					$tpl = str_replace ( $match[0], $call::instance()->{$this->modules[$match[2]][1]}(@$match[3]), $tpl );
			}
			elseif ( $match[1] == "PAGE" )
			{
				// *old&ugly
				$page = \View\Home::loadPage($match[2]);
				$tpl = str_replace ( $match[0], $page, $tpl );
			}
			else $tpl = str_replace ( $match[0], "*missing block: {$match[2]}*", $tpl );
			return $this->tagWork ( $tpl );
		}
		else return $tpl;
	}

	private function post_render($buffer)
	{
		$this->f3->set( 'JS_HEAD', implode("\n", $this->f3->JS['head']) );
		$this->f3->set( 'JS_BODY', implode("\n", $this->f3->JS['body']) );

		if($this->config['page_title_add']=='slogan')
		{
			$this->f3->set('TITLE', $this->config['page_title'].$this->config['page_title_separator'].$this->config['page_slogan']);
		}
		elseif($this->config['page_title_add']=='path')
		{
			$this->f3->set('TITLE', implode($this->config['page_title_separator'], array_merge([$this->config['page_title']],$this->title) ) );
		}

		else $this->f3->set('TITLE', $this->config['page_title']);

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
			'<a href="https://efiction.org/credits.php"><!-- give_credits --></a>',
			'<a href="https://efiction.org/credits.php"><img src="give_credits.gif" height="1" width="1" border="0"></a>',
			'<a href="https://efiction.org/credits.php" style="display: none;">give_credits</a>',
			'<div style="display: none;"><a href="https://efiction.org/credits.php">give_credits</a></div>',
			'<a href="https://efiction.org/credits.php"></a>',
			'<!-- <a href="https://efiction.org/credits.php">give_credits</a> -->',
			'<div style="position: absolute; top: -250px; left: -250px;"><a href="https://efiction.org/credits.php">give_credits</a></div>',
			'<a href="https://efiction.org/credits.php"><span style="display: none;">give_credits</span></a>',
			'<a href="https://efiction.org/credits.php"><div style="height: 0px; width: 0px;"></div></a>'
		];
		return $links[array_rand($links)];
	}
}

// create and register custom output filters
class TemplateFilter extends \Prefab
{
	/**
	* crop a given text down to the amount of characters / paragraphs provided
	* Example: {{nl2br(@data.description),500,3 | crop,raw }}
	* 2020-09
	*
	* @param	string	$text
	* @param	int		$characters
	* @param	int		$paragraphs		Optional
	*
	* @return	string					Cropped text
	*/
	public function crop( string $text, int $characters=150, int $paragraphs=NULL, bool $readmore=FALSE) : string
	{
		// define the regular expression for preg_split
		$regular = $paragraphs===NULL ? '/((?:\s*\.\s*)+)/' : '/((?:\R|<\s*br\s*\/?>\s*|&lt;\s*br\s*\/?&gt;\s*)+)/';
		$original = $text;

		// crop by letter count, but make an attempt to let the sentence be completed.
		// if paragraph = 0 is provided, also complete the paragraph
		if ( $characters > 0 AND (int)$paragraphs<1 )
		{
			$count = sizeof
			(
				preg_split
				(
					// split by sentence (-ish)
					$regular,
					substr ( strip_tags($text), 0, $characters ),
				)
			);
			$text = implode
			(
				// additional glue
				"",
				// paragraph array cut in length
				array_slice
				(
					preg_split
					(
						// split by sentence (-ish)
						$regular,
						$text,
						-1,
						// discard empty lines and keep note of the delimiters
						PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
					),
					// start slicing with element #0
					0,
					// take $characters slices and include their original delimiters
					$count*2-(int)($paragraphs!==NULL)
				)
			);
		}

		// limit the amount of paragraphs returned.
		// will respect character limitations made above
		if ( (int)$paragraphs>0 )
		{
			// crop by paragraph
			$text = implode
			(
				// additional glue
				"",
				// paragraph array cut in length
				array_slice
				(
					preg_split
					(
						// split by <br> tags and all newline codes
						$regular,
						$text,
						-1,
						// discard empty lines and keep note of the delimiters
						PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
					),
					// start slicing with element #0
					0,
					// take $paragraphs slices and include their original delimiters
					$paragraphs*2-1
				)
			);
		}

		if ( $readmore )
		{
			$remains = substr( $original, strlen($text) );
			$text = \Template::instance()->render('main/moretext.html', 'text/html', [ "text" => $text, "remains" => $remains ] );
		}

		return $text;
	}

	public function cropmore( string $text, int $characters=150, int $paragraphs=NULL)
	{
		$text = $this->crop( $text, $characters, $paragraphs, TRUE);
		return $text;
	}

}

\Template::instance()->filter('crop','\View\TemplateFilter::instance()->crop');
\Template::instance()->filter('cropmore','\View\TemplateFilter::instance()->cropmore');
