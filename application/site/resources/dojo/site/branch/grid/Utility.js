// summary:
//      Site/branch grid utility functions.

dojo.provide("p4cms.site.branch.grid.Utility");

p4cms.site.branch.grid.Utility = {
    openFormDialog: function(action, params) {
        var dialog = p4cms.site.branch.getDialog(action, params);

        // refresh the grid when form is saved
        dojo.connect(dialog, 'onSaveSuccess', function(){
            p4cms.site.branch.grid.instance.refresh();
        });

        dialog.show();
    }
};

