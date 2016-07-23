<?php
/**
 * Encapsulates and generates a UUID (Universally Unique IDentifier).
 *
 * Casting a new UUID object to a string will automatically generate and
 * return a UUID. For example:
 *
 *  print new P4Cms_Uuid;
 *  // outputs: 550e8400-e29b-41d4-a716-446655440000
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Uuid
{
    protected   $_uuid  = null;

    /**
     * Get the UUID. If no UUID is presently set, a new one will be generated.
     *
     * @return  string  a generated or explicitly set UUID (in lower-case)
     */
    public function get()
    {
        // if we don't have a uuid yet, generate one.
        if (!$this->_uuid) {
            $chars = md5(uniqid(mt_rand(), true));
            $uuid  = $this->_md5ToUuid($chars);

            $this->_uuid = $uuid;
        }

        return $this->_uuid;
    }

    /**
     * Set an arbitrary UUID or clear the existing one.
     *
     * @param   string|null     $uuid       the UUID to hold or null to clear.
     * @return  P4Cms_Uuid                  provides fluent interface.
     * @throws  InvalidArgumentException    if the input is not a valid string or null
     */
    public function set($uuid)
    {
        if (!is_null($uuid) && !$this->isValid($uuid)) {
            throw new InvalidArgumentException(
                "Cannot set UUID. Must be a valid UUID string or null."
            );
        }

        // set (normalize to lower-case)
        $this->_uuid = strtolower($uuid);

        return $this;
    }

    /**
     * Determine if the given string is a valid UUID.
     * Characters a-z, 0-9 in the arrangement: 8-4-4-4-12
     *
     * @param   string      $uuid   the UUID to validate.
     * @return  bool        true if the given UUID is valid; false otherwise.
     */
    public function isValid($uuid)
    {
        // verify type.
        if (!is_string($uuid)) {
            return false;
        }

        // verify correct format.
        $pattern = "/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/i";
        return (bool) preg_match($pattern, $uuid);
    }

    /**
     * Returns a UUID instnace where the passed md5 string has been
     * formatted as a UUID and can be retrieved by calling 'get'.
     *
     * @param   string  $md5                an md5 hash
     * @return  P4Cms_UUID                  a uuid instance utilizing the passed md5
     * @throws  InvalidArgumentException    if input is not a 32 character hex string
     */
    public static function fromMd5($md5)
    {
        if (!is_string($md5) || !preg_match('/[a-z0-9]{32}/i', $md5)) {
            throw new InvalidArgumentException(
                "Cannot create UUID from passed value. Value must be a 32 character hex string."
            );
        }

        $uuid = new static;
        $uuid->set($uuid->_md5ToUuid($md5));

        return $uuid;
    }

    /**
     * Automatically return the UUID string when cast to a string.
     *
     * @return  string  a generated or explicitly set UUID (in lower-case)
     */
    public function __toString()
    {
        return $this->get();
    }

    /**
     * Formats the passed 32 character hex md5 as a UUID by
     * insert hyphen '-' characters appropriately.
     *
     * @param   string  $md5    the 32 character hex formatted md5 to uuid'ize
     * @return  string  the uuid formatted result
     */
    protected function _md5ToUuid($md5)
    {
        return substr($md5,  0,  8) . '-'
             . substr($md5,  8,  4) . '-'
             . substr($md5, 12,  4) . '-'
             . substr($md5, 16,  4) . '-'
             . substr($md5, 20, 12);
    }
}
