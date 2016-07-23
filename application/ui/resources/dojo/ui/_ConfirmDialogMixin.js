// summary:
//      Provides confirmation dialog features (with default cancel
//      and submit buttons) for mixing in with dialogs and tooltips.

dojo.provide("p4cms.ui._ConfirmDialogMixin");
dojo.require("p4cms.ui.Dialog");
dojo.require("p4cms.ui.SingleClickButton");
dojo.require("dijit.form.Button");

dojo.declare("p4cms.ui._ConfirmDialogMixin", null, {

    actionButtonOptions:    {},
    cancelButtonOptions:    {},
    showCancelButton:       true,

    // determines which dijit will be used for the buttons:
    // true  - p4cms.ui.SingleClickButton
    // false - dijit.form.Button
    actionSingleClick:      true,
    cancelSingleClick:      false,

    postCreate: function() {
        this.inherited(arguments);

        // add css class to identify as confirmation dialog.
        dojo.addClass(this.domNode, 'confirm-dialog');

        // add action button
        var actionButtonOptions = dojo.mixin(
            {
                "label":    'Confirm',
                "class":    "preferred",
                "id":       this.domNode.id + "-button-action",
                "onClick":  dojo.hitch(this, "onConfirm")
            },
            this.actionButtonOptions
        );

        this.addButton(
            this.actionSingleClick
              ? new p4cms.ui.SingleClickButton(actionButtonOptions)
              : new dijit.form.Button(actionButtonOptions)
        );

        // add cancel button
        if (this.showCancelButton && this.hide) {
            var cancelButtonOptions = dojo.mixin(
                {
                    "label":    'Cancel',
                    "id":       this.domNode.id + "-button-cancel",
                    "onClick":  dojo.hitch(this, "onCancel")
                },
                this.cancelButtonOptions
            );

            this.addButton(
                this.cancelSingleClick
                  ? new p4cms.ui.SingleClickButton(cancelButtonOptions)
                  : new dijit.form.Button(cancelButtonOptions)
            );
        }
    },

    // define custom onClick event callback for the action button
    onConfirm: function(event) {}
});