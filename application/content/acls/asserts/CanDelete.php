<?php
/**
 * Check if the active user can delete the given content resource.
 * Extends CanEdit assert and sets privilege to 'delete' - behavior
 * is otherwise identical.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Acl_Assert_CanDelete extends Content_Acl_Assert_CanEdit
{
    protected   $_privilege    = 'delete';
    protected   $_privilegeAll = 'delete-any';
}
