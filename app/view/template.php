<?php
namespace View;

class Template extends Base
{
	/*
		class Template extends the base view, 
		providing everything required to handle
		the actual output.
		This way, the view class remains slim
	*/

	/**
     * create and return response content
     * @return mixed
     */
	public function __construct() {
		parent::__construct();
		$UI_BASE = $this->f3->get('UI');
		$UI = "";

		// get a layout to be used
		if ( isset($_SESSION['preferences']['layout']) AND isset($this->config["layout_available"][$_SESSION['preferences']['layout']]) AND FALSE==$this->config["layout_forced"] )
			$tpl = $_SESSION['preferences']['layout'];
		else
			$tpl = $this->config["layout_default"];

		if ( !file_exists( $UI_BASE.$tpl ) ) $tpl = "default";

		/*
			This so needs to be reworked!
		*/
		if($tpl!="default")
		{
			$this->f3->set('CSS_UI', "{$UI_BASE}{$tpl}/");
			$UI = "{$UI_BASE}{$tpl}/,";
		}
		else $this->f3->set('CSS_UI', "{$UI_BASE}default/");
		$UI .= "{$UI_BASE}default/";
		$this->f3->set('UI', $UI);
		
		$this->f3->set('SELF', rawurlencode($_SERVER["QUERY_STRING"]));

		\View\Base::javascript('body', TRUE, 'global.js' );
		\View\Base::javascript('body', FALSE, "var base='{$this->f3->get('BASE')}'" );
	}

	public function addTitle($string)
	{
		 $this->title[] = $string;
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
	
	static public function parse($key)
	{
		list(, $label, $visibility, $text) = $key;
		if (empty($label)) return NULL;

		// Empty $text should not overwrite a title tag of a parent item
		if ($text)
			return str_replace("@T@", "title='{$text}'", self::instance()->{$label});
		else
			return str_replace("@T@", "", self::instance()->{$label});
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
			else
			{
				if(strpos($item["name"],",")!==0)
				{
					$names = explode(",",$item['name']);
					foreach ( $names as $name )
						$icon->{$name} = str_replace("@1@",$item["value"],$pattern);
				}
				else
					$icon->{$item['name']} = str_replace("@1@",$item["value"],$pattern);
			}
		}
		$icon->save();
		return $icon;
	}
}
