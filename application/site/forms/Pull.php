<?php
/**
 * Form to pull changes from one branch to another.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Form_Pull extends P4Cms_Form
{
    const       MODE_MERGE  = 'merge';
    const       MODE_COPY   = 'copy';

    protected   $_pathGroup = null;

    /**
     * Setup form to collect pull information.
     */
    public function init()
    {
        // set the method for the form to POST
        $this->setMethod('post');

        // set id prefix to avoid collisions with other in-page forms.
        $this->setIdPrefix('pull');

        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui pull-form');

        // add a hidden element to track the headChange (to 'pin' the source)
        $this->addElement('hidden', 'headChange');

        // generate list of possible source branches.
        $options  = array();
        $active   = P4Cms_Site::fetchActive();
        $branches = P4Cms_Site::fetchAll(
            array(
                P4Cms_Site::FETCH_BY_SITE   => $active->getSiteId(),
                P4Cms_Site::FETCH_BY_ACL    => array('branch', 'pull-from'),
                P4Cms_Site::FETCH_SORT_FLAT => true
            )
        );
        foreach ($branches as $branch) {
            if ($branch->getId() != $active->getId()) {
                $options[$branch->getId()] = $branch->getStream()->getName();
            }
        }

        $this->addElement(
            'select',
            'source',
            array(
                'label'         => 'From',
                'required'      => true,
                'multiOptions'  => $options
            )
        );

        $this->addElement(
            'text',
            'target',
            array(
                'label'     => 'To',
                'required'  => true,
                'readOnly'  => true,
                'value'     => $active->getStream()->getName()
            )
        );

        $pathGroup   = $this->getPathGroup();
        $source      = $pathGroup->getValue('source');
        $mode        = $pathGroup->getValue('mode');
        $sourceName  = $source instanceof P4Cms_Site
            ? $this->getView()->escape($source->getStream()->getName())
            : null;

        $this->addElement(
            'radio',
            'mode',
            array(
                'label'         => 'Mode',
                'required'      => true,
                'value'         => $mode,
                'escape'        => false,
                'multiOptions'  => array(
                    static::MODE_MERGE  => 'Update'
                                        .  '<span class=description>Pull items that have changed in the '
                                        .  ($sourceName ?: 'from') . ' branch</span>',
                    static::MODE_COPY   => 'Clone'
                                        .  '<span class=description>Make items identical to the '
                                        .  ($sourceName ?: 'from') . ' branch</span>',
                )
            )
        );

        // add a checklist to control which paths are included.
        $pathOptions = $this->_getPathOptions();
        $this->addElement(
            'nestedCheckbox',
            'paths',
            array(
                'label'         => 'Select What to Pull',
                'required'      => true,
                'escape'        => false,
                'emptyText'     => "Nothing to pull"
                                .  ($sourceName ? " from $sourceName." : '.'),
                'description'   => '<span class="header-items">Items</span>'
                                .  '<span class="header-quantity">Quantity</span>',
                'onClick'       => "p4cms.ui.toggleChildCheckboxes(this);"
                                .  "p4cms.ui.toggleParentCheckbox(this);"
            ) + $pathOptions
        );

        $this->getElement('paths')
             ->getDecorator('description')
             ->setOption('placement', 'prepend')
             ->setEscape(false);

        $conflicts = $this->getPathGroup()->getCount(
            array(Site_Model_PullPathGroup::RECURSIVE, Site_Model_PullPathGroup::ONLY_CONFLICTS)
        );
        if ($conflicts) {
            $message = '<span class="conflict-note">'
                     . 'Changes to <span class="count">' . $conflicts . '</span>'
                     . ' <span class="items">item' . ($conflicts > 1 ? 's' : '') . '</span>'
                     . ' in the ' . $active->getStream()->getName() . ' branch will be overwritten.'
                     . '</span>';

            $this->addElement(
                'note',
                'note',
                array(
                    'value'  => $message,
                )
            );
        }

        // add the submit button (disable it if no pull paths)
        $this->addElement(
            'SubmitButton',
            'pull',
            array(
                'label'     => 'Pull',
                'class'     => 'preferred',
                'required'  => false,
                'ignore'    => true,
                'disabled'  => !$this->getElement('paths')->getMultiOptions()
            )
        );

        // put the button in a fieldset.
        $this->addDisplayGroup(
            array('pull'),
            'buttons',
            array(
                'class' => 'buttons',
                'order' => 100
            )
        );
    }

    /**
     * Set the path group object to inform path inclusion controls.
     *
     * @param   Site_Model_PullPathGroup    $pathGroup  the path groupings
     * @return  Site_Form_Pull              provides fluent interface.
     */
    public function setPathGroup(Site_Model_PullPathGroup $pathGroup)
    {
        $this->_pathGroup = $pathGroup;

        return $this;
    }

    /**
     * Get the path group object to inform path inclusion controls.
     *
     * @return  Site_Model_PullPathGroup    the path groupings.
     */
    public function getPathGroup()
    {
        if (!$this->_pathGroup instanceof Site_Model_PullPathGroup) {
            throw new Site_Exception(
                "Cannot get path group. No path group has been set."
            );
        }

        return $this->_pathGroup;
    }

    /**
     * Generate element options suitable for use with a nested checkbox
     * for all paths and sub-groups in the given pull path group object.
     *
     * Provides the 'multiOptions' and 'value' keys. Label and other
     * details should be added by caller.
     *
     * This function is recursive to deal with the potential for groups
     * to contain sub-groups.
     *
     * @param   Site_Model_PullPathGroup|null   $pathGroup  the path group to use or null to
     *                                                      use the group set on the instance
     * @return  array                           the options for use with nested checkbox
     */
    protected function _getPathOptions(Site_Model_PullPathGroup $pathGroup = null)
    {
        $options   = array();
        $values    = array();
        $class     = array();
        $readOnly  = array();
        $pathGroup = $pathGroup ?: $this->getPathGroup();
        $subGroups = $pathGroup->getSubGroups();
        $subGroups->sortBy(
            array(
                'order'         => array($subGroups::SORT_NUMERIC),
                'pullByDefault' => array($subGroups::SORT_NUMERIC, $subGroups::SORT_DESCENDING),
                'label'         => array($subGroups::SORT_NATURAL, $subGroups::SORT_NO_CASE)
            )
        );

        foreach ($subGroups as $subGroup) {
            // get options/values for this sub-group recursively
            $subResult = $this->_getPathOptions($subGroup);

            // skip empty path groups.
            if (!$subResult['multiOptions'] && !$subGroup->getValue('paths')->count()) {
                continue;
            }

            // option key is the group id.
            $key = $subGroup->getId();

            // compose option label from the group's label and a
            // count of the paths within the group (recursively).
            $label    = $subGroup->getValue('label');
            $classes  = $subGroup->getValue('hideCount') ? ' hidden' : '';
            $classes .= $subGroup->getIncludePaths() ? ' has-paths' : ' no-paths';
            $label   .= '<span class="count-column' . $classes . '">'
                     .  ' <span class="count">' . $subGroup->getCount(Site_Model_PullPathGroup::RECURSIVE) . '</span>'
                     .  ' <span class="conflict-count">' .  $subGroup->getConflictCount() . '</span>'
                     .  '</span>';

            $options[$key] = $label;

            // if sub-group indicates it should be read-only add it
            // to our list of read-only elements
            if ($subGroup->getValue('readOnly')) {
                $readOnly[] = $key;
            }

            // if sub-group indicates it should be pulled by default
            // add it to our values array so it is checked
            if ($subGroup->getValue('pullByDefault')) {
                $values[] = $key;
            }

            // add a class to identify entries with conflicts
            if ($subGroup->getConflictCount()) {
                $class[$key] = 'conflict';
            }

            // the sub-group multiOptions come immediately after our own
            if ($subResult['multiOptions']) {
                $options[$key . '-subGroups'] = $subResult['multiOptions'];
            }

            // include any values provided by the sub-group
            if ($subResult['value']) {
                $values = array_merge($values, $subResult['value']);
            }
            if ($subResult['readOnly']) {
                $readOnly = array_merge($readOnly, $subResult['readOnly']);
            }
            if ($subResult['class']) {
                $class = array_merge($class, $subResult['class']);
            }
        }

        return array(
            'multiOptions' => $options,
            'readOnly'     => $readOnly,
            'value'        => $values,
            'class'        => $class
        );
    }
}