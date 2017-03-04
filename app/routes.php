<?php

/* check member status create SESSION  */
\Controller\Auth::isLoggedIn($f3);

/* --------------------
	Common routes
-------------------- */


if ( FALSE == $cfg->getPublic('maintenance') OR $_SESSION['groups'] & 64 )
{
	$f3->route( [ 'GET /', 'GET /page/*', 'GET /*' ], 'Controller\Page->getMain' );

	// Load routes if not in maintenance
	$f3->route(
	  [ 'GET /*/redirect/@b/@c', 'GET /redirect/@a/@b', ],
	  'Controller\Redirect->filter' );

	$f3->route(
	  [ 'GET /story', 'GET /story/@action', 'GET /story/@action/*', ],
		'Controller\Story->index' );

	$f3->route(
	//  [ 'POST /story', 'POST /story/@action' , 'POST /story/@action/*' ],
	  [ 'POST /story/@action' , 'POST /story/@action/*' ],
		'Controller\Story->save' );

	$f3->route(
	  [ 'GET /story/search', 'GET /story/search/*', 'POST /story/search',
		'GET /story/browse', 'GET /story/browse/*', 'POST /story/browse' ],
		'Controller\Story->search' );

	$f3->route(
	  [ 'GET /authors', 'GET /authors/@id', 'GET /authors/@id/*' ],
		'Controller\Authors->index' );

	$f3->route(
	  [ 'GET|POST /news', 'GET /news/*' ],
		'Controller\News->index' );
	$f3->route(
	  [ 'POST /news/*' ],
		'Controller\News->save' );

	$f3->route( 'GET /shoutbox/@action/@sub', 'Controller\Blocks->shoutbox' );


	// Ajax routes
	$f3->route( 
			[ 'GET|POST /captcha [ajax]', 'GET|POST /captcha/* [ajax]' ],
			'Controller\Auth->captcha' );
	$f3->route( 'GET /blocks/calendar/* [ajax]', 'Controller\Blocks->calendar' );
	$f3->route( 'POST /shoutbox/* [ajax]', 'Controller\Blocks->shoutbox' );
	$f3->route( 'POST /story/ajax/@segment [ajax]', 'Controller\Story->ajax' );
}
else
	$f3->route( [ 'GET /', 'GET /page/*', 'GET /*' ], 'Controller\Page->maintenance' );


if ($_SESSION['groups'] & 1)
{
	// Logout is always possible
	$f3->route(
	[ 'GET /logout', 'GET /logout/*' ],
	'Controller\Auth->logout' );

	if ( FALSE == $cfg->getPublic('maintenance') OR $_SESSION['groups'] & 64 )
	{
		/* --------------------
			Member routes
			only if not in
			maintenance
		-------------------- */
		$f3->route([ 'GET|POST /login', 'GET|POST /register'] , function($f3) { $f3->reroute('/', false); } );

		$f3->route(
			[ 'GET|POST /userCP', 'GET|POST /userCP/*' ],
			'Controller\UserCP->index' );

		// Ajax routes
		$f3->route( ['POST /userCP/ajax/@module [ajax]', 'POST /userCP/ajax/@module/@sub [ajax]'], 'Controller\UserCP->ajax' );

		if ( $_SESSION['groups'] & 32 )
		{
			/* --------------------
				additional Mod routes
				only if not in
				maintenance
			-------------------- */
			$f3->route(
				[ 'GET|POST /adminCP', 'GET|POST /adminCP/*' ],
				'Controller\AdminCP->fallback' );

			// Home
			$f3->route(
				[ 'GET /adminCP/home', 'GET|POST /adminCP/home/@module', 'GET|POST /adminCP/home/@module/*' ],
				'Controller\AdminCP_Home->index' );

			// Stories
			$f3->route(
				[ 'GET /adminCP/stories', 'GET /adminCP/stories/@module', 'GET /adminCP/stories/@module/*' ],
				'Controller\AdminCP_Stories->index' );
			$f3->route( 'POST /adminCP/stories/@module/*', 'Controller\AdminCP_Stories->save' );
			$f3->route( [ 'POST /adminCP/ajax/stories/@module [ajax]', 'POST /adminCP/ajax/stories/@module/* [ajax]' ], 'Controller\AdminCP_Stories->ajax' );
			
		}
	}

	if ( $_SESSION['groups'] & 64 )
	{
		/* --------------------
			SuperMod/Admin routes
		-------------------- */
		// Archive
		$f3->route(
			[ 'GET /adminCP/archive', 'GET|POST /adminCP/archive/@module', 'GET|POST /adminCP/archive/@module/*' ],
			'Controller\AdminCP_Archive->index' );
		$f3->route( 'POST /adminCP/ajax/archive/@module [ajax]', 'Controller\AdminCP_Archive->ajax' );

		// Members
		$f3->route(
			[ 'GET /adminCP/members', 'GET /adminCP/members/@module', 'GET /adminCP/members/@module/*' ],
			'Controller\AdminCP_Members->index' );
		$f3->route( 'POST /adminCP/members/@module', 'Controller\AdminCP_Members->save' );

		// Settings
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

	if ( FALSE == $cfg->getPublic('maintenance') )
		$f3->route( [ 'GET|POST /register', 'GET|POST /register/@status' ], 'Controller\Auth->register' );
	else
		$f3->route( [ 'GET /', 'GET /*', 'GET /*' ], 'Controller\Auth->login' );

	$f3->route(
		[ 'GET|POST /logout', 'GET|POST /logout' ],
		function($f3) { $f3->reroute('/', false); } );

	$f3->route(
		[
			'GET|POST /userCP',
			'GET|POST /userCP/*',
			'GET|POST /adminCP',
			'GET|POST /adminCP/*',
			'GET|POST /login',
			'GET|POST /login/*'
		],
		'Controller\Auth->login' );
}
