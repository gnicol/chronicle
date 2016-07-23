<?php
/**
 * A button with a drop-down confirmation tooltip dialog.
 *
 * Set the onConfirm option to the javascript you want 
 * executed when the confirmation button is clicked.
 *
 * To alter the confirmation button label, set the
 * actionButtonLabel option.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Element_ConfirmTooltipButton extends P4Cms_Form_Element_TooltipDialogButton
{
    protected   $_dialogDojoType = 'p4cms.ui.ConfirmTooltip';
}
