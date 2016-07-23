<?php
/**
 * This is sub-form for filtering by content types utilized by the data grid.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_GridTypeFilter extends P4Cms_Form_SubForm
{
    /**
     * Initialize content types form.
     */
    public function init()
    {
        // set sub-form properties
        $this->setName('type')
             ->setAttrib('class', 'types-form')
             ->setOrder(10);

        // add field to collect list of selected content types
        $this->addElement(
            'typeGroup',
            'types',
            array(
                'label'         => 'Type',
                'autoApply'     => true,
                'order'         => 10
            )
        );
    }
}