<?php
/**
 * Simple back-end for the IDE editor.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo
 *              safe mode for broken stuff
 *              move/rename file
 *              style to better match chronicle look and feel
 *              move via drag and drop
 *              enter-key confirm dialogs
 *              enforce csrf protection
 *              only load ace js for editor requests.
 *              better error messages
 */
class Ide_IndexController extends Zend_Controller_Action
{
    public      $contexts       = array(
        'files'     => array('json'),
        'paths'     => array('json'),
        'copy'      => array('json'),
        'package'   => array('json')
    );

    protected   $_hiddenFiles   = array(
        '.DS_Store',
        '.placeholder'
    );

    /**
     * Enforce permissions.
     */
    public function init()
    {
        $this->getHelper('acl')->check('system', 'ide');
        $this->getHelper('layout')->disableLayout();
    }

    /**
     * Render the file editor (dijit).
     */
    public function indexAction()
    {
        $this->getHelper('layout')->setLayout('editor-layout');
        $this->view->headTitle()->set('IDE');

        // ace scripts are stored here instead of the .ini file so they are only loaded on the IDE page.
        $aceScripts = array(
            // main ace script
            "ace-uncompressed.js",

            // themes
            "theme-chrome.js", "theme-clouds.js", "theme-cobalt.js", "theme-crimson_editor.js",
            "theme-dawn.js", "theme-eclipse.js", "theme-idle_fingers.js", "theme-kr_theme.js",
            "theme-merbivore.js", "theme-merbivore_soft.js", "theme-mono_industrial.js",
            "theme-monokai.js", "theme-pastel_on_dark.js", "theme-solarized_dark.js",
            "theme-solarized_light.js", "theme-textmate.js", "theme-twilight.js", "theme-tomorrow.js",
            "theme-tomorrow_night.js", "theme-tomorrow_night_blue.js", "theme-tomorrow_night_bright.js",
            "theme-tomorrow_night_eighties.js", "theme-vibrant_ink.js",

            // syntax highlighting modes
            "mode-css.js", "mode-html.js", "mode-javascript.js", "mode-json.js", "mode-php.js",
            "mode-xml.js"
        );

        $module = P4Cms_Module::fetch('ide');
        foreach ($aceScripts as $script) {
            $this->view->headScript()->appendFile($module->getBaseUrl() . '/ace/' . $script);
        }
    }

    /**
     * Support reading and writing of server files.
     *  - On get, reads file and presents file contents
     *  - On post, writes file and presents bytes written
     *  - On delete, removes file and presents 'true' or 'false' for success/failure.
     */
    public function filesAction()
    {
        $view     = $this->view;
        $request  = $this->getRequest();
        $file     = $this->_resolvePath($request->getParam('file'));
        $basename = basename($request->getParam('file'));

        // if user has modified a package file, clear package cache.
        if ($request->isDelete() || $request->isPost()) {
            if ($basename === 'theme.ini') {
                P4Cms_Theme::clearCache();
            }
            if ($basename === 'module.ini') {
                P4Cms_Module::clearCache();
            }
        }

        // delete request method implies unlink.
        if ($request->isDelete()) {
            // attempt to make file writable.
            if (!is_writable($file)) {
                @chmod($file, 0755);
            }

            // present true/false.
            $view->data = @unlink($file);
            return;
        }

        // post request method implies writing.
        if ($request->isPost()) {
            // if file did not resolve, create a new file if the path exists.
            if (!$file) {
                $path = $this->_resolvePath(dirname($request->getParam('file')));
                $file = $path . "/" . $basename;
                @touch($file);
            }

            // attempt to make file writable.
            if (!is_writable($file)) {
                @chmod($file, 0755);
            }

            // present bytes written.
            // if file content was uploaded, move the temp file into place.
            // otherwise, write contents of 'data' request param to the file.
            if (isset($_FILES['data']['tmp_name'])) {
                $result     = @move_uploaded_file($_FILES['data']['tmp_name'], $file);
                $view->data = $result ? $_FILES['data']['size'] : false;
            } else {
                $view->data = @file_put_contents($file, $request->getParam('data'));
            }

            return;
        }

        $view->file = $file;
    }

