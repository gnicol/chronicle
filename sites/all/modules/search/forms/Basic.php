<?php
/**
 * This is the basic search query form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Search_Form_Basic extends P4Cms_Form
{
    /**
     * Defines the elements that make up the seach form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // disable CSRF protection as its not needed and also to exclude the token from the query
        $this->setCsrfProtection(false);

        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui search-form');

        // add a field to collect the user's query.
        $this->addElement(
            'text',
            'query',
            array(
                'label'         => 'Search',
                'filters'       => array('StringTrim')
            )
        );

        // add the search button
        // beware - we set a ' name' attribute (notice the leading space)
        // to empty string so that this element isn't included in the request 
        // params - this works because the space ensures attrib isn't mapped to 
        // setName, but does make it into the form element at render time and
        // inputs with no name are not included in form submits.
        $this->addElement(
            'SubmitButton',
            'submit',
            array(
                'label'     => 'Search',
                'required'  => false,
                'ignore'    => true,
                'class'     => 'preferred',
                'attribs'   => array(
                    ' name' => ''
                )
            )
        );
    }
}
