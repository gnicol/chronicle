// summary:
//      Support for menu grid.

dojo.provide("p4cms.menu.grid.Utility");
dojo.require("p4cms.ui.FormDialog");
dojo.require("p4cms.ui.ConfirmDialog");

p4cms.menu.grid.Utility = {
    openFormDialog: function(action, params, titleSuffix) {
        var urlParams   = {
            module:     'menu',
            controller: 'manage',
            action:     action
        };

        // create form dialog
        var dialog      = new p4cms.ui.FormDialog({
            title:      p4cms.ui.capitalize(action.replace(/\-item$/, ''))
                        + ' Menu'
                        + (titleSuffix || ''),
            urlParams:  dojo.mixin(params, urlParams)
        });

        // if this is an item dialog with a type select; ensure we update
        // the dialog content when the type changes.
        dojo.connect(dialog, 'onLoad', function() {
            dojo.query('select[name=type]', dialog.domNode)
                .connect('onchange', function(){
                    dialog.refreshForm({action: 'item-form'});
                });
        });

        // refresh the grid when form is saved
        dojo.connect(dialog, 'onSaveSuccess', function() {
            p4cms.menu.grid.instance.refresh();
        });

        dialog.show();
    },

    confirmReset: function() {
        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Reset Menus',
            content:             'Are you sure you want to reset all menus to defaults?<br>'
                               + 'This will remove any custom menus or menu items.',
            actionButtonOptions: { label: 'Reset' },
            onConfirm:           function() {
                window.location = p4cms.url({
                    module:     'menu',
                    controller: 'manage',
                    action:     'reset'
                });
            }
        });

        dialog.show();
    }
};