<?php
/**
 * Handles importing a WordPress xml document into Chronicle; brings in users, content; if comment module
 * is enabled, brings in comments as well.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Wpimport_IndexController extends Zend_Controller_Action
{
    const BATCH_SIZE      = 100;
    const STATUS_FILE     = 'wpimport.status.file';
    public      $contexts = array(
        'status'    => array('json')
    );

    protected   $_feedLink          = '';
    protected   $_totalFeedItems    = 0;
    protected   $_currentItemCount  = 0;

    /**
     * Show form and handle import on submit.
     * Sets up site information, categories, and users before iterating over exported items.  Items can be attachments
     * (files or images), menu items, or content entries.  Tags are imported as categories and are not maintained as
     * separate data.  Menu items may reference content entries or other menus not created until later in the feed
     * therefore the menu structure is created after entries are processed.
     */
    public function indexAction()
    {
        $this->acl->check('content', 'manage');
        $this->getHelper('layout')->setLayout('manage-layout');

        $request    = $this->getRequest();
        $form       = new Wpimport_Form_Configure;
        $view       = $this->view;
        $view->form = $form;

        $view->headTitle()->set('WordPress Import');

        if ($request->isPost() && $form->isValid($request->getPost()) && $form->importfile->receive()) {
            $xmlFile = $form->importfile->getFileName();
            $module  = P4Cms_Module::fetch('wpimport');

            if (!Zend_Feed_Reader::isRegistered('WordPress')) {
                $basePath = $module->getPath();
                Zend_Feed_Reader::addPrefixPath(
                    'Wp_FeedReader_Extension', $basePath
                );
                Zend_Feed_Reader::registerExtension('WordPress');
            }

            $feed = Zend_Feed_Reader::importFile($xmlFile);

            // write initial status update and disconnect browser
            $view->showStatus = getmypid();
            $this->getHelper('layout')->direct()->content = $this->view->render('index/index.phtml');
            echo $this->getHelper('layout')->render();

            // disconnect the browser and continue
            $this->getHelper('browserDisconnect')->disconnect();

            // calculate total count of items in the feed; pad it by 1% so when we do the final batch submit we don't
            // get odd status bar behavior in large data sets
            $this->_totalFeedItems = $feed->count() + count($feed->get('categories')) + count($feed->getWpAuthors());
            $this->_totalFeedItems = $this->_totalFeedItems + round($this->_totalFeedItems / 100);

            $this->_updateStatus();

            $this->_feedLink = $feed->getLink();
            $this->_updateSite($feed);
            $this->_importCategories($feed);
            $authors = $this->_createUsers($feed);

            // recreate the primary menu; it will be populated as part of the import process
            if (P4Cms_Menu::exists('primary')) {
                P4Cms_Menu::fetch('primary')->delete();
            }
            $primaryMenu = P4Cms_Menu::create(
                array(
                    'id'    => 'primary',
                    'label' => 'Primary'
                )
            )->save();

            // store menu definitions in this array until all feeds are imported
            $menuItems          = array();

            $adapter            = P4Cms_Content::getDefaultAdapter();
            $batchEntryCount    = 0;
            $batchCounter       = 0;

            // iterate over the feed entries
            foreach ($feed as $feedEntry) {
                $this->_currentItemCount++;
                $this->_updateStatus();

                if (!$adapter->inBatch()) {
                    $batchCounter++;
                    $adapter->beginBatch(
                        'Importing ' . $feed->count() . ' content entries.  '
                        . 'Batch ' . $batchCounter . ' of ' . ceil($this->_totalFeedItems / static::BATCH_SIZE) . '.'
                    );
                }

                $postType = $feedEntry->get('wp:post_type');
                switch ($postType) {
                    case 'nav_menu_item':
                        // do not import menu items with the 'draft' status as Chronicle does not support this concept.
                        if ($feedEntry->get('wp:status') !== 'draft') {
                            $itemDefinition = $this->_defineMenuItem($feedEntry);
                            if (!array_key_exists($itemDefinition['parentId'], $menuItems)) {
                                $menuItems[$itemDefinition['parentId']] = array('pages' => array());
                            }
                            $menuItems[$itemDefinition['parentId']]['pages'][$itemDefinition['id']] = $itemDefinition;
                        }
                        break;
                    case 'attachment':
                        $contentEntry = $this->_getContentAssetEntry($feedEntry)->save();
                        break;
                    case 'post':
                    case 'page':
                    default:
                        $this->_getContentPageEntry($feedEntry)->save();
                }

                $batchEntryCount++;

                // end and start a new batch if current one is full
                if ($batchEntryCount == static::BATCH_SIZE) {
                    $batchEntryCount = 0;
                    // this can take some time with no progress bar update; inform the user
                    $this->_updateStatus();
                    $adapter->commitBatch();
                }
            }

            // Create menu items from item definition list; this is delayed until all content is created so that
            // content references do attempt to reference non-exi
            $this->_createMenuItems($menuItems, $adapter);

            // commit or revert open batch
            if ($adapter->inBatch()) {
                $this->_updateStatus();
                $adapter->commitBatch();
            }

            $message = 'Import complete.';
            if (count($authors)) {
                $message .= 'Users have been imported and their passwords reset.<br/>'
                          . 'Please inform the users of their new passwords.<ul class="imported-users">';
                foreach ($authors as $id => $password) {
                    $message .= "<li><span class='username'>$id</span><span class='password'>$password</span></li>";
                }
                $message .= '</ul>';
            }

            $this->_writeStatusFile(
                array(
                    'time'    => time(),
                    'done'    => true,
                    'count'   => $this->_totalFeedItems,
                    'total'   => $this->_totalFeedItems,
                    'message' => $message
                )
            );

            // clear the global 'sites' cache.
            P4Cms_Cache::remove(P4Cms_Site::CACHE_KEY, 'global');
        } else {
            $this->getHelper('helpUrl')->setUrl('installation.management.wordpress-import.html');
        }
    }

    /**
     * Provide a status update in Json format.
     */
    public function statusAction()
    {
        // enforce permissions.
        $this->acl->check('content', 'manage');

        // set context
        $this->contextSwitch->initContext('json');

        $processId  = $this->getRequest()->getParam('processId');
        $statusFile = sys_get_temp_dir() . '/' . static::STATUS_FILE . $processId;

        if (!file_exists($statusFile) ) {
            $status = array(
                'label'   => 'no status',
                'message' => 'No current status file.',
                'done'    => true
            );
            $this->view->status = $status;
            return;
        }

        $this->view->status = $this->_readStatusFile($processId);
    }

    /**
     * Read JSON-encoded information from temporary status file
     *
     * @param   string      $processId  The process for which to read the status.
     * @return  mixed       The JSON-decoded contents from the status file.
     */
    private function _readStatusFile($processId)
    {
        $statusFile = sys_get_temp_dir() . '/' . static::STATUS_FILE . $processId;

        $status = '';
        if (file_exists($statusFile)) {
            $content = file_get_contents($statusFile);
            $status = Zend_Json::decode($content);
        }
        return $status;
    }

    /**
     * Write status information to the status file.
     *
     * @param  array   $data      The status data to report.
     * @param  string  $processId The process for which to write the status.  If empty, use current process.
     */
    private function _writeStatusFile($data, $processId = null)
    {
        $processId = ($processId) ?: getmypid();

        $statusFile = sys_get_temp_dir() . '/' . static::STATUS_FILE . $processId;

        file_put_contents($statusFile, Zend_Json::encode($data));
    }

    /**
     * Write out current values to the status file.
     */
    protected function _updateStatus()
    {
        $this->_writeStatusFile(
            array(
                'time'    => time(),
                'done'    => ($this->_currentItemCount == $this->_totalFeedItems),
                'count'   => $this->_currentItemCount,
                'total'   => $this->_totalFeedItems,
                'message' => "Importing content.  Item " . $this->_currentItemCount
                           . " of " . $this->_totalFeedItems . "."
            )
        );
    }

    /**
     * Updates settings on the current site from the information provided in the feed.
     *
     * @param Zend_Feed_Reader_FeedInterface $feed The feed from which to update the site.
     */
    protected function _updateSite(Zend_Feed_Reader_FeedInterface $feed)
    {
        // update branch url
        $branch = P4Cms_Site::fetchActive();
        $branch->getConfig()
               ->setUrls(array($this->_feedLink))
               ->save();

        // update stream related to the branch
        $stream = $branch->getStream();
        $stream->setName($feed->getTitle())
               ->setDescription($feed->getDescription())
               ->save();
    }

    /**
     * Imports categories from the provided feed into Chronicle.
     *
     * @param Zend_Feed_Reader_FeedInterface $feed The feed from which to import the category structure.
     */
    protected function _importCategories(Zend_Feed_Reader_FeedInterface $feed)
    {
        $categories = $feed->get('categories');
        foreach ($categories as $categoryValues) {
            Category_Model_Category::store($categoryValues);
        }

        $this->_currentItemCount += count($categories);
        $this->_updateStatus();
    }

    /**
     * Creates users from xml feed; because passwords aren't imported, creates them using uniqid and
     * returns them for later display.
     *
     * @param Zend_Feed_Reader_FeedInterface $feed The feed from which to import the users.
     * @return array List of newly-defined user ids and passwords.
     */
    protected function _createUsers(Zend_Feed_Reader_FeedInterface $feed)
    {
        $created   = array();
        $wpAuthors = $feed->getWpAuthors();

        foreach ($wpAuthors as $author) {
            $id = $author['id'];

            // catch any user creation failures and display a message
            try {

                // attempt to update existing user, fallback to creating a new user
                try {
                    P4Cms_User::fetch($id)->setValues($author)->save();
                } catch (P4Cms_Model_NotFoundException $e) {
                    $author['password'] = P4Cms_User::generatePassword(10, 3);
                    $user = new P4Cms_User;
                    $user->setValues($author)
                         ->save();

                    // default to member role
                    P4Cms_Acl_Role::setUserRoles($user, array(P4Cms_Acl_Role::ROLE_MEMBER));

                    $created[$id] = $author['password'];
                }
            }
            catch (Exception $e) {
                $created[$id] = "Unable to create user; please create manually.";
                P4Cms_Log::log(print_r($e, true), P4Cms_Log::ERR);
            }
        }

        $this->_currentItemCount += count($wpAuthors);
        $this->_updateStatus();

        return $created;
    }

    /**
     * Transforms a feed entry to an array containing the properties of a menu or menu item.
     *
     * @param Zend_Feed_Reader_EntryAbstract $feedEntry Entry to transform into a menu definition.
     * @return array Defined menu item.
     */
    protected function _defineMenuItem(Zend_Feed_Reader_EntryAbstract $feedEntry)
    {
        $meta       = $feedEntry->getPostMeta();

        $targetId   = (array_key_exists('menu_item_parent', $meta)
                   && is_numeric($meta['menu_item_parent'])
                   && (int)$meta['menu_item_parent'] > '0')
            ? $meta['menu_item_parent']
            : 'primary';

        $itemValues = array(
            'id'        => $feedEntry->get('wp:post_id'),
            'order'     => $feedEntry->get('wp:menu_order'),
            'type'      => 'P4Cms_Navigation_Page_Content',
            'autoLabel' => true,
            'contentId' => $meta['object_id'],
            'parentId'  => $targetId,
            'pages'     => array()
        );

        return $itemValues;
    }

    /**
     * Converts a feed entry to a content entry with content type in the Pages category.  Currently only Blog Post and
     * Basic Page types are supported.
     *
     * @param   Zend_Feed_Reader_EntryAbstract $feedEntry  The RSS feed entry.
     * @return  P4Cms_Content  The created content entry.
     */
    protected function _getContentPageEntry(Zend_Feed_Reader_EntryAbstract $feedEntry)
    {
        $entryData = array(
            'id'    => $feedEntry->get('wp:post_id'),
            'title' => $feedEntry->getTitle()
        );
        $entryData['body'] = $feedEntry->getWpContent($this->_feedLink);

        if ($feedEntry->get('wp:status') == 'publish') {
            $entryData['workflow'] = array(
                'state'     => 'published',
                'scheduled' => 'false'
            );
        }

        $postType = $feedEntry->get('wp:post_type');
        if ($postType == 'post') {
            $type = P4Cms_Content_Type::fetch('blog-post');

            $entryData['date']       = $feedEntry->get('postDateGmt');
            $entryData['category']   = array('categories' => $feedEntry->get('categories'));
            $entryData['excerpt']    = $feedEntry->getDescription();
            $entryData['author']     = $feedEntry->get('dc:creator');
        } else {
            $type = P4Cms_Content_Type::fetch('basic-page');
        }

        $entryData['url'] = array(
            'path' => strtolower($feedEntry->get('wp:post_name')),
            'auto' => false
        );

        $entry = new P4Cms_Content();
        $entry->setContentType($type);
        $entry->setValues($entryData);
        return $entry;
    }

    /**
     * Converts a feed entry to a content entry with content type in the Assets category.
     *
     * @param   Zend_Feed_Reader_EntryAbstract $feedEntry  The RSS feed entry.
     * @return  P4Cms_Content  The created content entry.
     */
    protected function _getContentAssetEntry(Zend_Feed_Reader_EntryAbstract $feedEntry)
    {
        $entryData = array(
            'id'            => $feedEntry->get('wp:post_id'),
            'title'         => $feedEntry->getTitle(),
            'description'   => $feedEntry->getDescription(),
            'date'          => $feedEntry->get('postDateGmt')
        );

        $url = $feedEntry->get('wp:attachment_url');
        $client = new Zend_Http_Client();
        $client->setUri($url);

        try {
            $response      = $client->request();
            $validResponse = $response->isSuccessful();
        } catch (Zend_Http_Client_Exception $e) {
            $validResponse = false;
        }

        $entry = new P4Cms_Content();
        // verify remote content exists
        if ($validResponse) {
            $contentType = $response->getHeader('Content-Type');
            list($type, $subtype) = explode('/', $contentType);
            if ($type == 'image') {
                $type = P4Cms_Content_Type::fetch('image');
                $entryData['creator'] = $feedEntry->get('dc:creator');
                $entryData['alt']     = $feedEntry->get('wp:post_name');

                // update the id to be the image file name for images, for easier linking from the content
                $entryData['id']      = basename($url);
            } else {
                $type = P4Cms_Content_Type::fetch('file');
            }

            $entry->setValue('file', $response->getBody());
            $entry->setFieldMetadata(
                'file',
                array(
                    'mimeType' => $response->getHeader('Content-Type'),
                    'filename' => basename($url),
                    'fileSize' => $response->getHeader('Content-Length')
                )
            );
        } else {
            // unable to fetch, make an image entry with no file
            $type = P4Cms_Content_Type::fetch('image');
        }

        return $entry->setContentType($type)->setValues($entryData);
    }

    /**
     * Iterate over the list of provided menu item values, creating menu items.
     *
     * @param array                $menuItems   An array of values used to define menu items.
     * @param P4Cms_Record_Adapter $adapter     The record adapter to use to save the menus.
     */
    protected function _createMenuItems($menuItems, P4Cms_Record_Adapter $adapter)
    {
        // move child structures under the primary menu
        foreach ($menuItems as $id => $items) {
            if ($id == 'primary') {
                continue;
            }

            if (array_key_exists($id, $menuItems['primary']['pages'])) {
                $menuItems['primary']['pages'][$id]['pages'] = $menuItems['primary']['pages'][$id]['pages']
                                                             + $items['pages'];
                unset($menuItems[$id]);
            }
        }

        $menu = P4Cms_Menu::fetch('primary', null, $adapter);

        // add each entry to the menu.
        // if entry is an array, it must be a menu item or sub-menu.
        // otherwise, assume it's a menu property (e.g. label, order).
        foreach ($menuItems['primary']['pages'] as $entryId => $entry) {
            if (is_array($entry)) {
                $menu->addDefaultEntry($entry, $entryId);
            } else {
                $menu->setValue($entryId, $entry);
            }
        }

        $menu->save();
    }
}