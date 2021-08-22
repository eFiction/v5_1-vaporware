<?php
namespace View;
abstract class Base {

  public 		$data 		= array();
  public 		$modules 	= [];
	protected $title 		= [];

	public function __construct()
	{
		$this->config = \Config::getTree();
		$this->f3 		= \Base::instance();
		$this->TPL 		= \Template::instance();
	}

	public function javascript(string $location, string $string, bool $file=FALSE): void
	{
		if($file)
		{
			$this->f3->JS[$location][] = (strpos($string,"//")===0)
																? "<script src=\"{$string}\"></script>"
																: "<script src=\"".$this->f3->get('BASE')."/app/js/{$string}\"></script>";
		}
		else $this->f3->JS[$location][] = "<script type=\"text/javascript\">{$string}</script>";
	}

	public function addTitle(string $string): void
	{
		// Used by a controller, this writes the title to the main render view
		 \Registry::get('VIEW')->addTitle($string);
	}

	public static function render(string $file, string $mime='text/html', array $hive=NULL, int $ttl=0): string
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

	public static function stub(string $text=""): string
	{
		return \Template::instance()->render('main/stub.html');
	}

	public function commentFormBase($structure,$data): string
	{
		// 'structure' formating, clearing and naming in child function call
		$this->f3->set('structure', $structure);
		$this->f3->set('data', $data );

		return $this->render('main/feedback.html');
	}

}
