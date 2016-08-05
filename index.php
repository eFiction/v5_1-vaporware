<?php
/**

    eFiction v5

    The contents of this file are subject to the terms of the GNU General
    Public License Version 3.0. You may not use this file except in
    compliance with the license. Any of the license terms and conditions
    can be waived if you get permission from the copyright holder.

    Copyright (c) 2015,2016 by the eFiction development team:
    Rainer Volkrodt <rainer@efiction.org>

    Following the footsteps of eFiction 3
    Copyright (c) 2007 by Tammy Keefer,
    which was based on eFiction 1.1
    Copyright (C) 2003 by Rebecca Smallwood.

    For over one decade, you shaped the way fanfiction sites were run.
    Humbly trying to pick up from where you left - because nobody else did.

    @version 5.0.0-dev.0

    This software is based on the fat-free framework - http://fatfreeframework.com

    The structure was heavily inspired by fabulog, a demo blog by Christian Knuth.
    Also using parts of his extended pagination module.
    Both can be found at - https://github.com/ikkez - Thanks for you works.

    Also using various jQuery modules, apart form the mighty jQuery itself.
    http://jquery.com

 **/

/** initialize the framework **/
$f3 = require('lib/base.php');

/** define the current version of eFiction **/
$f3->set('APP_VERSION', '5.0.0-dev.0');

//new Session();
/** define debugging and error reporting options **/
ini_set('display_errors', 1);
error_reporting(1);
error_reporting(defined('E_STRICT') ? E_ALL | E_STRICT : E_ALL );

/** load the core config file **/
$f3->config('data/config.ini');
$cfg = Config::instance();

/** Establish database connection **/
if ($cfg->ACTIVE_DB)
    $f3->set('DB', storage::instance()->get($cfg->ACTIVE_DB));
else {
    $f3->error(500,'Sorry, but there is no active DB setup.');
}

/** Add the configuration to the framework **/
$f3->set('CONFIG', $cfg);

/** We have DB and Config, let's check for bad ppl **/
if ( TRUE === $cfg->bb2_enabled )
	require('bad-behaviour.php');

/** Load routes **/
require('app/routes.php');

/** Define the basic language **/
$f3->set('LANGUAGE','de.UTF-8');
//$f3->set('DEBUG', 1);
setlocale(LC_ALL, __transLocale);
/** Knock on wood and set sails **/
$f3->run();
/** S.D.G. **/
