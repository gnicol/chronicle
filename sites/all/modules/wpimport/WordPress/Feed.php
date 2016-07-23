<?php
/**
 * Extends the Zend_Feed_Reader_Extension_FeedAbstract to implement the custom WordPress feed-level tags found in the
 * exported xml file.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Wp_FeedReader_Extension_WordPress_Feed extends Zend_Feed_Reader_Extension_FeedAbstract
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
     * Parses the list of category and tag ids for this entry out of the xml and returns them as an array which can be
     * passed to the Chronicle content entry.
     *
     * @return array An associative array of category and tag id and title pairs.
     */
    public function getCategories()
    {
        $categoryList  = $this->_xpath->query($this->getXpathPrefix() . '/wp:category');
        $categoryCount = $categoryList->length;
        $categories    = array();
        for ($position = 0; $position < $categoryCount; $position++) {
            $category = array();
            foreach ($categoryList->item($position)->childNodes as $categoryNode) {
                switch($categoryNode->nodeName) {
                    case 'wp:category_nicename':
                        $category['id'] = $categoryNode->nodeValue;
                        break;
                    case 'wp:cat_name':
                        $category['title'] = $categoryNode->nodeValue;
                        break;
                    default:
                }
            }
            if (!empty($category)) {
                $categories[] = $category;
            }
        }

        $tagList  = $this->_xpath->query($this->getXpathPrefix() . '/wp:tag');
        $tagCount = $tagList->length;
        for ($position = 0; $position < $tagCount; $position++) {
            $tag = array();
            foreach ($tagList->item($position)->childNodes as $tagNode) {
                switch($tagNode->nodeName) {
                    case 'wp:tag_slug':
                        $tag['id'] = $tagNode->nodeValue;
                        break;
                    case 'wp:tag_name':
                        $tag['title'] = $tagNode->nodeValue;
                        break;
                    default:
                }
            }
            if (!empty($tag)) {
                $categories[] = $tag;
            }
        }

        return $categories;
    }

    /**
     * WordPress places the CMS users in the author xml tag, along with user details such as name and email address.
     * This method extracts them into a managable array.
     *
     * @return array The list of authors.
     */
    public function getWpAuthors()
    {
        $authorList  = $this->_xpath->query($this->getXpathPrefix() . '/wp:author');
        $authorCount = $authorList->length;
        $authors     = array();
        for ($position = 0; $position < $authorCount; $position++) {
            $author = array();
            foreach ($authorList->item($position)->childNodes as $authorNode) {
                switch($authorNode->nodeName) {
                    case 'wp:author_login':
                        $author['id'] = $authorNode->nodeValue;
                        break;
                    case 'wp:author_email':
                        $author['email'] = $authorNode->nodeValue;
                        break;
                    case 'wp:author_first_name':
                        $author['first'] = $authorNode->nodeValue;
                        break;
                    case 'wp:author_last_name':
                        $author['last'] = $authorNode->nodeValue;
                        break;
                    default:
                }
            }

            $author['fullName'] = trim($author['first'] . ' ' . $author['last']);
            unset($author['first']);
            unset($author['last']);

            // full name is required, if not present, use the id (this happens with the default 'admin' WP user)
            // if id is not present, the user is not added
            if (empty($author['fullName'])) {
                $author['fullName'] = $author['id'];
            }

            if (!empty($author)) {
                $authors[] = $author;
            }
        }
        return $authors;
    }
}