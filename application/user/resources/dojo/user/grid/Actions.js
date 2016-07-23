dojo.provide("p4cms.user.grid.Actions");

dojo.require("p4cms.ui.ConfirmDialog");
dojo.require("p4cms.ui.Notice");

p4cms.user.grid.Actions = {
    onClickEdit: function() {
        var rowIndex  = dijit.byId('dijitmenu-user-grid').rowIndex;
        p4cms.user.grid.Utility.openFormDialog('edit', {
            id: p4cms.user.grid.instance.getItemValue(rowIndex, 'id')
        });
    },

    onClickDelete: function() {
        var rowIndex  = dijit.byId('dijitmenu-user-grid').rowIndex;
        var user      = p4cms.user.grid.instance.getItemValues(rowIndex);
        var dialog    = new p4cms.ui.ConfirmDialog({
            title:               'Delete User',
            content:             'Are you sure you want to delete the user "' + user.id + '"?',
            actionButtonOptions: { label: 'Delete User' },
            actionSingleClick:  true,
            onConfirm: function() {
                // xhr delete entry.
                dojo.xhrPost({
                    url:        user.deleteUri,
                    content:    {id: user.id, format: 'json'},
                    handleAs:   'json',
                    load:       function(response) {
                        // redirect if active user's account has been deleted
                        if (response.isActiveUserDeleted) {
                            document.location.href = p4cms.url();
                        }

                        p4cms.user.grid.instance.refresh();
                        var notice = new p4cms.ui.Notice({
                            message:  "User '" + response.userId + "' has been deleted.",
                            severity: "success"
                        });
                        dialog.hide();
                    },
                    error:      function() {
                        // construct error message (append details if available).
                        var errorMessage    = p4cms.ui.getXhrErrorMessage(arguments),
                            message         = "Unexpected error when trying to delete '"
                                            + user.id + "' user"
                                            + (errorMessage ? ": <br />" + errorMessage : '.'),
                            notice          = new p4cms.ui.Notice({
                                message:    message,
                                severity:   "error"
                            });

                        // re-enable delete button
                        dijit.byId(dialog.domNode.id + "-button-action").enable();
                    }
                });
            }
        });

        dialog.show();
    }
};