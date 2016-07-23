// summary:
//      An extended p4cms.ui.Dialog class to provide confirmation dialog with
//      default cancel and submit buttons.

dojo.provide("p4cms.ui.ConfirmDialog");
dojo.require("p4cms.ui.Dialog");
dojo.require("p4cms.ui._ConfirmDialogMixin");

dojo.declare("p4cms.ui.ConfirmDialog", [p4cms.ui.Dialog, p4cms.ui._ConfirmDialogMixin], {});