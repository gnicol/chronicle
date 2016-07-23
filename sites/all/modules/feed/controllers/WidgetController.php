<?php
/**
 * A simple RSS/Atom widget.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Feed_WidgetController extends P4Cms_Widget_ControllerAbstract
{
    /**
     * Present the configured rss/atom feed.
     */
    public function indexAction()
    {
        $view = $this->view;
        if ($this->getOption('feedUrl')) {
            $view->feed            = Zend_Feed_Reader::import($this->getOption('feedUrl'));
            $view->maxItems        = $this->getOption('maxItems');
            $view->showDate        = $this->getOption('showDate');
            $view->showDescription = $this->getOption('showDescription');
            if ($this->getOption('showFeedUrl')) {
                $view->feedUrl = $this->getOption('feedUrl');
            }
        }
    }

    /**
     * Add sub-form to collect rss/atom feed url.
     *
     * @param   P4Cms_Widget            $widget     the widget instance being configured.
     * @return  Zend_Form_SubForm|null  the sub-form to integrate into the default
     *                                  widget config form or null for no sub-form.
     */
    public static function getConfigSubForm($widget)
    {
        return new Feed_Form_Widget;
    }
}
