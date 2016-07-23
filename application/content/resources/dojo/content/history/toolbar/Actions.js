dojo.provide('p4cms.content.history.toolbar.Actions');

dojo.require('p4cms.ui.ConfirmDialog');
dojo.require('p4cms.content.history.grid.Actions');

p4cms.content.history.toolbar.Actions = {
    onClickRollback: function() {
        var entry = p4cms.content.getActive(),
            dialog = new p4cms.ui.ConfirmDialog({
                title:               'Rollback',
                content:             'Are you sure you want to rollback to version ' + entry.contentVersion + '?',
                actionButtonOptions: { label: 'Rollback' },
                onConfirm:           function() {
                    window.location = p4cms.url({
                        module:  'content',
                        action:  'rollback',
                        id:      entry.contentId,
                        version: entry.contentVersion
                    });
                }
            });
        dialog.show();
    },

    onShowRollback: function(menuItem) {
        var entry = p4cms.content.getActive();

        menuItem.set(
            'disabled',
            !entry || !entry.contentVersion || entry.contentVersion === entry.headVersion
        );
    },

    onClickDiffLatest: function() {
        var entry  = p4cms.content.getActive(),
            dialog = p4cms.content.history.grid.Actions.getDiffDialog({
                left:   entry.contentId + '#' + entry.contentVersion,
                right:  entry.contentId + '#' + entry.headVersion
            });
        dialog.show();
    },

    onShowDiffLatest: function(menuItem) {
        var entry = p4cms.content.getActive();

        menuItem.set(
            'disabled',
            !entry || !entry.contentVersion || entry.contentVersion === entry.headVersion
        );
    },

    onClickDiffPrevious: function() {
        var entry  = p4cms.content.getActive(),
            dialog = p4cms.content.history.grid.Actions.getDiffDialog({
                left:   entry.contentId + '#' + (entry.contentVersion - 1),
                right:  entry.contentId + '#' + entry.contentVersion
            });
        dialog.show();
    },

    onShowDiffPrevious: function(menuItem) {
        var entry = p4cms.content.getActive();

        menuItem.set('disabled', !entry || entry.contentVersion <= 1);
    }
};