<?php
/**
 * This is a generic grid options form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_Form_GridOptions extends P4Cms_Form_PubSubForm
{
    /**
     * Set topic and gridId from options 'namespace' param.
     * 
     * @param   array   $options    Zend provides no description for this parameter.
     */
    public function setOptions(array $options)
    {
        // set topic and gridId if namespace is defined
        if (isset($options['namespace'])) {
            $this->setTopic($options['namespace'] . '.form');
            $this->setAttrib('gridId', $options['namespace'] . '.instance');
            unset($options['namespace']);
        }

        parent::setOptions($options);
    }

    /**
     * Initialize grid options form.
     */
    public function init()
    {
        // set class to identify as p4cms-ui component
        $this->setAttrib('class',    'p4cms-ui')
             ->setAttrib('dojoType', 'p4cms.ui.grid.Form');

        // turn off CSRF protection - its not useful here (form data are
        // used for filtering the data grid and may be exposed in the URL)
        $this->setCsrfProtection(false);

        // call parent to publish the form.
        parent::init();
    }
}