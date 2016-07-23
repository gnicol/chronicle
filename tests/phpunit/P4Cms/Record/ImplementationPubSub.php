<?php
/**
 * Test implementation of pub/sub record.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_ImplementationPubSub extends P4Cms_Record_PubSubRecord
{
    protected static    $_fields            = array('foo', 'bar');
    protected static    $_storageSubPath    = 'records';
    protected static    $_topic             = 'p4cms.record.test';
    protected static    $_fileContentField  = 'content';
}
