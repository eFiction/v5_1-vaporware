<?php
namespace View;
abstract class Base {
	
    public $data = array();
	public $JS = [];
    public $modules = [];
	protected $title = [];

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
	
	public function addTitle($string)
	{
		 $this->title[] = $string;
	}
	
	public static function render($file,$mime='text/html',array $hive=NULL,$ttl=0)
	{
		return "<!-- FILE: {$file} -->".\Template::instance()->render($file,$mime,$hive,$ttl)."<!-- END: {$file} -->";
	}
	
}

class Iconset extends \DB\Jig\Mapper {
	
	public function __construct()
	{
		$db = new \DB\Jig('tmp/');
		parent::__construct($db,"iconset.{$_SESSION['tpl'][1]}.json");
		$this->load();
	}
	
	static public function instance()
	{
		if (\Registry::exists('ICONSET'))
			return \Registry::get('ICONSET');
		else
		{
			$icon = new self;
			if ( empty($icon->_name) ) $icon = self::rebuild($icon);
			\Registry::set('ICONSET',$icon);
			return $icon;
		}
	}
	
	static protected function rebuild($icon)
	{
		$set = $_SESSION['tpl'][1];
		$sql = "SELECT `name`, `value` FROM `tbl_iconsets` WHERE `set_id` = {$set}";
		$db = \Model\Base::instance();
		$data = $db->exec($sql);
		foreach ( $data as $item )
		{
			if(strpos($item["name"],"#")===0)
			{
				if ( $item["name"]=="#pattern" && $item['value']!=NULL )
					$pattern = $item["value"];
				elseif ( $item["name"]=="#directory" && $item['value']!=NULL )
					$pattern = "<img src=\"{$BASE}/template/iconset/{$item['value']}/@1@\" >";
				if ( $item["name"]=="#name" )
					$icon->_name = $item['value'];
			}
			else $icon->{$item['name']} = str_replace("@1@",$item["value"],$pattern);
		}
		$icon->save();
		return $icon;
	}
}
