<?php
/**
 * Bootstrap a test
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */

/**
 * Set error reporting to the level to code must comply.
 */
error_reporting(E_ALL & ~E_STRICT);

/**
 * Determine the root, library and tests directories of the distribution.
 */
$appRoot       = dirname(dirname(dirname(__FILE__)));
$appCore       = $appRoot . "/application";
$appLibrary    = $appRoot . "/library";
$appTests      = $appRoot . "/tests/phpunit";

/**
 * Prepend the app library and tests directories to the include path
 * so that tests can be run without manual configuration of the include path.
 */
$path = array($appCore, $appLibrary, $appTests, get_include_path());
set_include_path(implode(PATH_SEPARATOR, $path));

/**
 * Enable zend auto loader.
 */
require_once "P4Cms/Loader.php";
Zend_Loader_Autoloader::getInstance()->pushAutoloader(array('P4Cms_Loader', 'autoload'));

/**
 * If tests are run from other than main test folder,
 * include config section from main phpunit.xml file.
 */
$cwd = getcwd();
if ($cwd != $appTests) {
    $configFile = $appTests . '/phpunit.xml';
    if (file_exists($configFile)) {
        chdir(dirname($configFile));
        $config = PHPUnit_Util_Configuration::getInstance($configFile);
        $config->handlePHPConfiguration();
        chdir($cwd);
    }
}

/**
 * Define parameters for operating a test server instance.
 * Note: For each test, a fresh server/depot is created; previous depots are
 * recursively deleted. Do not modify these settings unless you know what
 * you are doing.
 */
if (!defined('TEST_DATA_PATH')) {
    define('TEST_DATA_PATH', $appRoot . '/tests/data/' . getmypid());
}
if (!defined('TEST_ASSETS_PATH')) {
    define('TEST_ASSETS_PATH', 	__DIR__ . '/assets');
}
if (!defined('TEST_SITES_PATH')) {
    define('TEST_SITES_PATH',  	TEST_ASSETS_PATH . '/sites');
}
if (!defined('TEST_SCRIPTS_PATH')) {
    define('TEST_SCRIPTS_PATH', TEST_ASSETS_PATH . '/scripts');
}

/**
 * Make sure the session storage is defined/setup.
 */
if (!defined('TEST_SESSION_SAVE_PATH')) {
    define(
        'TEST_SESSION_SAVE_PATH',
        getenv('P4CMS_TEST_SESSION_SAVE_PATH') ? : TEST_DATA_PATH . '/sessions'
    );
}
ini_set('session.save_path', TEST_SESSION_SAVE_PATH);

// HTTP_HOST
if (!defined('HTTP_HOST') && getenv('P4CMS_TEST_HTTP_HOST')) {
    define('HTTP_HOST', getenv('P4CMS_TEST_HTTP_HOST'));
}
// if HTTP_HOST still not defined, warn the tester.
if (!defined('HTTP_HOST')) {
    echo <<<EOM
---------------------------------------------------------
Note: The variable HTTP_HOST is not defined.
      Any tests against a host will therefore be skipped.
---------------------------------------------------------

EOM;
}

// Log file path
if (!defined('TEST_LOG_PATH')) {
    define(
        'TEST_LOG_PATH',
        getenv('P4CMS_TEST_LOG_PATH') ? : dirname(TEST_DATA_PATH) . "/phpunit-logs"
    );
}

/**
 * Define path constants.
 * Use test data directories for sites and output.
 * This allows us to test against a known set of sites
 * and avoid polluting the production paths.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH',        realpath(dirname(dirname(__DIR__))));
}
if (!defined('DATA_PATH')) {
    define('DATA_PATH',        TEST_DATA_PATH);
}
if (!defined('APPLICATION_ENV')) {
    define('APPLICATION_ENV',  'testing');
}
if (!defined('APPLICATION_PATH')) {
    define('APPLICATION_PATH', BASE_PATH . '/application');
}
if (!defined('LIBRARY_PATH')) {
    define('LIBRARY_PATH',     BASE_PATH . '/library');
}
if (!defined('SITES_PATH')) {
    define('SITES_PATH',       TEST_SITES_PATH);
}
if (!defined('MODULE_PATH')) {
    define('MODULE_PATH',      BASE_PATH . '/sites/all/modules');
}

/**
 * Set Perforce environment variables to allow for test parallelization
 */
if (!putenv('P4TICKETS=' . TEST_DATA_PATH . '/p4tickets.txt')) {
    echo "WARNING: Cannot set P4TICKETS\n";
}

/**
 * Set default timezone to suppress PHP warnings.
 */
date_default_timezone_set(@date_default_timezone_get());

/**
 * Unset global variables that are no longer needed.
 */
unset($appRoot, $appLibrary, $appTests, $path);
