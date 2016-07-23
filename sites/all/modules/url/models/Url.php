<?php
/**
 * Storage for custom urls.
 *
 * Each entry maps an arbitrary url path to a set of route parameters
 * (e.g. module/controller/action/id/etc.).
 *
 * The id of each record is the url path. The url path is always
 * normalized to a consistent level of url encoding (and hex encoded
 * for storage). This makes lookups by path quick. To speed up access
 * by parameters a param lookup record is written every time a custom
 * url is saved. Similarly, the lookup record is removed any time a
 * custom url is deleted. Lookups by parameters will only match if
 * the params are identical to those in the primary url record.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Url_Model_Url extends P4Cms_Record
{
    protected static    $_idField           = null;
    protected static    $_encodeIds         = true;
    protected static    $_storageSubPath    = 'urls/by-path';
    protected static    $_lookupPath        = 'urls/by-params';

    /**
     * Get a url record by route parameters.
     *
     * The route parameters must exactly match those specified when
     * the primary url record was saved.
     *
     * @param   array                           $params     the route parameters to lookup.
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record                    the primary url record.
     * @throws  P4Cms_Record_NotFoundException  if the requested record can't be found.
     */
    public static function fetchByParams(array $params, $query = null, P4Cms_Record_Adapter $adapter = null)
    {
        // check param lookup table for a reference to a url record.
        $lookup = P4Cms_Record::fetch(static::makeParamId($params), $query, $adapter);

        // if we're still here, we found one - the id of the url record
        // is held in the path field, use that to fetch the record.
        $record = static::fetch($lookup->getValue('path'), $query, $adapter);

        // verify params match - due to the structure of the data, it is
        // technically possible for two lookup records to point to the same
        // primary record, ensuring the params match protects against this.
        if ($record->getParams() != $params) {
            throw new P4Cms_Record_NotFoundException(
                "Cannot find url record with matching parameters."
            );
        }

        return $record;
    }

    /**
     * Get a url record by content id.
     *
     * @param   string|P4Cms_Content            $content    the content to get a url for.
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record                    the primary url record.
     */
    public static function fetchByContent($content, $query = null, P4Cms_Record_Adapter $adapter = null)
    {
        return static::fetchByParams(
            static::getContentRouteParams($content),
            $query,
            $adapter
        );
    }

    /**
     * Get route parameters to view a specific content entry. Saves
     * us from having to re-iterate the route params everywhere.
     *
     * @param   string|P4Cms_Content    $content    the content to get route params for.
     * @return  array                   the module/controller/action/id params.
     */
    public static function getContentRouteParams($content)
    {
        $content = $content instanceof P4Cms_Content ? $content->getId() : $content;

        // verify content is now a string id.
        if (!is_string($content)) {
            throw new InvalidArgumentException(
                "Cannot fetch url record. Content must be a content object or string id."
            );
        }

        return array(
            'module'        => 'content',
            'controller'    => 'index',
            'action'        => 'view',
            'id'            => $content
        );
    }

    /**
     * Extends save to write a param lookup record for each url record.
     * Also, verifies path has been set (that is the whole point).
     *
     * @param   string              $description    optional - a description of the change.
     * @param   null|string|array   $options        optional - passing the SAVE_THROW_CONFLICTS
     *                                              flag will cause exceptions on conflict; default
     *                                              behaviour is to crush any conflicts.
     *                                              Note this flag has no effect in batches.
     * @return  P4Cms_Record        provides a fluent interface
     * @throws  Url_Exception       if no path (id) has been set.
     */
    public function save($description = null, $options = null)
    {
        $description = $description ?: $this->_generateSubmitDescription();

        // ensure we have a path/id.
        if (!$this->getPath()) {
            throw new Url_Exception("Cannot save url record without a path.");
        }

        // begin a batch if we're not already in one.
        $adapter = $this->getAdapter();
        $batch   = !$adapter->inBatch()
            ? $adapter->beginBatch($description)
            : false;

        // wrap in a try/catch so we can cleanup if something goes wrong.
        try {
            // standard save behavior.
            parent::save($description);

            // save our param lookup record (for quick lookup by params)
            P4Cms_Record::store(
                array(
                    'id'   => static::makeParamId($this->getParams()),
                    'path' => $this->getId()
                ),
                $adapter
            );
        } catch (Exception $e) {
            if ($batch) {
                $adapter->revertBatch();
            }
            throw $e;
        }

        // commit the batch.
        if ($batch) {
            $adapter->commitBatch(null, $options);
        }

        return $this;
    }

    /**
     * Extends delete to also remove the param-lookup record.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides fluent interface.
     */
    public function delete($description = null)
    {
        $description = $description ?: $this->_generateSubmitDescription();

        // begin a batch if we're not already in one.
        $adapter = $this->getAdapter();
        $batch   = !$adapter->inBatch()
            ? $adapter->beginBatch($description)
            : false;

        // wrap in a try/catch so we can cleanup if something goes wrong.
        try {
            // standard delete behavior.
            parent::delete($description);

            // remove our param lookup record.
            P4Cms_Record::remove(static::makeParamId($this->getParams()), $adapter);
        } catch (Exception $e) {
            if ($batch) {
                $adapter->revertBatch();
            }
            throw $e;
        }

        // commit the batch.
        if ($batch) {
            $adapter->commitBatch(null);
        }

        return $this;
    }

    /**
     * Set url route parameters to associate with this url path.
     * Any existing parameters will be cleared.
     *
     * @param   array|null    $params   array of url route parameters to set.
     * @return  P4Cms_Record  provides a fluent interface
     */
    public function setParams($params)
    {
        $this->_values = array();
        return $this->setValues($params);
    }

    /**
     * Get the url route parameters associated with this path.
     * Effectively an alias to getValues().
     *
     * @return  array   the route parameters associated with this url.
     */
    public function getParams()
    {
        return $this->getValues();
    }

    /**
     * Set the id of this record (aka. the url path).
     * Extended to always normalize the path encoding.
     *
     * @param   string|null     $id     the identifier of this record.
     * @return  P4Cms_Record            provides fluent interface.
     */
    public function setId($id)
    {
        return parent::setId(static::normalizePath($id));
    }

    /**
     * Set the path of this url (alias for setId).
     *
     * @param   string|null     $path       the path of the url.
     * @return  P4Cms_Record                provides a fluent interface
     */
    public function setPath($path)
    {
        return $this->setId($path);
    }

    /**
     * Get the path of this url (alias for getId).
     *
     * @return  string|null     the path of the url.
     */
    public function getPath()
    {
        return $this->getId();
    }

    /**
     * Get a copy of the associated param record (not a reference).
     *
     * @return  P4Cms_Record                    a copy of the associated param record.
     * @throws  P4Cms_Record_NotFoundException  if no associated record exists in storage.
     */
    public function getParamRecord()
    {
        return P4Cms_Record::fetch(
            static::makeParamId($this->getParams()),
            null,
            $this->getAdapter()
        );
    }

    /**
     * Normalize a url path component. See Url_Filter_UrlPath for details.
     *
     * @param   string|null                 $path   the url path component to filter.
     * @throws  InvalidArgumentException    if given value is not a string or null.
     * @return  string|null                 the normalized url path string or null.
     */
    public static function normalizePath($path)
    {
        // null in, null out.
        if (is_null($path)) {
            return null;
        }

        $filter = new Url_Filter_UrlPath;
        return $filter->filter($path);
    }

    /**
     * Generate the lookup record id for a given set of url
     * route parameters. The id is a combination of the param
     * lookup storage path and the param hash.
     *
     * @param   array   $params     the params to generate an id for.
     * @return  string  the lookup record id.
     */
    public static function makeParamId(array $params)
    {
        $hash = static::_makeParamHash($params);
        return static::$_lookupPath . '/' . $hash;
    }

    /**
     * Encode id for storage - extended to normalize path.
     *
     * @param   string  $id     the id to encode.
     * @return  string  the encoded id.
     */
    protected function _encodeId($id)
    {
        return parent::_encodeId(static::normalizePath($id));
    }

    /**
     * Make a hash (md5) for a given set of url route params.
     * The params are sorted to ensure the id is consistently
     * generated regardless of the input order.
     *
     * @param   array   $params     the params to generate a hash for.
     * @return  string  the param hash (md5).
     */
    protected static function _makeParamHash($params)
    {
        ksort($params);
        return md5(serialize($params));
    }
}