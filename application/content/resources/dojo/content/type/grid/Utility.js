// summary:
//      Content type grid utility functions.

dojo.provide("p4cms.content.type.grid.Utility");
dojo.require("p4cms.ui.ConfirmDialog");
dojo.require("p4cms.ui.FormDialog");

p4cms.content.type.grid.Utility = {
    openFormDialog: function(action, params) {
        // assemble url params
        var urlParams = {
            module:     'content',
            controller: 'type',
            action:     action
        };

        // create form dialog
        var dialog = new p4cms.ui.FormDialog({
            title:      p4cms.ui.capitalize(action) + ' Content Type',
            urlParams:  dojo.mixin(params, urlParams),
            dataFormat: 'dojoio'
        });

        // refresh the grid when form is saved
        dojo.connect(dialog, 'onSaveSuccess', function() {
            p4cms.content.type.grid.instance.refresh();
        });

        dialog.show();
    },

    confirmReset: function() {
        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Reset Content Types',
            content:             'This will delete content types that you may have created.<br>'
                               + 'Are you sure you want to reset content types to defaults?',
            actionButtonOptions: { label: 'Reset' },
            onConfirm:           function() {
                window.location = p4cms.url({
                    module:     'content',
                    controller: 'type',
                    action:     'reset'
                });
            }
        });
        dialog.show();
    }
};