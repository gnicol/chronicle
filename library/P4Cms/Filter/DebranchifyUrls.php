<?php
/**
 * Opposite of branchify. Removes branch base urls from tags/attributes
 * instead of adding them.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_DebranchifyUrls extends P4Cms_Filter_BranchifyUrls
{
    protected   $_strip = true;
}