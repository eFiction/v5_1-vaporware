<?php

class Config extends \Prefab
{
	static private $protected = array();
	static private $public = array();
	
	public function __construct()
	{
		include('data/config.php');
		self::$protected = $config;
		//self::$public['prefix'] = $config['prefix'];
	}
	
	public function load()
	{
		if ( FALSE === self::$public = \Cache::instance()->get('config') OR !is_array(self::$public) )
		{
			//echo "cache not loaded";exit;
			self::$public = self::cache();
			\Cache::instance()->set('config', self::$public, 3600);
		}
		self::$public['prefix'] = self::$protected['prefix'];
		if(!self::$public['maintenance'])
		{
			// this will host the place where custom routes for plugins will be loaded when not in maintenance mode
			
		}

		if ( (FALSE === $report = \Cache::instance()->get('report')) OR ( $report!=self::$public['server_report']) )
			self::report();

		return self::$public;
	}
	
	private static function report()
	{
		/*
			This function will, provided the settings allow it, gather statistical data and submit them to efiction.org
			This happens either weekly or montly and will be kept confidential
		*/
		if ( self::$public['server_report'] == 0 )
			$seconds = 0;
		else
		{
			if ( self::$public['server_report'] == 1 )
				$seconds = strtotime('next Monday',strtotime("now")) - 1 - time();
			elseif ( self::$public['server_report'] == 2 )
				$seconds = strtotime('+1 month',strtotime(date('m').'/01/'.date('Y').' 00:00:00')) - 1 - time();
			
			// workaround to allow pre-built queries to be run prior to the config being fully loaded
			\Base::instance()->set('CONFIG.prefix', self::$protected['prefix']);
			
			// gather data
			$server_data =
			[
				"php_version" => phpversion(),
				"mysql_version" => \Model\Base::instance()->exec("select version() as v;")[0]["v"],
				"mysql_version2" => \Base::instance()->get('DB')->version(),
				"extensions"  => get_loaded_extensions(),
				"identity" => hash( "sha256", $_SERVER['SERVER_NAME'].\Base::instance()->get('BASE') ),
				"stats"	=> \Model\Story::instance()->blockStats(),
			];
			
			// attemt to create and drop an SQL View
			try {
				$probe = \Model\Base::instance()->exec( "CREATE VIEW test_view AS SELECT * FROM `".self::$protected['prefix']."config`;");
				$server_data['create_view'] = 1;
				\Model\Base::instance()->exec( "DROP VIEW IF EXISTS test_view;");
			} catch (PDOException $e) {
				$server_data['create_view'] = 0;
			}
			
			if ( self::$public['server_report_anon'] == 0 )
				$server_data['location'] = \Base::instance()->get('SCHEME')."://".$_SERVER['SERVER_NAME'].\Base::instance()->get('BASE');
			
			// submit data to efiction.org
			$curl_handle=curl_init();
			curl_setopt($curl_handle,CURLOPT_URL,'https://efiction.org/gatherer/collect5.0.php');
			curl_setopt($curl_handle, CURLOPT_POST, 1);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER , 1);
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, http_build_query($server_data) );
			$res = curl_exec($curl_handle);
			curl_close($curl_handle);
		}

		\Cache::instance()->set('report', self::$public['server_report'], $seconds );
	}
	public static function getPublic($key)
    {
        return isset(self::$public[$key]) ? self::$public[$key] : false;
    }

	public static function getTree()
    {
        return isset(self::$public) ? self::$public : false;
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
		$sqlList = "SELECT `name`, `value` from `".self::$protected['prefix']."config` ORDER BY `admin_module`, `section_order` ASC";
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
		
		if ( "" == trim($configData['date_format']) )
			$configData['date_format'] = $configData['date_preset'];
		if ( "" == trim($configData['time_format']) )
			$configData['time_format'] = $configData['time_preset'];
		if ( "" == trim($configData['datetime_format']) )
			$configData['datetime_format'] = $configData['date_preset']." ".$configData['time_preset'];
		
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