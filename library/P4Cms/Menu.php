<?php
/**
 * Menus provide persistent storage for Navigation Containers.
 * Additionally, they handle the expansion of 'dynamic' items and
 * assist in installing default menus.
 *
 * Dynamic menu items provide a means injecting variable items.
 * At display time they can be replaced with zero or more actual
 * navigation pages or containers.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Menu extends P4Cms_Record_Config
{
    const DEFAULT_MENU                      = 'primary';

    const MENU_MAX_DEPTH                    = 'maxDepth';
    const MENU_MAX_ITEMS                    = 'maxItems';
    const MENU_KEEP_ROOT                    = 'keepRoot';
    const MENU_ROOT                         = 'root';

    const ITEM_ORDER_PADDING                = 10;

    protected           $_container         = null;
    protected static    $_storageSubPath    = 'menus';
    protected static    $_handlers          = null;
    protected static    $_fields            = array(
        'config'        => array(
            'accessor'  => 'getConfig',
            'mutator'   => 'setConfig'
        ),
        'label'         => array(
            'accessor'  => 'getLabel',
            'mutator'   => 'setLabel'
        )
    );

    /**
     * Fetch the default menu. If the default menu has been removed, returns a
     * new in-memory menu with the default menu id.
     *
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     */
    static public function fetchDefault(P4Cms_Record_Adapter $adapter = null)
    {
        if (static::exists(static::DEFAULT_MENU, $adapter)) {
            $menu = static::fetch(static::DEFAULT_MENU, null, $adapter);
        } else {
            $menu = new static;
            $menu->setId(static::DEFAULT_MENU);
        }

        return $menu;
    }

    /**
     * Get all menus. Extended to sort by 'order' and 'label' by default.
     *
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator            all records of this type.
     * @todo    Change default return to be keyed by record id. This will break numerous tests.
     */
    public static function fetchAll($query = null, P4Cms_Record_Adapter $adapter = null)
    {
        $query = static::_normalizeQuery($query);
        $menus = parent::fetchAll($query, $adapter);

        // if no sorting options in the query, sort by order then label.
        if (!$query->getSortBy()) {
            $menus->sortBy(
                array(
                    'order' => array(P4Cms_Model_Iterator::SORT_NUMERIC),
                    'label' => array(P4Cms_Model_Iterator::SORT_NATURAL)
                )
            );
        }

        return $menus;
    }

    /**
     * Retrieve all menus and all menu items in a single flat list.
     * Both menus and menu items will be wrapped in a P4Cms_Menu_Mixed
     * Model to normalize them.
     *
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator            menus and menu items in a single flat list.
     */
    static public function fetchMixed($query = null, P4Cms_Record_Adapter $adapter = null)
    {
        $items = new P4Cms_Model_Iterator;
        $menus = P4Cms_Menu::fetchAll($query, $adapter);

        foreach ($menus as $menu) {
            $mixed = new P4Cms_Menu_Mixed;
            $mixed->setMenu($menu);
            $items[] = $mixed;

            $pages = new RecursiveIteratorIterator(
                $menu->getContainer(),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($pages as $page) {
                $mixed = new P4Cms_Menu_Mixed;
                $mixed->setMenu($menu);
                $mixed->setMenuItem($page);
                $mixed->setDepth($pages->getDepth() + 1);

                // if this page doesn't live directly under the menu,
                // assign the parent menu item.
                if ($pages->getDepth()) {
                    $mixed->setParentMenuItem($pages->getSubIterator());
                }

                $items[] = $mixed;
            }
        }

        return $items;
    }

    /**
     * Fetches a menu instance even if given a dynamic handler id.
     *
     * In some higher-level code, occassionally we need to get a menu
     * instance from an identifier that might represent a menu id OR a
     * dynamic handler id (@see isDynamicHandlerId for details).
     *
     * This method will determine what type of id we are looking at.
     * If it is a plain menu id, it will attempt to fetch the menu.
     * If it is a dynamic handler id, it will attempt to fetch the
     * handler and place it into a new menu instance.
     *
     * @param   string  $id                     the menu or dynamic handler id
     * @return  P4Cms_Menu                      the fetched or created menu instance
     * @throws  P4Cms_Model_NotFoundException   if id is not a valid menu or handler id.
     */
    public static function fetchMenuOrHandlerAsMenu($id)
    {
        $handlerId = static::isDynamicHandlerId($id);
        if ($handlerId) {

            // fetch the handler (to ensure it's valid) and put it in a page
            // so that we can stuff it in a menu.
            $handler       = P4Cms_Navigation_DynamicHandler::fetch($handlerId);
            $menu          = new P4Cms_Menu;
            $page          = new P4Cms_Navigation_Page_Dynamic;
            $page->handler = $handler->getId();

            // we need to give the page a contrived uuid so that it can be
            // identified consistently (e.g. for menu root purposes) the
            // handler id seems a good choice and is encoded to ensure it
            // doesn't contain any unexpected characters that would break
            // the code that splits uuid's from their dynamic expansion ids
            // @see getItemId
            $page->uuid = bin2hex($handler->getId());

            $menu->addPage($page);
        } else {
            $menu = static::fetch($id);
        }

        return $menu;
    }

    /**
     * Determine if the given id represents a dynamic handler id.
     *
     * This is denoted by a dynamic handler class prefix. If the
     * id is a dynamic handler id, returns the trailing handler id;
     * otherwise false.
     *
     * @param   string  $id     the id to examine
     * @return  string|bool     the trailing handler id or false if not a handler
     */
    public static function isDynamicHandlerId($id)
    {
        if (!preg_match('#P4Cms_Navigation_DynamicHandler/(.+)#', $id, $matches)) {
            return false;
        }

        return $matches[1];
    }

    /**
     * Get this menu's raw navigation container
     * (dynamic items will be left unexpanded).
     *
     * @return  P4Cms_Navigation    this menu's (unexpanded) nav container.
     */
    public function getContainer()
    {
        // load container from config (once).
        if (!$this->_container) {
            $this->_container = new P4Cms_Navigation($this->getConfig()->container);
        }

        return $this->_container;
    }

    /**
     * Sets the raw Navigation Container. Expects dynamic items to be unexpanded.
     *
     * @param   Zend_Navigation_Container|array|null    $container  The top level navigation container
     * @return  P4Cms_Menu      Provides fluent interface
     * @throws  InvalidArgumentException        If passed $container is invalid type
     */
    public function setContainer($container)
    {
        if (!is_null($container)
            && !is_array($container)
            && !$container instanceof Zend_Navigation_Container
        ) {
            throw new InvalidArgumentException(
                "Cannot set container, expected Zend_Navigation_Container, array or null."
            );
        }

        $this->_container = $container instanceof Zend_Navigation_Container
            ? $container
            : new P4Cms_Navigation($container);

        return $this;
    }

    /**
     * Based on the current config, returns the full Navigation Container;
     * Dynamic items will be replaced with their expanded value(s).
     *
     * @param   array   $options    optional - flags to augment the contents of the navigation
     *                              container - supported options include:
     *
     *                                  MENU_MAX_DEPTH - limit the depth of the container - a depth
     *                                                   of zero will only include top level items.
     *
     * @return  P4Cms_Navigation    The items in this menu, will be empty if none
     */
    public function getExpandedContainer($options = array())
    {
        $options = $this->_normalizeOptions($options);

        // attempt to expand original container recursively.
        $expanded = new P4Cms_Navigation;
        try {
            $original = $this->getContainer();
            $this->_expandContainer($original, $expanded, $options);
        } catch (Exception $e) {
            P4Cms_Log::logException("Failed to get expanded menu.", $e);
            $expanded = new P4Cms_Navigation;
        }

        return $expanded;
    }

    /**
     * Get the human friendly menu name. If no explicit label
     * has been set, the ID will be used to generate a default
     * value.
     *
     * @return  string  Human friendly menu label
     */
    public function getLabel()
    {
        return $this->_getValue('label') ?: ucwords(
            str_replace('-', ' ', $this->getId())
        );
    }

    /**
     * Set a human friendly menu name.
     *
     * @param   string|null     $label  The human friendly menu name to use
     * @return  P4Cms_Menu      Provides fluent interface
     */
    public function setLabel($label)
    {
        return $this->_setValue('label', $label);
    }

    /**
     * Get the ids of all menus contributed by active packages.
     *
     * @return  array   a list of ids of default menus.
     */
    public static function getDefaultMenuIds()
    {
        // get all enabled modules.
        $packages = P4Cms_Module::fetchAllEnabled();

        // add current theme to packages
        if (P4Cms_Theme::hasActive()) {
            $packages[] = P4Cms_Theme::fetchActive();
        }

        $ids = array();
        foreach ($packages as $package) {
            $ids = array_merge($ids, array_keys($package->getMenus()));
        }

        return array_unique($ids);
    }

    /**
     * Collect all of the default menus/items and install any that are missing.
     *
     * @param   string|null             $limit      optional - limit install to the given menu id.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     */
    public static function installDefaultMenus($limit = null, P4Cms_Record_Adapter $adapter = null)
    {
        // clear the module/theme cache
        P4Cms_Module::clearCache();
        P4Cms_Theme::clearCache();

        // get all enabled modules.
        $packages = P4Cms_Module::fetchAllEnabled();

        // add current theme to packages
        if (P4Cms_Theme::hasActive()) {
            $packages[] = P4Cms_Theme::fetchActive();
        }

        // install default menus for each package.
        foreach ($packages as $package) {
            static::installPackageDefaults($package, $limit, $adapter);
        }
    }

    /**
     * Install the default menus contributed by a package.
     *
     * @param  P4Cms_PackageAbstract    $package     the package whose menu items will be installed
     * @param  string|null              $limit       optional - limit install to the given menu id.
     * @param  P4Cms_Record_Adapter     $adapter     optional - storage adapter to use.
     */
    public static function installPackageDefaults(
        P4Cms_PackageAbstract $package,
        $limit = null,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        $menus = array();
        foreach ($package->getMenus() as $menuId => $entries) {

            // if limiting install to a single menu, only process matching menu.
            if ($limit && $menuId !== $limit) {
                continue;
            }

            // fetch or create the menu as appropriate.
            if (isset($menus[$menuId])) {
                $menu = $menus[$menuId];
            } else if (static::exists($menuId, null, $adapter)) {
                $menu = static::fetch($menuId, null, $adapter);
            } else {
                $menu = new static;
                $menu->setId($menuId)
                     ->setAdapter($adapter);
            }

            $menus[$menuId] = $menu;

            // add each entry to the menu.
            // if entry is an array, it must be a menu item or sub-menu.
            // otherwise, assume it's a menu property (e.g. label, order).
            foreach ($entries as $entryId => $entry) {
                if (is_array($entry)) {
                    $menu->addDefaultEntry($entry, $entryId);
                } else {
                    $menu->setValue($entryId, $entry);
                }
            }
        }

        // save menus.
        foreach ($menus as $menu) {
            $menu->save();
        }
    }

    /**
     * Remove the default menus contributed by a package.
     *
     * @param P4Cms_PackageAbstract    $package     the package whose menu items will be removed
     * @param P4Cms_Record_Adapter     $adapter     optional - storage adapter to use.
     */
    public static function removePackageDefaults(
        P4Cms_PackageAbstract $package,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        $menus    = array();
        foreach ($package->getMenus() as $menuId => $entries) {

            // fetch the menu if we haven't already done so.
            // skip this menu item if the menu doesn't exist.
            if (isset($menus[$menuId])) {
                $menu = $menus[$menuId];
            } else if (static::exists($menuId, null, $adapter)) {
                $menu = static::fetch($menuId, null, $adapter);
            } else {
                continue;
            }

            $menus[$menuId] = $menu;

            // remove each entry from the menu.
            foreach ($entries as $entryId => $entry) {
                if (is_array($entry)) {
                    $menu->removeDefaultEntry($entry, $entryId);
                }
            }
        }

        // save menus.
        foreach ($menus as $menu) {
            if ($menu->getContainer()->hasPages()) {
                $menu->save();
            } else {
                $menu->delete();
            }
        }
    }

    /**
     * Add a menu entry from a package to the set of existing menu items.
     * Operates recursively, adding any sub-pages in the same fashion.
     *
     * If a container is specified, the entry will be added as a sub-page
     * of that container; otherwise, the entry is placed at the top-level
     * of this menu.
     *
     * A predictable UUID is generated from the given entry id; this
     * allows us to find this particular menu item in the future. This
     * is needed so that we can remove package defaults when a package
     * is disabled. It is also needed during this install process because
     * multiple packages may contribute to the same menu item and we need
     * to be able to locate the item consistently.
     *
     * For example:
     *
     *  foo/module.ini
     *   [menus]
     *   header.links.label = Links
     *
     *  bar/module.ini
     *   [menus]
     *   header.links.pages.home.label = Home
     *   header.links.pages.home.uri   = /
     *
     * The entry ids are formed from the declared array keys (e.g.
     * 'links/home' for the home page link). Without the ids, we would
     * have no way of correlating menu structures across packages
     * (normally Zend Navigation entries are completely anonymous).
     *
     * @param   array                       $entry      the menu item definition
     * @param   string                      $entryId    the identifier for finding this item
     * @param   Zend_Navigation_Container   $container  optional - container to insert item into
     * @return  P4Cms_Menu                  To maintain a fluent interface
     */
    public function addDefaultEntry(
        array $entry,
        $entryId,
        Zend_Navigation_Container $container = null)
    {
        // target container defaults to top-level of menu
        $container = $container ?: $this->getContainer();

        // define the new page item, excluding sub-pages
        // note: we use the entry id to make a predictable
        // uuid so that we can find this page in the future.
        $uuid               = P4Cms_Uuid::fromMd5(md5($entryId))->get();
        $newPage            = $entry;
        $newPage['pages']   = array();
        $newPage['uuid']    = $uuid;

        // if this entry doesn't exist, create it;
        // otherwise, merge with (and re-create) the existing entry.
        if (!$oldPage = $this->getContainer()->findBy('uuid', $uuid)) {
            $newPage = P4Cms_Navigation::inferPageType($newPage);
            $container->addPage($newPage);
        } else {
            // merge old-page with new-page.
            $newPage = array_merge(
                $this->_getPageProperties($oldPage),
                $newPage
            );
            $newPage['pages'] = $oldPage->getPages();

            // re-assess page type (this is a bit tricky)
            // we want explicit types to win, so there are a few cases:
            //  a. new page has explicit type, use it.
            //  b. old page has explicit type, keep it.
            //  c. no explicit type, infer it.
            if (isset($entry['type'])) {
                $newPage['type'] = $entry['type'];
            } else if (!$oldPage->get('typeInferred')) {
                $newPage['type'] = get_class($oldPage);
            } else {
                $newPage = P4Cms_Navigation::inferPageType($newPage);
            }

            // replace old-page with new-page.
            $container->removePage($oldPage);
            $container->addPage($newPage);
        }

        $page = $container->findBy('uuid', $uuid);

        // if the given entry has sub-entries, add them too (recursively)
        if (isset($entry['pages']) && is_array($entry['pages'])) {
            foreach ($entry['pages'] as $subEntryId => $subEntry) {
                $this->addDefaultEntry(
                    $subEntry,
                    $entryId . "/" . $subEntryId,
                    $page
                );
            }
        }

        return $this;
    }

    /**
     * Remove items introduced via addDefaultEntry().
     * Only removes items that still have their default values
     * and have no sub-pages.
     *
     * @param   array                       $entry      the menu item definition
     * @param   string                      $entryId    the identifier for finding this item
     * @param   Zend_Navigation_Container   $container  optional - container to remove item from
     * @return  P4Cms_Menu                  To maintain a fluent interface.
     */
    public function removeDefaultEntry(
        array $entry,
        $entryId,
        Zend_Navigation_Container $container = null)
    {
        // source container defaults to top-level of menu
        $container = $container ?: $this->getContainer();

        // find the entry, nothing to do if we can't.
        // when we installed the item we generated an uuid from
        // the entry id, we do this again so we can find it.
        $uuid = P4Cms_Uuid::fromMd5(md5($entryId))->get();
        if (!$page = $container->findBy('uuid', $uuid)) {
            return $this;
        }

        // if the entry has sub-pages, remove them first.
        if (isset($entry['pages']) && is_array($entry['pages'])) {
            foreach ($entry['pages'] as $subEntryId => $subEntry) {
                $this->removeDefaultEntry(
                    $subEntry,
                    $entryId . "/" . $subEntryId,
                    $page
                );
            }
        }

        // attempt to turn the default item into an actual object then
        // translate it back to an array. running through the object
        // like this will often add additional derived or default values
        // to the array and allow our later matching logic to work.
        try {
            // remove any child pages if present; we are
            // only looking at this particular item.
            unset($entry['pages']);

            // instantiate the inferred type then to array it
            $entry = Zend_Navigation_Page::factory(
                P4Cms_Navigation::inferPageType($entry)
            )->toArray();
        } catch (Exception $e) {
            // simply eat any exceptions and chug
            // along with the existing values array
        }

        // remove the entry provided:
        //  - it has no sub-pages
        //  - it has not been modified (can be moved)
        $exclude = array('pages', 'type', 'typeInferred', 'order', 'uuid', 'visible');
        $default = $this->_getPageProperties($entry, $exclude);
        $current = $this->_getPageProperties($page,  $exclude);
        if (!$page->hasPages() && $current == $default) {
            $container->removePage($page);
        }

        return $this;
    }

    /**
     * Add a page to the raw navigation container in this menu.
     *
     * @param   array|Zend_Navigation_Page|Zend_Config  $page   a page to add to the menu.
     * @return  P4Cms_Menu      provides fluent interface.
     */
    public function addPage($page)
    {
        try {
            $this->getContainer()->addPage($page);
        } catch (Exception $e) {
            P4Cms_Log::log('failed to add page:'. print_r($page, true), P4Cms_Log::DEBUG);
            throw $e;
        }

        return $this;
    }

    /**
     * Save this menu.
     * Extends parent to force UUIDs on menu items.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides a fluent interface
     */
    public function save($description = null)
    {
        // generate UUIDs to any items with missing or duplicate UUIDs
        $container = $this->getContainer();
        $recursive = new RecursiveIteratorIterator(
            $container,
            RecursiveIteratorIterator::SELF_FIRST
        );

        $uuids = array();
        foreach ($recursive as $item) {
            if (empty($item->uuid) || isset($uuids[$item->uuid])) {
                $item->uuid = (string) new P4Cms_Uuid;
            }
            $uuids[$item->uuid] = true;
        }

        // update config from instance container.
        if ($this->_container instanceof Zend_Navigation_Container) {
            $this->getConfig()->container = $container->toArray();
        }

        // let parent do the rest.
        parent::save($description);
    }

    /**
     * Determine the unique identifier of the given menu item.
     * Returns null if no unique id can be determined.
     *
     * For standard menu items, the id is taken from the UUID
     * field. If a standard item has no UUID, returns null.
     *
     * For items that result from dynamic expansion, the id
     * is the combination of the dynamic item's UUID and the
     * 'expansionId' field. If the expanded item has no expansionId,
     * we return null. The expansionId can be provided by the
     * dynamic handler during expansion.
     *
     * @param   Zend_Navigation_Page    $item   item to determine id of.
     * @return  string|null             id of form uuid, or uuid/hex-encoded-expansion-id.
     */
    public static function getItemId($item)
    {
        // detect dynamic expansion items.
        if ($item->dynamic instanceof P4Cms_Navigation_Page_Dynamic) {
            if (empty($item->expansionId)
                || !is_string($item->expansionId)
                || empty($item->dynamic->uuid)
            ) {
                return null;
            }

            return $item->dynamic->uuid . '/' . bin2hex($item->expansionId);
        }

        return empty($item->uuid) ? null : $item->uuid;
    }

    /**
     * Trim the given navigation container according to the passed
     * maximum depth and maximum items limits. Returns the total
     * number of items left in the container.
     *
     * @param   Zend_Navigation_Container   $container  a navigation container to trim.
     * @param   int|null                    $maxDepth   a maximum depth to permit before trimming.
     * @param   int|null                    $maxItems   a maximum number of items to allow.
     * @return  int                         the total number of items left in the container.
     */
    public function trimContainer($container, $maxDepth, $maxItems)
    {
        $remove    = array();
        $itemCount = 0;
        $recursive = new RecursiveIteratorIterator(
            $container,
            RecursiveIteratorIterator::SELF_FIRST
        );

        // flag item for removal if max items or max depth exceeded.
        foreach ($recursive as $item) {
            if (($maxItems !== null && $itemCount >= $maxItems)
                || ($maxDepth !== null && $recursive->getDepth() > $maxDepth)
            ) {
                $remove[] = $item;
            } else {
                $itemCount++;
            }
        }

        // remove items flagged for removal.
        foreach ($remove as $item) {
            $item->getParent()->removePage($item);
        }

        return $itemCount;
    }

    /**
     * Helper to count all of the items in a navigation container recursively.
     *
     * @param   Zend_Navigation_Container   $container  the container to count
     *                                                  all of the items in.
     * @return  int                         the count of all items in container
     */
    public function recursiveCount(Zend_Navigation_Container $container)
    {
        $recursive = new RecursiveIteratorIterator(
            $container,
            RecursiveIteratorIterator::SELF_FIRST
        );

        // it appears we need to convert to an array
        // to count the items because count() on the
        // recursive iterator doesn't seem to work.
        return count(iterator_to_array($recursive));
    }

    /**
     * Diff our menu instance against the given menu.
     *
     * This will produce a flat list of diff details for items
     * in either this menu or the given menu (keyed by UUID).
     *
     * Each diff detail will have the following elements:
     *
     *    type: same|change|insert|delete (purely positional changes are 'same')
     *  isMove: boolean flag indicating that positional properties differ
     *    left: item from the given menu (null if 'insert')
     *   right: item from our instance menu (null if 'delete')
     *
     * @param   P4Cms_Menu  $menu   the menu to diff against
     * @return  array       diff details for items in either menu
     */
    public function diff(P4Cms_Menu $menu)
    {
        $menu = $menu->getContainer();

        // simple function to flatten container and index
        // result by uuid for quicker lookup
        $flatten = function($container)
        {
            $recursive = new RecursiveIteratorIterator(
                $container,
                RecursiveIteratorIterator::SELF_FIRST
            );

            $items = array();
            foreach ($recursive as $item) {
                if (empty($item->uuid)) {
                    continue;
                }
                $items[$item->uuid] = $item;
            }

            return $items;
        };

        // returns positional properties (order/parent)
        $getPositionValues = function($item)
        {
            $parent = $item->getParent();
            $parent = $parent instanceof Zend_Navigation_Page ? $parent->uuid : null;
            return array('parent' => $parent, 'order' => $item->order);
        };

        $left  = $flatten($menu);
        $right = $flatten($this->getContainer());
        $both  = array_keys($left + $right);
        $diffs = array();
        foreach ($both as $uuid) {
            $isMove    = false;
            $leftItem  = isset($left[$uuid])  ? $left[$uuid]  : null;
            $rightItem = isset($right[$uuid]) ? $right[$uuid] : null;

            // determine the type of difference (if there is one)
            //  - if item not in left, this is an insert.
            //  - if item is not in right, this is a delete.
            //  - if we have left and right
            //  -- if non-positional properties differ, type is 'change'
            //  -- if non-positional properties match, type is 'same'.
            //  -- additionally, if positional values differ, flag as move
            if (!$leftItem) {
                $type = 'insert';
            } else if (!$rightItem) {
                $type = 'delete';
            } else {
                $leftValues  = $this->_getPageProperties($leftItem, array('order'));
                $rightValues = $this->_getPageProperties($rightItem, array('order'));
                $type = $leftValues == $rightValues ? 'same' : 'change';

                // flag item as moved if the position has changed
                $isMove = $getPositionValues($leftItem) != $getPositionValues($rightItem);
            }

            $diffs[$uuid] = array(
                'type'   => $type,
                'isMove' => $isMove,
                'left'   => $leftItem,
                'right'  => $rightItem
            );
        }

        return $diffs;
    }

    /**
     * Merge the given menu into this menu.
     *
     * Differences in their menu (with respect to the given base menu)
     * are applied to our menu, unless the difference conflicts with
     * a diff in our menu (also with respect to base).
     *
     * @param   P4Cms_Menu  $theirs     the menu to merge to apply changes from.
     * @param   P4Cms_Menu  $base       the menu to diff ours and theirs against.
     * @return  P4Cms_Menu  provides fluent interface
     */
    public function merge(P4Cms_Menu $theirs, P4Cms_Menu $base)
    {
        $container  = $this->getContainer();
        $ourDiffs   = $this->diff($base);
        $theirDiffs = $theirs->diff($base);

        foreach ($theirDiffs as $uuid => $diff) {
            // if this is a non-positional difference and the item is unchanged
            // or doesn't exist in our container we want to incorporate their diff
            // three distinct cases to handle here:
            //  a) insert (they added a new item)
            //  b) change (they modified an existing item)
            //  c) delete (they removed an existing item)
            if (!isset($ourDiffs[$uuid]) || $ourDiffs[$uuid]['type'] == 'same') {
                // a) just insert this item, its children will be handled later
                if ($diff['type'] == 'insert') {
                    $insert = clone $diff['right'];
                    $insert->setPages(array());

                    $container->addPage($insert);
                }

                // b) clobber our item with the modified item from theirs
                // but keep the sub-pages and position of our item.
                // if we can't find the item in our container we assume we
                // have deleted it; our delete trumps their edit
                if ($diff['type'] == 'change') {
                    $item = $container->findBy('uuid', $uuid);
                    if ($item) {
                        $updated = clone $diff['right'];
                        $updated->setPages($item->getPages());
                        $updated->set('order', $item->get('order'));

                        $parent = $item->getParent();
                        $parent->removePage($item);
                        $parent->addPage($updated);
                    }
                }

                // c) simply remove the item from our container.
                if ($diff['type'] == 'delete') {
                    $item = $container->findBy('uuid', $uuid);
                    if ($item) {
                        $item->getParent()->removePage($item);
                    }
                }
            }

            // if their diff is a move or insert and we don't have this item or our
            // diff is not a move, position the item within our container based on
            // its position in their container
            if (($diff['isMove'] || $diff['type'] == 'insert')
                && (!isset($ourDiffs[$uuid]) || !$ourDiffs[$uuid]['isMove'])
            ) {
                // if item cannot be located; nothing to move
                $item = $container->findBy('uuid', $uuid);
                if (!$item) {
                    continue;
                }

                // skip items whose parent cannot be located in our container
                $parent = $diff['right']->getParent();
                $parent = $parent instanceof Zend_Navigation_Page
                    ? $container->findBy('uuid', $parent->uuid)
                    : $container;
                if (!$parent) {
                    continue;
                }

                // ensure item is the correct place in our menu hierarchy
                $item->setParent($parent);

                // attempt to position item in the same place in our container
                // scan over siblings before this one in their container
                // and locate the first one that also exists in our container
                $found    = false;
                $previous = array();
                foreach ($diff['right']->getParent() as $sibling) {
                    if ($sibling->uuid == $uuid) {
                        break;
                    }
                    $previous[] = $sibling;
                }
                foreach (array_reverse($previous) as $sibling) {
                    foreach ($parent as $candidate) {
                        if ($candidate->uuid == $sibling->uuid) {
                            $found = $candidate;
                            break 2;
                        }
                    }
                }

                // update order of all items with this parent
                // if we could not find a suitable prior sibling
                // place this item first under this parent;
                // otherwise position after the found item.
                $order   = 0;
                $padding = P4Cms_Menu::ITEM_ORDER_PADDING;
                if (!$found) {
                    $item->order = ++$order * $padding;
                }
                foreach (iterator_to_array($parent) as $sibling) {
                    if ($sibling == $item) {
                        continue;
                    }
                    $sibling->order = ++$order * $padding;
                    if ($found && $sibling == $found) {
                        $item->order = ++$order * $padding;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Copy items from the given 'original' container to the given 'expanded'
     * container, expanding dynamic menu items as we go. Calls itself to
     * expand dynamic menu items at any depth.
     *
     * Options are passed by reference so this function can recursively
     * decrement max-items.
     *
     * @param   P4Cms_Navigation    $original   the original (unexpanded) navigation container.
     * @param   P4Cms_Navigation    $expanded   the new (expanded) navigation container.
     * @param   array               &$options   normalized options (@see _normalizeOptions).
     * @return  void
     */
    protected function _expandContainer($original, $expanded, &$options)
    {
        $maxDepth =& $options[self::MENU_MAX_DEPTH];
        $maxItems =& $options[self::MENU_MAX_ITEMS];
        $root     =  $options[self::MENU_ROOT];
        $rooted   =  false;

        // if a root has been specified, find root by matching against UUID and
        // update 'original' to point to found item - return if root can't be found.
        $uuid = reset(explode('/', $root, 2));
        if (!empty($uuid)) {
            $recursive = new RecursiveIteratorIterator(
                $original,
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($recursive as $item) {
                if (static::getItemId($item) === $uuid) {
                    $original = $item;
                    $rooted   = true;

                    // strip UUID from root so we don't re-parse it when recursing.
                    $options[self::MENU_ROOT] = substr($root, strlen($uuid));
                    break;
                }
            }

            // if we didn't find the root, return early.
            if ($original !== $item) {
                return;
            }
        }

        // if we are rooted and options stipulate we keep the root, push it down
        // we don't push down dynamic items because that is handled elsewhere
        if ($rooted
            && $options[self::MENU_KEEP_ROOT]
            && !$original instanceof P4Cms_Navigation_Page_Dynamic
        ) {
            $original = new P4Cms_Navigation(array($original));
        }

        // if the original container is a empty dynamic item, push it down a level.
        // (no relation to keep root above - this is done because dynamic items are
        // expanded in place of the dynamic item, not as children)
        if ($original instanceof P4Cms_Navigation_Page_Dynamic
            && !$original->hasPages()
        ) {
            $original = new P4Cms_Navigation(array($original));
        }

        // loop over original menu items and copy them to the
        // expanded container, expanding dynamic items as we go.
        // note: we explicitly set item order to preserve the original
        // order and prevent jostling of items with equal weight.
        $order = 0;
        foreach ($original as $item) {

            // skip items the user should not see.
            if (!$this->_passesAcl($item)) {
                continue;
            }

            // if we already hit max items, stop processing items.
            if ($maxItems !== null && $maxItems < 1) {
                break;
            }

            // if item is dynamic, expand it.
            if ($item instanceof P4Cms_Navigation_Page_Dynamic) {
                try {

                    // forcibly copy options before we pass them to the callback
                    // we do this because we use references above and any option
                    // changes in the callback would affect us here (due to the
                    // peculiar behavior of references in PHP).
                    $itemOptions = unserialize(serialize($options));

                    // dynamic items can be configured with max-depth/items
                    // options - we want to take the more restrictive of the
                    // item options vs. the options provided by the caller.
                    // (take the lowest integer value for max-items/depth)
                    $itemLimits  = array_filter(array($item->get(self::MENU_MAX_ITEMS), $maxItems), 'is_int');
                    $depthLimits = array_filter(array($item->get(self::MENU_MAX_DEPTH), $maxDepth), 'is_int');
                    $itemOptions[self::MENU_MAX_ITEMS] = $itemLimits  ? min($itemLimits)  : null;
                    $itemOptions[self::MENU_MAX_DEPTH] = $depthLimits ? min($depthLimits) : null;

                    // we want to copy certain dynamic item properties to
                    // the expanded replacement items.
                    $properties = $this->_getPageProperties($item);

                    // move replacements to expanded container.
                    // use iterator_to_array because moving pages upsets the loop.
                    $replacements = $this->_expandDynamic($item, $itemOptions);
                    foreach (iterator_to_array($replacements) as $replacement) {

                        // skip replacement items the user should not see.
                        if (!$this->_passesAcl($replacement)) {
                            continue;
                        }

                        // merge original dynamic properties with replacement
                        $replacement->setOptions(
                            array_merge(
                                $properties,
                                $this->_getPageProperties($replacement)
                            )
                        );

                        $replacement->setOrder($order++);
                        $expanded->addPage($replacement);
                    }

                } catch (Exception $e) {
                    P4Cms_Log::logException("Failed to expand dynamic menu item.", $e);
                }

                // next item - dynamic items don't get added
                continue;
            }

            // standard item, copy and add to expanded container (w. out children).
            $itemCopy = clone $item;
            $expanded->addPage($itemCopy);
            $itemCopy->setOrder($order++)
                     ->removePages();
            $maxItems--;

            // if the item has sub-pages, expand them as well
            // decrement maxdepth as we go deeper.
            if ($item->hasPages() && ($maxDepth === null || $maxDepth > 0)) {
                $maxDepth = $maxDepth === null ? null : $maxDepth - 1;
                $this->_expandContainer($item, $itemCopy, $options);
                $maxDepth = $maxDepth === null ? null : $maxDepth + 1;
            }
        }
    }

    /**
     * Expand the given dynamic item via the expansion callback and
     * return the replacement items. Options are passed by reference.
     * The max-items option will be decremented by the total number of
     * replacement items.
     *
     * @param   P4Cms_Navigation_Page_Dynamic   $dynamic    the dynamic item to expand.
     * @param   array                           &$options   normalized options (@see _normalizeOptions).
     * @return  Zend_Navigation_Container       the expanded items honoring expansion options.
     */
    protected function _expandDynamic($dynamic, &$options)
    {
        // if the dynamic item does not specify a valid handler, nothing to do.
        $handler = $this->_getHandler($dynamic->getHandler());
        if (!$handler) {
            return new P4Cms_Navigation;
        }

        // get replacement items via handler callback.
        $root = $options[self::MENU_ROOT];
        $options[self::MENU_ROOT] = pack("H*", substr($root, 1));
        $replacements = $handler->callExpansionCallback($dynamic, $options);
        $options[self::MENU_ROOT] = $root;

        // normalize to a navigation container.
        if (!$replacements instanceof Zend_Navigation_Container) {
            $replacements = new P4Cms_Navigation($replacements);
        }

        // associate dynamic item with each expanded item and search
        // for root if one has been specified.
        $recursive = new RecursiveIteratorIterator(
            $replacements,
            RecursiveIteratorIterator::SELF_FIRST
        );
        unset($root);
        foreach ($recursive as $item) {
            $item->dynamic = $dynamic;

            if (!empty($options[self::MENU_ROOT])
                && !isset($root)
                && static::getItemId($item) === $dynamic->uuid . $options[self::MENU_ROOT]
            ) {
                $root = $item;
            }
        }

        // if a root has been found, update 'replacements' to point to found item
        // if a root was specified, but not found, return empty container.
        if (isset($root) && $root instanceof Zend_Navigation_Container) {
            $replacements = $root;
        } else if (!empty($options[self::MENU_ROOT])) {
            return new P4Cms_Navigation;
        }

        // if the options stipulate we should keep the root, push it down.
        if (isset($root) && $options[self::MENU_KEEP_ROOT]) {
            $replacements = new P4Cms_Navigation(array($replacements));
        }

        // trim replacement items according to max-depth and max-items.
        $itemCount = $this->trimContainer(
            $replacements,
            $options[self::MENU_MAX_DEPTH],
            $options[self::MENU_MAX_ITEMS]
        );

        // options are passed by reference, decrement max-items
        if ($options[self::MENU_MAX_ITEMS] !== null) {
            $options[self::MENU_MAX_ITEMS] -= $itemCount;
        }

        return $replacements;
    }

    /**
     * Get all of the dynamic handlers. Only fetches handlers
     * the first time and caches them for subsequent calls.
     *
     * @return  P4Cms_Model_Iterator    the dynamic handlers in the system.
     */
    protected function _getHandlers()
    {
        if (!static::$_handlers) {
            static::$_handlers = P4Cms_Navigation_DynamicHandler::fetchAll();
        }

        return static::$_handlers;
    }

    /**
     * Get the named dynamic handler from our local cache.
     *
     * @param   string  $handler                        the name of the handler to get.
     * @return  P4Cms_Navigation_DynamicHandler|null    the requested handler or null.
     */
    protected function _getHandler($handler)
    {
        $handlers = $this->_getHandlers();
        return isset($handlers[$handler]) ? $handlers[$handler] : null;
    }

    /**
     * Process expansion options to ensure consistent entries and values.
     *
     * @param   array   $options    the expansion options to normalize.
     * @return  array   the normalized expansion options.
     */
    protected function _normalizeOptions($options)
    {
        $normalized = array(
            static::MENU_MAX_DEPTH        => null,
            static::MENU_MAX_ITEMS        => null,
            static::MENU_KEEP_ROOT        => false,
            static::MENU_ROOT             => null,
        );

        if (isset($options[static::MENU_MAX_DEPTH])
            && strlen($options[static::MENU_MAX_DEPTH])
            && intval($options[static::MENU_MAX_DEPTH]) >= 0
        ) {
            $normalized[static::MENU_MAX_DEPTH] = intval($options[static::MENU_MAX_DEPTH]);
        }

        if (isset($options[static::MENU_MAX_ITEMS])
            && strlen($options[static::MENU_MAX_ITEMS])
            && intval($options[static::MENU_MAX_ITEMS]) > 0
        ) {
            $normalized[static::MENU_MAX_ITEMS] = intval($options[static::MENU_MAX_ITEMS]);
        }

        if (isset($options[static::MENU_ROOT])
            && !empty($options[static::MENU_ROOT])
        ) {
            $normalized[static::MENU_ROOT] = $options[static::MENU_ROOT];
        }

        if (isset($options[static::MENU_KEEP_ROOT])) {
            $normalized[static::MENU_KEEP_ROOT] = $options[static::MENU_KEEP_ROOT];
        }

        // the options we care about will be normalized,
        // any other options will be merged in as-is.
        return $normalized + (array) $options;
    }

    /**
     * Get page properties suitable for merging with another page.
     *
     * Excludes type info, sub-pages and any empty properties by
     * default - pass an array of properties to override.
     *
     * This method is needed because the Zend_Navigation_Page toArray()
     * method calls toArray() on all sub-pages recursively.
     *
     * @param   array|Zend_Navigation_Page  $page       the page to get mergeable properties from.
     * @param   array                       $exclude    optional - pass to over-ride default excluded
     * @return  array                       the mergeable properties of the page.
     */
    protected function _getPageProperties($page, array $exclude = null)
    {
        // convert page objects to array form.
        // strip pages first to avoid extra work.
        if ($page instanceof Zend_Navigation_Page) {
            $page = clone $page;
            $page->removePages();
            $page = $page->toArray();
        }

        // must have an array at this point.
        if (!is_array($page)) {
            throw new InvalidArgumentException(
                "Cannot get properties. Page must be an array or page instance."
            );
        }

        // strip out the excluded or empty properties
        $exclude = is_null($exclude) ? array('pages', 'type', 'typeInferred') : $exclude;
        $filter  = array_fill_keys($exclude, null);
        $values  = array_filter(array_merge($page, $filter));

        return $values;
    }

    /**
     * Check if the current user is allowed to access the given menu item.
     *
     * @param   Zend_Navigation_Page    $item   the item to check access for.
     * @return  bool                    true if the active user can access item.
     */
    protected function _passesAcl($item)
    {
        // if item has no acl resource, nothing to check.
        if (!$item->getResource()) {
            return true;
        }

        // if no active user, can't check acl - assume the worst.
        if (!P4Cms_User::hasActive()) {
            return false;
        }

        return P4Cms_User::fetchActive()->isAllowed(
            $item->getResource(),
            $item->getPrivilege()
        );
    }
}
