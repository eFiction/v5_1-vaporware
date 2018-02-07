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

// Turn off strict return type declaration for PHP 7
declare(strict_types=0);

/** initialize the framework **/
$f3 = @require('lib/f3/base.php');

/** define the current version of eFiction **/
$f3->set('APP_VERSION', '5.0.0-dev.0');

//new Session();
/** define debugging and error reporting options **/

ini_set('display_errors', '1');
error_reporting(1);
error_reporting(E_ALL);// & ~E_NOTICE & ~E_DEPRECATED );

/*
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
        $severity =
            1 * E_ERROR |
            0 * E_WARNING |
            0 * E_PARSE |
            0 * E_NOTICE |
            0 * E_CORE_ERROR |
            0 * E_CORE_WARNING |
            0 * E_COMPILE_ERROR |
            0 * E_COMPILE_WARNING |
            0 * E_USER_ERROR |
            0 * E_USER_WARNING |
            0 * E_USER_NOTICE |
            0 * E_STRICT |
            0 * E_RECOVERABLE_ERROR |
            0 * E_DEPRECATED |
            0 * E_USER_DEPRECATED;
        $ex = new ErrorException($errstr, 0, $errno, $errfile, $errline);
        if (($ex->getSeverity() & $severity) != 0) {
            //critical Error, revert to f3s error handler
            restore_error_handler();
        }
        else
        {
            $f3=Base::instance();
            // Only show non Critical Errors if the DEBUG Variable is higher than 0
            if($f3->get('DEBUG')>0)
            {
                //echo $errstr."<br>";
            }   
        }
    }
	
set_error_handler('exception_error_handler');
	*/

/** load the framework core config file **/
$f3->config('data/config.ini');

/* get database config */
$config = new Config();

/** Establish database connection **/
$f3->set('DB', storage::instance()->build() );
if ( FALSE === $f3->get('DB') )
{
	$f3->error(500,'Sorry, but there is no active DB setup.');
}

/** Add the configuration to the framework **/
$f3->set('CONFIG', $config->load());

/** We have DB and Config, let's check for bad ppl **/
if ( TRUE == $config->bb2_enabled )
	require('app/bad-behaviour-efiction5.php');

/** Load routes **/
require('app/routes.php');

/** Define the basic language **/
$f3->set('ENCODING','UTF-8');
$f3->set('LANGUAGE',$_SESSION['preferences']['language']);
setlocale(LC_ALL, __transLocale);		// http://www.php.net/setlocale
//$f3->set('DEBUG', 1);
/** Knock on wood and set sails **/
$f3->run();
/** S.D.G. **/
