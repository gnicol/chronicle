<?php
/**
 * Extends utf8 number query analyzer to be case-insensitive even if
 * the mbstring extension is not installed (falls back to strtolower).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive extends
    Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num
{
    /**
     * Add a lower-case token filter to the analyzer. If the mbstring
     * extension is present, use the UTF-8 aware strtolower, otherwise,
     * use PHP's built-in strtolower - not ideal, but should be relatively
     * harmless.
     */
    public function __construct()
    {
        parent::__construct();

        if (extension_loaded("mbstring")) {
            $this->addFilter(new Zend_Search_Lucene_Analysis_TokenFilter_LowerCaseUtf8());
        } else {
            $this->addFilter(new Zend_Search_Lucene_Analysis_TokenFilter_LowerCase());
        }
    }
}

