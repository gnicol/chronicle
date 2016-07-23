<?php
/**
 * This is the generic search sub-form utilized by the data grid.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_Form_GridSearch extends P4Cms_Form_SubForm
{
    /**
     * Initialize grid search form.
     */
    public function init()
    {
        $this->setName('search')
             ->setAttrib('class', 'search-form');

        // set order to place this sub-form to the top of the list
        $this->setOrder(-10);

        // add field to collect the search query
        $this->addElement(
            'Text',
            'query',
            array(
                'label'     => 'Search',
                'filters'   => array('StringTrim'),
                'autoApply' => true
            )
        );
    }
}