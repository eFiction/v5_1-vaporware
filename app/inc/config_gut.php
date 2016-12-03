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
		if (empty(self::$public['version']))
		{
			$cache = \Model\Base::instance()->exec("SELECT `data` FROM `tbl_cache` WHERE `store`='cache';");

			if(sizeof($cache)>0)
				self::$public = self::$public + json_decode($cache[0]['data'],TRUE);

			else
				self::$public = self::$public + self::cache();

		}
	}
	
	public static function getPublic($key)
    {
        return isset(self::$public[$key]) ? self::$public[$key] : false;
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
		
		$configJSON = json_encode($configData);

		\Model\Base::instance()->exec ( "INSERT INTO `tbl_cache` (`store`, `data`) VALUES ('cache', :data)
							ON DUPLICATE KEY UPDATE `data` = :data2;", [ ":data" => $configJSON, ":data2" => $configJSON ] );
		
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