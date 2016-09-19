<?php

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataModel;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Core\Startup\ParameterConfirmationToken;
use SilverStripe\Core\Startup\ErrorControlChain;
use SilverStripe\Control\Session;
use SilverStripe\Control\Director;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.5.0 or higher                       .                    **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

if (version_compare(phpversion(), '5.5.0', '<')) {
	header($_SERVER['SERVER_PROTOCOL'] . " 500 Server Error");
	echo str_replace('$PHPVersion', phpversion(), file_get_contents("Dev/Install/php5-required.html"));
	die();
}

/**
 * Main file that handles every page request.
 *
 * The main.php does a number of set-up activities for the request.
 *
 *  - Includes the first one of the following files that it finds: (root)/_ss_environment.php,
 *    (root)/../_ss_environment.php, or (root)/../../_ss_environment.php
 *  - Gets an up-to-date manifest from {@link ManifestBuilder}
 *  - Sets up error handlers with {@link Debug::loadErrorHandlers()}
 *  - Calls {@link DB::connect()}, passing it the global variable $databaseConfig that should
 *    be defined in an _config.php
 *  - Sets up the default director rules using {@link Director::$rules}
 *
 * After that, it calls {@link Director::direct()}, which is responsible for doing most of the
 * real work.
 *
 * CONFIGURING THE WEBSERVER
 *
 * To use SilverStripe, every request that doesn't point directly to a file should be rewritten to
 * framework/main.php?url=(url).  For example, http://www.example.com/about-us/rss would be rewritten
 * to http://www.example.com/framework/main.php?url=about-us/rss
 *
 * It's important that requests that point directly to a file aren't rewritten; otherwise, visitors
 * won't be able to download any CSS, JS, image files, or other downloads.
 *
 * On Apache, RewriteEngine can be used to do this.
 *
 * @see Director::direct()
 */

// require composers autoloader, unless it is already installed
if(!class_exists('Composer\\Autoload\\ClassLoader', false)) {
	if (file_exists($autoloadPath = dirname(__DIR__) . '/vendor/autoload.php')) {
		require_once $autoloadPath;
	}
	else {
		if (!headers_sent()) {
			header($_SERVER['SERVER_PROTOCOL'] . " 500 Server Error");
			header('Content-Type: text/plain');
		}
		echo "Failed to include composer's autoloader, unable to continue\n";
		exit(1);
	}
}

$request = Request::createFromGlobals();

/**
 * Include SilverStripe's core code
 */
require_once('Core/Startup/ErrorControlChain.php');
require_once('Core/Startup/ParameterConfirmationToken.php');

// Prepare tokens and execute chain
$reloadToken = ParameterConfirmationToken::prepare_tokens(array('isTest', 'isDev', 'flush'));
$chain = new ErrorControlChain();
$chain
	->then(function($chain) use ($reloadToken, $request) {
		// If no redirection is necessary then we can disable error supression
		if (!$reloadToken) $chain->setSuppression(false);

		// Load in core
		require_once('Core/Core.php');

		// Connect to database
		global $databaseConfig;
		if ($databaseConfig) DB::connect($databaseConfig);

		// Check if a token is requesting a redirect
		if (!$reloadToken) return;

		// Otherwise, we start up the session if needed
		if(!isset($_SESSION) && Session::request_contains_session_id()) {
			Session::start();
		}

		// Next, check if we're in dev mode, or the database doesn't have any security data, or we are admin
		if (Director::isDev() || !Security::database_is_ready() || Permission::check('ADMIN')) {
			return $reloadToken->reloadWithToken();
		}

		// Fail and redirect the user to the login page
		$loginPage = Director::absoluteURL(Security::config()->login_url);
		$loginPage .= "?BackURL=" . urlencode($request->getUri());
		Response::create('', 302, array(
			'Location' => $loginPage,
		))->sendHeaders();
		die;
	})
	// Finally if a token was requested but there was an error while figuring out if it's allowed, do it anyway
	->thenIfErrored(function() use ($reloadToken){
		if ($reloadToken) {
			$reloadToken->reloadWithToken();
		}
	})
	->execute();

global $databaseConfig;

// Redirect to the installer if no database is selected
if(!isset($databaseConfig) || !isset($databaseConfig['database']) || !$databaseConfig['database']) {
	if(!file_exists(BASE_PATH . '/install.php')) {
		Response::create('SilverStripe Framework requires a $databaseConfig defined.', 500)->send();
		exit(1);
	}
	Response::create('', 302, array(
		'Location' => $request->getUriForPath('install.php'),
	))->send();
	exit(1);
}

// Direct away - this is the "main" function, that hands control to the appropriate controller
DataModel::set_inst(new DataModel());
Director::direct($request->getPathInfo(), DataModel::inst());
