// summary:
//      An extended p4cms.ui.TooltipDialog class to provide confirmation tooltip
//      dialog with default cancel and submit buttons.

dojo.provide("p4cms.ui.ConfirmTooltip");
dojo.require("p4cms.ui.TooltipDialog");
dojo.require("p4cms.ui._ConfirmDialogMixin");

dojo.declare("p4cms.ui.ConfirmTooltip", [p4cms.ui.TooltipDialog, p4cms.ui._ConfirmDialogMixin], {});