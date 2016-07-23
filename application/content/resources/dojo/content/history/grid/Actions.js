dojo.provide('p4cms.content.history.grid.Actions');

dojo.require('p4cms.ui.ConfirmDialog');

// handle actions on the history grid
p4cms.content.history.grid.Actions = {
    onClickView: function() {
        var rowIndex = dijit.byId('dijitmenu-history-grid').rowIndex,
            row      = p4cms.history.grid.instance.getItemValues(rowIndex);

        window.location = p4cms.url({
            module:  'content',
            action:  'view',
            id:      row.recordId,
            version: row.version
        });
    },

    onClickRollback: function() {
        var rowIndex = dijit.byId('dijitmenu-history-grid').rowIndex,
            row      = p4cms.history.grid.instance.getItemValues(rowIndex),
            dialog   = new p4cms.ui.ConfirmDialog({
                title:               'Rollback',
                content:             'Are you sure you want to rollback to version ' + row.version + '?',
                actionButtonOptions: { label: 'Rollback' },
                onConfirm:           function() {
                    window.location = p4cms.url({
                        module: 'content',
                        action: 'rollback',
                        id:     row.recordId,
                        change: row.id
                    });
                }
            });
        dialog.show();
    },

    onShowRollback: function(menuItem) {
        var rowIndex = dijit.byId('dijitmenu-history-grid').rowIndex,
            values   = p4cms.history.grid.instance.getItemValues(rowIndex);

        menuItem.set('disabled', values.headVersion === values.version);
    },

    getDiffDialog: function(args) {
        return new p4cms.ui.Dialog({
            title:          'Diff',
            destroyOnHide:  true,
            href:           p4cms.url(
                dojo.mixin({
                    module:     'diff',
                    controller: 'index',
                    action:     'index',
                    format:     'partial',
                    type:       'content'
                }, args)
            )
        });
    },

    onClickDiffLatest: function() {
        var rowIndex = dijit.byId('dijitmenu-history-grid').rowIndex,
            row      = p4cms.history.grid.instance.getItemValues(rowIndex),
            dialog   = this.getDiffDialog({
                left:       row.recordId + '#' + row.version,
                right:      row.recordId + '#' + row.headVersion
            });
        dialog.show();
    },

    onShowDiffLatest: function(menuItem) {
        var rowIndex = dijit.byId('dijitmenu-history-grid').rowIndex,
            values   = p4cms.history.grid.instance.getItemValues(rowIndex);

        menuItem.set('disabled', values.version === values.headVersion);
    },

    onClickDiffPrevious: function() {
        var rowIndex = dijit.byId('dijitmenu-history-grid').rowIndex,
            row      = p4cms.history.grid.instance.getItemValues(rowIndex),
            dialog   = this.getDiffDialog({
                left:   row.recordId + '#' + (row.version - 1),
                right:  row.recordId + '#' + row.version
            });
        dialog.show();
    },

    onShowDiffPrevious: function(menuItem) {
        var rowIndex = dijit.byId('dijitmenu-history-grid').rowIndex,
            values   = p4cms.history.grid.instance.getItemValues(rowIndex);

        menuItem.set('disabled', values.version <= 1);
    },

    onClickDiffSelected: function() {
        var rowIndex    = dijit.byId('dijitmenu-history-grid').rowIndex,
            row         = p4cms.history.grid.instance.getItemValues(rowIndex),
            selectedRow = p4cms.history.grid.instance.selection.getSelected()[0].i,
            dialog      = this.getDiffDialog({
                left:   row.recordId + '#' + row.version,
                right:  row.recordId + '#' + selectedRow.version
            });
        dialog.show();
    },

    onShowDiffSelected: function(menuItem) {
        var rowIndex    = dijit.byId('dijitmenu-history-grid').rowIndex,
            row         = p4cms.history.grid.instance.getItemValues(rowIndex);

        menuItem.set(
            'disabled',
            p4cms.history.grid.instance.selection.getSelected().length === 0
            || p4cms.history.grid.instance.selection.getSelected()[0].i.version === row.version
        );
    }
};