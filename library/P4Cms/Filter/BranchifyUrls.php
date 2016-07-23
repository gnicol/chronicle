<?php
/**
 * Modifies urls in certain html tags to insert the current branch
 * base url. This is needed in some cases to ensure that resources
 * come from the correct branch.
 *
 * It is possible to control which tags/attributes are affected.
 * By default 'href' and 'src' attributes in 'a' and 'img' tags are
 * 'branchified'.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_BranchifyUrls implements Zend_Filter_Interface
{
    protected   $_tags          = array('img', 'a');
    protected   $_attributes    = array('src', 'href');
    protected   $_strip         = false;

    /**
     * Sets the filter options:
     *
     *        tags - Tags to modify urls in (defaults to: a, img)
     *  attributes - Attributes to modify urls in (defaults to: href, src)
     *
     * @param   array   $options    the options to augment filter behavior.
     */
    public function __construct(array $options = null)
    {
        if (isset($options['tags'])) {
            $this->setTags($options['tags']);
        }

        if (isset($options['attributes'])) {
            $this->setAttributes($options['attributes']);
        }
    }

    /**
     * Assuming html input, modify urls in specific attributes of certain tags
     * (default a, img and href, src) to inject the branch base url.
     *
     * @param   string  $value  html input to branchify urls in
     * @return  string  the filtered value
     */
    public function filter($value)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();

        // early exit if the request doesn't support the branch url concept
        if (!$request instanceof P4Cms_Controller_Request_Http) {
            return $value;
        }

        // if tags or attributes are empty, nothing to do.
        if (!$this->_tags || !$this->_attributes) {
            return $value;
        }

        $strip      = $this->_strip;
        $tags       = implode('|', $this->_tags);
        $attributes = implode('|', $this->_attributes);
        $baseUrl    = $request->getBaseUrl();
        $branchBase = $request->getBranchBaseUrl();

        return preg_replace_callback(
            '/<(' . $tags . ')(\\s+[^>]*)(' . $attributes . ')=([\'"]?)([^>]+)>/i',
            function($match) use ($baseUrl, $branchBase, $strip)
            {
                // 1 = tag (e.g. 'a' or 'img')
                // 2 = intervening content
                // 3 = attribute (e.g. 'src' or 'href')
                // 4 = quote (single quote, double quote or empty)
                // 5 = link value to end of tag

                $link = $match[5];

                // we only munge urls that are absolute with respect
                // to the current domain and start with our base-url.
                if ($link[0] != '/' || ($baseUrl && strpos($link, $baseUrl) !== 0)) {
                    return $match[0];
                }

                // strip off the base url and any leading branch specifier
                $link = substr($link, strlen($baseUrl));
                $link = isset($link[1]) && $link[1] == '-'
                    ? preg_replace('#^/-[^/]+-#', '', $link)
                    : $link;

                // if not stripping, prepend the link with the active branch base url.
                $link = $strip
                    ? $baseUrl    . $link
                    : $branchBase . $link;

                return '<' . $match[1] . $match[2] . $match[3] . '=' . $match[4] . $link . '>';
            },
            $value
        );
    }

    /**
     * Control which tags are affected.
     *
     * @param   array   $tags               list of tags to branchify.
     * @return  P4Cms_Filter_BranchifyUrls  provides fluent interface
     */
    public function setTags(array $tags = null)
    {
        $this->_tags = array_filter(
            (array) $tags,
            array($this, '_sanitize')
        );

        return $this;
    }

    /**
     * Control which attributes are affected.
     *
     * @param   array   $attributes         list of attributes to branchify.
     * @return  P4Cms_Filter_BranchifyUrls  provides fluent interface
     */
    public function setAttributes(array $attributes = null)
    {
        $this->_attributes = array_filter(
            (array) $attributes,
            array($this, '_sanitize')
        );

        return $this;
    }

    /**
     * Sanitize the given string (tag or attribute) for use in a
     * regular expression. Strips any special characters.
     *
     * @param   string  $value  the string to sanitize
     * @return  string  the string sanitized for regex.
     */
    protected function _sanitize($value)
    {
        return preg_replace('/[^a-z0-9\-]/i', '', $value);
    }
}
