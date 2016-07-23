<?php
/**
 * Extends the Zend_Feed_Reader_Extension_EntryAbstract to implement the custom WordPress entry-level tags found in the
 * exported xml file.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Wp_FeedReader_Extension_WordPress_Entry extends Zend_Feed_Reader_Extension_EntryAbstract
{
    /**
     * Register the namespace for the feed.
     */
    protected function _registerNamespaces()
    {
        $xml = $this->getDomDocument();
        $rootNamespace = $xml->lookupNamespaceUri($xml->namespaceURI);
        $this->_xpath->registerNamespace('wp', $rootNamespace);
    }

    /**
     * Gets a requested value from xml by key via xpath; if a specific method exists to do so, uses that method.
     *
     * @param string $key The key for which to get the value.
     * @return mixed The reqeusted value.
     */
    public function get($key)
    {
        $method = 'get' . ucfirst($key);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return $this->_xpath->evaluate(
            'string(' . $this->getXpathPrefix() . '/' . $key . ')'
        );
    }

    /**
     * Performs manipulation on the post content to update internal links.
     *
     * @param type $baseWordPressUrl The base url of the WordPress site.
     * @param type $baseChronicleUrl The base url of the Chronicle site, optional.
     * @return string The content, with updated internal links.
     */
    public function getWpContent($baseWordPressUrl, $baseChronicleUrl = '')
    {
        if (isset($this->_data['content'])) {
            return $this->_data['content'];
        }

        $content = str_replace(
            $baseWordPressUrl .'/?p=',
            $baseChronicleUrl . '/view/id/',
            $this->get('content:encoded')
        );

        $content = str_replace(
            $baseWordPressUrl .'/?post_id=',
            $baseChronicleUrl . '/view/id/',
            $content
        );

        $content = str_replace(
            $baseWordPressUrl .'/?attachment_id=',
            $baseChronicleUrl . '/view/id/',
            $content
        );

        $content = str_replace(
            '<?php echo get_site_url(); ?>',
            $baseChronicleUrl,
            $content
        );

        $content = preg_replace(
            '/\/wp-content\/uploads\/[\\d]{4}\/[\d]{2}\//i',
            '/image/id/',
            $content
        );

        $content = preg_replace(
            '/(src)(=)(")(\/)(image)(\/)(id)(\/)((?:[a-z][a-z\.\d_]+)\.(?:[a-z\d]{3}))(?![\w\.])(")/is',
            '$0 data-contentid="$9"',
            $content
        );

        if (!$content) {
            $content = null;
        }
        $this->_data['content'] = $content;
        return $this->_data['content'];
    }

    /**
     * Returns the WordPress entry post date expressed as a local timestamp.
     *
     * @return string The post date expressed as a local timestamp.
     */
    public function getPostDateGmt()
    {
        $postDate = $this->_xpath->evaluate(
            'string(' . $this->getXpathPrefix() . '/wp:post_date_gmt)'
        );
        if (!$postDate) {
            $postDate = 'now';
        }

        $d = new Zend_Date;
        $d->set($postDate, Zend_Date::ISO_8601);

        $dateFormat = Zend_Date::YEAR . '-' . Zend_Date::MONTH_SHORT . '-' . Zend_Date::DAY_SHORT;

        return $d->get($dateFormat);
    }

    /**
     * Collates and returns the WordPress post metadata as an associative array.
     *
     * @return array The post metadata
     */
    public function getPostMeta()
    {
        if (isset($this->_data['postMeta'])) {
            return $this->_data['postMeta'];
        }

        $postMetaList = $this->_xpath->evaluate(
            $this->getXpathPrefix() . '/wp:postmeta'
        );

        $postMeta = array();
        foreach ($postMetaList as $metaNode) {
            if ($metaNode->hasChildNodes()) {
                $key   = null;
                $value = null;
                foreach ($metaNode->childNodes as $node) {
                    if ($node->nodeName == 'wp:meta_key') {
                        $key = $node->nodeValue;
                    } else if ($node->nodeName == 'wp:meta_value') {
                        $value = $node->nodeValue;
                    }
                }
                if ($key && $value) {
                    if (stripos($key, '_menu_item_') !== false) {
                        $key = str_replace('_menu_item_', '', $key);
                    }
                    $postMeta[$key] = $value;
                }
            }
        }

        $this->_data['postMeta'] = $postMeta;
        return $this->_data['postMeta'];
    }

    /**
     * Parses the list of category ids for this entry out of the xml and returns them as an array which can be passed
     * to the Chronicle content entry.
     *
     * @return array The list of category ids for this entry.
     */
    public function getCategories()
    {
        $categoryNodeList = $this->_xpath->evaluate(
            $this->getXpathPrefix() . '/category'
        );

        $categories = array();
        foreach ($categoryNodeList as $categoryNode) {
            $categories[] = $categoryNode->getAttribute('nicename');
        }

        return $categories;
    }
}