// summary:
//      User grid utility functions.

dojo.provide("p4cms.user.grid.Utility");
dojo.require("p4cms.ui.FormDialog");

p4cms.user.grid.Utility = {
    openFormDialog: function(action, params) {
        // assemble url params
        var urlParams = {
            module: 'user',
            action: action
        };

        // create form dialog
        var dialog = new p4cms.ui.FormDialog({
            title:      p4cms.ui.capitalize(action) + ' User',
            urlParams:  dojo.mixin(params, urlParams)
        });

        // refresh the grid when form is saved
        dojo.connect(dialog, 'onSaveSuccess', function() {
            p4cms.user.grid.instance.refresh();
        });

        dialog.show();
    }
};