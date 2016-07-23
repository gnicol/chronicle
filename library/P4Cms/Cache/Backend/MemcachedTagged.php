<?php
/**
 * A Memcached backend that has basic support for tags.
 * Supports either the memcached or memecache php extensions for server communication.
 *
 * We accomplish tagging support through the use of tag counters. Assuming the entry
 * foo has the tags biz and bang we would get memcached entries similar to below:
 *  _tag-biz                A counter for the biz tag (assume a value of 1 for the example)
 *  _tag-bang               A counter for the bang tag (assume a value of 1 for the example)
 *  foo                     Really a stub, just holds the tag names used for this entry
 *  _tagged-foo-biz1-bang1  The actual data the caller is trying to store
 *
 * When a user later calls load for the id 'foo' we will first load 'foo' and read out
 * the tags that were used to store it. We will then read all of the associated tag
 * counters. Lastly, we generate the 'tagged id' using the original id and the value of
 * each counter.
 *
 * To clear a given tag we simply increment the associated tag counter which will cause
 * the 'tagged id' lookup described above to fail invalidating all entries with that tag.
 *
 * For entries that have no tags we store them directly under the user specified id.
 *
 * This system doesn't actually 'delete' anything immediately from memcached. We rely on
 * the memcached cleanup logic to take care of these entries as they fall into disuse.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache_Backend_MemcachedTagged extends Zend_Cache_Backend implements Zend_Cache_Backend_ExtendedInterface
{
    /**
     * Default Server Values
     */
    const DEFAULT_HOST      = '127.0.0.1';
    const DEFAULT_PORT      = 11211;
    const DEFAULT_WEIGHT    = 1;

    /**
     * Available options
     *
     * (array) servers :
     * an array of memcached server(s) ; each memcached server is described by an associative array :
     * 'host'   => (string) : the dns or ip address of the memcached server
     * 'port'   => (int)    : the port of the memcached server
     * 'weight' => (int)    : number of buckets to create for this server which in turn control its
     *                        probability of it being selected. The probability is relative to the total
     *                        weight of all servers.
     * (array) client :
     * if using the memcached php extension @see http://php.net/manual/memcached.constants.php for valid
     * options and their associated values. default options are set for Distribution, Hash and Libketama.
     * if using the memcache php extension the only option is 'compression' which can have the value 0 or
     * MEMCACHE_COMPRESSED (0 being the default).
     */
    protected $_options = array(
        'namespace' => null,
        'servers'   => array(
            array(
                'host'   => self::DEFAULT_HOST,
                'port'   => self::DEFAULT_PORT,
                'weight' => self::DEFAULT_WEIGHT,
            )
        ),
        'client'    => array()
    );

    protected $_memcache = null;

    /**
     * Constructor
     *
     * @param   array   $options        associative array of options
     * @throws  Zend_Cache_Exception    if neither the memcached or memcache extensions are present
     * @return  void
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        // for convenience users can specify a single server's settings as the direct
        // value of 'servers'; we normalize it here to always be an array of servers.
        if (isset($this->_options['servers'])) {
            $value = $this->_options['servers'];
            if (isset($value['host'])) {
                $value = array($value);
            }

            $this->setOption('servers', $value);
        }

        // select the optimal backend; throw if we can find neither
        if (extension_loaded('memcached')) {
            $this->_memcache = new Memcached;

            // add in our default options if they are not already present
            // we cannot define these when $_options is declared as the constants
            // won't exist if the memcached extension isn't loaded.
            $this->_options['client'] += array(
                Memcached::OPT_DISTRIBUTION         => Memcached::DISTRIBUTION_CONSISTENT,
                Memcached::OPT_HASH                 => Memcached::HASH_MD5,
                Memcached::OPT_LIBKETAMA_COMPATIBLE => true
            );

            // apply the client options to our instance
            foreach ($this->_options['client'] as $id => $value) {
                $this->_memcache->setOption($id, $value);
            }
        } else if (extension_loaded('memcache')) {
            $this->_memcache = new Memcache;

            // provide a default for the only supported option which is compression
            // this will be applied each time our _set method is used.
            $this->_options['client'] += array(
                'compression' => 0
            );
        } else {
            Zend_Cache::throwException('The memcached or memcache php extension must be loaded to use this backend!');
        }

        // setup memcached servers
        foreach ($this->_options['servers'] as $server) {
            if (!isset($server['host'])) {
                continue;
            }

            // add in default values if port/weight are not specified
            $server += array(
                'port'   => self::DEFAULT_PORT,
                'weight' => self::DEFAULT_WEIGHT
            );

            $this->_memcache->addServer($server['host'], $server['port'], $server['weight']);
        }
    }

    /**
     * Returns the cached value for the given ID or false if no cache entry exists.
     *
     * @param   string          $id                         Cache id to retrieve
     * @param   boolean         $doNotTestCacheValidity     Has no effect on this backend
     * @return  string|false    cached data or false
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        $entry = $this->_get($id);

        if (isset($entry['data'])) {
            return $entry['data'];
        }

        return false;
    }

    /**
     * Checks if the given ID has a cached value or not.
     * Note this will pull down the entries value in the process.
     * 
     * @param   string      $id     Cache id
     * @return  int|false   "last modified" timestamp (int) if available otherwise false
     */
    public function test($id)
    {
        $entry = $this->_get($id);

        // if this looks like a valid entry (has data) and
        // has a modified date we are happy; return it.
        if (isset($entry['data'], $entry['modified'])) {
            return (int)$entry['modified'];
        }

        // the item was expired or invalid, fail out
        return false;
    }

    /**
     * Save some data to cache, will replace any existing entry of the same ID .
     *
     * Note: $data is always a "string" (serialization is done by the core not by the backend)
     *
     * @param   string          $data             The value to cache
     * @param   string          $id               Cache id
     * @param   array           $tags             Array of strings, the cache record will be tagged by each string entry
     * @param   int|bool|null   $specificLifetime Number of seconds to keep entry for or 0/null for unlimited or false
     *                                            to use the default lifetime
     * @return  bool            True if entry was cached, false otherwise
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        // memcached supports a max data size of 1 meg; don't bother
        // pushing anything larger over the wire as it will fail.
        if (strlen($data) > 1024*1024) {
            $this->_log("MemcachedTagged::save() skipped, data over 1 meg");
            return false;
        }

        $lifetime = $this->getLifetime($specificLifetime);

        $entry    = array(
            'data'      => $data,
            'modified'  => time(),
            'lifetime'  => $lifetime
        );

        // if no tags have been specified just store the entry details directly
        // on its specified id.
        // if tags are present, we instead store the list of tags on the passed
        // id and locate the entry under a new 'taggedId' we generate.
        if (empty($tags)) {
            $result   = $this->_set($id, $entry, $lifetime);
        } else {
            $taggedId = $this->_makeTaggedId($id, $tags, true);
            $result   = $taggedId && $this->_set($id,       array('tags' => $tags), $lifetime);
            $result   = $result   && $this->_set($taggedId, $entry,                 $lifetime);
        }

        if ($result === false) {
            $this->_log("MemcachedTagged::save() failed");
        }

        return $result;
    }

    /**
     * Remove a cache entry by id
     *
     * @param   string      $id     Cache id
     * @return  boolean     True if entry was removed, false if it didn't exist
     */
    public function remove($id)
    {
        return $this->_memcache->delete($id);
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => not supported
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => not supported
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => not supported
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param   string  $mode   Cleaning mode
     * @param   array   $tags   Array of tags
     * @return  bool    True if no problem
     * @throws  Zend_Cache_Exception
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        switch ($mode) {
            case Zend_Cache::CLEANING_MODE_ALL:
                return $this->_memcache->flush();
                break;
            case Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                // increment the tag counters for the passed tags to invalidate any
                // related entries. increment will only succeed if there is already
                // an entry for the given id. any failures are moot as that, most
                // likely, means the tag is already expired
                foreach ($tags as $tag) {
                    $this->_memcache->increment($this->_makeTagCounterId($tag));
                }
                break;
            case Zend_Cache::CLEANING_MODE_OLD:
                $this->_log("MemcachedTagged::clean() : CLEANING_MODE_OLD is unsupported");
                break;
            case Zend_Cache::CLEANING_MODE_MATCHING_TAG:
            case Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
                $this->_log("MemcachedTagged::clean() : The MATCHING_TAG and NOT_MATCHING_TAG modes are unsupported");
                break;
           default:
                Zend_Cache::throwException('Invalid mode for clean() method');
               break;
        }

        return false;
    }

    /**
     * Returns true if automatic cleaning is available for the backend
     *
     * @return  bool    Always returns false for this type of backend
     */
    public function isAutomaticCleaningAvailable()
    {
        return false;
    }

    /**
     * Return an array of stored cache ids.
     * The operation is not supported by this backend.
     *
     * @return  array   Always returns an empty array, unsupported
     */
    public function getIds()
    {
        $this->_log("MemcachedTagged::getIds() : unsupported by this backend");

        return array();
    }

    /**
     * Return an array of stored tags
     * The operation is not supported by this backend.
     *
     * @return  array   Always returns an empty array, unsupported
     */
    public function getTags()
    {
        $this->_log("MemcachedTagged::getTags() : unsupported by this backend");

        return array();
    }

    /**
     * Return an array of stored cache ids which match given tags
     * The operation is not supported by this backend.
     *
     * @param   array   $tags   array of tags
     * @return  array   Always returns an empty array, unsupported
     */
    public function getIdsMatchingTags($tags = array())
    {
        $this->_log("MemcachedTagged::getIdsMatchingTags() : unsupported by this backend");

        return array();
    }

    /**
     * Return an array of stored cache ids which don't match given tags
     * The operation is not supported by this backend.
     *
     * @param   array   $tags   array of tags
     * @return  array   Always returns an empty array, unsupported
     */
    public function getIdsNotMatchingTags($tags = array())
    {
        $this->_log("MemcachedTagged::getIdsNotMatchingTags() : unsupported by this backend");

        return array();
    }

    /**
     * Return an array of stored cache ids which match any given tags
     * The operation is not supported by this backend.
     *
     * @param   array   $tags   array of tags
     * @return  array   Always returns an empty array, unsupported
     */
    public function getIdsMatchingAnyTags($tags = array())
    {
        $this->_log("MemcachedTagged::getIdsMatchingAnyTags() : unsupported by this backend");

        return array();
    }

    /**
     * Return the filling percentage of the backend storage
     *
     * @return  int     an number between 0 and 100
     * @throws  Zend_Cache_Exception
     */
    public function getFillingPercentage()
    {
        $results = $this->_memcache->getStats();

        if ($results === false) {
            return 0;
        }

        $totalSize = null;
        $totalUsed = null;
        foreach ($results as $server => $stats) {
            if ($stats === false) {
                $this->_log("cannot get stat from " . $server);
                continue;
            }

            $size = $stats['limit_maxbytes'];
            $used = $stats['bytes'];

            // don't allow usage to exceed max size for a given server
            if ($used > $size) {
                $used = $size;
            }

            $totalSize += $size;
            $totalUsed += $used;
        }

        if ($totalSize === null || $totalUsed === null) {
            Zend_Cache::throwException('Cannot determine filling percentage');
        }

        return (int) (100 * ($totalUsed / $totalSize));
    }

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags   : a string array of tags
     * - mtime  : timestamp of last modification time
     *
     * @param   string  $id     cache id
     * @return  array   array of metadatas (false if the cache id is not found)
     */
    public function getMetadatas($id)
    {
        $entry = $this->_get($id);

        if (isset($entry['modified'], $entry['lifetime'], $entry['tags'])) {
            return array(
                'expire' => $entry['modified'] + $entry['lifetime'],
                'tags'   => $entry['tags'],
                'mtime'  => $entry['modified']
            );
        }

        return false;
    }

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param   string  $id             cache id
     * @param   int     $extraLifetime  number of seconds to add to the current lifetime
     * @return  bool    true if ok false otherwise
     */
    public function touch($id, $extraLifetime)
    {
        $entry = $this->_get($id);

        if (isset($entry['data'], $entry['modified'], $entry['lifetime'])) {
            $newLifetime = $entry['lifetime'] - (time() - $entry['modified']) + $extraLifetime;

            if ($newLifetime <= 0) {
                return false;
            }

            return $this->save($entry['data'], $id, $entry['tags'], $newLifetime);
        }

        return false;
    }

    /**
     * Return an associative array of capabilities (bools) of the backend
     *
     * - automatic_cleaning     not supported
     * - tags                   supported (though only partially in reality)
     * - expired_read           not supported
     * - priority               not supported
     * - infinite_lifetime      not supported
     * - get_list               not supported
     *
     * @return  array   associative of with capabilities
     */
    public function getCapabilities()
    {
        return array(
            'automatic_cleaning' => false,
            'tags'               => true,
            'expired_read'       => false,
            'priority'           => false,
            'infinite_lifetime'  => false,
            'get_list'           => false
        );
    }
    
    /**
     * This method takes care of applying the namespace, if
     * present, as a prefix to the passed id. If no namespace
     * is in use the id is simply returned unchaned.
     * 
     * @param   string  $id     the id to prefix if needed
     * @return  string  the id prefix to use, blank if none
     */
    protected function _namespaceId($id)
    {
        $namespace = $this->_options['namespace'];
        if (!$namespace) {
            return $id;
        }
        
        return md5($this->_options['namespace']) . '-' . $id;
    }

    /**
     * Helper method to store a value; the two memcached extensions we
     * support have slightly different argument handling and this method
     * normalizes them.
     *
     * @param   string      $key            The key/id to store under
     * @param   mixed       $value          The value to store
     * @param   int|null    $lifetime       optional - number of seconds to keep the item for
     *                                      0 indicates no limit (though it can still be removed
     *                                      should memcached get full).
     * @return  bool        true on success false otherwise
     */
    protected function _set($key, $value, $lifetime = 0)
    {
        // ensure the id is namespaced if needed
        $key = $this->_namespaceId($key);

        // if the lifetime is greater than 30 days it has to be represented as
        // a unix time; do that conversion here.
        if ($lifetime > 30*24*60*60) {
            $lifetime = time() + $lifetime;
        }

        // zend cache uses null to mean forever; convert to 0 for memcached
        if ($lifetime === null) {
            $lifetime = 0;
        }

        if ($this->_memcache instanceof Memcached) {
            return $this->_memcache->set($key, $value, $lifetime);
        }

        // deal with a memcache backend which expects an extra 'flags' param
        return $this->_memcache->set($key, $value, $this->_options['client']['compression'], $lifetime);
    }

    /**
     * Get all data and metadata for the given ID from memcached.
     * If the entry utilized tags when it was stored this method will take
     * care of resolving the tag counter(s) to get the actual data value back.
     *
     * If successful the returned array will have the following layout:
     * array (
     *     'data'     => <string value>,
     *     'tags'     => <array of tags>,
     *     'modified' => <unix time>,
     *     'lifetime' => <lifetime when created, seconds of validity or 0 for unlimited>
     * )
     *
     * @param   string  $id     The item id to retrieve
     * @return  array|bool      The item data & metadata or false
     */
    protected function _get($id)
    {
        $directData = $this->_memcache->get($this->_namespaceId($id));

        // if we don't receive a properly formed array bail out
        if (!is_array($directData) || (!isset($directData['data']) && !isset($directData['tags']))) {
            return false;
        }

        // if data is stored directly on this id simply return result
        if (isset($directData['data'])) {
            return $directData + array('tags' => array());
        }

        // if we cannot determine the tagged id, a tag counter has
        // expired or something is amiss; bail out
        $taggedId = $this->_makeTaggedId($id, $directData['tags']);
        if (!$taggedId) {
            return false;
        }

        $taggedData = $this->_memcache->get($this->_namespaceId($taggedId));

        // if we still don't have data it is a failure
        if (!isset($taggedData['data'])) {
            return false;
        }

        // made it all the way; return the combined tag and data details
        return array_merge($directData, $taggedData);
    }

    /**
     * We create 'counters' in memcached for every tag we encounter. This
     * method will translate from a tag to the associated tag counter ID.
     *
     * @param   string  $tag    the tag to get an ID for
     * @return  string  the memcached ID associated with the passed tag
     */
    protected function _makeTagCounterId($tag)
    {
        return '_tag-' . md5($tag);
    }

    /**
     * Given an id and a list of tags, returns the 'tagged' id under which
     * the actual data will be stored. Optionally creates the 'tag counters'
     * needed for this id if the force param is specified.
     *
     * @param   string  $id     the plain id to utilize
     * @param   array   $tags   string based tags that we want to apply to the id
     * @param   bool    $force  normally we fail if any tag counters are missing, pass
     *                          true to cause any missing tag counters to be created
     * @return  bool|string     the tagged id or false
     */
    protected function _makeTaggedId($id, $tags, $force = false)
    {
        // we only do tagged ids for tagged content
        if (empty($tags)) {
            return false;
        }

        $tagCounterIds = array();
        foreach ($tags as $tag) {
            $tagCounterIds[] = $this->_makeTagCounterId($tag);
        }

        // read out all of the tag counters from memcached, use getMulti if possible
        if (method_exists($this->_memcache, 'getMulti')) {
            $tagCounts = $this->_memcache->getMulti($tagCounterIds);
        } else {
            $tagCounts = $this->_memcache->get($tagCounterIds);
        }

        // deal with getting a non array or only partial result back
        $tagCounts = is_array($tagCounts) ? $tagCounts : array();
        if (count($tagCounts) != count($tagCounterIds)) {
            // if we don't have force set; we have to give up at this point
            if (!$force) {
                return false;
            }

            // find the missing tags
            $missingTags = array_diff($tagCounterIds, array_keys($tagCounts));

            // use the current time as the value for all missing tag counters.
            // if we always started at 1 it would have two issues:
            // - one is a terribly obvious choice which makes it somewhat boring
            // - if a tag counter expires and we restore it to 1 we could erroneously
            //   resurrect associated entries which should really be expired as well.
            $missingTagCounts = array_fill_keys(array_values($missingTags), time());

            // ensure we include the missing tags in the full list
            $tagCounts += $missingTagCounts;

            // attempt to add all the missing tags
            foreach ($missingTagCounts as $tagId => $value) {
                // if the add fails likely someone beat us to it,
                // try and get the value they used
                if (!$this->_memcache->add($tagId, $value)) {
                    $tagCounts[$tagId] = $this->_memcache->get($tagId);

                    // if we cannot read out the value give up
                    if (!$tagCounts[$tagId]) {
                        return false;
                    }
                }
            }
        }

        // ensure the tag counts are alphabetically ordered
        uksort($tagCounts, 'strnatcmp');

        // generate a 'tagged' id to lookup the actual data
        return '_tagged-' . $id . '-'. md5(serialize($tagCounts));
    }
}
