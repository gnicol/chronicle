<?php
/**
 * A form to control how comments behave per-content-entry.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Comment_Form_Content extends P4Cms_Form_SubForm
{
    protected static  $_defaultOptions    = array(
        'allowComments'     => true,
        'requireLoginPost'  => false,
        'requireApproval'   => false,
        'showComments'      => true,
        'requireLoginView'  => false,
        'allowVoting'       => true,
        'oneVotePerUser'    => true
    );

    /**
     * Defines the elements of the content comment sub-form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $this->setLegend('Comments');

        $this->addElement(
            'checkbox',
            'allowComments',
            array(
                'label'     => 'Allow Comments',
                'value'     => true,
                'onClick'   => $this->_toggleCheckbox('requireLoginPost')
                            .  $this->_toggleCheckbox('requireApproval')
            )
        );

        $this->addElement(
            'checkbox',
            'requireLoginPost',
            array(
                'label'     => 'Require Login to Post',
                'class'     => 'sub-checkbox',
                'disabled'  => true
            )
        );

        $this->addElement(
            'checkbox',
            'requireApproval',
            array(
                'label'     => 'Require Approval',
                'class'     => 'sub-checkbox',
                'disabled'  => true
            )
        );        
        
        $this->addElement(
            'checkbox',
            'showComments',
            array(
                'label'     => 'Show Comments',
                'value'     => true,
                'onClick'   => $this->_toggleCheckbox('requireLoginView')
            )
        );

        $this->addElement(
            'checkbox',
            'requireLoginView',
            array(
                'label'     => 'Require Login to View',
                'class'     => 'sub-checkbox',
                'disabled'  => true
            )
        );

        $this->addElement(
            'checkbox',
            'allowVoting',
            array(
                'label'     => 'Allow Voting',
                'value'     => true,
                'onClick'   => $this->_toggleCheckbox('oneVotePerUser')
            )
        );

        $this->addElement(
            'checkbox',
            'oneVotePerUser',
            array(
                'label'     => 'One Vote Per-User',
                'value'     => true,
                'class'     => 'sub-checkbox',
                'disabled'  => true
            )
        );
        
        // put the checkbox inputs before their labels.
        foreach ($this->getElements() as $element) {
            P4Cms_Form::moveCheckboxLabel($element);
        }
    }

    /**
     * Extended to enable the 'require login to post/view' checkboxes
     * when the 'allow/show comments' checkboxes are checked.
     *
     * @param   P4Cms_Record|array  $defaults   the default values to set on elements
     * @return  Zend_Form           provides fluent interface
     */
    public function setDefaults($defaults)
    {
        parent::setDefaults($defaults);

        if ($this->getValue('allowComments')) {
            $this->getElement('requireLoginPost')->setAttrib('disabled', null);
            $this->getElement('requireApproval')->setAttrib('disabled', null);
        }
        if ($this->getValue('showComments')) {
            $this->getElement('requireLoginView')->setAttrib('disabled', null);
        }
        if ($this->getValue('allowVoting')) {
            $this->getElement('oneVotePerUser')->setAttrib('disabled', null);
        }

        return $this;
    }
    
    /**
     * Normalize the given options array to ensure it contains
     * all of the expected options.
     * 
     *  - allowComments
     *  - requireLoginPost
     *  - requireApproval
     *  - showComments
     *  - requireLoginView
     *  - allowVoting
     *  - oneVotePerUser
     *
     * @param   array   $options    the options array to normalize.
     * @return  array   the normalized options.
     */
    public static function getNormalizedOptions($options)
    {
        $options = array_merge(
            static::$_defaultOptions,
            (array) $options
        );

        // cast all options to booleans.
        foreach ($options as &$option) {
            $option = ($option == true);
        }

        return $options;
    }
    
    /**
     * Get javascript code to toggle the given checkbox when called
     * from the js event of another element in the form.
     * 
     * @param   string  $checkbox   the name of the checkbox to toggle
     * @return  string  the js to toggle the checkbox.
     */
    protected function _toggleCheckbox($checkbox)
    {
        return "var query = 'input[type=checkbox][name=\\'comments[" . $checkbox . "]\\']';"
            .  "var input = dojo.query(query, this.form)[0];"
            .  "dojo.attr(input, 'disabled', !this.checked);";
    }
}
