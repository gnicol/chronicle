<?php
/**
 * Implements Zend_Filter_Interface to convert html to plain text.
 * May optionally preserve links and html entities.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        re-write using a DOM.
 */
class P4Cms_Filter_HtmlToText implements Zend_Filter_Interface
{
    /**
     * Set options
     * @var array
     */
    protected $_options = array(
        'keepLinks'     => false,
        'keepEntities'  => false
    );

    /**
     * Set filter options (e.g. keepLinks and keepEntities).
     *
     * @param   array|Zend_Config  $options  options to influence filtering.
     * @return  void
     */
    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * Get the current filtering options.
     *
     * @return  array   current options (e.g. keepLinks and keepEntities).
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Set filtering options to use.
     *
     * @param   array   $options            optional - options to use.
     * @return  P4Cms_Filter_HtmlToText     provides fluent interface.
     */
    public function setOptions(array $options = null)
    {
        $this->_options = $options + $this->_options;
        return $this;
    }

    /**
     * Convert html to plain text. Attempts to format plain text.
     *
     * @param   mixed   $html  the html to be filtered.
     * @return  string  the plain text output.
     */
    public function filter($html)
    {
        // nothing to do if input is null.
        if (!strlen($html)) {
            return;
        }
        
        $options      = $this->getOptions();
        $keepLinks    = $options['keepLinks'];
        $keepEntities = $options['keepEntities'];

        // define tags that indicate blocks of text.
        $blockTags = array(
            "<p>", "</p>", "<blockquote>", "</blockquote>", "<div>",
            "</div>", "<table>", "</table>", "<tr>", "</tr>", "<ul>",
            "</ul>", "<ol>", "</ol>"
        );

        // normalize line-endings, convert all to LFs.
        $text = str_replace("\r\n", "\n", $html);
        $text = str_replace("\r", "\n", $text);

        // split article text on PRE tag boundaries.
        $textBlocks = preg_split("/<\/?\s*pre[^>]*>/i", $text);

        // loop over and clean alternating blocks of html and pre text.
        $cleanText = "";
        for ($i = 0; $i < count($textBlocks); $i++) {
            // even blocks are straight html.
            if ($i % 2 === 0) {
                $text = $textBlocks[$i];

                // strip tags that we don't handle below.
                $text = strip_tags(
                    $text,
                    "<a><blockquote><br><br/><div><h1><h2><h3><h4><h5>" .
                    "<h6><li><ol><p><script><table><tr><ul>"
                );

                // strip entire script tags.
                $text = preg_replace("/<\s*script.*?<\/\s*script[^>]*>/i", "", $text);

                // extract links - unless keepLinks set.
                if (!$keepLinks) {
                    $text = preg_replace(
                        "/<a\s*href=[\"']*([^\"'>]+)[\"']*>([^<]+)<\/\s*a[^>]*>/i",
                        "\\2 [\\1]",
                        $text
                    );
                }

                // capitalize headings.
                $text = preg_replace(
                    "/(<\s*h[0-9][^>]*>.*?<\/[ ]*h[0-9][^>]*>)/ie",
                    "strtoupper('\\1')",
                    $text
                );

                // decode html entities - unless keepEntities set.
                if (!$keepEntities) {
                    $filter = new P4Cms_Filter_HtmlEntityDecode;
                    $text   = $filter->filter($text);
                }

                // remove LFs and TABS (replace with spaces).
                $text = str_replace(array("\n", "\t"), " ", $text);

                // convert BRs and block tags to LFs.
                $text = preg_replace("/<\s*br\s*?[^>]*>/i", "\n", $text);
                $text = str_ireplace($blockTags, "\n\n", $text);

                // remove excess spaces.
                $text = preg_replace("/  */", " ", $text);
                $text = preg_replace("/\n */", "\n", $text);
                $text = preg_replace("/^ */", "", $text);
                $text = preg_replace("/ *$/", "", $text);

                // convert LIs to '\n-- '.
                $text = preg_replace("/<\s*li\s?[^>]*>[\n ]*/i", "\n-- ", $text);
                $text = str_ireplace("</\s*li\s?[^>]*>", "", $text);

                // remove excess LFs.
                $text = preg_replace("/\n\n+/", "\n\n", $text);

                // add block to cleaned text
                $cleanText .= $text;

            // odd blocks are pre-formatted text in html.
            } else {
                // leave PRE sections mostly intact.
                //  -> replace BRs with \n.
                //  -> strip tags.
                //  -> strip html entities - unless keep entities set.
                //  -> indent lines.
                $text = $textBlocks[$i];
                $text = preg_replace("/<\s*br\s?[^>]*>/i", "\n", $text);
                $text = strip_tags($text);
                if (!$keepEntities) {
                    $filter = new P4Cms_Filter_HtmlEntityDecode;
                    $text   = $filter->filter($text);
                }
                $text = str_replace("\n", "\n ", $text);
                $cleanText .= "<pre>" . $text . "</pre>";
            }
        }

        // remove pre tags, replace with LFs.
        $cleanText = preg_replace("/[\n ]*<\s*pre[^>]*>[\n]*/i", "\n\n", $cleanText);
        $cleanText = preg_replace("/[\n ]*<\/\s*pre[^>]*>[\n ]*/i", "\n\n", $cleanText);

        // pad headings consistently - three lines on top, two on bottom.
        // make headings end with ':'.
        $cleanText = preg_replace("/[\n ]*<\s*h[0-9][^>]*>[\n ]*/i", "\n\n\n", $cleanText);
        $cleanText = preg_replace("/[\n: ]*<\/\s*h[0-9][^>]*>[\n ]*/i", ":\n\n", $cleanText);

        // indent list items with a space.
        $cleanText = preg_replace("/\n-- /", "\n - ", $cleanText);

        // strip any remaining tags - except for links if keepLinks set.
        if ($keepLinks) {
            $cleanText = strip_tags($cleanText, '<a>');
        } else {
            $cleanText = strip_tags($cleanText);
        }

        // return cleaned and trimmed text.
        return trim($cleanText);
    }
}
