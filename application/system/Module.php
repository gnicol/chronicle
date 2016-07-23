<?php
/**
 * Contribute information to the system module and integrate it with
 * the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class System_Module extends P4Cms_Module_Integration
{
    /**
     * Perform early integration work (before load).
     */
    public static function init()
    {
        P4Cms_PubSub::subscribe('p4cms.system.info', 'System_Module', 'getGeneralInformation');
        P4Cms_PubSub::subscribe('p4cms.system.info', 'System_Module', 'getPerforceInformation');
        P4Cms_PubSub::subscribe('p4cms.system.info', 'System_Module', 'getWebserverInformation');
        P4Cms_PubSub::subscribe('p4cms.system.info', 'System_Module', 'getPhpInformation');
        P4Cms_PubSub::subscribe('p4cms.system.info', 'System_Module', 'getModuleInformation');
        P4Cms_PubSub::subscribe('p4cms.system.info', 'System_Module', 'getThemeInformation');
    }

    /**
     * Perforce Client Application
     *   p4 or p4php version
     *   p4port
     * Perforce Server (p4 info)
     *   (some client* entries removed)
     * License info
     *   number of licenses & expiration date
     *   number of users
     *
     * @param P4Cms_Model_Iterator $systemInformation The model list to add to.
     */
    public static function getPerforceInformation($systemInformation)
    {
        $sysinfo = new System_Model_Info();
        $sysinfo->setId('support');
        $sysinfo->title = 'Perforce';
        $sysinfo->order = 0;

        // use the default connection.
        $p4 = P4_Connection::getDefaultConnection();

        // client version
        $identity   = $p4->getConnectionIdentity();
        $minVersion = strtolower(Setup_IndexController::MIN_P4_VERSION);
        $ourVersion = strtolower($identity['version']);
        $p4Valid    = version_compare($ourVersion, $minVersion) >= 0;
        $p4Version  = $identity['original'];

        $content['Client Version'] = array(
            'displayClass'  => $p4Valid ? 'good' : 'bad',
            'details'       => $p4Version
        );

        // p4port that client uses
        $p4Port = $p4->getPort();
        $isPortRsh = false;
        if (preg_match('/^rsh:/', $p4Port)) {
            $isPortRsh = true;
            $content['P4PORT'] = $p4Port . ' (local)';
        } else {
            $content['P4PORT'] = $p4Port;
        }

        // capture p4 info output into content
        foreach ($p4->getInfo() as $key => $value) {
            // exclude these entries always
            if (preg_match('/^client(Cwd|Name|Root)/', $key)) {
                continue;
            }
            // exclude these entries if connection is rsh
            if ($isPortRsh && $key === 'serverUptime') {
                continue;
            }

            // "keyName" -> "key Name"
            $newKey = preg_replace('/([a-z])([A-Z])/', '$1 $2', $key);
            $newKey = ucfirst($newKey);

            // tweak serverAddress if rsh
            if ($isPortRsh && $key === 'serverAddress') {
                $content[$newKey] = $value . ' (local)';
            } else {
                // default case
                $content[$newKey] = $value;
            }
        }

        // license info
        $license = $p4->run('license', array('-u'))->getData(0);
        $content['Is Licensed'] = $license['isLicensed'];

        // if a particular count is numeric, display "Used X out of " portion
        // if a soft limit is in play, use that, with explanation

        $formatLimit = function($limit, $softMessage) use ($license)
        {
            $message  = '';
            $license += array($limit . "SoftLimit" => null);
            if (is_numeric($license[$limit . 'Count'])) {
                $message .= 'Used '. $license[$limit . 'Count'] .' out of ';
            }

            $message .= $license[$limit . 'SoftLimit'] ?: $license[$limit . 'Limit'];
            if ($license[$limit . 'SoftLimit']) {
                $message .= ' (' . $license[$limit . 'Limit'] . ' if '
                         .  $softMessage . ' not exceeded)';
            }

            return $message;
        };

        // display user limit
        $content['User Limit']   = $formatLimit('user',   'File Limit is');
        $content['Client Limit'] = $formatLimit('client', 'File Limit is');
        $content['File Limit']   = $formatLimit('file',   'User and Client Limits are');

        $sysinfo->content = $content;

        $systemInformation[] = $sysinfo;
    }

    /**
     * General P4CMS info (version, active site, sites available, etc.)
     *
     * @param P4Cms_Model_Iterator $systemInformation The model list to add to.
     */
    public static function getGeneralInformation($systemInformation)
    {
        $sysinfo = new System_Model_Info();
        $sysinfo->setId('p4cms');
        $sysinfo->title             = 'General';
        $sysinfo->order             = -10;
        $sysinfo->view              = APPLICATION_PATH . '/system/views/scripts/general-info.phtml';
        $supportData                = array();
        $supportData['version']     = P4CMS_VERSION;

        // get active site data usage info
        $report        = '';

        $userCount     = P4Cms_User::count();
        $plural        = $userCount != 1 ? 's' : '';
        $report       .= "$userCount user$plural";

        $roleCount     = P4Cms_Acl_Role::count();
        $plural        = $roleCount != 1 ? 's' : '';
        $report       .= ", $roleCount role$plural";

        $contentCount  = P4Cms_Content::count();
        $plural        = $contentCount != 1 ? 'ies' : 'y';
        $report       .= ", $contentCount content entr$plural";

        $typeCount     = P4Cms_Content_Type::count();
        $plural        = $typeCount != 1 ? 's' : '';
        $report       .= ", $typeCount content type$plural";

        $categoryCount = count(Category_Model_Category::fetchAll());
        $plural        = $categoryCount != 1 ? 'ies' : 'y';
        $report       .= " and $categoryCount categor$plural.";

        $activeSite    = P4Cms_Site::fetchActive();
        $supportData['Active Site'] = array(
            'site'     => $activeSite,
            'details'  => $report
        );

        // produce a list of all sites and branches
        $siteList = P4Cms_Site::fetchAll();
        $siteInfo = array();
        
        foreach ($siteList as $site) {
            $title = $site->getConfig()->getTitle();
            
            if (!array_key_exists($title, $siteInfo)) {
                $siteInfo[$title] = array();
            }
            
            $stream             = $site->getStream()->getName();
            $siteInfo[$title][] = $stream;
        }

        $supportData['All Sites'] = $siteInfo;

        // case sensitive on case-sensitive OSs.
        $directoryHandle = dir(LIBRARY_PATH);
        while (false != ($entry = $directoryHandle->read())) {
            if (strpos($entry, '.') !== 0) {
                $supportData['libraries'][] = $entry;
            }
        }
        $directoryHandle->close();

        $originalMd5    = static::fetchMd5(BASE_PATH . '/index.php.md5', true);
        $currentMd5     = md5_file(BASE_PATH . '/index.php');
        $supportData['index.php'] = array(
            'displayClass'      => $originalMd5 == $currentMd5
                ? 'good'
                : 'bad',
            'status'            => $originalMd5 == $currentMd5
                ? 'Ok'
                : 'Failed',
            'details'           => $currentMd5
        );

        $originalMd5    = static::fetchMd5(APPLICATION_PATH . '/Bootstrap.php.md5', true);
        $currentMd5     = md5_file(APPLICATION_PATH . '/Bootstrap.php');
        $supportData['Bootstrap.php'] = array(
            'displayClass'      => $originalMd5 == $currentMd5
                ? 'good'
                : 'bad',
            'status'            => $originalMd5 == $currentMd5
                ? 'Ok'
                : 'Failed',
            'details'           => $currentMd5
        );

        $sysinfo->content       = $supportData;
        $systemInformation[]    = $sysinfo;
    }

    /**
     * Fetches the contents of a file containing one or more md5 calculations, and the
     * filenames for which they have been calculated.
     * Optionally, strips the filenames from the lines in the file, leaving only the md5
     * calculations.
     *
     * @param   string  $path            The full path to the file.
     * @param   bool    $stripFilenames  Whether or not to strip the filenames.
     * @return  string                   The fetched file contents, or empty string if file cannot be read.
     */
    public static function fetchMd5($path, $stripFilenames = false)
    {
        // pull in the requested file contents
        $md5 = is_readable($path) ? trim(file_get_contents($path)) : '';

        // optionally remove trailing file-names
        if ($stripFilenames) {
            $md5 = preg_replace('/^(\w*).*$/m', '\\1', $md5);
        }

        return $md5;
    }

    /**
     * Web server info
     * Which one being used (Apache, etc.)
     * Version
     * Modules in use (can we detect this?  Not right now.  @todo, add this later)
     * Other settings (perhaps a dump of what we can detect from the web server?)
     *
     * @param P4Cms_Model_Iterator $systemInformation The model list to add to.
     */
    public static function getWebserverInformation($systemInformation)
    {
        $sysinfo = new System_Model_Info();
        $sysinfo->setId('webserver');
        $sysinfo->title             = 'Webserver';
        $sysinfo->order             = 40;
        $supportData                = array();

        // When running unit tests, this key will not exist, throwing an error and failing any
        // related tests.
        $supportData['server']      = (array_key_exists('SERVER_SOFTWARE', $_SERVER))
                                    ? $_SERVER['SERVER_SOFTWARE']
                                    : 'Unavailable';

        $sysinfo->content           = $supportData;
        $systemInformation[]        = $sysinfo;
    }

    /**
     * PHP info
     * Version
     * Modules in use
     * phpinfo() ???
     *
     * @param P4Cms_Model_Iterator $systemInformation The model list to add to.
     */
    public static function getPhpInformation($systemInformation)
    {
        $sysinfo = new System_Model_Info();
        $sysinfo->setId('php');
        $sysinfo->title             = 'PHP';
        $sysinfo->order             = 30;
        $supportData                = array();
        $supportData['version']     = array(
            'displayClass'  => (version_compare(PHP_VERSION, Setup_IndexController::MIN_PHP_VERSION) >= 0)
                ? 'good'
                : 'bad',
            'status'        => PHP_VERSION
        );

        $supportData['modules']     = implode(', ', get_loaded_extensions());

        // collect phpinfo()'s HTML markup and make it available to the report.
        ob_start();
        phpinfo();
        $supportData['phpinfo']     = trim(ob_get_clean());

        $sysinfo->content           = $supportData;
        $sysinfo->view              = APPLICATION_PATH . '/system/views/scripts/php-info.phtml';

        $systemInformation[]        = $sysinfo;
    }

    /**
     * Optional/contributed Modules installed / enabled / disabled / MD5 checksums
     *
     * @param P4Cms_Model_Iterator $systemInformation The model list to add to.
     */
    public static function getModuleInformation($systemInformation)
    {
        $sysinfo = new System_Model_Info();
        $sysinfo->setId('modules');
        $sysinfo->title         = 'Modules';
        $sysinfo->order         = 130;
        $sysinfo->content       = P4Cms_Module::fetchAll()->sortBy(
            array(
                array('core', array(P4_Model_Iterator::SORT_DESCENDING)),
                array('name', array(P4_Model_Iterator::SORT_NATURAL))
            )
        );
        $sysinfo->view          = APPLICATION_PATH . '/system/views/scripts/packages-info.phtml';

        $systemInformation[]    = $sysinfo;
    }

    /**
     * Themes installed / enabled / disabled / MD5 checksums (?)
     *
     * @param P4Cms_Model_Iterator $systemInformation The model list to add to.
     */
    public static function getThemeInformation($systemInformation)
    {
        $sysinfo = new System_Model_Info();
        $sysinfo->setId('themes');
        $sysinfo->title     = 'Themes';
        $sysinfo->order     = 140;
        $sysinfo->view      = APPLICATION_PATH . '/system/views/scripts/packages-info.phtml';
        $sysinfo->content   = P4Cms_Theme::fetchAll();

        $systemInformation[] = $sysinfo;
    }
}
