<?php
/**
 * Provides a facility for content entry links. These links store the
 * content entry id they are associated with to dynamically generate
 * a URL. Also, they will defer to the content entry's title if no label
 * is provided.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Navigation_Page_Content extends Zend_Navigation_Page
{
    const   ACL_RESOURCE    = 'content';
    const   ACL_PRIVILEGE   = 'access';

    /**
     * Content entry id.
     *
     * @var string|null
     */
    protected $_contentId;

    /**
     * Content entry, cached here after first fetch.
     *
     * @var P4Cms_Content|false|null
     */
    protected $_contentEntry    = false;

    /**
     * Name of the action for the content.
     *
     * @var string|null
     */
    protected $_contentAction;

    /**
     * Returns the id of the content entry associated with this item.
     *
     * @return  string|null     id of the associated content entry or null
     */
    public function getContentId()
    {
        return is_string($this->_contentId) ? $this->_contentId : null;
    }

    /**
     * Set the id of the content entry associated with this item.
     *
     * @param   string|null     $id             id of the associated content entry or null
     * @return  P4Cms_Navigation_Page_Dynamic   to maintain a fluent interface
     */
    public function setContentId($id)
    {
        $this->_contentId = $id;

        // reset instance copy of content entry.
        $this->_contentEntry = false;
        return $this;
    }

    /**
     * Returns the action name for the content. 'View' action will be returned
     * by default if no action has been previously set.
     *
     * @return  string  action name for the content
     */
    public function getContentAction()
    {
        return $this->_contentAction ?: 'view';
    }

    /**
     * Set the content action name.
     *
     * @param   string|null     $action         content action
     * @return  P4Cms_Navigation_Page_Content   provides fluent interface
     */
    public function setContentAction($action)
    {
        $this->_contentAction = $action;
        return $this;
    }

    /**
     * The url of our content entry or an empty string.
     *
     * @return string  the page's href
     */
    public function getHref()
    {
        $entry = $this->_getEntry();

        // we only return a link if we have a non-deleted entry
        if (!$entry || $entry->isDeleted()) {
            return "";
        }

        // return link to content with specified action (defaults to view if no action was specified)
        $action = $this->get('contentAction') ?: 'view';
        return $entry->getUri($action);
    }

    /**
     * Extend parent to make this entry invisble if it isn't valid.
     *
     * @param  bool $recursive  [optional] whether page should be considered
     *                          invisible if parent is invisible. Default is
     *                          false.
     * @return bool             whether page should be considered visible
     */
    public function isVisible($recursive = false)
    {
        // if no entry is accessible; hide
        if (!$this->_getEntry()) {
            return false;
        }

        // if our entry is deleted only show if we have children
        if ($this->_getEntry()->isDeleted() && !count($this->getPages())) {
            return false;
        }

        return parent::isVisible($recursive);
    }

    /**
     * Returns an array representation of the page
     *
     * @return array  associative array containing all page properties
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            array(
                'contentId'     => $this->getContentId(),
                'contentAction' => $this->getContentAction(),
                'label'         => $this->_label,
                'visible'       => $this->_visible
            )
        );
    }

    /**
     * If we have a content entry set, and no label has
     * been set on this page, returns the entries title.
     * Otherwise returns the label as per normal.
     *
     * @return string|null  page label or null
     */
    public function getLabel()
    {
        if ($this->_label) {
            return $this->_label;
        }

        $entry = $this->_getEntry();
        if (!$entry) {
            return $this->getContentId();
        }

        return $entry->getTitle();
    }

    /**
     * Returns whether page should be considered active or not.
     * A content link is considered active if the content entry is
     * currently being viewed. We determine this by looking for an
     * active (default) entry on the content entry view helper.
     *
     * @param  bool $recursive  [optional] whether page should be considered
     *                          active if any child pages are active. Default is
     *                          false.
     * @return bool             whether page should be considered active
     */
    public function isActive($recursive = false)
    {
        $layout = Zend_Layout::getMvcInstance();
        $view   = $layout ? $layout->getView() : null;
        if (!$view || $this->_active) {
            return parent::isActive($recursive);
        }

        // if we have a content entry view helper, check it for an active entry.
        $helpers = $view->getPluginLoader('helper');
        $helper  = $helpers->load('contentEntry', false)
            ? $view->getHelper('contentEntry')
            : false;
        if ($helper
            && $helper->getDefaultEntry()
            && $helper->getDefaultEntry()->getId() == $this->getContentId()
        ) {
            return true;
        }

        // check the child pages if $recursive is set
        if ($recursive) {
            foreach ($this->_pages as $page) {
                if ($page->isActive(true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns ACL resource assoicated with this page.
     * Extended to always incorporate associated content id.
     *
     * @return string|Zend_Acl_Resource_Interface|null  ACL resource or null
     */
    public function getResource()
    {
        return static::ACL_RESOURCE . ($this->_contentId
            ? '/' . $this->_contentId
            : '');
    }

    /**
     * Returns ACL privilege associated with this page
     * Extended to always return 'access' privilege.
     *
     * @return string|null  ACL privilege or null
     */
    public function getPrivilege()
    {
        return static::ACL_PRIVILEGE;
    }

    /**
     * If a content entry id has been set and it can be
     * fetched this will return it. Otherwise if no id
     * has been set or fetch fails null is returned.
     *
     * Deleted entries will be returned if possible; it
     * is up to caller to screen them if needed.
     *
     * @return P4Cms_Content|null   The associated content entry or null
     */
    protected function _getEntry()
    {
        // attempt to fetch content entry if we haven't already done so.
        if ($this->_contentEntry === false) {
            $this->_contentEntry = null;
            try {
                $id = $this->getContentId();
                if ($id) {
                    $this->_contentEntry = P4Cms_Content::fetch($id, array('includeDeleted' => true));
                }
            } catch (Exception $e) {
                // eat any exceptions we don't want to break
                // menu management or menu display
            }
        }

        return $this->_contentEntry;
    }
}
