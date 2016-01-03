<?php
/** @var Base $f3 */
$fw = require('lib/base.php');
$fw->set('APP_VERSION', '5.0.0-dev');

//new Session();
/*
		function storyDataBuild(&$item, $key)
		{
			print_r($item);
		}
*/
ini_set('display_errors', 1);
error_reporting(1);
error_reporting(defined('E_STRICT') ? E_ALL | E_STRICT : E_ALL );

$fw->config('data/config.ini');

// Load configuration
$cfg = Config::instance();

if ($cfg->ACTIVE_DB)
    $fw->set('DB', storage::instance()->get($cfg->ACTIVE_DB));
else {
    $fw->error(500,'Sorry, but there is no active DB setup.');
}
$fw->set('CONFIG', $cfg);

require('app/routes.php');

$fw->set('LOCALES','languages/');
$fw->set('LANGUAGE','de.UTF-8');
//$fw->set('DEBUG', 1);

$fw->run();
// S.D.G.