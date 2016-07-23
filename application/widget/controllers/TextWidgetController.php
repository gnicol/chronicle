<?php
/**
 * A widget that displays arbitrary text in a region.
 * The text can be edited.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_TextWidgetController extends P4Cms_Widget_ControllerAbstract
{
    /**
     * Display the text stored in the widget.
     */
    public function indexAction()
    {
        // disable autorendering for this action.
        $this->_helper->viewRenderer->setNoRender();

        // apply macro expansion so users can embed macros
        // branchify urls so they point to the active branch
        $text      = $this->getOption('text');
        $macro     = new P4Cms_Filter_Macro(array('widget' => $this->_getWidget()));
        $branchify = new P4Cms_Filter_BranchifyUrls;

        print $branchify->filter($macro->filter($text));
    }

    /**
     * Get config sub-form to present additional options when
     * configuring a widget of this type.
     *
     * @param   P4Cms_Widget            $widget     the widget instance being configured.
     * @return  Zend_Form_SubForm|null  the sub-form to integrate into the default
     *                                  widget config form or null for no sub-form.
     */
    public static function getConfigSubForm($widget)
    {
        // if we are configured to solidify macros expand macros the first
        // time the widget is configured and clear the solidify flag.
        if ($widget->getConfig('solidifyMacros')) {
            $filter = new P4Cms_Filter_Macro(array('widget' => $widget));
            $text   = $filter->filter($widget->getConfig('text'));
            $widget->getConfig()->text = $text;
            $widget->getConfig()->solidifyMacros = false;
        }

        // de-branchify urls so that they do not get edited
        // or stored with a branch specific base url.
        $filter = new P4Cms_Filter_DebranchifyUrls;
        $text   = $filter->filter($widget->getConfig('text'));
        $widget->getConfig()->text = $text;

        return new Widget_Form_TextWidget;
    }
}
