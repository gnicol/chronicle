<?php
/**
 * Provides static/global access to a zend cache manager instance
 * as well as easy access to load/save/etc. against named cache
 * manager templates.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache
{
    protected static    $_manager           = null;
    protected static    $_loggingEnabled    = true;

    /**
     * Private constructor to prevent instances from being created.
     *
     * @codeCoverageIgnore
     */
    final private function __construct()
    {
    }

    /**
     * Fetch data from the cache by id.
     *
     * Returns false if no such id in the cache or if cache manager
     * or named template are not configured.
     *
     * @param   string  $id         the id of the cache entry.
     * @param   string  $template   optional - the cache configuration to use
     *                              defaults to 'default'. 
     * @return  mixed|false         the cached data or false if no such entry
     *                              or cache manager/template not configured.
     */
    public static function load($id, $template = 'default')
    {
        // ensure manager and template are good.
        if (!static::canCache($template)) {
            return false;
        }

        $cache = static::getManager()->getCache($template);
        return $cache->load($id);
    }

    /**
     * Save some data in the cache.
     *
     * Returns false if cache manager or template are not configured.
     *
     * @param   mixed   $data       data to put in cache (must be string if automatic
     *                              serialization is off)
     * @param   string  $id         cache id (if not set, the last cache id will be used)
     * @param   array   $tags       cache tags
     * @param   int     $lifetime   a specific lifetime for this cache record (null => infinite lifetime)
     * @param   int     $priority   integer between 0 (very low priority) and 10 (maximum priority) used
     *                              by some particular backends
     * @param   string  $template   optional - the named cache configuration to use.
     * @return  bool    true if save successful, false otherwise.
     */
    public static function save($data, $id, $tags = array(), $lifetime = false, $priority = 8, $template = 'default')
    {
        // ensure manager and template are good.
        if (!static::canCache($template)) {
            return false;
        }

        $cache = static::getManager()->getCache($template);
        return $cache->save($data, $id, $tags, $lifetime, $priority);
    }

    /**
     * Remove a entry from the cache.
     *
     * @param   string  $id         id of entry to remove.
     * @param   string  $template   optional - the named cache configuration to use.
     * @return  bool    true if entry removed; false otherwise.
     */
    public static function remove($id, $template = 'default')
    {
        // ensure manager and template are good.
        if (!static::canCache($template)) {
            return false;
        }

        $cache = static::getManager()->getCache($template);
        return $cache->remove($id);
    }

    /**
     * Clean cache entries. If a template is specified only it will be affected
     * otherwise all templates are cleared.
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => remove too old cache entries ($tags is not used)
     * 'matchingTag'    => remove cache entries matching all given tags
     *                     ($tags can be an array of strings or a single string)
     * 'notMatchingTag' => remove cache entries not matching one of the given tags
     *                     ($tags can be an array of strings or a single string)
     * 'matchingAnyTag' => remove cache entries matching any given tags
     *                     ($tags can be an array of strings or a single string)
     *
     * @param   string          $mode       optional - the clearing mode, see above
     * @param   array|string    $tags       optional - tags to use as per clearing mode
     * @param   string|null     $template   optional - template to limit to, all if null
     * @return  boolean True if ok
     */
    public static function clean($mode = 'all', $tags = array(), $template = null)
    {
        if (!static::hasManager()) {
            return false;
        }

        // normalize tags
        if (is_string($tags)) {
            $tags = array($tags);
        }

        // determine the templates we are cleaning
        $templates = static::getManager()->getCaches();
        if ($template != null) {
            if (!static::canCache($template)) {
                return false;
            }

            $templates = array(static::getCache($template));
        }

        // clean template(s) and track success
        $result = true;
        foreach ($templates as $template) {
            try {
                $result = $template->clean($mode, $tags) && $result;
            } catch (Zend_Cache_Exception $e) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Set the cache manager to use.
     *
     * @param   Zend_Cache_Manager  $manager    the cache manager instance to use.
     */
    public static function setManager(Zend_Cache_Manager $manager = null)
    {
        static::$_manager = $manager;
    }

    /**
     * Check if a cache manager is configured.
     * 
     * @return  bool    true if a manager instance is set.
     */
    public static function hasManager()
    {
        return static::$_manager instanceof Zend_Cache_Manager;
    }

    /**
     * Get the configured cache manager instance.
     *
     * @return  Zend_Cache_Manager      the configured zend cache manager instance.
     * @throws  P4Cms_Cache_Exception   if no cache manager has been set.
     */
    public static function getManager()
    {
        if (!static::hasManager()) {
            throw new P4Cms_Cache_Exception(
                "Cannot get cache manager. No manager is set."
            );
        }

        return static::$_manager;
    }

    /**
     * Check if the given template is registered with the cache manager.
     * Returns false template is invalid, or no manager is set.
     *
     * @param   string  $template   optional - the named cache configuration to use.
     * @return  bool    true if template and manager are valid.
     */
    public static function canCache($template = 'default')
    {
        // false if no manager set.
        if (!static::hasManager()) {
            if (static::$_loggingEnabled) {
                P4Cms_Log::log(
                    "Cannot read/write cache. No cache manager has been set.",
                    P4Cms_Log::WARN
                );
            }
            return false;
        }

        // false if named template is invalid.
        $manager = static::getManager();
        if (!$manager->hasCache($template)) {
            if (static::$_loggingEnabled) {
                P4Cms_Log::log(
                    "Cannot read/write cache. Cache template $template is not configured.",
                    P4Cms_Log::WARN
                );
            }
            return false;
        }

        // good to go!
        return true;
    }

    /**
     * A convienence passthrough to the getCache method on the cache manager.
     *
     * @param   string  $template   The template name to pass to manager's getCache
     * @return  Zend_Cache_Core|false   Returns the result of manager getCache or false if no manager
     */
    public static function getCache($template = 'default')
    {
        if (!static::canCache($template)) {
            return false;
        }

        return static::getManager()->getCache($template);
    }

    /**
     * Enable/disable logging when unable to read/write the cache.
     * 
     * @param   bool    $enabled    true to enable logging, false otherwise.
     */
    public static function setLoggingEnabled($enabled = true)
    {
        static::$_loggingEnabled = (bool) $enabled;
    }
}
