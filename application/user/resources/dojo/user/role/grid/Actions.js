dojo.provide("p4cms.user.role.grid.Actions");

dojo.require("p4cms.ui.ConfirmDialog");
dojo.require("p4cms.ui.Notice");

p4cms.user.role.grid.Actions = {
    onClickEdit: function() {
        var rowIndex = dijit.byId('dijitmenu-role-grid').rowIndex;
        p4cms.user.role.grid.Utility.openFormDialog('edit', {
            id: p4cms.user.role.grid.instance.getItemValue(rowIndex, 'id')
        });
    },

    onShowEdit: function(menuItem) {
        var rowIndex  = dijit.byId('dijitmenu-role-grid').rowIndex;
        var isVirtual = p4cms.user.role.grid.instance.getItemValue(rowIndex, 'isVirtual');

        menuItem.set('disabled', isVirtual);
    },

    onClickDelete: function() {
        var rowIndex = dijit.byId('dijitmenu-role-grid').rowIndex;
        var role     = p4cms.user.role.grid.instance.getItemValues(rowIndex);
        var dialog   = new p4cms.ui.ConfirmDialog({
            title:               'Delete Role',
            content:             'Are you sure you want to delete the "' + role.id + '" role?',
            actionButtonOptions: { label: 'Delete Role' },
            actionSingleClick:   true,
            onConfirm: function() {
                // xhr delete entry.
                dojo.xhrPost({
                    url:        role.deleteUri,
                    content:    {id: role.id, format: 'json'},
                    handleAs:   'json',
                    load:       function(response) {
                        p4cms.user.role.grid.instance.refresh();
                        var notice = new p4cms.ui.Notice({
                            message:  "Role '" + role.id + "' has been deleted.",
                            severity: "success"
                        });
                        dialog.hide();
                    },
                    error:      function() {
                        // construct error message (append details if available).
                        var errorMessage    = p4cms.ui.getXhrErrorMessage(arguments),
                            message         = "Unexpected error when trying to delete '"
                                            + role.id + "' role"
                                            + (errorMessage ? ": <br />" + errorMessage : '.'),
                            notice          = new p4cms.ui.Notice({
                                message:  message,
                                severity: "error"
                            });

                        // re-enable delete button
                        dijit.byId(dialog.domNode.id + "-button-action").enable();
                    }
                });
            }
        });

        // wire reset button to close dialog.
        dojo.query('.p4cms-ui-cancel input[type="button"]', dialog.domNode).onclick(
            dojo.hitch(dialog, 'hide')
        );

        dialog.show();
    },

    onShowDelete: function(menuItem, menu) {
        var rowIndex = dijit.byId('dijitmenu-role-grid').rowIndex;
        var isSystem = p4cms.user.role.grid.instance.getItemValue(rowIndex, 'isSystem');

        menuItem.set('disabled', isSystem);
    }
};