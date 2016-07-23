<?php
/**
 * This is the site configure form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Form_Configure extends P4Cms_Form_PubSubForm
{
    /**
     * Defines the elements that make up the site settings form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // set the pub/sub topic so others can influence form
        $this->setTopic('p4cms.site.configure.form');

        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui site-form site-configure-form');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the site's title
        $this->addElement(
            'text',
            'title',
            array(
                'label'         => 'Title',
                'required'      => true,
                'filters'       => array('StringTrim'),
                'size'          => 30,
                'order'         => 20
            )
        );

        // add a field to collect the site description.
        $this->addElement(
            'textarea',
            'description',
            array(
                'label'         => 'Description',
                'rows'          => 3,
                'cols'          => 56,
                'required'      => false,
                'filters'       => array('StringTrim'),
                'description'   => "Enter a short summary of your site.<br/>"
                                .  "This summary will appear in meta description tags for non-content pages.",
                'order'         => 40
            )
        );
        $this->getElement('description')
             ->getDecorator('Description')
             ->setEscape(false);

        // add a field to collect the site's robots.txt content
        $this->addElement(
            'textarea',
            'robots',
            array(
                'label'         => 'robots.txt',
                'rows'          => 3,
                'cols'          => 56,
                'description'   => "Provide the contents for the site's robots.txt file.",
                'order'         => 50,
                'validators'    => array(array('RobotsTxt'))
            )
        );
        $this->getElement('robots')
             ->getDecorator('label')
             ->setOption(
                'helpUri',
                Zend_Controller_Front::getInstance()->getBaseUrl()
                . '/' . Ui_Controller_Helper_HelpUrl::HELP_BASE_URL . '/'
                . 'sites.management.html#robots'
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
                'order' => 200
            )
        );
    }
}