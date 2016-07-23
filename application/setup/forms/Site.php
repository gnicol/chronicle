<?php
/**
 * This is the setup site creation form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Setup_Form_Site extends P4Cms_Form
{
    protected   $_connection;

    /**
     * Defines the elements that make up the site form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui site-form');

        // form should submit on enter
        $this->setAttrib('submitOnEnter', true);

        // set the method for the display form to POST
        $this->setMethod('post');

        // add a hidden id field, this will be auto-populated based on title
        $this->addElement(
            'hidden',
            'id',
            array(
                'disabled'      => true
            )
        );

        // add a field to collect the site title.
        $this->addElement(
            'text',
            'title',
            array(
                'label'         => 'Title',
                'value'         => static::getRequestHost(),
                'required'      => true,
                'description'   => "Enter a recognizable title for this site.",
                'filters'       => array('StringTrim')
            )
        );

        // add a field to collect the site's description.
        $this->addElement(
            'textarea',
            'description',
            array(
                'label'         => 'Description',
                'value'         => '',
                'required'      => false,
                'rows'          => 2,
                'cols'          => 50,
                'description'   => "Enter a short summary of your site.<br/>"
                                .  "This summary will appear in meta description tags for non-content pages."
            )
        );
        $this->getElement('description')
             ->getDecorator('Description')
             ->setEscape(false);

        // add a field to collect the site's urls.
        $this->addElement(
            'textarea',
            'urls',
            array(
                'label'         => 'Site Address',
                'value'         => $this->_getDefaultUrls(),
                'required'      => true,
                'rows'          => 3,
                'cols'          => 50,
                'description'   => "Provide a list of urls for which this site will be served.<br/>"
                                . "For example: domain.com, www.domain.com"
            )
        );
        $this->getElement('urls')
             ->getDecorator('Description')
             ->setEscape(false);

        // add the submit button
        $this->addElement(
            'SubmitButton',
            'create',
            array(
                'label'     => 'Create Site',
                'class'     => 'button-large preferred',
                'ignore'    => true
            )
        );
        $this->addElement(
            'SubmitButton',
            'goback',
            array(
                'label'     => 'Go Back',
                'class'     => 'button-large',
                'ignore'    => true
            )
        );

        // put the button in a fieldset.
        $this->addDisplayGroup(
            array('create', 'goback'),
            'buttons',
            array('class' => 'buttons')
        );
    }

    /**
     * Override isValid to check that site title is unique.
     *
     * @param   array       $data   the field values to validate.
     * @return  boolean     true if the form values are valid.
     */
    public function isValid($data)
    {
        // always set id from title
        $title      = isset($data['title']) ? $data['title'] : null;
        $data['id'] = $this->_composeSiteId($title);

        if (!parent::isValid($data)) {
            return false;
        }

        $valid = true;

        // if individual values are valid, ensure that site title/id is unique.
        if ($this->_isSiteIdTaken($this->getValue('id'))) {
            $this->getElement('title')->addError(
                "The site title you provided appears to be taken. Please choose a different title."
            );
            $valid = false;
        }

        // ensure that site addresses are not in use
        $urls = array_map('trim', preg_split("/\n|,/", $this->getValue('urls')));
        foreach ($urls as $url) {
            if ($this->_isSiteAddressTaken($url)) {
                $this->getElement('urls')->addError(
                    "The site address '$url' you provided appears to be taken. Please choose a different address."
                );
                $valid = false;
                break;
            }
        }

        return $valid;
    }

    /**
     * Set element values.
     *
     * Extended here to set the site id from the site title, if a
     * title is present in the given defaults array.
     *
     * @param   P4Cms_Record|array  $defaults   the default values to set on elements
     * @return  Zend_Form           provides fluent interface
     */
    public function setDefaults($defaults)
    {
        // always set id from title if title is present
        if (array_key_exists('title', $defaults)) {
            $defaults['id'] = $this->_composeSiteId($defaults['title']);
        }

        return parent::setDefaults($defaults);
    }

    /**
     * Set the target server connection to use.
     *
     * @param   P4_Connection_Interface     $connection     a connection to the server we
     *                                                      intend to create the site in.
     */
    public function setConnection(P4_Connection_Interface $connection = null)
    {
        $this->_connection = $connection;
    }

    /**
     * Get the current host name.
     *
     * @param   boolean     $removePort   If true, removes the port from the host.
     * @return  false|string    the current http host - false if unable to determine the host.
     */
    public static function getRequestHost($removePort = true)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if (!$request instanceof Zend_Controller_Request_Http) {
            return false;
        }

        $host = $request->getHttpHost();
        if (!$removePort) {
            return $host;
        }

        if (preg_match('#:\d+$#', $host, $result) === 1) {
            $host = substr($host, 0, -strlen($result[0]));
        }
        return $host;
    }

    /**
     * Get the default value for the site urls field.
     * Defaults to current address with and without 'www' prefix.
     *
     * @return  false|string    the default site urls - false if unable to get current address.
     */
    protected function _getDefaultUrls()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if (!$request instanceof Zend_Controller_Request_Http) {
            return false;
        }

        // set default to current address with and without www.
        // skipping the www prefixed version if we have more/less
        // than a single period in the hostname (e.g. IP address,
        // sub-domain, localhost, etc.)
        $default = static::getRequestHost(false) . $request->getBaseUrl();
        if (substr($default, 0, 4) == 'www.') {
            $default .= "\n" . substr($default, 4);
        } else if (substr_count($default, '.') == 1) {
            $default .= "\n" . "www." . $default;
        }
        return $default;
    }

    /**
     * Check if the given site id is already in use.
     *
     * @param   string  $id     the id of the site to check for.
     * @return  bool    true if the id is taken.
     */
    protected function _isSiteIdTaken($id)
    {
        // check for conflicting site packages folder.
        if (is_dir(P4Cms_Site::getSitesPackagesPath($id))) {
            return true;
        }

        // check for conflicting site data directory.
        if (is_dir(P4Cms_Site::getSitesDataPath($id))) {
            return true;
        }

        // if no connection, can't check perforce for conflicts.
        // (server type must be 'new' or a connection would have been set)
        if (!$this->_connection) {
            return false;
        }

        $p4 = $this->_connection;

        // check for depot name conflict - site ids relate 1:1 with depot names
        if (P4_Depot::exists($id, $p4)) {
            return true;
        }

        // check for site 'umbrella' group name conflict.
        // as with depots, site ids relate 1:1 with their parent group.
        if (P4_Group::exists($id, $p4)) {
            return true;
        }

        // check if any group with prefix exists.
        $groupPrefix = $id . P4Cms_Acl_Role::PREFIX_DELIMITER;
        foreach (P4_Group::fetchAll(array(), $p4) as $group) {
            if (strpos($group->getId(), $groupPrefix) === 0) {
                return true;
            }
        }

        // appears to be unique.
        return false;
    }

    /**
     * Check if the given site address is already in use.
     *
     * @param   string  $url    the address of the site to check for
     * @return  boolean         true,  if the site address is used by any existing sites
     *                          false, otherwise
     */
    protected function _isSiteAddressTaken($url)
    {
        // if no connection, can't check perforce for conflicts.
        // (server type must be 'new' or a connection would have been set)
        if (!$this->_connection) {
            return false;
        }

        $sites = P4Cms_Site::fetchAll(null, $this->_connection);

        // collect urls from all sites
        $urls  = array();
        foreach ($sites as $site) {
            $urls = array_merge($urls, $site->getConfig()->getUrls());
        }

        return in_array($url, $urls);
    }

    /**
     * Generate a site id from a given site title.
     *
     * Replaces non-alphanumeric characters with dashes ('-')
     * and prefixes the site id with the site prefix ('chronicle-').
     *
     * @param   string          $title  the site title to make an id for.
     * @return  string|null     the generated site id.
     */
    protected function _composeSiteId($title)
    {
        $filter = new P4Cms_Filter_TitleToId;
        $id     = $filter->filter($title);

        return $id ? P4Cms_Site::SITE_PREFIX . $id : null;
    }
}
