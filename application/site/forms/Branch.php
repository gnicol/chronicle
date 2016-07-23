<?php
/**
 * Form to add site branches.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Form_Branch extends P4Cms_Form
{
    /**
     * Setup form to collect branch information.
     */
    public function init()
    {
        // set the method for the form to POST
        $this->setMethod('post');

        // set id prefix to avoid collisions with other in-page forms.
        $this->setIdPrefix('branch');

        $this->addElement('hidden', 'id', array('ignore' => true));

        $this->addElement(
            'text',
            'name',
            array(
                'label'         => 'Name',
                'required'      => true,
                'filters'       => array('StringTrim')
            )
        );

        // generate list of possible source sites
        $siteOptions  = array();
        $fetchOptions = array(P4Cms_Site::FETCH_BY_ACL => array('branch', 'pull-from'));
        foreach (P4Cms_Site::fetchAll($fetchOptions) as $site) {
            if (!array_key_exists($site->getSiteId(), $siteOptions)) {
                $siteOptions[$site->getSiteId()] = $site->getConfig()->getTitle();
            }
        }
        $this->addElement(
            'select',
            'site',
            array(
                'label'         => 'Site',
                'required'      => true,
                'multiOptions'  => $siteOptions
            )
        );

        $this->addElement(
            'select',
            'parent',
            array(
                'label'         => 'Branch From',
                'required'      => true
            )
        );
        $this->_updateParentOptions();

        // add a field to collect the site description.
        $this->addElement(
            'textarea',
            'description',
            array(
                'label'         => 'Description',
                'rows'          => 3,
                'cols'          => 56,
                'required'      => false,
                'filters'       => array('StringTrim')
            )
        );

        // add a field to collect the branch's urls.
        $this->addElement(
            'textarea',
            'urls',
            array(
                'label'         => 'Branch Address',
                'rows'          => 3,
                'cols'          => 56,
                'description'   => "Optionally provide a list of urls for which this branch will be served.<br/>"
                                .  "For example: dev.domain.com, stage.domain.com"
            )
        );
        $this->getElement('urls')
             ->getDecorator('Description')
             ->setEscape(false);

        // add the submit button
        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'label'     => 'Save',
                'class'     => 'preferred',
                'required'  => false,
                'ignore'    => true
            )
        );

        // put the button in a fieldset.
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array(
                'class' => 'buttons',
                'order' => 100
            )
        );
    }

    /**
     * Validate the form, ensure id isn't empty (id is based on
     * a filtered version of the name).
     *
     * @param  array    $data   the data to validate.
     * @return boolean
     */
    public function isValid($data)
    {
        $isValid = parent::isValid($data);

        $name    = isset($data['name']) ? $data['name'] : '';
        $filter  = new P4Cms_Filter_TitleToId;
        if ($name && !$filter->filter($name)) {
            $this->getElement('name')->addError(
                "Name must contain at least one letter or number."
            );
            $isValid = false;
        }

        // ensure branch urls are unique within all branches
        return $isValid && !$this->_isBranchAddressTaken();
    }

    /**
     * Override parent to update 'site' and 'parent' elements options when form is populated.
     *
     * @param   P4Cms_Record|array  $defaults   the default values to set on elements
     * @return  Site_Form_Branch    provides fluent interface
     */
    public function setDefaults($defaults)
    {
        parent::setDefaults($defaults);
        $this->_updateParentOptions();

        return $this;
    }

    /**
     * Set options for 'parent' element.
     */
    protected function _updateParentOptions()
    {
        // deal with edit and add cases separately
        //   add: determine site from form's site value, fall back to first site
        //  edit: determine site from existing branch, remove site element and,
        //        if editing a mainline, remove parent element (mainline can't have parent)
        $parentId = null;
        $branchId = $this->getValue('id');
        if (!$branchId) {
            $siteElement = $this->getElement('site');
            $options     = $siteElement->getMultiOptions();
            reset($options);    // we need this reset as the cursor isn't on the first option
            $siteId      = $siteElement->getValue() ?: key($options);
        } else {
            $site        = P4Cms_Site::fetch($branchId);
            $siteId      = $site->getSiteId();
            $stream      = $site->getStream();
            $parentId    = $stream->getParent();

            // we are editing, its impossible to change the site, so we remove the element
            $this->removeElement('site');

            // if we are editing the mainline, no need for parent at all
            // and therefore nothing more to do in this method (return)
            if ($stream->getType() === 'mainline') {
                $this->removeElement('parent');
                return;
            }
        }

        // generate parent options (filter for branches on selected site)
        $options  = array();
        $disabled = array();
        $exclude  = array();
        $user     = P4Cms_User::fetchActive();
        $branches = P4Cms_Site::fetchAll(array(P4Cms_Site::FETCH_BY_SITE => $siteId));
        foreach ($branches as $branch) {
            // during edit, exclude the branch we are editing and all of its children
            // (prevent user from trying to move branch under itself)
            $stream = $branch->getStream();
            $id     = $branch->getId();
            if ($id === $branchId || in_array($stream->getParent(), $exclude)) {
                if (!in_array($id, $exclude)) {
                    $exclude[] = $id;
                }
                continue;
            }

            // disable branches that we are not allowed to pull-from.
            // unless we are editing and that branch is already our parent
            if ($id !== $parentId && !$user->isAllowed('branch', 'pull-from', $branch->getAcl())) {
                $disabled[] = $id;
            }

            // indent option label according to branch depth
            $prefix       = str_repeat(static::UTF8_NBSP, $stream->getDepth() * 2);
            $options[$id] = $prefix . $stream->getName();
        }

        $this->getElement('parent')
             ->setMultiOptions($options)
             ->setAttrib('disable', $disabled);
    }

    /**
     * Helper function to ensure that branch urls are not taken.
     *
     * @param   boolean     $setError   optional - whether to set error on urls
     *                      element if url was already taken (true by default)
     * @return  boolean     true if any of the urls present in 'urls' field value
     *                      has already been taken by some other branch
     */
    protected function _isBranchAddressTaken($setError = true)
    {
        // prepare callback function to return given url with no schema
        $normalizeUrlCallback = function($url)
        {
            $url = stripos($url, 'http://')  === 0 ? substr($url, 7) : $url;
            $url = stripos($url, 'https://') === 0 ? substr($url, 8) : $url;

            return $url;
        };

        // compose list of taken branch urls
        $branchId = $this->getValue('id');
        $taken    = array();
        foreach (P4Cms_Site::fetchAll() as $branch) {
            if ($branch->getId() !== $branchId) {
                $taken = array_merge(
                    $taken,
                    array_map($normalizeUrlCallback, $branch->getConfig()->getUrls())
                );
            }
        }

        // check if any url passed in urls element is already taken
        $urls    = $this->getValue('urls');
        $urls    = array_filter(array_map('trim', preg_split("/\n|,/", $urls)));
        $isTaken = false;
        foreach ($urls as $url) {
            $url = $normalizeUrlCallback(trim($url));
            if (in_array($url, $taken)) {
                $isTaken = true;
                if ($setError) {
                    $this->getElement('urls')->addError(
                        "Url '$url' is already taken by other branch."
                    );
                }
            }
        }

        return $isTaken;
    }
}