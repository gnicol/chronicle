// summary:
//      Extended dialog with some extra classes suitable for displaying error messages.
//

dojo.provide("p4cms.ui.ErrorDialog");
dojo.require("p4cms.ui.Dialog");
dojo.require("dijit.form.Button");

dojo.declare("p4cms.ui.ErrorDialog", p4cms.ui.Dialog, {

    title: 'Error',

    postCreate: function(){
        this.inherited(arguments);

        // add css class to identify as error dialog
        dojo.addClass(this.domNode, 'p4cms-ui-errorDialog');

        // add ok button
        this.addButton(new dijit.form.Button({
            id:         this.domNode.id + "-button-ok",
            label:      "Ok",
            onClick:    dojo.hitch(this, 'hide')
        }));
    }
});