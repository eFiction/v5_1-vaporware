<?php
namespace View;
abstract class Base {
	
    public $data = array();
    public $modules = [];
	protected $title = [];

	public function __construct()
	{
		$this->config = \Config::getTree();
		$this->f3 = \Base::instance();
		$this->TPL = \Template::instance();
	}

	public function javascript($location, $file=FALSE, $string)
	{
		if($file)
		{
			$this->f3->JS[$location][] = (strpos($string,"//")===0)
																? "<script src=\"{$string}\"></script>"
																: "<script src=\"".$this->f3->get('BASE')."/app/js/{$string}\"></script>";
		}
		else $this->f3->JS[$location][] = "<script type=\"text/javascript\">{$string}</script>";
	}
	
	public function addTitle($string)
	{
		// Used by a controller, this writes the title to the main render view
		 \Registry::get('VIEW')->addTitle($string);
	}
	
	public static function render($file,$mime='text/html',array $hive=NULL,$ttl=0)
	{
		return "<!-- FILE: {$file} -->".\Template::instance()->render($file,$mime,$hive,$ttl)."<!-- END: {$file} -->";
	}
	
	public static function directrender($file,$mime='text/html',array $hive=NULL,$ttl=0)
	{
		$content = \Template::instance()->render($file,$mime,$hive,$ttl);

		$content = preg_replace_callback(
						'/\{ICON:([\w-]+)(:|\!|#)?(.*?)\}/s',	// allowed seperators: ':' (normal), '!', '#' (id)
						function ($icon)
						{
							return Iconset::parse($icon);
						},
						$content
					);

		return "<!-- FILE: {$file} -->".$content."<!-- END: {$file} -->";
	}

	public static function stub($text="")
	{
		return \Template::instance()->render('main/stub.html');
	}

	protected function dataProcess(&$item, $key=NULL)
	{
		if (isset($item['modified']))	$item['modified']	= ($item['modified'] > ($item['published'] + (24*60*60) ) ) ?
																	date(\Config::getPublic('date_format'),$item['modified']) :
																	NULL;
		if (isset($item['published']))	$item['published']	= date(\Config::getPublic('date_format'),$item['published']);
		//								$item['number']		= isset($item['inorder']) ? "{$item['inorder']}&nbsp;" : "";
		if (isset($item['wordcount'])) 	$item['wordcount']	= number_format($item['wordcount'], 0, '','.');
		if (isset($item['count'])) 		$item['count']		= number_format($item['count'], 0, '','.');

		if (isset($item['cache_authors']))
		{
												if ( NULL !== $item['authors'] 	= $item['cache_authors'] = json_decode($item['cache_authors'],TRUE) )
													array_walk($item['authors'], function (&$v, $k){ $v = $v[1];} );
		}

		if (isset($item['cache_categories'])) 	$item['cache_categories']	= json_decode($item['cache_categories'],TRUE);
		if (isset($item['cache_rating'])) 		$item['cache_rating']		= json_decode($item['cache_rating'],TRUE);
		if (isset($item['max_rating'])) 		$item['max_rating']			= json_decode($item['max_rating'],TRUE);
		if (isset($item['cache_tags'])) 		$item['cache_tags']			= json_decode($item['cache_tags'],TRUE);
		if (isset($item['cache_characters'])) 	$item['cache_characters']	= json_decode($item['cache_characters'],TRUE);
												// build a combined tag/character array
												$item['all_tags'] 			= array_merge( $item['cache_tags']['simple']??[], $item['cache_characters']??[] );
		if (isset($item['cache_stories']))		$item['cache_stories']		= json_decode($item['cache_stories'],TRUE);
	}

	public function commentFormBase($structure,$data)
	{
		// 'structure' formating, clearing and naming in child function call
		$this->f3->set('structure', $structure);
		$this->f3->set('data', $data );

		return $this->render('main/feedback.html');
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

		// crop by letter count, but make an attempt to let the sentence be completed
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
			$text .= '<span class="toggle">
						<span class="toggle_more" style=""><i class="fas fa-caret-square-down"></i> Show details</span>
						<span class="toggle_less" style="display: none;"><i class="fas fa-caret-square-up"></i> Hide details</span>
					</span>
					<div class="toggle_container" style="display: none;">'.$remains.'</div>';
//			$text .= "<span id='more' style='display: none;'>{$remains}</span><button onclick='myFunction()' id='myBtn'>Read more</button>";
/*			$text .= '<script>
						function myFunction() {
							var moreText = document.getElementById("more");
							var btnText = document.getElementById("myBtn");

							if (moreText.style.display === "inline") {
								btnText.innerHTML = "Read more"; 
								moreText.style.display = "none";
							} else {
								btnText.innerHTML = "Read less"; 
								moreText.style.display = "inline";
							}
						}
					</script>';			*/
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
