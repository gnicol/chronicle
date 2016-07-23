<?php
/**
 * A drop-down button with a tooltip dialog.
 *
 * To populate the tooltip dialog, set the href or
 * content properties of the element.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Element_TooltipDialogButton extends Zend_Dojo_Form_Element_Dijit
{
    public      $helper             = 'CustomDijit';

    protected   $_href              = null;
    protected   $_content           = null;
    protected   $_dialogDojoType    = 'p4cms.ui.TooltipDialog';

    /**
     * Dijit parameters
     * @var array
     */
    public $dijitParams = array(
        'dojoType' => 'dijit.form.DropDownButton',
    );

    /**
     * Set the label to display in the button.
     * We implement this method to copy the label to dijit params.
     *
     * @param   string  $label  the label to display in the button.
     * @return  P4Cms_Form_Element_TooltipDialogButton  provides fluent interface.
     */
    public function setLabel($label)
    {
        $this->dijitParams['label'] = $label;
        parent::setLabel($label);

        return $this;
    }

    /**
     * Set the href to use for the tooltip dialog.
     *
     * @param   string  $href   the href to pass set on the tooltip dialog.
     * @return  P4Cms_Form_Element_TooltipDialogButton  provides fluent interface.
     */
    public function setHref($href)
    {
        $this->_href = (string) $href;

        return $this;
    }

    /**
     * Set the content to put in the tooltip dialog.
     *
     * @param   string  $content    the content to put in the tooltip dialog.
     */
    public function setContent($content)
    {
        $this->_content = (string) $content;

        return $this;
    }

    /**
     * Get the rendered tooltip dialog. The custom dijit helper uses the
     * value of the element to populate the content of the dijit - that is
     * why we use getValue() to provide the rendered dialog.
     *
     * @return  string  the rendered tooltip dialog.
     */
    public function getValue()
    {
        $view = $this->getView();

        return $view->customDijit(
            $this->getId() . '-tooltip-dialog',
            $this->_content,
            $this->_getTooltipAttribs()
        );
    }

    /**
     * Default decorators
     *
     * Uses only 'DijitElement' and 'DtDdWrapper' decorators by default.
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('DijitElement')
                 ->addDecorator('DtDdWrapper');
        }
    }

    /**
     * Get the attributes to set on the tooltip element.
     *
     * @return  array   key/value attributes for the dijit element.
     */
    protected function _getTooltipAttribs()
    {
        return array_merge(
            $this->getAttribs(),
            array(
                'dojoType'  => $this->_dialogDojoType,
                'href'      => $this->_href
            )
        );
    }
}
