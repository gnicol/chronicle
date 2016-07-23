// summary:
//      User role grid utility functions.

dojo.provide("p4cms.user.role.grid.Utility");

p4cms.user.role.grid.Utility = {
    openFormDialog: function(action, params) {
        // assemble url params
        var urlParams = {
            module:     'user',
            controller: 'role',
            action:     action
        };

        // create form dialog
        var dialog = new p4cms.ui.FormDialog({
            title:      p4cms.ui.capitalize(action) + ' Role',
            urlParams:  dojo.mixin(params, urlParams)
        });

        // refresh the grid when form is saved
        dojo.connect(dialog, 'onSaveSuccess', function() {
            p4cms.user.role.grid.instance.refresh();
        });

        dialog.show();
    }
};