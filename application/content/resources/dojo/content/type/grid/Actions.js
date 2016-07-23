dojo.provide('p4cms.content.type.grid.Actions');

dojo.require('p4cms.ui.ConfirmDialog');

// handle clicks to the 'rollback' action on the history grid
p4cms.content.type.grid.Actions = {
    onClickView: function() {
        var rowIndex = dijit.byId('dijitmenu-type-grid').rowIndex,
            values   = p4cms.content.type.grid.instance.getItemValues(rowIndex),
            urlParms = {
                module:      'content',
                controller:  'type',
                action:      'view',
                id:          values.id
            };
        if (values.deleted) {
            urlParms.version = values.version;
        }
        window.location.href = p4cms.url(urlParms);
    },

    onClickEdit: function() {
        var rowIndex = dijit.byId('dijitmenu-type-grid').rowIndex,
            values   = p4cms.content.type.grid.instance.getItemValues(rowIndex);
        p4cms.content.type.grid.Utility.openFormDialog('edit', {
            id: values.id
        });
    },

    onClickDelete: function() {
        var rowIndex  = dijit.byId('dijitmenu-type-grid').rowIndex,
            values    = p4cms.content.type.grid.instance.getItemValues(rowIndex),
            dialog    = new p4cms.ui.ConfirmDialog({
                title:               'Confirmation required:',
                content:             'Are you sure you want to delete the ' + values.label + ' content type?',
                actionButtonOptions: { label: 'Delete' },
                actionSingleClick:   true,
                onConfirm:           function() {
                    window.location = p4cms.url({
                        module:     'content',
                        controller: 'type',
                        action:     'delete',
                        id:         values.id
                    });
                }
            });
        dialog.show();
    },

    onClickAddContent: function() {
        var rowIndex = dijit.byId('dijitmenu-type-grid').rowIndex,
            values   = p4cms.content.type.grid.instance.getItemValues(rowIndex);

        window.location = p4cms.url({
            module:     'content',
            controller: 'index',
            action:     'add',
            type:       values.id
        });
    },

    onShowAddContent: function(menuItem) {
        var rowIndex = dijit.byId('dijitmenu-type-grid').rowIndex,
            values   = p4cms.content.type.grid.instance.getItemValues(rowIndex);

        menuItem.set('label', "New '" + values.label + "' Entry");
    }
};