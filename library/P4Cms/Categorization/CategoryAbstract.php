<?php
/**
 * This class facilitates the creation of categories, including storage of arbitrary metadata about
 * the category, and associating arbitrary data with each category. Categories can provide nesting,
 * similar to folders in a filesystem, or can be flat, such as a list of tags.
 *
 * Provides category associations/storage.
 *
 * Typical usage:
 *
 * $category = new ConcreteCategory;
 * $category->setId('/')
 *          ->setLabel('My Directory')
 *          ->save();
 * $entries = $category->getEntries();
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4Cms_Categorization_CategoryAbstract extends P4Cms_Record
{
    /**
     * Defines the filename to contain category metadata.
     */
    const   CATEGORY_FILENAME   = '.index';

    /**
     * Defines the prefix for encoded category entry ids.
     */
    const   ENTRY_PREFIX        = '_';

    const   OPTION_RECURSIVE    = 'recursive';

    /**
     * Specifies the sub-path to use for storage of category data.
     * This is used in combination with the records path to construct
     * the full storage path.
     * The implementing class MUST set this property.
     */
    protected static $_storageSubPath = null;

    /**
     * Specifies whether the concrete category allows nested categories.
     */
    protected static $_nestingAllowed = false;

    /**
     * Specify the fields for category metadata.
     */
    protected static $_fields = array(
        'title'         => array(
            'accessor'  => 'getTitle',
            'mutator'   => 'setTitle'
        ),
        'description'   => array(
            'accessor'  => 'getDescription',
            'mutator'   => 'setDescription'
        )
    );

    /**
     * Set the id for this category category.
     *
     * @param   null|string                      $id    the id for this record - null to clear.
     * @return  P4Cms_Categorization_CategoryAbstract  provides fluent interface.
     * @throw   InvalidArgumentException         if $id contains '/' when
     *                                           nesting is not allowed.
     * @throw   InvalidArgumentException         if $id contains invalid characters.
     */
    public function setId($id)
    {
        $validator = new P4Cms_Validate_CategoryId;
        if ($id !== null && !$validator->isValid($id, static::CATEGORY_FILENAME)) {
            $messages = array_values($validator->getMessages());
            throw new InvalidArgumentException(
                "Cannot set id: ". $messages[0]
            );
        }

        if (strpos($id, '/') !== false) {
            static::_checkNestability();
        }

        return parent::setId($id);
    }

    /**
     * Get the category title or its base id if no title available.
     *
     * @return string|null  The title of this object, or ID if no title available.
     */
    public function getTitle()
    {
        $title = strlen($this->_getValue('title'))
            ? $this->_getValue('title')
            : $this->getBaseId();

        return $title === null ?: (string) $title;
    }

    /**
     * Update the title with a new value.
     *
     * @param   string  $title  The new title
     * @return  P4Cms_Category  To maintain a fluent interface
     */
    public function setTitle($title)
    {
        return $this->_setValue('title', $title);
    }

    /**
     * Get the current description or null if none set.
     *
     * @return string|null  The description of this category, or null.
     */
    public function getDescription()
    {
        return $this->_getValue('description');
    }

    /**
     * Update the description with a new value.
     *
     * @param   string  $description    The new description
     * @return  P4Cms_Category  To maintain a fluent interface
     */
    public function setDescription($description)
    {
        return $this->_setValue('description', $description);
    }

    /**
     * Get the base id - the trailing portion (basename) of the id.
     *
     * @return  string  the basename of the id.
     */
    public function getBaseId()
    {
        return basename($this->getId());
    }

    /**
     * Determines whether the specified category contains child categories.
     *
     * @param   bool  $recursive  optional - collect children recursively if true;
     *                            defaults to false.
     * @return  bool  true if at least one child category exists, false otherwise.
     */
    public function hasChildren($recursive = false)
    {
        return (bool) count($this->getChildren($recursive)) > 0;
    }

    /**
     * Retrieves child categories within the specified path, if any.
     *
     * @param   bool  $recursive  optional - collect children recursively if true;
     *                            defaults to false.
     * @return  P4Cms_Model_Iterator  An iterator of the child categories.
     */
    public function getChildren($recursive = false)
    {
        // perform validations
        static::_checkNestability();
        $id = $this->_checkIdSet('get children');

        // fetch all categories under this category.
        $selector = $recursive ? '...' : '*';
        return static::fetchAll(
            P4Cms_Record_Query::create()->addPath($id . '/' . $selector),
            $this->getAdapter()
        );
    }

    /**
     * Determines whether the current category has a parent category
     * in storage. If you just want to check if the category looks like
     * it should have a parent, use getDepth().
     *
     * @return  bool    true if parent exists, false otherwise.
     */
    public function hasParent()
    {
        return ($this->getDepth())
            ? static::exists($this->getParentId())
            : false;
    }

    /**
     * Retrieves the parent category for this category.
     *
     * @return P4Cms_Categorization_CategoryAbstract    the parent category.
     * @throw  P4Cms_Categorization_Exception           if has no parent or category id does not exist.
     */
    public function getParent()
    {
        if ($this->getDepth() === 0) {
            throw new P4Cms_Categorization_Exception(
                "Cannot get parent. This category has no parent."
            );
        }

        return static::fetch($this->getParentId(), null, $this->getAdapter());
    }

    /**
     * Get the id of this category's parent category.
     * Returns null for top-level categories.
     *
     * @return  string  the id of this category's parent.
     * @throws  P4Cms_Categorization_Exception  if no id is set.
     */
    public function getParentId()
    {
        $this->_checkIdSet('get parent category');

        if ($this->getDepth() > 0) {
            return dirname($this->getId());
        } else {
            return null;
        }
    }

    /**
     * Retrieves the ancestors for this category ordered from the top down
     * (greatest ancestor to direct parent).
     *
     * @return  P4Cms_Model_Iterator    all of the ancestors for this category.
     */
    public function getAncestors()
    {
        $query = new P4Cms_Record_Query;
        $query->addPaths($this->getAncestorIds());
        return static::fetchAll($query, $this->getAdapter());
    }

    /**
     * Get the ids of this categories ancestors ordered from the top down
     * (greatest ancestor to direct parent).
     *
     * @return  array   the ids of this category's ancestors.
     * @throws  P4Cms_Categorization_Exception  if no id is set.
     */
    public function getAncestorIds()
    {
        $this->_checkIdSet('get ancestor ids');

        // no ancestry if depth zero.
        if ($this->getDepth() === 0) {
            return array();
        }

        // extract ancestors from parent id.
        $ancestors = array();
        $segments  = explode('/', $this->getParentId());
        foreach ($segments as $segment) {
            $ancestorId  = (isset($ancestorId) ? $ancestorId . '/' : '') . $segment;
            $ancestors[] = $ancestorId;
        }

        return $ancestors;
    }

    /**
     * Extend save to verify that category ancestry exists.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides a fluent interface
     */
    public function save($description = null)
    {
        $this->_checkIdSet('save');

        // verify existence of parentage.
        $adapter = $this->getAdapter();
        $parent  = $this->getParentId();
        while ($parent) {
            if (!static::exists($parent, null, $adapter)) {
                throw new InvalidArgumentException(
                    'Cannot create new category; category ancestry does not exist.'
                );
            }
            $parent = strstr($parent, '/') ? dirname($parent) : null;
        }

        return parent::save($description);
    }

    /**
     * Delete a category. Extends parent to delete entries and sub-categories.
     *
     * @param   string  $description                    optional - a description of the change.
     * @return  P4Cms_Categorization_CategoryAbstract   provides fluent interface.
     */
    public function delete($description = null)
    {
        $id = $this->_checkIdSet('delete category');

        // ensure id exists.
        if (!static::exists($id)) {
            throw new P4Cms_Categorization_Exception(
                "Cannot delete category. Category does not exist."
            );
        }

        $adapter    = $this->getAdapter();
        $connection = $adapter->getConnection();
        $filespec   = static::getStoragePath($adapter) . "/" . $id . "/...";

        // revert and delete this entire category (-v deletes without syncing).
        $connection->run('revert', $filespec);
        $connection->run('delete', array('-v', $filespec));

        // if we're in a batch, reopen in batch change, else submit.
        if ($adapter->inBatch()) {
            $connection->run('reopen', array('-c', $adapter->getBatchId(), $filespec));
        } else {
            if (!$description) {
                $description = "Deleted '" . static::$_storageSubPath . "' record.";
            }
            $connection->run('submit', array('-d', $description, $filespec));
        }

        return $this;
    }

    /**
     * Determines whether the current category has any entries.
     *
     * @param   bool  $recursive  optional - evaluate entries recursively if true;
     * @return  bool  true if entries exist, false otherwise.
     */
    public function hasEntries($recursive = false)
    {
        return (bool) count($this->getEntries(array(static::OPTION_RECURSIVE => $recursive)));
    }

    /**
     * Retrieve the entries within this category.
     * By default this will return a list of unique entry identifiers.
     * If dereference is set to true, entry ids get resolved to their
     * original form (e.g. objects).
     *
     * @param   array   $options    options to influence fetching entries, recognized keys are:
     *                              OPTION_RECURSIVE - whether to include entries in sub-categories
     *                              sort             - if true then entries will be sorted
     * @return  array   all unique entries within this category.
     */
    public function getEntries(array $options = array())
    {
        $id        = $this->_checkIdSet('get entries');
        $adapter   = $this->getAdapter();
        $recursive = isset($options[static::OPTION_RECURSIVE]) && $options[static::OPTION_RECURSIVE];
        $sort      = isset($options['sort']) && $options['sort'];

        // fetch entries in this category - exclude deleted and
        // category metadata files and adjust wildcard for recursive.
        $filespec = dirname(static::idToFilespec($id, $adapter));
        $wildcard = $recursive ? '...' : '*';
        $filter   = '^headAction=...delete ^depotFile=...' . static::CATEGORY_FILENAME;
        $query    = P4_File_Query::create()
                    ->addFilespec($filespec .'/'. $wildcard)
                    ->setFilter($filter);
        $files    = P4_File::fetchAll($query, $adapter->getConnection());

        // filenames are encoded entry ids.
        $entries = array();
        foreach ($files as $file) {
            $entries[] = static::decodeEntryId($file->getBasename());
        }

        // ensure entries are unique.
        $entries = array_unique($entries);

        // sort entries.
        if ($sort) {
            sort($entries);
        }

        return $entries;
    }

    /**
     * Determines if the specified entry exists in this category.
     *
     * @param   string  $id     the id of the entry to check for.
     * @return  bool    true if the entry is in this category; false otherwise.
     */
    public function hasEntry($id)
    {
        $this->_checkIdSet('check for entry');

        return P4Cms_Record::exists(
            $this->_getEntryRecordId($id),
            null,
            $this->getAdapter()
        );
    }

    /**
     * Add an entry to this category.
     *
     * @param   mixed   $entry                          an entry id, or a known entry type,
     *                                                  for association with the current category.
     * @return  P4Cms_Categorization_CategoryAbstract   provide fluent interface.
     * @throws  InvalidArgumentException                if $entry is not a string or known entry type.
     */
    public function addEntry($entry)
    {
        return $this->addEntries(array($entry));
    }

    /**
     * Add multiple entries to this category.
     *
     * @param   array   $entries                        an array of strings or entry objects to
     *                                                  associate with this category.
     * @return  P4Cms_Categorization_CategoryAbstract   provides fluent interface.
     * @throws  P4Cms_Categorization_Exception          if category id not set.
     * @throws  InvalidArgumentException                if $entries is not an array.
     */
    public function addEntries($entries)
    {
        return $this->_adjustEntries(
            'add',
            'Added entries to ' . $this->getId() . '.',
            $entries
        );
    }

    /**
     * Delete an entry from this category.
     *
     * @param   mixed   $entry                          an entry id, or a known entry type,
     *                                                  for removal from this category.
     * @return  P4Cms_Categorization_CategoryAbstract   provide fluent interface.
     * @throws  InvalidArgumentException                if $entry is not a string or known entry type.
     */
    public function deleteEntry($entry)
    {
        return $this->deleteEntries(array($entry));
    }

    /**
     * Delete multiple entries from this category.
     *
     * @param   array   $entries                 an array of strings or record objects to
     *                                           remove association from the current category.
     * @return  P4Cms_Categorization_CategoryAbstract  provide fluent interface.
     * @throws  InvalidArgumentException         if $entries is not an array.
     */
    public function deleteEntries($entries)
    {
        return $this->_adjustEntries(
            'delete',
            'Deleted entries from ' . $this->getId() . '.',
            $entries
        );
    }

    /**
     * Get the depth of this category in the hierarchy.
     * Categories at the root of the tree will have a depth of zero.
     * The depth is equivalent to the number of ancestors a category has.
     *
     * @return  int                         the depth of this category in the tree
     *                                      (zero for top-level categories).
     * @throws  P4Cms_Category_Exception    if this category class does not permit nesting.
     */
    public function getDepth()
    {
        // ensure nesting allowed.
        static::_checkNestability();

        return substr_count($this->getId(), '/');
    }

    /**
     * Extend parent to limit query to categories (excluding entries).
     * See parent implementation for full description of options.
     *
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator    all records of this type.
     */
    public static function fetchAll($query = null, P4Cms_Record_Adapter $adapter = null)
    {
        if (!$query instanceof P4Cms_Record_Query && !is_array($query) && !is_null($query)) {
            throw new InvalidArgumentException(
                'Query must be a P4Cms_Record_Query, array or null'
            );
        }

        // normalize array input to a query
        if (is_array($query)) {
            $query = new P4Cms_Record_Query($query);
        }

        // if null query given, make a new one.
        $query = $query ?: new P4Cms_Record_Query;

        // return all categories if we haven't been given any path/id limits.
        if ($query->getPaths() === null && !$query->getIds()) {
            $query->addPath('...');
        }

        // manipulate the fetch-by-path so we search for the category file(s).
        $newPaths = array();
        foreach ($query->getPaths() ?: array() as $path) {
            $newPaths[] = $path .'/'. static::CATEGORY_FILENAME;
        }
        $query->setPaths($newPaths);

        // category files push depth down a level, adjust max depth if set.
        if ($query->getMaxDepth() !== null && $query->getMaxDepth() >= 0) {
            $query->setMaxDepth($query->getMaxDepth() + 1);
        }

        return parent::fetchAll($query, $adapter);
    }

    /**
     * Get the ids of all categories that contain the given entry.
     *
     * @param   mixed   $item           an id, or a known entry type to search for.
     * @param   P4Cms_Record_Adapter    $adapter  optional - storage adapter to use.
     * @return  array  ids of categories containing the $item entry.
     */
    public static function fetchIdsByEntry($item, P4Cms_Record_Adapter $adapter = null)
    {
        $tempCategory = new static;
        if (!isset($item) || !$tempCategory->_canAcceptId($item)) {
            throw new InvalidArgumentException(
                "Cannot get categories; the entry must either be a string or known entry type."
            );
        }

        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // determine set of categories containing entry using embedded wildcard.
        $filespec = static::getDepotStoragePath($adapter)
                  . '/.../' . static::encodeEntryId($item);
        $query = P4_File_Query::create()->addFilespec($filespec)->setFilter('^headAction=...delete');
        $files = P4_File::fetchAll($query, $adapter->getConnection());

        // derive ids from file names.
        $categories = array();
        foreach ($files as $file) {
            $categories[] = static::depotFileToId(
                dirname($file->getFilespec()) . '/' . static::CATEGORY_FILENAME,
                $adapter
            );
        }

        return $categories;
    }

    /**
     * Get all categories that contain the given entry.
     * If you just want category ids (not objects), use fetchIdsByEntry.
     *
     * @param   mixed                   $item   an id, or a known entry type to search for.
     * @param   P4Cms_Record_Adapter    $adapter  optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator    categories containing the $item entry.
     */
    public static function fetchAllByEntry($item, P4Cms_Record_Adapter $adapter = null)
    {
        $query = new P4Cms_Record_Query;
        $query->addPaths(static::fetchIdsByEntry($item, $adapter));
        return static::fetchAll($query, $adapter);
    }

    /**
     * Set the categories that an entry resides in.
     *
     * @param   mixed   $item               An id, or a known data structure,
     *                                      to associate with the list of categories.
     * @param   array   $newCategoryIds     A list of category ids to associate with the $item.
     * @param   P4Cms_Record_Adapter    $adapter  optional - storage adapter to use.
     * @throws  InvalidArgumentException
     */
    public static function setEntryCategories(
        $item,
        $newCategoryIds,
        P4Cms_Record_Adapter $adapter = null)
    {
        $tempCategory = new static;
        if (!is_string($item) || !$tempCategory->_canAcceptId($item)) {
            throw new InvalidArgumentException(
                'Cannot set categories; the entry must either be a string or known data structure.'
            );
        }
        if (!is_array($newCategoryIds)) {
            throw new InvalidArgumentException(
                'Cannot set categories; categories must be an array.'
            );
        }

        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // ensure that the new categories exist
        foreach ($newCategoryIds as $id) {
            if (!static::exists($id)) {
                throw new P4Cms_Categorization_Exception(
                    "Cannot add entry to a non-existant category."
                );
            }
        }

        // now fetch the current categories
        $categoryIds = static::fetchIdsByEntry($item, $adapter);

        // compute the difference between the two lists.
        list($adds, $deletes) = P4Cms_ArrayUtility::computeDiff($categoryIds, $newCategoryIds);

        // add $item to these categories
        foreach ($adds as $id) {
            $category = new static;
            $category->setId($id)->addEntry($item);
        }

        // remove $item from these categories
        foreach ($deletes as $id) {
            $category = new static;
            $category->setId($id)->deleteEntry($item);
        }
    }

    /**
     * Encode an entry id so that it's safe to place in a filesystem.
     * Encoded ids are modified base64 (replacing slashes '/' with dashes '-')
     * with an ENTRY_PREFIX character.
     *
     * @param   string  $id  The id to encode.
     * @return  string  Encoded id suitable for filesystem storage.
     * @throws  InvalidArgumentException  if $id is not set, or has 0 length.
     */
    public static function encodeEntryId($id)
    {
        if (!isset($id) || strlen($id) == 0) {
            throw new InvalidArgumentException(
                'Cannot encode entry id; id not set or has no length.'
            );
        }

        return static::ENTRY_PREFIX . bin2hex($id);
    }

    /**
     * Decode an encoded entry id.
     *
     * @param   string  $encoded  The encoded id to decode.
     * @return  string  Decoded entry id.
     * @throws  InvalidArgumentException  if $encoded is not set, has 0 length,
     *                                    or cannot be successfully decoded.
     */
    public static function decodeEntryId($encoded)
    {
        if (!isset($encoded) || strlen($encoded) == 0) {
            throw new InvalidArgumentException(
                'Cannot decode entry id; encoded id not set or has no length.'
            );
        }

        $id = null;
        try {
            $id = pack('H*', ltrim($encoded, static::ENTRY_PREFIX));
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                "Cannot decode entry id; encoded id contains invalid characters."
            );
        }

        return $id;
    }

    /**
     * Move/rename a category.
     *
     * @param   string  $sourceId       The category id to be moved/renamed
     * @param   string  $targetId       The category destination id.
     * @param   P4Cms_Record_Adapter    $adapter  optional - storage adapter to use.
     *
     * @throws  InvalidArgumentException  if $sourceId is null or does not exist.
     * @throws  InvalidArgumentException  if $targetId is null or already exists.
     * @throws  InvalidArgumentException  if $sourceId or $targetId == '' (aka root).
     * @throws  InvalidArgumentException  if $targetId is a sub-category of $sourceId.
     * @throws  P4Cms_Categorization_Exception  if the move is unsuccessful for some other reason.
     */
    public static function move($sourceId, $targetId, P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        if (!isset($sourceId) || !isset($targetId)) {
            throw new InvalidArgumentException(
                'Cannot move category; both the source and target category must be specified.'
            );
        }
        if ($sourceId == '' || $targetId == '') {
            throw new InvalidArgumentException(
                'Cannot move category; neither the source or target category can be "".'
            );
        }
        if (!static::exists($sourceId, null, $adapter)) {
            throw new InvalidArgumentException(
                'Cannot move category; source category does not exist.'
            );
        }
        if (static::exists($targetId, null, $adapter)) {
            throw new InvalidArgumentException(
                'Cannot move category; target category already exists.'
            );
        }
        if (strpos($targetId, $sourceId . '/') === 0) {
            throw new InvalidArgumentException(
                'Cannot move category; target category is within source category.'
            );
        }

        // if we're not in a batch, setup a change to contain the moves
        if (!$adapter->inBatch()) {
            $change = new P4_Change;
            $change->setDescription("Preparing to move/rename category '$sourceId' to '$targetId'")
                   ->save();
            $changeId = $change->getId();
        } else {
            $changeId = $adapter->getBatchId();
        }

        // open the files to be moved for edit, and move them
        // move requires files to be opened for add or edit first
        $p4     = $adapter->getConnection();
        $source = dirname(static::idToFilespec($sourceId, $adapter)) . '/...';
        $target = dirname(static::idToFilespec($targetId, $adapter)) . '/...';
        $p4->run('revert', $source);
        $p4->run('revert', $target);
        $p4->run('sync',   $source);
        $p4->run('edit',   $source);
        $p4->run('move',   array($source, $target));
        $p4->run('reopen', array('-c', $changeId, $source, $target));

        // if we're not in a batch, submit the moves.
        if (!$adapter->inBatch()) {
            $change->setDescription("Move/rename category '$sourceId' to '$targetId'")
                   ->submit();
        }
    }

    /**
     * Reports whether the current category allows nesting of categories.
     *
     * @return  bool  true if nesting allowed, false otherwise.
     */
    public static function isNestingAllowed()
    {
        return (bool) static::$_nestingAllowed;
    }

    /**
     * Given a category id, determine the corresponding filespec.
     * This overrides P4Cms_Record's mapping to force the use of
     * a particular filename.
     *
     * @param   string                  $id         the category id to get the filespec for.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  string  the path used to store this category in perforce.
     */
    public static function idToFilespec($id, P4Cms_Record_Adapter $adapter = null)
    {
        return parent::idToFilespec($id, $adapter) . '/' . static::CATEGORY_FILENAME;
    }

    /**
     * Checks whether the current instance has an id set.
     *
     * @param   string  $action  Identifies the action in the exception, if required.
     * @throws  P4Cms_Categorization_Exception  if the id is not set.
     */
    protected function _checkIdSet($action = '')
    {
        if ($this->getId() === null) {
            if (strlen($action)) {
                $action = " $action";
            }
            throw new P4Cms_Categorization_Exception(
                "Cannot$action; category id is not set."
            );
        }

        return $this->getId();
    }

    /**
     * Determines whether an id can be extracted from the argument.
     *
     * @param  mixed  $item  an id, or other structure that has an id.
     * @return  bool  true if an id can be extracted, false otherwise.
     */
    protected function _canAcceptId($item)
    {
        try {
            $id = $this->_acceptId($item);
            return (isset($id) && strlen($id)) ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Accept ids passed as strings or objects with getId() method.
     * Override in a concrete class to implement the required id extraction.
     * Note: ids passed as integers will be converted to strings.
     *
     * @param   string|object  $item         an id, or an object that has an id,
     * @param   string         $errorPrefix  a prefix to include in exceptions, if necessary.
     * @throws  InvalidArgumentException     if $item is not a string or object with getId().
     */
    protected function _acceptId($item, $errorPrefix = '')
    {
        $id = '';
        if (is_string($item) || is_int($item)) {
            $id = (string) $item;
        } else if (is_object($item)) {
            if (method_exists($item, 'getId')) {
                $id = $item->getId();
            } else {
                throw new InvalidArgumentException(
                    $errorPrefix .'the provided object does not have a getId() method.'
                );
            }
        } else {
            throw new InvalidArgumentException(
                $errorPrefix .'the provided entry is not a string or object with a getId() method.'
            );
        }

        return $id;
    }

    /**
     * Given a category filespec in depotFile syntax, determine the id.
     *
     * @param  string                   $depotFile  a record depotFile.
     * @param  P4Cms_Record_Adapter     $adapter    optional - storage adapter to use.
     * @return  string|int  the id portion of the depotFile file spec.
     */
    public static function depotFileToId($depotFile, P4Cms_Record_Adapter $adapter = null)
    {
        // ensure depotFile is a category file.
        if (basename($depotFile) != static::CATEGORY_FILENAME) {
            throw new InvalidArgumentException(
                "Cannot get category id. Given depot file is not a valid category."
            );
        }

        return dirname(parent::depotFileToId($depotFile, $adapter));
    }

    /**
     * Get the record id for the given entry id.
     *
     * @param   string  $id     the entry id to get the filespec for.
     * @return  string  the record id for the given entry id.
     */
    protected function _getEntryRecordId($id)
    {
        // ensure we have a category id.
        $this->_checkIdSet('get entry record id');

        return static::$_storageSubPath
            . '/' . $this->getId()
            . '/' . static::encodeEntryId($id);
    }

    /**
     * Check whether this category class permits nesting.
     *
     * @throws  P4Cms_Categorization_Exception  If nesting is not allowed.
     */
    protected static function _checkNestability()
    {
        if (!static::isNestingAllowed()) {
            throw new P4Cms_Categorization_Exception(
                "This category does not permit nesting."
            );
        }
    }

    /**
     * Adjust the entries by performing the specified action (add or delete).
     *
     * @param   string  $action                         the action to perform (add or delete).
     * @param   string  $description                    the description to supply to perforce.
     * @param   array   $entries                        an array of strings or entry objects to
     *                                                  associate with the current category.
     * @return  P4Cms_Categorization_CategoryAbstract   provides fluent interface.
     * @throws  P4Cms_Categorization_Exception          if category id not set.
     * @throws  InvalidArgumentException                if $entries is not an array.
     */
    protected function _adjustEntries($action, $description, $entries)
    {
        // ensure action is valid.
        if (!in_array($action, array('add', 'delete'))) {
            throw new InvalidArgumentException(
                "Cannot '$action' entries. Action must be add or delete."
            );
        }

        // ensure we have an id.
        $catId = $this->_checkIdSet("$action entries");

        // validate entries
        if (!is_array($entries)) {
            throw new InvalidArgumentException(
                "Cannot $action entries; you must provide an array of entries."
            );
        }
        foreach ($entries as $entry) {
            if (is_string($entry) || $this->_canAcceptId($entry)) {
                continue;
            }
            throw new InvalidArgumentException(
                "Cannot $action entries; all entries must either be strings or known entry types."
            );
        }

        // begin a batch if we're not already in one.
        $adapter = $this->getAdapter();
        $batch   = !$adapter->inBatch()
            ? $adapter->beginBatch($description)
            : false;

        // add the entries
        foreach ($entries as $entry) {
            $id = $this->_acceptId($entry, "Cannot $action entry; ");

            // skip existing entries for add and
            // non-existant entries for delete.
            $shouldExist = ($action == 'add');
            if ($shouldExist == $this->hasEntry($id)) {
                continue;
            }

            // adjust method call to add or delete as appropriate.
            $method = $action == 'add' ? 'save' : 'delete';

            // setup entry record.
            $record = new P4Cms_Record;
            $record->setAdapter($this->getAdapter())
                   ->setId($this->_getEntryRecordId($id))
                   ->$method();
        }

        // commit batch if we made it.
        if ($batch) {
            $adapter->commitBatch();
        }

        return $this;
    }
}
