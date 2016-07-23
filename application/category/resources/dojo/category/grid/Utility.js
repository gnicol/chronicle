// summary:
//      Category grid utility functions.

dojo.provide("p4cms.category.grid.Utility");
dojo.require("p4cms.ui.FormDialog");

p4cms.category.grid.Utility = {
    openFormDialog: function(action, params) {
        // assemble url params
        var urlParams = {
            module:     'category',
            controller: 'manage',
            action:     action
        };

        // create form dialog
        var dialog = new p4cms.ui.FormDialog({
            title:      p4cms.ui.capitalize(action) + ' Category',
            urlParams:  dojo.mixin(params, urlParams),
            routeName:  'category-manage'
        });

        // refresh the grid when form is saved
        dojo.connect(dialog, 'onSaveSuccess', function() {
            p4cms.category.grid.instance.refresh();
        });

        dialog.show();
    }
};