<?php
/**
 * A class to encapsulate resource privilege information.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Acl_Privilege implements P4Cms_ModelInterface
{
    protected   $_id;
    protected   $_label;
    protected   $_defaultAllow;
    protected   $_options;
    protected   $_resource;

    /**
     * Sets the privilege id and optionally the label and default allowed roles.
     *
     * @param   string          $id         the string-based id of this privilege
     * @param   string          $label      optional - human friendly label for this resource
     * @param   string|array    $allow      optional - default set of roles granted this privilege.
     *                                      string input is assumed to be comma-delimited.
     * @param   array           $options    optional - custom options to store with the privilege.
     * @return  void
      */
    public function __construct($id, $label = null, $allow = null, array $options = null)
    {
        $this->setId($id)
             ->setLabel($label)
             ->setDefaultAllowed($allow)
             ->setOptions($options);
    }

    /**
     * Generate a acl privilege instance from string or array input.
     * If a string is given, it will be taken to be the privilege id.
     * If an array is given, it may contain:
     *
     *       id - the id of the privilege
     *    label - optional, human-friendly privilege label
     *    allow - default set of roles to be granted this privilege
     *  options - optional custom properties to store on privilege
     *
     * @param   array|string            $privilege  an array of privilege information or a
     *                                              string-based privilege id.
     * @return  P4Cms_Acl_Privilege     the new privilege instance.
     */
    public static function factory($privilege)
    {
        // expected elements.
        $info = array(
            'id'        => null,
            'label'     => null,
            'allow'     => null,
            'options'   => array()
        );

        // if privilege itself is a string, take it as the id.
        if (is_string($privilege)) {
            $info['id'] = $privilege;
        }

        // if privilege is an array, it's properties win.
        // any non-standard properties add to the options element.
        if (is_array($privilege)) {
            $options         = array_diff_key($privilege, $info);
            $info            = array_merge($info, array_intersect_key($privilege, $info));
            $info['options'] = array_merge($options, $info['options']);
        }

        // generate privilege instance.
        $privilege = new P4Cms_Acl_Privilege(
            $info['id'],
            $info['label'],
            $info['allow'],
            $info['options']
        );

        return $privilege;
    }

    /**
     * Get the id of this privilege.
     *
     * @return  string|null     the id of this privilege, or null if none set.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set the id of this privilege.
     *
     * @param   string|null     $id     the id of this privilege, or null to clear.
     * @return  P4Cms_Acl_Privilege     provides fluent interface.
     */
    public function setId($id)
    {
        if (!is_string($id) && !is_null($id)) {
            throw new InvalidArgumentException("Cannot set id. Id must be a string or null");
        }

        $this->_id = $id;

        return $this;
    }

    /**
     * Get the human-friendly label of this privilege.
     * Any macros in the label will be expanded automatically.
     *
     * @return  string  the label of this privilege, or the id if no label is set.
     */
    public function getLabel()
    {
        $macro = new P4Cms_Filter_Macro(array('privilege' => $this));
        return $macro->filter($this->_label ?: $this->getId());
    }

    /**
     * Set the friendly label of this privilege.
     *
     * @param   string|null     $label  the label of this privilege, or null to clear.
     * @return  P4Cms_Acl_Privilege     provides fluent interface.
     */
    public function setLabel($label)
    {
        if (!is_string($label) && !is_null($label)) {
            throw new InvalidArgumentException("Cannot set label. Label must be a string or null");
        }

        $this->_label = $label;

        return $this;
    }

    /**
     * Get the default set of roles that are allowed this privilege.
     *
     * @return  array   the default allowed roles.
     */
    public function getDefaultAllowed()
    {
        return is_array($this->_defaultAllow) ? $this->_defaultAllow : array();
    }

    /**
     * Set the default default set of roles to be granted this privilege.
     *
     * @param   array|string|null       $roles  the list of roles, or null to clear.
     *                                          string input is assumed to be comma-delimited.
     * @return  P4Cms_Acl_Privilege     provides fluent interface.
     */
    public function setDefaultAllowed($roles)
    {
        // explode string on comma.
        if (is_string($roles)) {
            $roles = explode(',', $roles);
            $roles = array_map('trim', $roles);
        }

        if (!is_array($roles) && !is_null($roles)) {
            throw new InvalidArgumentException(
                "Cannot set default allowed roles. Roles must be an array, string or null"
            );
        }

        $this->_defaultAllow = $roles;

        return $this;
    }

    /**
     * Get custom options stored with this privilege
     *
     * @return  array   key/value pairs.
     */
    public function getOptions()
    {
        return is_array($this->_options) ? $this->_options : array();
    }

    /**
     * Set custom options to be stored with this privilege.
     *
     * @param   array|null              $options    a list of key/value pairs to store.
     * @return  P4Cms_Acl_Privilege     provides fluent interface.
     */
    public function setOptions(array $options = null)
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * Get a custom option by name.
     *
     * @param   string  $name   the name of the option to get
     * @return  mixed   the value of the custom option (null if unset).
     */
    public function getOption($name)
    {
        return isset($this->_options[$name])
            ? $this->_options[$name]
            : null;
    }

    /**
     * Set a custom option to be stored with this privilege.
     *
     * @param   string                  $name       the option name
     * @param   mixed                   $value      the option value
     * @return  P4Cms_Acl_Privilege     provides fluent interface.
     */
    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;

        return $this;
    }

    /**
     * Set the resource this privilege is associated with.
     *
     * @param   P4Cms_Acl_Resource      $resource   the resource this privilege is associated with
     * @return  P4Cms_Acl_Privilege     provides fluent interface.
     */
    public function setResource(P4Cms_Acl_Resource $resource)
    {
        $this->_resource = $resource;
    }

    /**
     * Get the resource this privilege is associated with.
     *
     * @return  P4Cms_Acl_Resource      the resource this privilege is associated with
     * @throws  P4Cms_Acl_Exception     if there is no associated resource.
     */
    public function getResource()
    {
        if (!$this->_resource instanceof P4Cms_Acl_Resource) {
            throw new P4Cms_Acl_Exception(
                "Cannot get resource. No resource associated with this privilege."
            );
        }

        return $this->_resource;
    }

    /**
     * Determine if this privilege is associated with a resource.
     *
     * @return  bool    true if this resource is associated with a resource; false otherwise.
     */
    public function hasResource()
    {
        try {
            $this->getResource();
            return true;
        } catch (P4Cms_Acl_Exception $e) {
            return false;
        }
    }

    /**
     * Get the id of the resource this privilege is associated with.
     *
     * @return  string                  the id of the resource this privilege is associated with
     * @throws  P4Cms_Acl_Exception     if there is no associated resource.
     */
    public function getResourceId()
    {
        return $this->getResource()->getResourceId();
    }

    /**
     * Convert this privilege to array form.
     *
     * @return  array   this privilege as an array.
     */
    public function toArray()
    {
        return array(
            'id'        => $this->getId(),
            'label'     => $this->getLabel(),
            'allow'     => $this->getDefaultAllowed(),
            'resource'  => $this->getResourceId(),
            'options'   => $this->getOptions()
        );
    }

    /**
     * Check if given field is a valid privilege field.
     *
     * @param  string   $field  privilege field to check
     * @return boolean  true if the field exists.
     */
    public function hasField($field)
    {
        return in_array($field, $this->getFields());
    }

    /**
     * Return array with all privilege fields.
     *
     * @return  array    all privilege fields.
     */
    public function getFields()
    {
        return array_keys($this->toArray());
    }

    /**
     * Return value of given field of the privilege.
     *
     * @param  string   $field  model field to retrieve
     * @return mixed    the value of the named field.
     */
    public function getValue($field)
    {
        $fields = $this->toArray();
        return isset($fields[$field]) ? $fields[$field] : null;
    }
}
