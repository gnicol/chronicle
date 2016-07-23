<?php
/**
 * A widget that displays a list of content, defaulting to the most recently added content, sorted
 * so that the newest content is first. This content list can also be generated as an RSS feed.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_ListWidgetController extends P4Cms_Widget_ControllerAbstract
{
    public $contexts = array(
        'rss' => array('xml')
    );

    /**
     * Display the content list.
     */
    public function indexAction()
    {
        // get the query for fetching content entries of this widget
        $query = $this->_createRecordQuery();

        $fields = array();
        if ($this->getOption('showIcons')) {
            $fields['icon'] = array(
                'field' => 'title',
                'decorators' => array(
                    'contentIcon',
                    'contentLink',
                    array(
                        'decorator' => 'htmlTag',
                        'options'   => array(
                            'tag'       => 'span',
                            'class'     => 'content-list-icon-field'
                        )
                    )
                )
            );
        }
        $fields['title'] = array(
            'decorators' => array(
                'value',
                'contentLink',
                array(
                    'decorator' => 'htmlTag',
                    'options'   => array(
                        'tag'       => 'span',
                        'class'     => 'content-list-title-field'
                    )
                )
            )
        );

        $widget = $this->_getWidget();
        if ($this->getOption('showRssLink')) {
            $view           = $this->view;
            $rssUri         = $this->_getRssUri();
            $view->rssUri   = $rssUri;

            $view->headLink()->appendAlternate(
                $rssUri,
                'application/rss+xml',
                $widget->getConfig('rssTitle', $widget->getValue('title'))
            );
        }

        $this->view->query   = $query;
        $this->view->options = array('fields' => $fields);
    }

    /**
     * Generate rss feed for this widget.
     */
    public function rssAction()
    {
        // initialize to xml context
        $this->contextSwitch->initContext('xml');

        $request  = $this->getRequest();
        $siteUrl  = P4Cms_Site::fetchActive()->getConfig()->getUrl();

        // generate feed
        $feed        = new Zend_Feed_Writer_Feed;
        $widgetTitle = $this->_getWidget()->getValue('title');
        $feed->setTitle($this->getOption('feedTitle', $widgetTitle));
        $feed->setLink($siteUrl);
        $feed->setFeedLink($siteUrl . $this->_getRssUri(), 'rss');
        $feed->setDescription($this->getOption('feedDescription', $widgetTitle));

        // add items representing content entries in the widget
        $query   = $this->_createRecordQuery();
        $entries = P4Cms_Content::fetchAll($query);
        $authors = array();
        foreach ($entries as $entry) {
            // prepare feed data
            $author = $entry->getValue('author') ?: $entry->getOwner();
            if ($author && !isset($authors[$author])) {
                try {
                    $authors[$author] = P4Cms_User::fetch($author)->getFullName();
                } catch (P4Cms_Model_NotFoundException $e) {
                    $authors[$author] = $author;
                }
            }

            $title       = $entry->getTitle();
            $description = $entry->getExcerpt();
            $link        = $siteUrl . $entry->getUri();
            $createDate  = $entry->hasField('date')
                ? strtotime($entry->getValue('date'))
                : $entry->getModTime();
            $modDate     = $entry->getModTime();

            // populate feed item with entry data
            $feedEntry = $feed->createEntry();
            $feedEntry->setTitle($title);
            $feedEntry->setLink($link);
            $feedEntry->setDateModified($modDate);
            $feedEntry->setDateCreated($createDate);
            if ($description) {
                $feedEntry->setDescription($description);
            }
            if (isset($authors[$author])) {
                $feedEntry->addAuthor(array('name' => $authors[$author]));
            }

            // add item to the feed
            $feed->addEntry($feedEntry);
        }

        $this->view->feed = $feed;

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')->addTag('p4cms_content_list');
        }
    }

    /**
     * Get config sub-form to present additional options when configuring a widget of this type.
     *
     * @param   P4Cms_Widget            $widget     the widget instance being configured.
     * @return  Zend_Form_SubForm|null  the sub-form to integrate into the default
     *                                  widget config form or null for no sub-form.
     */
    public static function getConfigSubForm($widget)
    {
        return new Content_Form_ListWidget;
    }

    /**
     * Helper function to get record query for fetching content entries for this content
     * list widget.
     *
     * @return  P4Cms_Record_Query  record query for fetching content entries
     *                              for this content list widget
     */
    protected function _createRecordQuery()
    {
        $sortFields = array();
        if ($this->getOption('primarySortField')) {
            $sortFields[$this->getOption('primarySortField')] = array(
                $this->getOption('primarySortOrder') ?: P4Cms_Record_Query::SORT_DESCENDING
            );
        }

        if ($this->getOption('secondarySortField')) {
            $sortFields[$this->getOption('secondarySortField')] = array(
                $this->getOption('secondarySortOrder') ?: P4Cms_Record_Query::SORT_DESCENDING
            );
        }

        // if no options were specified, provide default
        if (empty($sortFields)) {
            $sortFields[P4Cms_Record_Query::SORT_DATE] = array(P4Cms_Record_Query::SORT_DESCENDING);
        }

        $query = P4Cms_Record_Query::create()
            ->setSortBy($sortFields);

        $contentType = $this->getOption('contentType');
        if ($contentType instanceof Zend_Config) {
            $contentType = $contentType->toArray();
        }

        $types = $this->getConfigSubForm($this->_getWidget())
                      ->getElement('contentType')
                      ->setValue($contentType)
                      ->getNormalizedTypes();

        if (count($types)) {
            $filter = new P4Cms_Record_Filter;
            $filter->add(
                'contentType',
                $types,
                P4Cms_Record_Filter::COMPARE_EQUAL
            );
            $query->addFilter($filter);
        }

        if ($this->getOption('count')) {
            $query->setMaxRows($this->getOption('count'));
        }

        return $query;
    }

    /**
     * Return uri for the rss feed generated by this widget using the custom
     * 'rss' route.
     *
     * @return  string  uri for the rss feed generated by this widget
     */
    protected function _getRssUri()
    {
        return $this->getHelper('url')->url(
            array(
                'widget' => $this->_getWidget()->getId()
            ),
            'rss'
        );
    }
}
