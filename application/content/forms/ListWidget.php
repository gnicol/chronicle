<?php
/**
 * This is the content list widget configuration form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_ListWidget extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the recent content widget config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // add option to limit number of displayed content entries.
        $options = array('' => 'Unlimited')
                 + array_combine(range(1, 10),       range(1, 10))
                 + array_combine(range(15, 50, 5),   range(15, 50, 5))
                 + array_combine(range(60, 100, 10), range(60, 100, 10));
        $this->addElement(
            'select',
            'count',
            array(
                'label'         => 'Maximum Items',
                'description'   => "Enter the maximum number of content entries to display.",
                'multiOptions'  => $options
            )
        );

        // checkbox to show/hide icon
        $this->addElement(
            'checkbox',
            'showIcons',
            array(
                'label'         => 'Show Icons'
            )
        );

        // content type filter (checklist) so users can pick multiple content types
        $this->addElement(
            'typeGroup',
            'contentType',
            array(
                'label'         => 'Content Types'
            )
        );

        // build a list of element names and labels for later use
        $fieldLabels = array();
        $fieldNames  = array();
        $types       = P4Cms_Content_Type::fetchAll();
        foreach ($types->invoke('getFormElements') as $elements) {
            foreach ($elements as $element) {
                $name  = $element->getName();
                $label = $element->getLabel();
                if (!isset($fieldLabels[$name])) {
                    $fieldLabels[$name] = array();
                }
                if (!isset($fieldNames[$label])) {
                    $fieldNames[$label] = array();
                }

                if (!in_array($label, $fieldLabels[$name])) {
                    $fieldLabels[$name][] = $label;
                }
                if (!in_array($name, $fieldNames[$label])) {
                    $fieldNames[$label][] = $name;
                }
            }
        }

        // Build sort field multioptions based on the following rules:
        // If a given field label appears for multiple keys, add the key name to the option
        // to help disambiguate them: Title (title), Title (articleTitle)
        // If a content type field 'key' has various labels, list all of the
        // unique/nat-case-sorted labels for that key (as a single select option):
        // Article Title, Page Title, Title
        $sortMultiOptions = array();
        foreach ($fieldLabels as $name => $labels) {
            natcasesort($labels);
            $sortMultiOptions[$name] = implode(', ', $labels);

            foreach ($labels as $label) {
                if (count($fieldNames[$label]) > 1) {
                    $sortMultiOptions[$name] .= ' (' . $name . ')';
                    break;
                }
            }
        }

        // add Last Modified, which is a non-element sort option built into P4Cms_Record_Query
        $sortMultiOptions[P4Cms_Record_Query::SORT_DATE] = 'Last Modified';
        natcasesort($sortMultiOptions);

        $sortOrder = array(
            P4Cms_Record_Query::SORT_ASCENDING  => 'Ascending',
            P4Cms_Record_Query::SORT_DESCENDING => 'Descending'
        );

        $this->addElement(
            'select',
            'primarySortField',
            array(
                'label'         => 'Primary Sort',
                'multiOptions'  => $sortMultiOptions
            )
        );

        $this->getElement('primarySortField')
             ->getDecorator('htmlTag')
             ->setOption('class', 'content-list-config-sort-field');

        $this->addElement(
            'select',
            'primarySortOrder',
            array(
                'multiOptions'  => $sortOrder
            )
        );

        $this->getElement('primarySortOrder')
             ->getDecorator('label')
             ->setOption('tagClass', 'content-list-config-sort-order');

        $this->addElement(
            'select',
            'secondarySortField',
            array(
                'label'         => 'Secondary Sort',
                'multiOptions'  => array('' => '') + $sortMultiOptions
            )
        );

        $this->getElement('secondarySortField')
             ->getDecorator('htmlTag')
             ->setOption('class', 'content-list-config-sort-field');

        $this->addElement(
            'select',
            'secondarySortOrder',
            array(
                'multiOptions'  => $sortOrder
            )
        );

        $this->getElement('secondarySortOrder')
             ->getDecorator('label')
             ->setOption('tagClass', 'content-list-config-sort-order');

        // add checkbox to enable/disable RSS
        $this->addElement(
            'checkbox',
            'showRssLink',
            array(
                'label' => 'Show RSS Link',
                'onClick'      => "if (this.checked) {"
                                .  " p4cms.ui.show(dojo.query('#feedDetails-element fieldset')[0]);"
                                .  "} else {"
                                .  " p4cms.ui.hide(dojo.query('#feedDetails-element fieldset')[0]);"
                                .  "}",
            )
        );

        $this->addElement(
            'text',
            'feedTitle',
            array(
                'label'       => 'Feed Title'
            )
        );

        $this->addElement(
            'textarea',
            'feedDescription',
            array(
                'label'       => 'Feed Description',
                'rows'        => 2,
                'cols'        => 35
            )
        );

        $this->addDisplayGroup(
            array('feedTitle', 'feedDescription'),
            'feedDetails',
            array(
                'class'    => 'feed-details',
            )
        );
    }

    /**
     * Extend parent to hide feed details display group if 'showRssLink' is set to false.
     *
     * @param   array       $defaults       Zend provides no documentation for this
     * @return  Content_Form_ListWidget     provides fluent interface
     */
    public function setDefaults(array $defaults)
    {
        parent::setDefaults($defaults);

        // hide feed details group if showRssLink is unchecked
        if (!$this->getValue('showRssLink')) {
            $group = $this->getDisplayGroup('feedDetails');
            $group->setAttrib('class', $group->getAttrib('class') . ' hidden');
        }

        return $this;
    }
}
