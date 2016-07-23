<?php
/**
 * ShareThis module configuration form.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Sharethis_Form_Configure extends P4Cms_Form
{
    protected static  $_defaultOptions    = array(
        'buttonStyle'   => 'large',
        'services'      => 'sharethis, facebook, twitter, linkedin, email',
        'contentTypes'  => array('blog-post', 'press-release'),
        'publisherKey'  => ''
    );

    /**
     * Defines the elements that make up the import form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui sharethis-form-configure');

        // set dojoType attribute
        $this->setAttrib('dojoType', 'p4cms.sharethis.ConfigureForm');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to allow users specify the buttons style
        $this->addElement(
            'radio',
            'buttonStyle',
            array(
                'label'         => 'Button Style',
                'required'      => true,
                'multiOptions'  => array(
                    'large'         => 'Large (32x32)',
                    'small'         => 'Small (16x16)',
                    'vcount'        => 'Vertical Counters',
                    'hcount'        => 'Horizontal Counters'
                ),
                'value'         => static::$_defaultOptions['buttonStyle']
            )
        );

        // add a field to select services from available list
        $this->addElement(
            'text',
            'services',
            array(
                'label'         => 'Services',
                'description'   => 'Drag and drop to add or remove a service. '
                                .  'You can also drag and drop to reorder the services.',
                'value'         => static::$_defaultOptions['services']
            )
        );

        // add a multi-checkbox to collect content types with visible sharethis
        // container by default
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
                'description'   => 'Select the content types to show ShareThis buttons on by default.',
                'value'         => static::$_defaultOptions['contentTypes']
            )
        );

        // add a field to collect publisher key
        $this->addElement(
            'text',
            'publisherKey',
            array(
                'label'         => 'Publisher Key',
                'description'   => 'Provide your ShareThis key for tracking purposes.'
            )
        );

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
     * Normalize the given options array to ensure it contains
     * all of the expected options:
     *  - buttonStyle
     *  - services (as an array)
     *  - contentTypes
     *  - publisherKey
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

        // convert services to an array
        $services = is_array($options['services'])
            ? $options['services']
            : array_filter(explode(',', $options['services']));
        $options['services'] = array_map('trim', $services);

        return $options;
    }
}