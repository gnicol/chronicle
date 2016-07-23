<?php
/**
 * This class models hierarchical groupings of paths.
 *
 * The paths are files affected by merge or copy pull operations.
 * It is desirable to group these files under friendly labels
 * to assist users in selecting which files to include in a pull
 * operation (or to see which files are affected).
 *
 * One of the other features of this model is to provide control
 * over how files in each path group are counted. In some cases
 * multiple files are stored to represent one logical entry.
 * This allows us to adjust the count to better match the user's
 * expectations.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Model_PullPathGroup extends P4Cms_Model
{
    const   ONLY_CONFLICTS  = 'onlyConflicts';
    const   RECURSIVE       = 'recursive';

    protected static    $_fields    = array(
        'label'     => array(
            'accessor'  => 'getLabel',
            'mutator'   => 'setLabel'
        ),
        'basePaths' => array(
            'accessor'  => 'getBasePaths',
            'mutator'   => 'setBasePaths',
            'default'   => array()
        ),
        'paths'     => array(
            'accessor'  => 'getPaths',
            'mutator'   => 'setPaths'
        ),
        'parent'    => array(
            'accessor'  => 'getParent',
            'mutator'   => 'setParent'
        ),
        'subGroups' => array(
            'accessor'  => 'getSubGroups',
            'mutator'   => 'setSubGroups'
        ),
        'count'     => array(
            'accessor'  => 'getCount',
            'mutator'   => 'setCount'
        ),
        'details'   => array(
            'accessor'  => 'getDetails',
            'mutator'   => 'setDetails',
        )
    );

    /**
     * Set the label for this path group.
     *
     * @param   string|null     $label      the human-friendly label for this group.
     * @return  Site_Model_PullPathGroup    provides fluent interface.
     */
    public function setLabel($label)
    {
        return $this->_setValue('label', $label);
    }

    /**
     * Get the label for this path group.
     *
     * @return  string|null     the human-friendly label for this group.
     */
    public function getLabel()
    {
        return $this->_getValue('label');
    }

    /**
     * Set a callback which will provide details about the paths
     * held in this group. The callback will be passed an iterator
     * of Paths (output of getPaths) and is expected to return an
     * iterator of Models. The keys/values in the models will inform
     * the columns used to display your data. At a minimum a 'label',
     * 'conflict' and 'action' value should be present.
     *
     * If no callback is set the default details will be returned.
     * See getDetails for more information.
     *
     * @param   callable|null   $callback   the callback to use
     * @return  Site_Model_PullPathGroup    to maintain a fluent interface
     * @throws  InvalidArgumentException    if an invalid type is passed
     */
    public function setDetails($callback)
    {
        if (!is_callable($callback) && !is_null($callback)) {
            throw new InvalidArgumentException(
                'Details Callback must be callable or null'
            );
        }

        return $this->_setValue('details', $callback);
    }

    /**
     * Provides details about the paths held in this group.
     * Details are provided as an iterator of Models. The keys/values in
     * the models are intended to represent columns of data. At a minimum
     * a 'label', 'conflict' and 'action' value should be present on each
     * model.
     *
     * The returned iterator may optionally use a 'columns' property
     * to provide column id's (e.g. label) with custom titles. If no
     * columns property is present the default columns of Label and Action
     * will be used.
     *
     * If a details callback is present it will be utilized to generate
     * the details. Otherwise, each path will get default details using
     * the depotFile as a label and existing branch action and conflict
     * status.
     *
     * @param   string|array|null   $options    options to influence the result:
     *                                             ONLY_CONFLICTS - if passed, only conflicting
     *                                                              path details are returned
     *                                                  RECURSIVE - if passed, details of all sub
     *                                                              groups paths will be included
     * @return  P4Cms_Model_Iterator            the path details
     */
    public function getDetails($options = null)
    {
        $options       = (array) $options;
        $onlyConflicts = in_array(static::ONLY_CONFLICTS, $options) ? static::ONLY_CONFLICTS : null;
        $paths         = $this->getPaths($onlyConflicts);
        $callback      = $this->_getValue('details');
        $details       = new P4Cms_Model_Iterator;
        if (is_callable($callback) && $paths->count()) {
            $details = $callback($paths) ?: new P4Cms_Model_Iterator;
            foreach ($details as $detail) {
                if (!$detail->hasField("type")) {
                    $detail->setValue('type', $this->getLabel());
                }
            }
        } else if ($paths->count()) {
            foreach ($paths as $path) {
                $details[] = new P4Cms_Model(
                    array(
                        'action'    => $path->action,
                        'conflict'  => $path->conflict,
                        'type'      => $this->getLabel(),
                        'label'     => preg_replace('#^//[^/]+/[^/]+/#', '', $path->depotFile)
                    )
                );
            }

            $details->setProperty('columns', array('label' => 'File', 'action' => 'Action'));
        }

        $columns = $details->hasProperty('columns') ? $details->getProperty('columns') : array();
        if (in_array(static::RECURSIVE, (array) $options)) {
            foreach ($this->getSubGroups() as $group) {
                $subDetails = $group->getDetails($options);
                if ($subDetails->count()) {
                    if ($subDetails->hasProperty('columns')) {
                        $columns += $subDetails->getProperty('columns');
                    }

                    $details->merge($subDetails);
                }
            }
        }

        // ensure our details iterator has the aggregated columns
        // fall back to defaults of Label and Action if none found.
        $details->setProperty(
            'columns',
            $columns ?: array('label' => 'Label', 'action' => 'Action')
        );

        return $details;
    }

    /**
     * Returns the details callback without actually executing it.
     *
     * @return  callable|null   the details callback or null if none set
     */
    public function getDetailsCallback()
    {
        return $this->_getValue('details');
    }

    /**
     * Add the specified base path to this path group.
     *
     * Base path(s) can be set to optimize pull operations
     * (as they will be used instead of paths).
     *
     * @param   string      $basePath       the basepath to add
     * @return  Site_Model_PullPathGroup    to maintain a fluent interface
     */
    public function addBasePath($basePath)
    {
        $basePaths   = $this->getBasePaths();
        $basePaths[] = $basePath;

        return $this->_setValue('basePaths', $basePaths);
    }

    /**
     * Add the passed array of base paths to this path group.
     *
     * Base path(s) can be set to optimize pull operations
     * (as they will be used instead of paths).
     *
     * @param   array       $basePaths      the basepaths to add
     * @return  Site_Model_PullPathGroup    to maintain a fluent interface
     */
    public function addBasePaths(array $basePaths)
    {
        foreach ($basePaths as $path) {
            $this->addBasePath($path);
        }

        return $this;
    }

    /**
     * Set the base path(s) for this path group.
     *
     * Base path(s) can be set to optimize pull operations
     * (as they will be used instead of paths).
     *
     * @param   array|string|null   $basePaths  the base-path(s) for files in this group.
     * @return  Site_Model_PullPathGroup        provides fluent interface.
     */
    public function setBasePaths($basePaths)
    {
        $this->_setValue('basePaths', array());

        $basePaths = (array) $basePaths;
        foreach ($basePaths as $basePath) {
            $this->addBasePath($basePath);
        }

        return $this;
    }

    /**
     * Get the base paths for this path group.
     *
     * @return  array   the base-paths for files in this group.
     */
    public function getBasePaths()
    {
        return $this->_getValue('basePaths');
    }

    /**
     * Set the parent group for this path group.
     *
     * This gives sub-groups access to their parent.
     * If this group has basepaths set on it, specifiying a parent will
     * trigger moving any matching paths from parent group(s).
     *
     * @param   Site_Model_PullPathGroup|null   $parent     parent group or null.
     * @return  Site_Model_PullPathGroup        provides fluent interface.
     */
    public function setParent(Site_Model_PullPathGroup $parent = null)
    {
        $this->_setValue('parent', $parent);

        if ($parent) {
            $this->setBasePaths($this->getBasePaths());
        }

        return $this;
    }

    /**
     * Get the parent group for this path group (if one is set)
     *
     * @return  Site_Model_PullPathGroup|null   parent group or null.
     */
    public function getParent()
    {
        return $this->_getValue('parent');
    }

    /**
     * Control the count of paths in this path group.
     *
     * By default simply returns a count of all paths and paths in sub-groups
     * (recursively). Can be set to null for the default behaviour or a callback
     * that may use whatever logic is desired to return a count.
     *
     * @param   null|callable   $count      null to clear the count (fallback to default), or
     *                                      a callback that returns the count (passed $this,
     *                                      the computed count, $recursive and $onlyConflicts)
     * @return  Site_Model_PullPathGroup    provides fluent interface.
     */
    public function setCount($count)
    {
        if (!is_null($count) && !is_callable($count)) {
            throw InvalidArgumentException(
                "Cannot set count. Given count is not a valid type."
            );
        }

        return $this->_setValue('count', $count);
    }

    /**
     * Get the count of paths in this path group.
     * This will execute the count callback and use its result if one is set.
     *
     * @param   string|array|null   $options    options to influence the result:
     *                                             ONLY_CONFLICTS - if passed, only conflicting
     *                                                              paths are counted
     *                                                  RECURSIVE - if passed, count of all sub
     *                                                              groups will be included
     * @return  int                 the number of paths in this path group.
     *                              this is not always an exact count of paths
     *                              as a fixed value or a callback can be used.
     */
    public function getCount($options = null)
    {
        $options       = (array) $options;
        $onlyConflicts = in_array(static::ONLY_CONFLICTS, $options) ? static::ONLY_CONFLICTS : null;
        $computed      = $this->getPaths($onlyConflicts)->count();

        // if a count callback has been set, use it,
        // passing this and the count we computed above
        $count = $this->_getValue('count');
        if (is_callable($count)) {
            // just pass the 'conflictsOnly' option to the callback.
            // we handle recursion ourselves.
            $computed = call_user_func($count, $this, $computed, (array) $onlyConflicts);
        }

        // compute the number of paths recursively if needed.
        if (in_array(static::RECURSIVE, $options)) {
            foreach ($this->getSubGroups() as $group) {
                $computed += $group->getCount($options);
            }
        }

        return $computed;
    }

    /**
     * Returns the count callback without actually executing it.
     *
     * @return  callable|null   the count callback or null if none set
     */
    public function getCountCallback()
    {
        return $this->_getValue('count');
    }

    /**
     * A convienence method that calls through to getCount
     * returning a shallow count (non-recursive) of conflicts.
     *
     * @return  int     The number of conflicting entries (shallow)
     */
    public function getConflictCount()
    {
        return $this->getCount(Site_Model_PullPathGroup::ONLY_CONFLICTS);
    }

    /**
     * Set the paths in this path group.
     *
     * @param   P4Cms_Model_Iterator|null   $paths  the list of paths to put
     *                                              in this group (null to clear).
     * @return  Site_Model_PullPathGroup    provides fluent interface.
     */
    public function setPaths(P4Cms_Model_Iterator $paths = null)
    {
        $paths = $paths ?: new P4Cms_Model_Iterator;

        return $this->_setValue('paths', $paths);
    }

    /**
     * Get the paths in this path group (not recursive).
     *
     * @param   string|array|null   $options    options to influence the result:
     *                                             ONLY_CONFLICTS - if passed, only conflicting
     *                                                              paths are returned
     *                                                  RECURSIVE - if passed, paths of all sub
     *                                                              groups will be included
     * @return  P4Cms_Model_Iterator    an iterator of paths in this group.
     */
    public function getPaths($options = null)
    {
        // initialize paths to an iterator if necessary.
        $paths = $this->_getValue('paths') ?: new P4Cms_Model_Iterator;
        $this->_setValue('paths', $paths);

        if (in_array(static::ONLY_CONFLICTS, (array) $options)) {
            $paths = $paths->filter('conflict', true, array(P4Cms_Model_Iterator::FILTER_COPY));
        }

        if (in_array(static::RECURSIVE, (array) $options)) {
            foreach ($this->getSubGroups() as $group) {
                $paths->merge($group->getPaths($options));
            }
        }

        return $paths;
    }

    /**
     * Add a path to this path group. The path must be an array of
     * information about the path (containing at least a depotFile)
     * or a model. If an array is given it will be normalized to a
     * model.
     *
     * @param   array|P4Cms_Model   $path   the path array or model to add.
     * @return  Site_Model_PullPathGroup    provides fluent interface.
     */
    public function addPath($path)
    {
        if (!is_array($path) && !$path instanceof P4Cms_Model) {
            throw new InvalidArgumentArgument("Cannot set path. Path must be an array or model");
        }

        if (is_array($path)) {
            $path = new P4Cms_Model($path);
        }

        $paths   = $this->getPaths();
        $paths[] = $path;

        return $this;
    }

    /**
     * Set the sub-groups in this path group.
     *
     * @param   P4Cms_Model_Iterator|null   $groups     the sub-groups to put in
     *                                                  this group (null to clear).
     * @return  Site_Model_PullPathGroup    provides fluent interface.
     */
    public function setSubGroups(P4Cms_Model_Iterator $groups = null)
    {
        $groups = $groups ?: new P4Cms_Model_Iterator;

        foreach ($groups as $group) {
            $this->addSubGroup($group);
        }

        return $this->_setValue('subGroups', $groups);
    }

    /**
     * Add a sub-group to this path group. The sub-group must be an
     * array of information about the group or pull path group model.
     * If an array is given it will be normalized to a model.
     *
     * This method will set the parent of the given sub-group to be
     * this group.
     *
     * If the sub-group is passed as an array with a 'inheritPaths'
     * property any matching paths will automatically move from this
     * group (or its parents) to the new sub-group.
     *
     * @param   array|Site_Model_PullPathGroup  $group  the group array or model to add.
     * @return  Site_Model_PullPathGroup        provides fluent interface.
     */
    public function addSubGroup($group)
    {
        if (!is_array($group) && !$group instanceof Site_Model_PullPathGroup) {
            throw new InvalidArgumentException(
                "Cannot add sub-group. Group must be a array or path group object."
            );
        }

        $inheritPaths = array();
        if (is_array($group)) {
            if (isset($group['inheritPaths'])) {
                $inheritPaths = $group['inheritPaths'];
                unset($group['inheritPaths']);
            }

            $group = new Site_Model_PullPathGroup($group);
        }

        // ensure the sub-group has a link back to this (its parent)
        $group->setParent($this);
        $group->inheritPaths($inheritPaths);

        $groups = $this->getSubGroups();
        $groups[] = $group;

        return $this;
    }

    /**
     * Get the immediate child groups of this group (not recursive).
     *
     * @return  P4Cms_Model_Iterator    a list of child groups.
     */
    public function getSubGroups()
    {
        // initialize sub-groups to an iterator if necessary.
        $groups = $this->_getValue('subGroups') ?: new P4Cms_Model_Iterator;
        $this->_setValue('subGroups', $groups);

        return $groups;
    }

    /**
     * Try to find a sub-group with the given label under this group.
     *
     * @param   string      $label              the name of the sub-group to look for.
     * @return  Site_Model_PullPathGroup|bool   a matching sub-group or false if no such group.
     */
    public function getSubGroup($label)
    {
        return $this->getSubGroups()
             ->filter('label', $label, array(P4Cms_Model_Iterator::FILTER_COPY))
             ->first();
    }

    /**
     * Find a group under this group (includes this group) with the given id.
     * If no matching group can be found, returns false.
     *
     * @param   string                          $id     the id of the group to look for.
     * @param   Site_Model_PullPathGroup|null   $group  used for recursion.
     * @return  Site_Model_PullPathGroup|bool   the found group or false.
     */
    public function findById($id, $group = null)
    {
        $group = $group ?: $this;

        // check for immediate match.
        if ($group->getId() === $id) {
            return $group;
        }

        // check for recursive (sub-group) match.
        foreach ($group->getSubGroups() as $subGroup) {
            $found = $this->findById($id, $subGroup);
            if ($found) {
                return $found;
            }
        }

        return false;
    }

    /**
     * Get an identifier for this group.
     *
     * If an explicit id is set, it will be returned. Otherwise, we
     * generate an identifier for this path group based on its label
     * and the labels of its parents (e.g. 'configuration/permissions')
     *
     * Note: this identifier is not guaranteed to be unique!
     *
     * @return  string  an identifier for this group.
     */
    public function getId()
    {
        // if an explicit id was set, use it.
        if (parent::getId()) {
            return parent::getId();
        }

        $ids    = array();
        $filter = new P4Cms_Filter_TitleToId;
        $parent = $this;
        while ($parent) {
            $ids[]  = $filter->filter($parent->getLabel());
            $parent = $parent->getParent();
        }

        return trim(implode('/', array_reverse($ids)), '/');
    }

    /**
     * Get a list of paths in this group to use in a pull operation.
     *
     * If base path(s) are set, these will be returned (as an optimization).
     * Otherwise, a list of depotFiles will be taken from getPaths and returned.
     *
     * @return  array   paths in this group to use in a pull operation
     */
    public function getIncludePaths()
    {
        return $this->getBasePaths()
            ?: $this->getPaths()->invoke('getValue', array('depotFile'));
    }

    /**
     * Scans up through parent objects and brings over any paths matching
     * the passed filespec(s). If the passed filespec ends in the wildcard
     * '...' anything starting with the value will be considered a match.
     *
     * If a path model is passed its 'depotFile' value will be used as the
     * search value and any matching paths brought over.
     *
     * @param   array|Iterator|string|P4Cms_Model   $paths  one or more paths to inherit
     * @return  Site_Model_PullPathGroup            to maintain a fluent interface
     */
    public function inheritPaths($paths)
    {
        // we need to selectively 'arrayize' input if only a single
        // path was passed. blindly casting to array would 'toArray'
        // P4Cms_Model's causing a later invalid argument exception.
        if (!$paths instanceof Iterator && !is_array($paths)) {
            $paths = array($paths);
        }

        // if paths are in an iterator the cursor may become corrupt
        // if we take a path from a parent with a shared reference.
        // swap it over to being an array of paths for safety.
        if ($paths instanceof Iterator) {
            $paths = iterator_to_array($paths);
        }

        foreach ($paths as $path) {
            // normalize to a string
            if ($path instanceof P4Cms_Model && $path->depotFile) {
                $path = $path->depotFile;
            }

            if (!$path || !is_string($path)) {
                throw new InvalidArgumentException(
                    'Inherit path must be a non empty string or path model.'
                );
            }

            // our 'needle' starts out as the full path value requiring
            // an exact match. if the path ends in '...' we switch to a
            // starts with match and strip the trailing wildcard.
            $needle = $path;
            $mode   = 'exact';
            if (substr($needle, -3) == '...') {
                $mode   = 'starts';
                $needle = substr($needle, 0, -3);
            }

            $parent = $this->getParent();
            while ($parent) {
                $paths = $parent->getPaths();
                foreach ($paths->toArray(true) as $key => $path) {
                    // if we have an acceptable match, add the path to our group
                    // and remove it from the parent that originally had it.
                    if (($mode == 'exact' && $needle === $path->getValue('depotFile'))
                        || ($mode == 'starts' && strpos($path->getValue('depotFile'), $needle) === 0)
                    ) {
                        $this->addPath($path);
                        unset($paths[$key]);
                    }
                }

                // up the tree.
                $parent = $parent->getParent();
            }
        }
    }

    /**
     * Helper function to fetch records from either the source or the target
     * branch as necessary. First attempts to fetch from the source branch
     * and falls back to the target branch if any of the identified records
     * could not be found in the source.
     *
     * When doing a 'copy' style of pull, we can get into a situation where
     * items that only exist in the target are opened for delete. Naturally,
     * these records can't be fetched from the source, so we fetch them from
     * the target branch instead.
     *
     * If a given id cannot be found in either the source or the target branch
     * it will not be included in the result.
     *
     * @param   array       $ids        a list of ids to fetch
     * @param   string      $class      the type of record to fetch.
     * @param   P4Cms_Site  $source     the source site/branch to fetch from.
     * @param   P4Cms_Site  $target     the target site/branch to fallback to.
     * @return  P4Cms_Model_Iterator    fetched records.
     */
    public static function fetchRecords(array $ids, $class, P4Cms_Site $source, P4Cms_Site $target)
    {
        if (!class_exists($class) || (!is_subclass_of($class, 'P4Cms_Record') && $class !== 'P4Cms_Record')) {
            throw new InvalidArgumentException("Cannot fetch entries. Invalid class type specified.");
        }

        // nothing to do if no ids given.
        if (empty($ids)) {
            return new P4Cms_Model_Iterator;
        }

        // first attempt to fetch from the source branch.
        $entries = $class::fetchAll(
            array(
                'ids'               => $ids,
                'includeDeleted'    => true
            ),
            $source->getStorageAdapter()
        );

        // if we got everything, all done!
        $missing = array_diff($ids, $entries->invoke('getId'));
        if (!$missing) {
            return $entries;
        }

        // try to fetch missing records from the target branch.
        return $entries->merge(
            $class::fetchAll(
                array(
                    'ids'               => $missing,
                    'includeDeleted'    => true
                ),
                $target->getStorageAdapter()
            )
        );
    }

    /**
     * Helper function to re-index a given paths iterator by record id.
     * Any paths that are not of the given record class type (ie. not
     * under the appropriate storage path) will be excluded.
     *
     * @param   P4Cms_Model_Iterator        $paths      an iterator of paths to re-index.
     * @param   string                      $class      the type of record to use to convert paths to ids.
     * @param   P4Cms_Record_Adapter|null   $adapter    optional - a specific storage adapter to use.
     * @return  P4Cms_Model_Iterator        iterator of paths of the given record type indexed by id.
     */
    public static function pathsByRecordId(P4Cms_Model_Iterator $paths, $class, P4Cms_Record_Adapter $adapter = null)
    {
        $byId = new P4Cms_Model_Iterator;
        foreach ($paths as $path) {
            try {
                $id        = $class::depotFileToId($path->depotFile, $adapter);
                $byId[$id] = $path;
            } catch (P4Cms_Record_Exception $e) {
                // skip entries whose id can't be determined (assumed to be of a different type).
            }
        }

        return $byId;
    }
}
