<?php
/**
 * A specialized record class for use with site branch objects.
 *
 * Site/Branch config consists of a few rather discrete items
 * including Urls, Active Theme Selection and other General config.
 * We break this data into three seperate records to allow
 * our branching operations to deal with them granularly.
 *
 * The Site Config model adds support for a 'record' property
 * when declaring field definitions so items can choose which
 * location thier data should be stored in. By default fields
 * fall back to the 'ID_GENERAL' record for storage/retrieval.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Site_Config extends P4Cms_Model
{
    const               ID_GENERAL  = 'config/general';
    const               ID_THEME    = 'config/theme';
    const               ID_URLS     = 'config/urls';

    protected           $_site      = null;
    protected           $_records   = array();
    protected           $_dirty     = array();

    protected static    $_fields    = array(
        'description'   => array(
            'accessor'  => 'getDescription',
            'mutator'   => 'setDescription'
        ),
        'title'          => array(
            'accessor'  => 'getTitle',
            'mutator'   => 'setTitle'
        ),
        'theme'          => array(
            'accessor'  => 'getTheme',
            'mutator'   => 'setTheme',
            'record'    => self::ID_THEME
        ),
        'urls'          => array(
            'accessor'  => 'getUrls',
            'mutator'   => 'setUrls',
            'record'    => self::ID_URLS
        )
    );

    /**
     * Create a new site config instance for the given site/branch.
     *
     * @param   P4Cms_Site  $site   site to represent config for
     */
    public function __construct(P4Cms_Site $site)
    {
        $this->_site = $site;
    }

    /**
     * Get the site branch this configuration is for.
     *
     * @return  P4Cms_Site  the site branch this config model is for.
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * Get the friendly (configurable) title for the associated site branch.
     *
     * @return  string  the site branch's title (defaults to the site id).
     */
    public function getTitle()
    {
        return (string) $this->_getValue('title') ?: $this->getSite()->getId();
    }

    /**
     * Set the friendly (configurable) title for the associated site branch.
     *
     * @param   string  $title      the title for the site branch.
     * @return  P4Cms_Site_Config   provides a fluent interface.
     */
    public function setTitle($title)
    {
        if (!is_string($title)) {
            throw new InvalidArgumentException("The provided title is not a string.");
        }

        return $this->_setValue('title', $title);
    }

    /**
     * Get the urls the associated site branch is configured to respond to.
     *
     * @return  array   the urls the site branch is configured to respond to.
     */
    public function getUrls()
    {
        $urls = $this->_getValue('urls');

        return is_array($urls) ? $urls : array();
    }

    /**
     * Set the urls for the associated site branch as an array of strings, or null.
     *
     * @param   array|string|null   $urls   the urls for the site branch.
     * @return  P4Cms_Site_Config   provides a fluent interface.
     */
    public function setUrls($urls)
    {
        // if provided with a string, convert to an array
        if (is_string($urls)) {
            $urls = array_filter(array_map('trim', preg_split("/\n|,/", $urls)));
        }

        return $this->_setValue('urls', $urls);
    }

    /**
     * Get a single url for this site/branch.
     *
     * If one or more explicit urls have been set for this site/branch
     * (e.g. http://stage.example.com), the first one will be used. If no
     * explicit urls have been configured, we search up through the parent
     * branches until we find one and append the name of the branch as the
     * first path component (e.g. http://example.com/-stage-).
     *
     * @return  string  a url for this site/branch
     */
    public function getUrl()
    {
        $urls = $this->getUrls();

        // if we don't have any urls, we need to grab urls from the
        // closest parent that does and append a branch specifier.
        if (!$urls) {
            $parent = $this->getSite();
            while (!$urls && ($parent = $parent->getParent())) {
                $urls = $parent->getConfig()->getUrls();
            }
        }

        // normalize url to begin with a protocol/scheme.
        $url = array_shift($urls);
        if ($url
            && substr($url, 0, 7) != 'http://'
            && substr($url, 0, 8) != 'https://'
        ) {
            $url = 'http://' . $url;
        }

        // if we got a url from a parent, we need to append
        // a branch specifier to route to this specific branch
        if ($url && isset($parent)) {
            $url = trim($url, "/") . "/-" . $this->getSite()->getBranchBasename() . "-";
        }

        return rtrim($url, "/");
    }

    /**
     * Get the current theme for the associated site branch.
     *
     * @return  string  the name of the theme for the site branch
     *                  returns the default theme if none has been set.
     */
    public function getTheme()
    {
        return $this->_getValue('theme') ?: P4Cms_Theme::DEFAULT_THEME;
    }

    /**
     * Set the theme for the associated site branch.
     *
     * @param   string  $theme          the name of the theme to use.
     * @return  P4Cms_Site_Config       provides a fluent interface.
     * @throws  P4Cms_Site_Exception    if theme does not exist
     */
    public function setTheme($theme)
    {
        if ($theme && !P4Cms_Theme::exists($theme)) {
            throw new P4Cms_Site_Exception("Theme $theme is invalid or does not exist.");
        }

        return $this->_setValue('theme', $theme);
    }

    /**
     * Get the description for the associated site branch.
     *
     * @return  string  the description for the associated site branch
     *                  returns an empty string if none set.
     */
    public function getDescription()
    {
        return (string) $this->_getValue('description');
    }

    /**
     * Set the description for the associated site branch.
     *
     * @param   string  $description    the description for the associated site branch
     * @return  P4Cms_Site_Config       provides a fluent interface.
     */
    public function setDescription($description = null)
    {
        if (!is_null($description) && !is_string($description)) {
            throw new InvalidArgumentException("The provided description is not a string.");
        }

        return $this->_setValue('description', $description);
    }

    /**
     * This record utilizes a general, theme and urls record to store
     * the various data. The parent getFields would have returned the
     * defined fields for all three; we have extended it to also include
     * any custom fields present on the general config.
     *
     * This model always routes unknown fields to the general config.
     * As such, we don't include any custom fields from the theme or
     * url records as they would not be accessible using our models
     * accessors/mutators.
     *
     * @return  array   all fields for this model
     */
    public function getFields()
    {
        $fields = array_merge(
            parent::getFields(),
            $this->_getRecord(static::ID_GENERAL)->getFields()
        );

        // return field names.
        return array_unique($fields);
    }

    /**
     * Our model wraps several records which seperately store the urls,
     * theme and general configuration details. When save is called
     * we start a batch (if one is not already in progress) and save
     * any of the files which have been modified.
     *
     * @param   string              $description    optional - a description of the change.
     * @return  P4Cms_Site_Config   provides a fluent interface
     */
    public function save($description = null)
    {
        // ensure we have a save description.
        $description = $description
            ?: "Saved configuration for '" . $this->getTitle() . "' site.";

        // start the batch
        $adapter = $this->getSite()->getStorageAdapter();
        $batch   = !$adapter->inBatch()
            ? $adapter->beginBatch($description)
            : false;

        // try to save each of the 'dirty' records.
        // note: we reset the adapter in case the record came
        // from cache or otherwise has a bogus adapter.
        try {
            foreach (array_keys($this->_dirty) as $id) {
                $this->_records[$id]->setAdapter($adapter)
                                    ->save();
            }
            $this->_dirty = array();
        } catch (Exception $e) {
            if ($batch) {
                $adapter->revertBatch();
            }
            throw $e;
        }

        // commit the batch.
        if ($batch) {
            $adapter->commitBatch();
        }

        return $this;
    }

    /**
     * The normal _getValue behaviour is to return values present
     * on this model. We have replaced our parent to route the
     * request to one of our underlying records based on the 'record'
     * property of the field. If the field is unknown or has no record
     * property we default to the general record.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the value of the field.
     */
    protected function _getValue($field)
    {
        $id = $this->_getFieldProperty($field, 'record') ?: static::ID_GENERAL;

        return $this->_getRecord($id)->getValue($field);
    }

    /**
     * The normal _setValue behaviour is to update values present
     * on this model. We have replaced our parent to route the
     * request to one of our underlying records based on the 'record'
     * property of the field. If the field is unknown or has no record
     * property we default to the general record.
     *
     * We also flag the associated record as 'dirty' so any later
     * save operations know to include it.
     *
     * @param   string  $field          the name of the field to set the value of.
     * @param   mixed   $value          the value to set in the field.
     * @return  P4Cms_Site_Config       provides fluent interface.
     * @throws  P4Cms_Model_Exception   if the field does not exist.
     */
    protected function _setValue($field, $value)
    {
        $id = $this->_getFieldProperty($field, 'record') ?: static::ID_GENERAL;

        $this->_getRecord($id)->setValue($field, $value);

        // flag record as dirty so we know to save it later.
        $this->_dirty[$id] = true;

        return $this;
    }

    /**
     * This method will return the specified record using the
     * model's associated storage adapter. If the requested
     * record does not already exist an in memory version will
     * be silently created.
     *
     * The returned records are cached in the protected '_records'
     * array for later re-use.
     *
     * @param   string  $id         The record id to retrieve/create
     * @return  P4Cms_Site_Config   provides fluent interface.
     */
    protected function _getRecord($id)
    {
        if (isset($this->_records[$id])) {
            return $this->_records[$id];
        }

        $adapter = $this->getSite()->getStorageAdapter();
        try {
            $record = P4Cms_Record::fetch($id, null, $adapter);
        } catch (P4Cms_Record_NotFoundException $e) {
            $record = new P4Cms_Record(null, $adapter);
            $record->setId($id);
        }

        $this->_records[$id] = $record;

        return $record;
    }
}
