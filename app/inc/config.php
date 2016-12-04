<?php

class Config extends \Prefab
{
	static private $protected = array();
	static private $public = array();
	
	public function __construct()
	{
		include('data/config.php');
		self::$protected = $config;
		self::$public['prefix'] = $config['prefix'];
	}
	
	public function load()
	{
		if ( FALSE === self::$public = \Cache::instance()->get('config') )
		{
			self::$public['prefix'] = self::$protected['prefix'];
			self::$public = self::$public + self::cache();
			\Cache::instance()->set('config', self::$public, 3600);
		}
		return self::$public;
	}
	
	public static function getPublic($key)
    {
        return isset(self::$public[$key]) ? self::$public[$key] : false;
    }

	public static function setPublic($key, $value=NULL)
    {
        self::$public[$key] = $value;
    }

	public static function getProtected($key)
    {
        return isset(self::$protected[$key]) ? self::$protected[$key] : false;
    }

	public static function cache()
	{
		$sqlList = "SELECT `name`, `value` from `tbl_config` ORDER BY `admin_module`, `section_order` ASC";
		$configValues = \Model\Base::instance()->exec($sqlList);
		$configData = [];
		
		foreach ( $configValues as $cfgVal )
		{
			$key	= $cfgVal['name'];
			$value	= $cfgVal['value'];

			if ( $value == "TRUE") $value = TRUE;
			elseif ( $value == "FALSE") $value = FALSE;

			$key = explode("__", $key);
			if ( isset($key[1]) )
			{	
				// nested key structures, like bb2__verbose -> bb2[verbose]
				if ( empty( $configData[$key[0]] ) ) $configData[$key[0]] = [];
				$configData[$key[0]][$key[1]] = $value;
			}
			else
			{
				if ( NULL === $c = json_decode( $value ,TRUE ) )
					$configData[$key[0]] = $value;
				else
					$configData[$key[0]] = $c;
			}
		}
		
		return $configData;
	}

	public function __get($key)
    {//$this->key // returns public->key
        return isset(self::$public[$key]) ? self::$public[$key] : false;
    }

    public function __isset($key)
    {
        return isset(self::$public[$key]);
    }
}

?>