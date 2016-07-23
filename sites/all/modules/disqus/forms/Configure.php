<?php
/**
 * This is the Disqus module config form.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Disqus_Form_Configure extends P4Cms_Form
{
    const   SHORT_NAME   = 'shortName';

    protected static  $_defaultOptions  = array(
        'contentTypes'  => array('basic-page', 'blog-post', 'press-release')
    );

    /**
     * Defines the elements that make up the config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui disqus-form-configure');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the Disqus short name.
        $this->addElement(
            'text',
            static::SHORT_NAME,
            array(
                'label'         => 'Disqus Short Name',
                'required'      => true,
                'description'   => 'Your shortname is a unique identifier which references your site.',
                'filters'       => array('StringTrim')
            )
        );

        // add a multi-checkbox to collect content types for
        // default enabling of Disqus comments
        $types = P4Cms_Content_Type::fetchAll();
        $this->addElement(
            'multiCheckbox',
            'contentTypes',
            array(
                'label'         => 'Content Types',
                'multiOptions'  => array_combine(
                    $types->invoke('getId'),
                    $types->invoke('getLabel')
                ),
                'description'   => 'Select the content types to show Disqus conversations on by default.',
                'value'         => static::$_defaultOptions['contentTypes']
            )
        );

        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'label'         => 'Save',
                'required'      => false,
                'ignore'        => true,
                'class'         => 'preferred'
            )
        );

        // put the buttons in a fieldset.
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array('class'       => 'buttons')
        );
    }

    /**
     * Normalize the given options array to ensure it contains
     * all of the expected options:
     *  - contentTypes
     *
     * @param   array   $options    the options array to normalize
     * @return  array   the normalized options
     */
    public static function getNormalizedOptions($options)
    {
        $options = array_merge(
            static::$_defaultOptions,
            (array) $options
        );

        // ensure contentTypes is an array
        $options['contentTypes'] = (array) $options['contentTypes'];

        return $options;
    }
}