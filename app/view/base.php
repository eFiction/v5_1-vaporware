<?php
namespace View;
abstract class Base {
	
    public $data = array();
	public $JS = [];
    public $modules = [];

	/**
     * create and return response content
     * @return mixed
     */
	public function __construct() {
		$fw = \Base::instance();
		$UI = $fw->get('UI');

		// develop
		$tpl = 'default';
		
		$folder = file_exists($UI.$tpl.'/layout.html') ? $tpl : 'default';
		$fw->set('UI', "{$UI}{$folder}/");

		
		$fw->set('SELF', rawurlencode($_SERVER["QUERY_STRING"]));

		\View\Base::javascript('body', TRUE, 'global.js' );
		//$this->css[] = "styles.css";
	}
	
	public function javascript($location, $file=FALSE, $string)
	{
		$fw = \Base::instance();
		if($file)
		{
			$this->JS[$location][] = (strpos($string,"//")===0)
																? "<script src=\"{$string}\"></script>"
																: "<script src=\"".$fw->get('BASE')."/app/inc/{$string}\"></script>";
		}
		else $this->JS[$location][] = "<script type=\"text/javascript\">{$string}</script>";
	}
	
	//abstract public function render();
	
	/*
	static public function getCSS()
	{
		// in template:_ 		{{ \View\Base::getCSS() }}
		$fw = \Base::instance();
		$view = \Registry::get('VIEW');
		foreach ( $view->css as $cssfile )
		{
			$css[] = "<link rel='stylesheet' type='text/css' href='{$fw->get('UI')}css/styles.css'>";
		}
	return implode("\n",$css)."\n";
	}
	*/
	
}