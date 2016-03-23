<?php
/* --------------------
	Common routes
-------------------- */

$f3->route( 'GET /', 'Controller\Page->getMain' );

$f3->route(
  [ 'GET /?/redirect/@b/@c', 'GET /redirect/@a/@b', ],
  'Controller\Redirect->filter' );

$f3->route(
  [ 
	'GET /story',
	'GET /story/@action',
	'GET /story/@action/@id',
	'GET /story/@action/@id/*'
  ],
  'Controller\Story->index' );
$f3->route(
  [ 'POST /story', 'POST /story/@action' ], 'Controller\Story->save' );

$f3->route(
  [ 'GET /story/search', 'GET /story/search/*', 'POST /story/search' ],
  'Controller\Story->search' );

$f3->route(
  [ 'GET /authors', 'GET /authors/@id', 'GET /authors/@id/*' ],
  'Controller\Authors->index' );

$f3->route( 'GET /shoutbox/@action/@sub', 'Controller\Blocks->shoutbox' );

// Ajax routes
$f3->route( 'GET /blocks/calendar/* [ajax]', 'Controller\Blocks->calendar' );
$f3->route( 'POST /story/ajax/@segment [ajax]', 'Controller\Story->ajax' );

if (\Controller\Auth::isLoggedIn())
{
	/* --------------------
		Member routes
	-------------------- */
	$f3->route('GET|POST /login', function($f3) { $f3->reroute('/', false); } );

	$f3->route(
		[ 'GET /logout', 'GET /logout/*' ],
		'Controller\Auth->logout' );

	$f3->route( 'GET|POST /panel', 'Controller\Panel->main' );

	$f3->route(
		[ 'GET|POST /userCP', 'GET|POST /userCP/*' ],
		'Controller\UserCP->index' );
	
	$f3->route(
		[ 'GET|POST /userCP/messaging', 'GET|POST /userCP/messaging/*' ],
		'Controller\UserCP->messaging' );
	
	// Ajax routes
	$f3->route( 'POST /userCP/ajax/@module [ajax]', 'Controller\UserCP->ajax' );

	if ( $_SESSION['groups'] & 64 )
	{
		/* --------------------
			Mod/Admin routes
		-------------------- */
		$f3->route(
			[ 'GET|POST /adminCP', 'GET|POST /adminCP/*' ],
			'Controller\AdminCP->catch' );

		// Archive
		$f3->route(
			[ 'GET /adminCP/archive', 'GET /adminCP/archive/@module', 'GET /adminCP/archive/@module/*' ],
			'Controller\AdminCP_Archive->index' );
		$f3->route( 'POST /adminCP/archive/@module', 'Controller\AdminCP_Archive->save' );

		// Home
		$f3->route(
			[ 'GET /adminCP/home', 'GET /adminCP/home/@module', 'GET /adminCP/home/@module/*' ],
			'Controller\AdminCP_Home->index' );
		$f3->route( 'POST /adminCP/home/@module', 'Controller\AdminCP_Home->save' );

		// Members
		$f3->route(
			[ 'GET /adminCP/members', 'GET /adminCP/members/@module', 'GET /adminCP/members/@module/*' ],
			'Controller\AdminCP_Members->index' );
		$f3->route( 'POST /adminCP/members/@module', 'Controller\AdminCP_Members->save' );

		// Stories
		$f3->route(
			[ 'GET /adminCP/stories', 'GET /adminCP/stories/@module', 'GET /adminCP/stories/@module/*' ],
			'Controller\AdminCP_Stories->index' );
		$f3->route( 'POST /adminCP/stories/@module', 'Controller\AdminCP_Stories->save' );

	}

	if ( $_SESSION['groups'] & 128 )
	{
		/* --------------------
			Admin only routes
		-------------------- */
		$f3->route(
			[ 'GET /adminCP/settings', 'GET|POST /adminCP/settings/@module' ],
			'Controller\AdminCP_Settings->index' );
		$f3->route( 'POST /adminCP/settings/@module', 'Controller\AdminCP_Settings->save' );

		
	}
}
else
{
	/* --------------------
		Guest routes
	-------------------- */

	$f3->route( 'GET|POST /forgotpw', 'Controller\Auth->forgotpw' );

	$f3->route( 'GET|POST /register', 'Controller\Auth->register' );

	$f3->route(
		[ 'GET|POST /logout', 'GET|POST /logout' ],
		function($f3) { $f3->reroute('/', false); } );

	$f3->route(
		[
			'GET|POST /userCP',
			'GET|POST /userCP/*',
			'GET|POST /adminCP',
			'GET|POST /adminCP/*',
			'GET|POST /login'
		],
		'Controller\Auth->login' );
}
