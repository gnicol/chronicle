<?php
/**
 * Extends acl resources to support associated privileges.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Acl_Resource extends Zend_Acl_Resource
{
    protected   $_label         = null;
    protected   $_privileges    = array();
    protected   $_published     = false;

    /**
     * Sets the resource identifier, and optionally the privileges and label.
     *
     * @param   string  $resourceId     the string-based id of this resource
     * @param   array   $privileges     optional - set of privileges for this resource
     * @param   string  $label          optional - human friendly label for this resource
     * @return  void
     */
    public function __construct($resourceId, array $privileges = null, $label = null)
    {
        parent::__construct($resourceId);

        $this->setPrivileges($privileges);
        $this->setLabel($label);
    }

    /**
     * Get the id of this resource.
     *
     * @return  string|null     the id of this resource, or null if none set.
     */
    public function getId()
    {
        return $this->getResourceId();
    }

    /**
     * Get the privileges associated with this resource.
     *
     * Each element in the privileges list is keyed on the privilege id.
     * The element value is itself an array gauranteed to contain the
     * id of the privilege. It may also contain a human-friendly label
     *
     * @return  array   the privileges defined for this resource.
     *
     * @publishes   p4cms.acl.<resource>.privileges
     *              Modify the passed resource, intended to allow subscribers to add/remove/modify
     *              the resource's privileges. The <resource> portion of the topic is the resource's
     *              ID.
     *              P4Cms_Acl_Resource  $resource   The resource to modify.
     */
    public function getPrivileges()
    {
        // publish resource to allow modification of privileges
        // only publish when privileges first accessed, this
        // also permits access to privileges from subscribers
        if (!$this->_published) {
            $this->_published = true;
            P4Cms_PubSub::publish(
                'p4cms.acl.' . $this->getId() . '.privileges',
                $this
            );
        }

        return $this->_privileges;
    }

    /**
     * Set the privileges associated with this resource.
     *
     * @param   array   $privileges         the privileges to associate with this resource.
     *                                      each entry must be a privilege instance, an array of privilege
     *                                      information or a string-based privilege id.
     * @return  P4Cms_Acl_Resource          provides fluent interface.
     * @throws  InvalidArgumentException    if any of the privileges are invalid.
     */
    public function setPrivileges(array $privileges = null)
    {
        // clear existing privileges.
        $this->_privileges = array();

        // if given privileges are null, all done.
        if (!$privileges) {
            return $this;
        }

        // mormalize and add each privilege.
        foreach ($privileges as $key => $value) {
            $this->addPrivilege(
                $this->normalizePrivilege($value, $key)
            );
        }

        return $this;
    }

    /**
     * Add a privilege to this resource.
     *
     * @param   P4Cms_Acl_Privilege|array|string    $privilege  a privilege instance, an array of privilege
     *                                                          information or a string-based privilege id.
     * @return  P4Cms_Acl_Resource                  provides fluent interface.
     * @throws  InvalidArgumentException            if the given privilege is invalid.
     */
    public function addPrivilege($privilege)
    {
        $privilege = $this->normalizePrivilege($privilege);
        $privilege->setResource($this);

        $this->_privileges[$privilege->getId()] = $privilege;

        return $this;
    }

    /**
     * Remove a privilege from this resource.
     *
     * @param   string               $id    a string-based privilege id.
     * @return  P4Cms_Acl_Resource   provides fluent interface.
     */
    public function removePrivilege($id)
    {
        if ($this->hasPrivilege($id)) {
            unset($this->_privileges[$id]);
        }

        return $this;
    }

    /**
     * Check if this resource has the named privilege.
     *
     * @param   string  $id     the id of the privilege to check for.
     * @return  bool    true if the identified privilege exists on this resource.
     */
    public function hasPrivilege($id)
    {
        return array_key_exists($id, $this->_privileges);
    }

    /**
     * Check if this resource has any privileges.
     *
     * @return  bool    true, if there are any privileges on this resource.
     */
    public function hasPrivileges()
    {
        return !empty($this->_privileges);
    }

    /**
     * Get a specific privilege associated with this resource.
     *
     * @param   string                  $id     the associated privilege to get
     * @return  P4Cms_Acl_Privilege     the specified privilege.
     */
    public function getPrivilege($id)
    {
        if (!$this->hasPrivilege($id)) {
            throw new P4Cms_Acl_Exception(
                "Cannot get privilege. Privilege $id not found."
            );
        }
        return $this->_privileges[$id];
    }

    /**
     * Get the label set for this resource.
     * Any macros in the label will be expanded automatically.
     *
     * @return  string  the label of this resource, or the id if no label is set.
     */
    public function getLabel()
    {
        $macro = new P4Cms_Filter_Macro(array('resource' => $this));
        return $macro->filter($this->_label ?: $this->getId());
    }

    /**
     * Set the human-friendly label for this resource.
     *
     * @param   string  $label      the label for this resource.
     * @return  P4Cms_Acl_Resource  provides fluent interface.
     */
    public function setLabel($label)
    {
        $this->_label = (string) $label;
    }

    /**
     * Normalize mixed input to a identifiable privilege.
     *
     * If a string is given, it will be taken to be the privilege id.
     * If an array is given, it may contain:
     *
     *      id - the id of the privilege
     *   label - optional, human-friendly privilege label
     *   allow - default set of roles granted this privilege
     *
     * If the optional id parameter is given, it will be set as the
     * privilege id, but only if the privilege parameter is an array
     * without an id element.
     *
     * @param   mixed   $privilege          a privilege info array, string id, or instance.
     * @param   string  $id                 optional - id of the privilege if not present in privilege.
     * @return  P4Cms_Acl_Privilege         the new privilege instance.
     * @throws  InvalidArgumentException    if we can't produce an identifable privilege.
     */
    public function normalizePrivilege($privilege, $id = null)
    {
        if (!$privilege instanceof P4Cms_Acl_Privilege) {
            $privilege = P4Cms_Acl_Privilege::factory($privilege);
        }

        // last chance to identify privilege.
        if (!$privilege->getId()) {
            $privilege->setId($id);
        }

        // throw exception if privilege is not identified.
        if (!$privilege->getId()) {
            throw new InvalidArgumentException(
                "Cannot normalize input to an identifiable privilege."
            );
        }

        return $privilege;
    }
}
