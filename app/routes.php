<?php
/* --------------------
	Common routes
-------------------- */

$fw->route( 'GET /', 'Controller\Page->getMain' );

$fw->route(
  [ 'GET /?/redirect/@b/@c', 'GET /redirect/@a/@b', ],
  'Controller\Redirect->filter' );

$fw->route(
  [ 
	'GET /story',
	'GET /story/@action',
	'GET /story/@action/@id',
	'GET /story/@action/@id/*'
  ],
  'Controller\Story->index' );
$fw->route(
  [ 'POST /story', 'POST /story/@action' ], 'Controller\Story->save' );

$fw->route(
  [ 'GET /story/search', 'GET /story/search/*', ],
  'Controller\Story->search' );

$fw->route(
  [ 'GET /authors', 'GET /authors/@id', 'GET /authors/@id/*' ],
  'Controller\Authors->index' );

$fw->route( 'GET /shoutbox/@action/@sub', 'Controller\Blocks->shoutbox' );

// Ajax routes
$fw->route( 'GET /blocks/calendar/* [ajax]', 'Controller\Blocks->calendar' );

if (\Controller\Auth::isLoggedIn())
{
	/* --------------------
		Member routes
	-------------------- */
	$fw->route('GET|POST /login', function($fw) { $fw->reroute('/', false); } );

	$fw->route(
		[ 'GET /logout', 'GET /logout/*' ],
		'Controller\Auth->logout' );

	$fw->route( 'GET|POST /panel', 'Controller\Panel->main' );

	$fw->route(
		[ 'GET|POST /userCP', 'GET|POST /userCP/*' ],
		'Controller\UserCP->index' );
	
	$fw->route(
		[ 'GET|POST /userCP/messaging', 'GET|POST /userCP/messaging/*' ],
		'Controller\UserCP->messaging' );
	
	// Ajax routes
	$fw->route( 'GET /userCP/@module/* [ajax]', 'Controller\UserCP->ajax' );

	if ( $_SESSION['groups'] & 64 )
	{
		/* --------------------
			Mod/Admin routes
		-------------------- */
		$fw->route(
			[ 'GET|POST /adminCP', 'GET|POST /adminCP/*' ],
			'Controller\AdminCP->catch' );

		// Archive
		$fw->route(
			[ 'GET /adminCP/archive', 'GET /adminCP/archive/@module', 'GET /adminCP/archive/@module/*' ],
			'Controller\AdminCP_Archive->index' );
		$fw->route( 'POST /adminCP/archive/@module', 'Controller\AdminCP_Archive->save' );

		// Home
		$fw->route(
			[ 'GET /adminCP/home', 'GET /adminCP/home/@module', 'GET /adminCP/home/@module/*' ],
			'Controller\AdminCP_Home->index' );
		$fw->route( 'POST /adminCP/home/@module', 'Controller\AdminCP_Home->save' );

		// Members
		$fw->route(
			[ 'GET /adminCP/members', 'GET /adminCP/members/@module', 'GET /adminCP/members/@module/*' ],
			'Controller\AdminCP_Members->index' );
		$fw->route( 'POST /adminCP/members/@module', 'Controller\AdminCP_Members->save' );

		// Stories
		$fw->route(
			[ 'GET /adminCP/stories', 'GET /adminCP/stories/@module', 'GET /adminCP/stories/@module/*' ],
			'Controller\AdminCP_Stories->index' );
		$fw->route( 'POST /adminCP/stories/@module', 'Controller\AdminCP_Stories->save' );

	}

	if ( $_SESSION['groups'] & 128 )
	{
		/* --------------------
			Admin only routes
		-------------------- */
		$fw->route(
			[ 'GET /adminCP/settings', 'GET|POST /adminCP/settings/@module' ],
			'Controller\AdminCP_Settings->index' );
		$fw->route( 'POST /adminCP/settings/@module', 'Controller\AdminCP_Settings->save' );

		
	}
}
else
{
	/* --------------------
		Guest routes
	-------------------- */

	$fw->route( 'GET|POST /forgotpw', 'Controller\Auth->forgotpw' );

	$fw->route( 'GET|POST /register', 'Controller\Auth->register' );

	$fw->route(
		[ 'GET|POST /logout', 'GET|POST /logout' ],
		function($fw) { $fw->reroute('/', false); } );

	$fw->route(
		[
			'GET|POST /userCP',
			'GET|POST /userCP/*',
			'GET|POST /adminCP',
			'GET|POST /adminCP/*',
			'GET|POST /login'
		],
		'Controller\Auth->login' );
}
