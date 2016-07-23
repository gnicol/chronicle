dojo.provide("p4cms.workflow.grid.Actions");

p4cms.workflow.grid.Actions = {
    onClickEdit: function() {
        var rowIndex = dijit.byId('dijitmenu-workflow-grid').rowIndex;
        p4cms.workflow.grid.Utility.openFormDialog('edit', {
            id: p4cms.workflow.grid.instance.getItemValue(rowIndex, 'id')
        });
    },
    onClickDelete: function() {
        var rowIndex = dijit.byId('dijitmenu-workflow-grid').rowIndex;
        var values   = p4cms.workflow.grid.instance.getItemValues(rowIndex);
        var urlParms = {
            module:     'workflow',
            action:     'delete'
        };
        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Delete Workflow',
            content:             'Are you sure you want to delete the "' + values.label + '" workflow?',
            actionButtonOptions: { label: 'Delete Workflow' },
            actionSingleClick:   true,
            onConfirm: function(){
                // xhr delete entry.
                dojo.xhrPost({
                    url:        p4cms.url(urlParms),
                    content:    {id: values.id, format: 'json'},
                    handleAs:   'json',
                    load:       function(){
                        p4cms.workflow.grid.instance.refresh();
                        var notice = new p4cms.ui.Notice({
                            message:  "Workflow '" + values.label + "' has been deleted.",
                            severity: "success"
                        });
                        dialog.hide();
                    },
                    error:      function(){
                        // construct error message (append details if available).
                        var errorMessage = p4cms.ui.getXhrErrorMessage(arguments);
                        var message = "Unexpected error when trying to delete '"
                                    + values.label + "' workflow"
                                    + (errorMessage ? ": <br />" + errorMessage : '.');

                        var notice = new p4cms.ui.Notice({
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