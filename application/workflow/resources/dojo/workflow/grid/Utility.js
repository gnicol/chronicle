// summary:
//      Workflow grid utility functions.

dojo.provide("p4cms.workflow.grid.Utility");
dojo.require("p4cms.ui.FormDialog");
dojo.require("p4cms.ui.ConfirmDialog");

p4cms.workflow.grid.Utility = {
    openFormDialog: function(action, params) {
        // assemble url params
        var urlParams = {
            module: 'workflow',
            action: action
        };

        // create form dialog
        var dialog = new p4cms.ui.FormDialog({
            title:      p4cms.ui.capitalize(action) + ' Workflow',
            urlParams:  dojo.mixin(params, urlParams)
        });

        // refresh the grid when form is saved
        dojo.connect(dialog, 'onSaveSuccess', function(){
            p4cms.workflow.grid.instance.refresh();
        });

        dialog.show();
    },

    confirmReset: function() {
        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Reset Workflows',
            content:             'This will delete workflows that you may have created.<br>'
                               + 'Are you sure you want to reset workflows to defaults?',
            actionButtonOptions: { label: 'Reset' },
            onConfirm:           function(){
                window.location = p4cms.url({
                    module:     'workflow',
                    controller: 'index',
                    action:     'reset'
                });
            }
        });
        dialog.show();
    }
};
