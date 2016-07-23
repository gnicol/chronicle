<?php
/**
 * Provides a 'seperator' page for menus, this is really intended
 * for the dijit.Menu though it could be used elsewhere.
 *
 * If utilizing P4Cms_Navigation's addPage facility with a hash, entries
 * containing no uri or MVC link details and a label consisting of pure
 * dash '-' characters will become a Separator.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Navigation_Page_Separator extends P4Cms_Navigation_Page_Heading
{
    /**
     * Always returns a dash ('-') for the separator label.
     *
     * @return  string  page label
     */
    public function getLabel()
    {
        return '-';
    }
}
