<?php
/**
 * A widget that displays a flickr photo stream.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Flickr_StreamController extends P4Cms_Widget_ControllerAbstract
{
    public  $contexts   = array(
        'image'     => array('json')
    );

    /**
     * Provides information needed for initial render of javascript.
     */
    public function indexAction()
    {
        $view = $this->view;
        $widget = $this->_getWidget();

        // ensure that the api key has been set
        $values         = P4Cms_Module::fetch('Flickr')->getConfig()->toArray();
        $view->apiKey   = isset($values['key']) ? $values['key'] : '';
        
        // ensure that a type (tag/user/group) and key have been entered so the flickr search
        // can be performed
        $sourceType = $this->getOption('sourceType');
        $sourceKey  = $this->getOption('source' . $sourceType);

        if ($sourceType && $sourceKey) {
            $view->hasConfig = true;

            switch($sourceType) {
                case Flickr_Form_StreamWidget::SOURCE_TAG:
                    $view->tags         = $sourceKey;
                    break;
                case Flickr_Form_StreamWidget::SOURCE_USER:
                    $flickr             = new Zend_Service_Flickr($view->apiKey);
                    $view->userId       = $flickr->getIdByUsername($sourceKey);
                    break;
                case Flickr_Form_StreamWidget::SOURCE_GROUP:
                    $view->groupId      = $sourceKey;
                    break;
                default:
                    $view->hasConfig   = false;
            }

            $view->widgetId         = $widget->getId();
            $view->widgetRegion     = $widget->getValue('region');
            $view->imageDelay       = $this->getOption('imageDelay');
            $view->imageDimensions  = Flickr_Form_StreamWidget::$sizeDimensions[$this->getOption('imageSize')];
            $view->showTitle        = $this->getOption('showImageTitle');
        }
    }

    /**
     * Get a widget config sub-form to present additional options
     * to the user when configuring a widget instance.
     *
     * @param   P4Cms_Widget            $widget     the widget model instance being configured.
     * @return  Widget_Form_SubConfig   the sub-form to integrate into the default
     *                                  widget config form.
     */
    public static function getConfigSubForm($widget)
    {
        return new Flickr_Form_StreamWidget;
    }
}