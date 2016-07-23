// summary:
//      Site/branch grid actions functions.

dojo.provide("p4cms.site.branch.grid.Actions");

p4cms.site.branch.grid.Actions = {
    onClickView: function() {
        var rowIndex       = dijit.byId('dijitmenu-branches-grid').rowIndex;
        var branchValues   = p4cms.site.branch.grid.instance.getItemValues(rowIndex);

        if (branchValues.id && branchValues.type !== 'site') {
            p4cms.site.branch.switchTo(branchValues);
        }
    },

    onShowView: function(menuItem) {
        var rowIndex = dijit.byId('dijitmenu-branches-grid').rowIndex;
        var type     = p4cms.site.branch.grid.instance.getItemValue(rowIndex, 'type');

        // don't allow viewing a site
        menuItem.set('disabled', type === 'site');
    },
    
    onClickEdit: function() {
        var rowIndex = dijit.byId('dijitmenu-branches-grid').rowIndex;
        var values   = p4cms.site.branch.grid.instance.getItemValues(rowIndex);

        if (values.id && values.type !== 'site') {
            p4cms.site.branch.grid.Utility.openFormDialog('edit', {id: values.id});
        }
    },

    onShowEdit: function(menuItem) {
        var rowIndex = dijit.byId('dijitmenu-branches-grid').rowIndex;
        var type     = p4cms.site.branch.grid.instance.getItemValue(rowIndex, 'type');

        // don't allow editing a site
        menuItem.set('disabled', type === 'site');
    },

    onClickAddBranch: function() {
        var rowIndex = dijit.byId('dijitmenu-branches-grid').rowIndex;
        var values   = p4cms.site.branch.grid.instance.getItemValues(rowIndex);

        if (values.id && values.type !== 'site') {
            p4cms.site.branch.grid.Utility.openFormDialog('add', {site: values.siteId, parent: values.id});
        }
    },

    onShowAddBranch: function(menuItem) {
        var rowIndex = dijit.byId('dijitmenu-branches-grid').rowIndex;
        var canPull  = p4cms.site.branch.grid.instance.getItemValue(rowIndex, 'canPull');

        // cannot add branch if we can't pull
        menuItem.set('disabled', !canPull);
    },

    onClickDelete: function() {
        var rowIndex = dijit.byId('dijitmenu-branches-grid').rowIndex;
        var values   = p4cms.site.branch.grid.instance.getItemValues(rowIndex);

        var urlParams = {
            module:     'site',
            controller: 'branch',
            action:     'delete'
        };
        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Delete Branch',
            content:             'Are you sure you want to delete the "' + values.name + '" branch?',
            actionButtonOptions: {label: 'Delete Branch'},
            actionSingleClick:   true,
            onConfirm:           function(){
                // xhr delete entry.
                dojo.xhrPost({
                    url:        p4cms.url(urlParams),
                    content:    {id: values.id, format: 'json'},
                    handleAs:   'json',
                    load:       function(){
                        p4cms.site.branch.grid.instance.refresh();
                        var notice = new p4cms.ui.Notice({
                            message:  "Branch '" + values.name + "' has been deleted.",
                            severity: "success"
                        });
                        dialog.hide();
                    },
                    error:      function(){
                        // construct error message (append details if available).
                        var errorMessage = p4cms.ui.getXhrErrorMessage(arguments);
                        var message = "Unexpected error when trying to delete '"
                                    + values.name + "' branch"
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
    },

    onShowDelete: function(menuItem) {
        var rowIndex  = dijit.byId('dijitmenu-branches-grid').rowIndex;
        var canDelete = p4cms.site.branch.grid.instance.getItemValue(rowIndex, 'canDelete');

        // cannot delete branch if we can't delete
        menuItem.set('disabled', !canDelete);
    }
};