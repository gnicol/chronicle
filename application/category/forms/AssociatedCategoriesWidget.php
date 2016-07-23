<?php
/**
 * This is the text widget configuration form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_Form_AssociatedCategoriesWidget extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the config form for the
     * associated categories widget.
     *
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $this->addElement(
            'editor',
            'preamble',
            array(
                'label'         => 'Preamble',
                'required'      => true,
                'description'   => "Enter the text to display before category associations.",
                'attribs'       => array(
                    "height"    => 200,
                    "plugins"   => "['undo','redo','|','bold','italic','underline',"
                                .  "'strikethrough','|','justifyLeft','justifyRight',"
                                .  "'justifyCenter','justifyFull']"
                )
            )
        );
    }
}
