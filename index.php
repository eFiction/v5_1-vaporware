<?php
/** @var Base $f3 */
$fw = require('lib/base.php');
$fw->set('APP_VERSION', '5.0.0-dev');

new Session();

ini_set('display_errors', 1);
error_reporting(1);
error_reporting(defined('E_STRICT') ? E_ALL | E_STRICT : E_ALL );

$fw->config('data/config.ini');
$fw->config('data/routes.common.ini');

// Load configuration
$cfg = Config::instance();

if ($cfg->ACTIVE_DB)
    $fw->set('DB', storage::instance()->get($cfg->ACTIVE_DB));
else {
    $fw->error(500,'Sorry, but there is no active DB setup.');
}
$fw->set('CONFIG', $cfg);

if (\Controller\Auth::isLoggedIn()) {
//	$fw->route('GET|POST /login', 'Controller\Page->getMain');
//	$fw->route('GET /logout/*', 'Controller\Auth->logout');
	
	$fw->config('data/routes.member.ini');
	if ( $_SESSION['groups'] & 64 )
		$fw->config('data/routes.admin.ini');
}
else
{
//	$fw->route('GET|POST /login', 'Controller\Auth->login');
//	$fw->route('GET /logout/*', 'Controller\Page->getMain');

	$fw->config('data/routes.guest.ini');
}
//$fw->set('DEBUG', 1);
$fw->run();