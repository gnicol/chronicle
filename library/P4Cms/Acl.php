<?php
/**
 * Extends Zend_Acl to add several features:
 *
 *  - Allows ACL to be fetched from and saved in records.
 *  - Allows roles to be set externally (e.g. from Perforce)
 *  - No longer serializes roles (so they may be stored elsewhere).
 *  - Adds concept of a statically accessible 'active' acl.
 *  - Provides access to resource objects.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Acl extends Zend_Acl
{
    const               RECORD_STORAGE_FIELD    = 'acl';

    protected static    $_activeAcl;
    protected           $_record;
    protected           $_tempResources = array();

    /**
     * Provide magic sleep to selectively serialize properties.
     * This is needed for caching and saving the acl. Note that
     * save is even more selective about what gets serialized.
     *
     * @return  array   list of properties to serialize
     */
    public function __sleep()
    {
        // remove temporary resources
        foreach ($this->_tempResources as $resource) {
            $this->remove($resource);
        }
        $this->_tempResources = array();

        return array(
            '_roleRegistry',
            '_resources',
            '_record',
            '_rules'
        );
    }

    /**
     * Overrides parent method to add resource temporarily if it doesn't exist,
     * but has the form 'parentResource/id' where parentResource is an existing
     * resource. Temporary resources are not serialized.
     *
     * @param  Zend_Acl_Resource_Interface|string $resource     resource to return identified
     *                                                          resource for.
     * @throws Zend_Acl_Exception
     * @return Zend_Acl_Resource_Interface                      identified resource.
     */
    public function get($resource)
    {
        try {
            return parent::get($resource);
        } catch (Zend_Acl_Exception $e) {
            $resource      = is_string($resource) ? $resource : $resource->getResourceId();
            $resourceParts = explode('/', $resource);
            if (count($resourceParts) > 1 && $this->has($resourceParts[0])) {
                $resource = new P4Cms_Acl_Resource($resource);
                $this->addResource($resource, $resourceParts[0]);
                $this->_tempResources[] = $resource;
                return $resource;
            } else {
                // resource not found
                throw $e;
            }
        }
    }

    /**
     * Fetch the active acl. Guaranteed to return an acl instance or
     * throw an exception if no acl instance has been set as active.
     *
     * @return  P4Cms_Acl               the currently active acl.
     * @throws  P4Cms_Acl_Exception     if there is no currently active acl.
     */
    public static function fetchActive()
    {
        if (!static::$_activeAcl || !static::$_activeAcl instanceof P4Cms_Acl) {
            throw new P4Cms_Acl_Exception("There is no active ACL.");
        }

        return static::$_activeAcl;
    }

    /**
     * Determine if there is an active acl.
     *
     * @return  boolean     true if there is an active acl.
     */
    public static function hasActive()
    {
        try {
            static::fetchActive();
            return true;
        } catch (P4Cms_Acl_Exception $e) {
            return false;
        }
    }

    /**
     * Set the statically accessible ACL instance.
     *
     * @param   P4Cms_Acl|null  $acl    the acl instance to make active or null to clear.
     */
    public static function setActive(P4Cms_Acl $acl = null)
    {
        static::$_activeAcl = $acl;
    }

    /**
     * Overrides isAllowed to return false for non-super roles
     * accessing privileges that require super-user access.
     *
     * @param  Zend_Acl_Role_Interface|string     $role         the role to check for access
     * @param  Zend_Acl_Resource_Interface|string $resource     the resource to check access to
     * @param  string                             $privilege    the privilege in question
     * @return boolean
     */
    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        $role     = is_string($role)     ? $this->getRole($role) : $role;
        $resource = is_string($resource) ? $this->get($resource) : $resource;

        // non-super roles are not allowed super privileges.
        if ($privilege && $resource->hasPrivilege($privilege)) {
            $needsSuper = $resource->getPrivilege($privilege)->getOption('needsSuper');
            if ($needsSuper && !P4Cms_Acl_Role::isSuper($role->getRoleId())) {
                return false;
            }
        }

        return parent::isAllowed($role, $resource, $privilege);
    }

    /**
     * Returns list of all privileges for which the role has access to the resource.
     *
     * @param  Zend_Acl_Role_Interface|string     $role         the role to check for
     * @param  Zend_Acl_Resource_Interface|string $resource     the resource to check for
     * @return array                                            list of privileges for which the
     *                                                          role has access to the resource
     */
    public function getAllowedPrivileges($role, $resource)
    {
        if ($resource instanceof P4Cms_Acl_Resource) {
            $privileges = $resource->getPrivileges();
        } else {
            $privileges = $this->getAllPrivileges()->toArray(true);
        }

        $allowed = array();
        foreach ($privileges as $privilege) {
            $privilege = $privilege->getId();
            if ($this->isAllowed($role, $resource, $privilege)) {
                $allowed[$privilege] = 1;
            }
        }

        return array_keys($allowed);
    }

    /**
     * Make this acl instance the active one.
     *
     * @return  P4Cms_Acl   provides fluent interface.
     */
    public function makeActive()
    {
        static::setActive($this);

        return $this;
    }

    /**
     * Get all of the registered resource objects.
     *
     * @return  array   list of resource instances
     */
    public function getResourceObjects()
    {
        return array_map(
            function($resource)
            {
                return $resource['instance'];
            },
            $this->_resources
        );
    }

    /**
     * Set all roles at once.
     *
     * To reduce the weight of serialized roles we normalize them to
     * Zend_Acl_Role objects. Our role objects compose Perforce group
     * objects and spec definitions, etc. and are considerably larger.
     *
     * @param   mixed       $roles  the set of roles to use for this acl
     *                              can be a Zend_Acl_Role_Registry instance,
     *                              a iterator of role objects, or null to clear.
     * @return  P4Cms_Acl   provides fluent interface.
     */
    public function setRoles($roles = null)
    {
        // extract roles from iterator.
        if ($roles instanceof Iterator) {
            $registry = new Zend_Acl_Role_Registry;
            foreach ($roles as $role) {
                if (!$role instanceof Zend_Acl_Role_Interface) {
                    throw new InvalidArgumentException(
                        "Cannot set roles. Encountered invalid role."
                    );
                }
                $registry->add(new Zend_Acl_Role($role->getRoleId()));
            }
            $roles = $registry;
        }

        if ($roles !== null && !$roles instanceof Zend_Acl_Role_Registry) {
            throw new InvalidArgumentException(
                "Cannot set roles. Roles must be a Zend_Acl_Role_Registry instance, "
              . "a iterator of role objects, or null."
            );
        }

        // set the registry.
        $this->_roleRegistry = $roles;

        return $this;
    }

    /**
     * Fetch a persisted ACL instance from a given record id.
     * Reads the identified record from storage and unserializes
     * it into a new P4Cms_Acl instance.
     *
     * @param   string                  $id         the id of the record to read from.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Acl               previously stored acl instance.
     */
    public static function fetch($id, P4Cms_Record_Adapter $adapter = null)
    {
        $record     = P4Cms_Record::fetch($id, null, $adapter);
        $serialized = $record->getValue(static::RECORD_STORAGE_FIELD);

        // attempt to unserialize acl from storage.
        $acl = unserialize($serialized);
        if (!$acl instanceof P4Cms_Acl) {
            throw new P4Cms_Acl_Exception(
                "Cannot fetch ACL from $id record. Record did not unserialize to ACL."
            );
        }

        // hang onto record for potential future save.
        $acl->setRecord($record);

        return $acl;
    }

    /**
     * Set the record to use for persistent storage of this acl.
     *
     * @param   P4Cms_Record|null   $record     a record object to save acl to - null to clear.
     * @return  P4Cms_Acl           provides fluent interface.
     */
    public function setRecord(P4Cms_Record $record = null)
    {
        $this->_record = $record;

        return $this;
    }

    /**
     * Get the record to use for persistent storage of this acl.
     *
     * @return  P4Cms_Record            the record to save acl to.
     * @throws  P4Cms_Acl_Exception     if no record object has been set.
     */
    public function getRecord()
    {
        if (!$this->_record instanceof P4Cms_Record) {
            throw new P4Cms_Acl_Exception("Cannot get record. No record has been set.");
        }

        return $this->_record;
    }

    /**
     * Determine if this acl has an associated record for storage purposes.
     *
     * @return  boolean     true if there is an associated record.
     */
    public function hasRecord()
    {
        try {
            $this->getRecord();
            return true;
        } catch (P4Cms_Acl_Exception $e) {
            return false;
        }
    }

    /**
     * Get all privileges in all resources registered with the acl.
     *
     * @return  array   a flat list of all privileges in all resources.
     */
    public function getAllPrivileges()
    {
        $privileges = new P4Cms_Model_Iterator;
        foreach ($this->getResourceObjects() as $resource) {
            if ($resource instanceof P4Cms_Acl_Resource) {
                foreach ($resource->getPrivileges() as $privilege) {
                    $privileges[] = $privilege;
                }
            }
        }

        return $privileges;
    }

    /**
     * Save this ACL to the associated record.
     *
     * @return  P4Cms_Acl               provides fluent interface.
     * @throws  P4Cms_Acl_Exception     if no record has been associated with this acl.
     */
    public function save()
    {
        // we don't want roles or the record to make it into storage
        $acl = clone $this;
        $acl->setRoles(null)
            ->setRecord(null);

        $this->getRecord()
             ->setValue(static::RECORD_STORAGE_FIELD, serialize($acl))
             ->save();

        return $this;
    }

    /**
     * Install default ACL resources and rules defined in packages.
     * Does not save the acl automatically, must call save() to persist.
     *
     * @return  P4Cms_Acl   provides fluent interface.
     */
    public function installDefaults()
    {
        // clear the module/theme cache
        P4Cms_Module::clearCache();
        P4Cms_Theme::clearCache();

        // defaults are defined by modules.
        $modules = P4Cms_Module::fetchAllEnabled();
        foreach ($modules as $module) {
            $this->installModuleDefaults($module);
        }

        return $this;
    }

    /**
     * Install default ACL resources and rules defined in a module.
     * Does not save the acl automatically, must call save() to persist.
     *
     * @param   P4Cms_Module  $module  the module whose ACL resources and rules need to be installed
     * @return  P4Cms_Acl              provides fluent interface.
     */
    public function installModuleDefaults(P4Cms_Module $module)
    {
        // extract resources from package info.
        $info      = $module->getPackageInfo();
        $resources = isset($info['acl']) && is_array($info['acl'])
                   ? $info['acl']
                   : array();

        // register resources with acl.
        foreach ($resources as $resourceId => $resourceInfo) {

            // add resource if it doesn't exist.
            if (!$this->has($resourceId)) {
                $this->add(new P4Cms_Acl_Resource($resourceId));
            }
            $resource = $this->get($resourceId);

            // set resource label if one is specified.
            if (isset($resourceInfo['label'])) {
                $resource->setLabel($resourceInfo['label']);
            }

            // add any new privileges to resource.
            $privileges = isset($resourceInfo['privileges']) && is_array($resourceInfo['privileges'])
                        ? $resourceInfo['privileges']
                        : array();
            foreach ($privileges as $key => $value) {

                // try to normalize privilege entry to object.
                try {
                    $privilege = $resource->normalizePrivilege($value, $key);
                } catch (InvalidArgumentException $e) {
                    P4Cms_Log::logException("Failed to install default privilege.", $e);
                    continue;
                }

                // skip if privilege exists.
                if ($resource->hasPrivilege($privilege->getId())) {
                    continue;
                }

                // add the privilege.
                $resource->addPrivilege($privilege);

                // use a proxy for assertions to allow for assert
                // classes that might not exist at all times.
                $assert = $privilege->getOption('assertion');
                if ($assert) {
                    $assert = new P4Cms_Acl_Assert_Proxy($assert);
                }

                // set default allow rules.
                // determine which roles to allow access for.
                if ($privilege->getOption('allowAll')) {
                    $this->allow(null, $resource, $privilege->getId(), $assert);
                } else {
                    $roles = $privilege->getDefaultAllowed();
                    $roles = array_filter($roles, array($this, 'hasRole'));
                    if ($roles) {
                        $this->allow($roles, $resource, $privilege->getId(), $assert);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Remove default ACL resources and rules defined in a module.
     * Does not save the acl automatically, must call save() to persist.
     *
     * @param   P4Cms_Module  $module  the module whose ACL resources and rules need to be removed
     * @return  P4Cms_Acl              provides fluent interface.
     */
    public function removeModuleDefaults(P4Cms_Module $module)
    {
        // extract resources from package info.
        $info      = $module->getPackageInfo();
        $resources = isset($info['acl']) && is_array($info['acl'])
                   ? $info['acl']
                   : array();

        // remove resources from acl.
        foreach ($resources as $resourceId => $resourceInfo) {
            // do nothing if resource doesn't exist.
            if (!$this->has($resourceId)) {
                continue;
            }
            $resource = $this->get($resourceId);

            // remove module default privileges from resource.
            $privileges = isset($resourceInfo['privileges']) && is_array($resourceInfo['privileges'])
                        ? $resourceInfo['privileges']
                        : array();
            foreach ($privileges as $key => $value) {
                // remove the privilege.
                $resource->removePrivilege($key);
            }

            // remove resource if it does not have any privileges.
            if (!$resource->hasPrivileges()) {
                $this->remove($resource);
            }
        }

        return $this;
    }
}
