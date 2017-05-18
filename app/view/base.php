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
		$this->f3->JS = [ "head" => [], "body" => [] ];
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
	
}