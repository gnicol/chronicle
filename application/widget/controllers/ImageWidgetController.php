<?php
/**
 * A widget that displays an arbitrary image in a region.
 * The image can be updated (replaced).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_ImageWidgetController extends P4Cms_Widget_ControllerAbstract
{
    /**
     * Output the widgets html; uses an img tag to call imageAction if configured
     */
    public function indexAction()
    {
        $widget = $this->_getWidget();

        // if we have an image setting, and it is valid, determine the src.
        $url         = $this->getOption('imageUrl');
        $contentId   = $this->getOption('contentId');
        $imageSource = $this->getOption('imageSource');
        $isLocal     = $imageSource === 'content';
        $isRemote    = $imageSource === 'remote';

        if ($isLocal && $contentId) {
            $imageSrc = $this->getHelper('url')->url(
                array(
                    'module'        => 'content',
                    'controller'    => 'index',
                    'action'        => 'image',
                    'id'            => $contentId,
                    'width'         => $this->getOption('imageWidth'),
                    'height'        => $this->getOption('imageHeight')
                )
            );
        } else if ($isRemote && $url) {
            $filter   = new P4Cms_Filter_Macro(array('widget' => $widget));
            $imageSrc = $filter->filter($url);
        }

        // setup the view.
        $this->view->widget   = $widget;
        $this->view->imageSrc = isset($imageSrc) ? $imageSrc : null;
    }

    /**
     * Get config sub-form to present additional options when
     * configuring a widget of this type.
     *
     * @param   P4Cms_Widget            $widget     the widget instance being configured.
     * @return  Zend_Form_SubForm       the sub-form to integrate into the default
     *                                  widget config form or null for no sub-form.
     */
    public static function getConfigSubForm($widget)
    {
        return new Widget_Form_ImageWidget;
    }
}
