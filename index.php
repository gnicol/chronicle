<?php
/**
 * Chronicle
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */

// check for PHP 5.3+
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300) {
    die("<html><body><h1>Chronicle has detected a configuration error</h1>"
        . "<p>Perforce Chronicle requires PHP 5.3 or higher.</p><br/>"
        . "Please ensure you restart your web server after making any changes.</body></html>");
}

// verify the iconv extension is present; we crash in bootstrap without it
if (!extension_loaded('iconv')) {
    die("<html><body><h1>Chronicle has detected a configuration error</h1>"
        . "<p>It appears the iconv PHP extension is not installed or not enabled for your web server.</p><br/>"
        . "Please ensure you restart your web server after making any changes.</body></html>");
}

// determine application environment (can be set in .htaccess or vhost).
// don't short circuit our ternary as php might be under 5.3 at this point.
if (!defined('APPLICATION_ENV')) {
    define(
        'APPLICATION_ENV',
        getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'
    );
}

// define the base, data, application and library paths relative to this file.
if (!defined('BASE_PATH')) {
    define('BASE_PATH',         realpath(__DIR__));
}
if (!defined('DATA_PATH')) {
    define('DATA_PATH',         BASE_PATH . '/data');
}
if (!defined('APPLICATION_PATH')) {
    define('APPLICATION_PATH',  BASE_PATH . '/application');
}
if (!defined('LIBRARY_PATH')) {
    define('LIBRARY_PATH',      BASE_PATH . '/library');
}

// smallest possible include path.
set_include_path(LIBRARY_PATH);

// create application, bootstrap and run
require_once 'P4Cms/Application.php';
require_once APPLICATION_PATH . '/Bootstrap.php';
$configFile  = DATA_PATH . '/application.ini';
$application = new P4Cms_Application(APPLICATION_ENV, $configFile);
$application->bootstrap()
            ->run();
