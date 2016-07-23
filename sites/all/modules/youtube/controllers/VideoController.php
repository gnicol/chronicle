<?php
/**
 * A widget that displays a youtube video.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Youtube_VideoController extends P4Cms_Widget_ControllerAbstract
{
    /**
     * Provides information needed for initial render of the widget.
     * Parses configuration options and passes them to the view.
     */
    public function indexAction()
    {
        $view = $this->view;

        try {
            $query          = Zend_Uri::factory($this->getOption('videoUrl'))->getQueryAsArray();
            $view->videoId  = $query['v'];
        }
        catch (Zend_Uri_Exception $e) {
            // empty or invalid uri, handled by view script
        }

        $view->autoplay         = $this->getOption('autoplay');
        $view->showRelated      = $this->getOption('showRelated');
        $view->loop             = $this->getOption('loop');
        $view->playHd           = $this->getOption('playHd');

        // valid options are 1 and 3 per youtube player embed code documentation
        // http://code.google.com/apis/youtube/player_parameters.html#iv_load_policy
        $view->showAnnotations  = ($this->getOption('showAnnotations')) ? 1 : 3;

        // autohide and full screen have a dependency on control visibility - if
        // no controls are shown at all, there's no point to hiding them or showing/hiding
        // the fullscreen control
        $controls = $this->getOption('controls');
        if ($controls == Youtube_Form_VideoWidget::CONTROLS_NEVER_SHOW) {
            $view->controls = 0;
        } else {
            $view->allowFullscreen  = $this->getOption('allowFullscreen');
            $view->autohide = $controls;
            $view->controls = 1;
        }

        // if not set, the inherent default values are used by the player
        // if a custom size is set, use it, otherwise use the specified size
        $size = $this->getOption('videoSize');
        $view->size = $size;
        if ($size == Youtube_Form_VideoWidget::DIMENSION_CUSTOM) {
            $view->videoHeight  = $this->getOption('videoHeight');
            $view->videoWidth   = $this->getOption('videoWidth');
        } else if (Youtube_Form_VideoWidget::hasDimension($size)) {
            $view->videoHeight  = Youtube_Form_VideoWidget::getHeight($size);
            $view->videoWidth   = Youtube_Form_VideoWidget::getWidth($size);
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
        return new Youtube_Form_VideoWidget;
    }
}