    /**
     * Support listing directory contents.
     *
     * Response format is suitable for consumption by a dijit.Tree using
     * the ForestStoreModel and JsonRestStore.
     */
    public function pathsAction()
    {
        $this->contextSwitch->initContext('json');

        $view       = $this->view;
        $request    = $this->getRequest();

        // extract path parameter.
        $path       = $request->getParam('path');
        $root       = $this->_getRootPath();
        $isRoot     = $path == 'root';

        // delete request method implies recursive unlink.
        if ($request->isDelete()) {
            // if path fails to resolve, it must not exist.
            $path = $this->_resolvePath($path);
            if (!$path) {
                throw new Exception(
                    "Cannot delete '$path'. Folder doesn't exist."
                );
            }

            // present true/false.
            $view->data = P4Cms_FileUtility::deleteRecursive($path);
            return;
        }

        // if a path was posted, attempt to create it.
        if ($request->getPost('path') && !$isRoot) {
            // strip leading/trailing slashes.
            $path = trim($path, '/');

            // if path resolves, it must exist already.
            if ($this->_resolvePath($path)) {
                throw new Exception(
                    "Cannot create '$path'. Path already exists."
                );
            }

            // verify parent folder resolves.
            $parent = $this->_resolvePath(dirname($path));
            if (!$parent) {
                throw new Exception(
                    "Cannot create '$path'. Containing folder doesn't exist."
                );
            }

            // attempt to make containing folder writable.
            if (!is_writable($parent)) {
                @chmod($parent, 0755);
            }

            // try to make it.
            $view->data = @mkdir($parent . '/' . basename($path), 0755);
            return;
        }

        // normalize path parameter.
        $path       = $isRoot ? $root : $this->_resolvePath($path);

        // collect entries in given path.
        $data       = array();
        $paths      = new DirectoryIterator($path);
        foreach ($paths as $entry) {
            if ($entry->isDot() || in_array($entry->getBasename(), $this->_hiddenFiles)) {
                continue;
            }

            $basename = $entry->getBasename();
            $pathname = str_replace($root . '/', '', $entry->getPathname());

            // if entry has children, only partially load it
            // (this uses dojo's JSON references (lazy-loading)
            if ($entry->isDir()) {
                $data[$basename] = array(
                    '$ref'      => $pathname,
                    'name'      => $basename,
                    'children'  => true
                );
            } else {
                $data[$basename] = array(
                    'id'        => $pathname,
                    'name'      => $basename,
                    'type'      => P4Cms_FileUtility::getMimeType($entry->getPathname())
                );
            }
        }

        // ensure orderly results.
        uksort($data, 'strnatcasecmp');
        $data = array_values($data);

        if (!$isRoot) {
            $path = $this->getRequest()->getParam('path');
            $data = array(
                'id'        => $path,
                'name'      => basename($path),
                'children'  => $data
            );
        }

        $view->data = $data;
    }

    /**
     * Support copying a file or directory.
     */
    public function copyAction()
    {
        $this->contextSwitch->initContext('json');

        $view    = $this->view;
        $request = $this->getRequest();

        // extract/normalize source and target details.
        $source  = $this->_resolvePath($request->getParam('source'));
        $target  = trim($request->getParam('target'), '/');
        $parent  = $this->_resolvePath(dirname($target));

        // verify:
        //  - request was posted
        //  - source exists
        //  - target does not exist
        //  - target parent folder exists
        $error = "Cannot copy from $source to $target. ";
        if (!$request->isPost()) {
            throw new Exception($message . "Request method must be POST.");
        }
        if (!$source) {
            throw new Exception($message . "Source path does not exist.");
        }
        if (!$parent) {
            throw new Exception($message . "Parent of target path does not exist.");
        }
        if ($this->_resolvePath($target)) {
            throw new Exception($message . "Target path already exists.");
        }

        P4Cms_FileUtility::copyRecursive($source, $parent . '/' . basename($target));
    }

    /**
     * Create a new module or theme.
     */
    public function packageAction()
    {
        $this->contextSwitch->initContext('json');

        $view        = $this->view;
        $request     = $this->getRequest();
        $type        = $request->getParam('type');
        $label       = $request->getParam('name');
        $name        = strtolower(preg_replace('/[^a-z0-9]/i', '', $label));
        $namespace   = ucfirst($name);
        $description = $request->getParam('description');
        $tags        = $request->getParam('tags');
        $path        = $this->_getRootPath() . '/all/' . $type . 's/' . $name;

        // verify package type is valid and package does not already exist.
        $error = "Cannot create '$label' package. ";
        if ($type !== 'module' && $type !== 'theme') {
            throw new Exception($error . "Invalid package type specified.");
        }
        if (file_exists($path)) {
            throw new Exception($error . ucfirst($type) . " already exists.");
        }

        // copy the package template into place.
        P4Cms_FileUtility::copyRecursive(
            dirname(__DIR__) . '/templates/' . $type, $path
        );

        // provide 'package' macro for exclusive use by this action.
        // (it would not work as a general purpose macro).
        P4Cms_PubSub::subscribe('p4cms.macro.package',
            function($params, $body, $context) use ($label, $name, $namespace, $description, $tags)
            {
                $field = isset($params[0]) ? $params[0] : 'name';
                switch ($field) {
                    case 'label':
                        return $label;
                        break;
                    case 'name':
                        return $name;
                        break;
                    case 'namespace':
                        return $namespace;
                        break;
                    case 'description':
                        return $description;
                        break;
                    case 'tags':
                        return $tags;
                        break;
                    default:
                        return null;
                }
            }
        );

        // iterate over newly copied files and expand macros.
        $filter = new P4Cms_Filter_Macro;
        $files  = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $contents = file_get_contents($file->getPathname());
                $contents = $filter->filter($contents);
                file_put_contents($file->getPathname(), $contents);
            }
        }

        // success!
        $view->data = true;
    }

    /**
     * Resolve given path to a location under the root path.
     *
     * @param   string          $path   the relative path to resolve.
     * @return  string|bool     the resolved path or false if the path cannot
     *                          be resolved to a location under the root path.
     */
    protected function _resolvePath($path)
    {
        $path = $this->_getRootPath() . '/' . trim($path, '/');
        $path = realpath($path);

        if (!$path) {
            return false;
        }

        $root = $this->_getRootPath();
        if (!$root || strpos($path, $root) !== 0) {
            return false;
        }

        return $path;
    }

    /**
     * Get the root path above which no files will be read/written.
     * Only files under the root path will be exposed via this controller.
     *
     * @return  string  the path files must be under to be exposed.
     */
    protected function _getRootPath()
    {
        return realpath(SITES_PATH);
    }
}
