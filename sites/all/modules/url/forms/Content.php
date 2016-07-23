<?php
/**
 * A sub-form intended for use with content forms.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Url_Form_Content extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the content url form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // set the title of this form.
        $this->setLegend('URL');

        // field to capture url path, filtered to strip leading/trailing 
        // slashes and to normalize special character encoding.
        $this->addElement(
            'text',
            'path',
            array(
                'label'         => 'Path', 
                'filters'       => array(new Url_Filter_UrlPath)
            )
        );

        $this->addElement(
            'radio',
            'auto',
            array(
                'value'         => 'true',
                'multiOptions'  => array(
                    'true'  => 'Use Title for URL',
                    'false' => 'Custom'
                ),
                'onClick'       => "
                    var query   = '[dojoType=p4cms.content.SubForm]';
                    var subForm = dijit.byNode(new dojo.NodeList(this).closest(query)[0]);
                    var entry   = subForm.contentEntry;
                    var path    = dojo.query('input[name*=\'url[path]\']', this.form)[0];
                    if (path) {
                        dojo.attr(path, 'readOnly', this.value === 'true');
                    }
                    if (this.value === 'true') {
                        dojo.attr(path, 'value', p4cms.url.autoGenerate(entry));
                    }
                "
            )
        );
        $this->getElement('auto')->removeDecorator('label');
    }
    
    /**
     * Make path field read only when auto is selected.
     * 
     * @param   array       $defaults   the values to set on the form
     * @return  Zend_Form   provides fluent interface
     */
    public function setDefaults(array $defaults)
    {
        parent::setDefaults($defaults);
        
        if ($this->getValue('auto') === 'true') {
            $this->getElement('path')->setOptions(array('readOnly' => true));
        }
    }
}