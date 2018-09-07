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

	public static function stub($text="")
	{
		return \Template::instance()->render('stub.html');
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
		if (isset($item['cache_tags'])) 		$item['cache_tags']			= json_decode($item['cache_tags'],TRUE);
		if (isset($item['cache_characters'])) 	$item['cache_characters']	= json_decode($item['cache_characters'],TRUE);
		if (isset($item['cache_stories']))		$item['cache_stories']		= json_decode($item['cache_stories'],TRUE);
	}

	public function commentFormBase($structure,$data)
	{
		// 'structure' formating, clearing and naming in child function call
		\Base::instance()->set('structure', $structure);
		\Base::instance()->set('data', $data );

		return $this->render('main/feedback_form.html');
	}
	
}
