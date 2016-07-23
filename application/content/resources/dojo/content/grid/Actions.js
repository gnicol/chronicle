dojo.provide("p4cms.content.grid.Actions");

dojo.require("p4cms.history");
dojo.require("p4cms.content.Entry");

p4cms.content.grid.Actions = {

    // hide menuItem if privilege is not in the list of item's privileges
    _setVisibility: function (privilege, menuItem) {
        var rowIndex   = dijit.byId('dijitmenu-content-grid').rowIndex,
            privileges = p4cms.content.grid.instance.getItemValue(rowIndex, 'privileges'),
            display    = (dojo.indexOf(privileges, privilege) === -1 ? 'none' : '');

        dojo.style(menuItem.domNode, 'display', display);
    },

    onShowView: function (menuItem) {
        // disable menu item if no content type.
        var rowIndex  = dijit.byId('dijitmenu-content-grid').rowIndex;
        var type      = p4cms.content.grid.instance.getItemValue(rowIndex, 'type');
        menuItem.set('disabled', !type.id);

        this._setVisibility('access', menuItem);
    },

    onClickView: function(newWindow) {
        var rowIndex = dijit.byId('dijitmenu-content-grid').rowIndex;
        var values   = p4cms.content.grid.instance.getItemValues(rowIndex);
        var params   = {
            module: 'content',
            action: 'view',
            id:     values.id
        };
        if (values.deleted) {
            params.version = values.version;
        }
        p4cms.openUrl(params, null, newWindow ? '_blank' : null);
    },

    onShowEdit: function (menuItem) {
        // disable menu item if no content type.
        var rowIndex  = dijit.byId('dijitmenu-content-grid').rowIndex;
        var type      = p4cms.content.grid.instance.getItemValue(rowIndex, 'type');
        menuItem.set('disabled', !type.id);

        this._setVisibility('edit', menuItem);
    },

    onClickEdit: function() {
        var rowIndex = dijit.byId('dijitmenu-content-grid').rowIndex;
        var values   = p4cms.content.grid.instance.getItemValues(rowIndex);
        var params   = {
            module: 'content',
            action: 'edit',
            id:     values.id
        };
        if (values.deleted) {
            params.version = values.version;
        }
        p4cms.openUrl(params);
    },

    onShowDelete: function(menuItem) {
        var rowIndex  = dijit.byId('dijitmenu-content-grid').rowIndex;
        var deleted   = p4cms.content.grid.instance.getItemValue(rowIndex, 'deleted');

        menuItem.set('disabled', deleted);
        this._setVisibility('delete', menuItem);
    },

    onClickDelete: function() {
        var rowIndex  = dijit.byId('dijitmenu-content-grid').rowIndex;
        p4cms.content.grid.Utility.openDeleteDialog(rowIndex);
    },

    onShowHistory: function (menuItem) {
        this._setVisibility('access-history', menuItem);
    },

    onClickHistory: function() {
        var rowIndex  = dijit.byId('dijitmenu-content-grid').rowIndex;
        var recordId  = p4cms.content.grid.instance.getItemValue(rowIndex, 'id');
        p4cms.history.view('content', recordId);
    }
};