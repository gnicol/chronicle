// summary:
//      Support for history module.

dojo.provide("p4cms.history");
dojo.require("dijit.form.DropDownButton");
dojo.require("p4cms.ui.Menu");
dojo.require("p4cms.ui.grid.DataGrid");
dojo.require("p4cms.ui.grid.Form");
dojo.require("p4cms.ui.grid.formatters.ActionsButton");
dojo.require("dojox.data.QueryReadStore");
dojo.require("dojox.grid.enhanced.plugins.Menu");

p4cms.history.view = function(recordType, recordId) {
    if (!recordType || !recordId) {
        return;
    }

    var dialog = new p4cms.ui.Dialog({
        title:          'History',
        executeScripts: true,
        destroyOnHide:  true,
        href:           p4cms.url({
            module: 'history',
            format: 'partial',
            type:   recordType,
            id:     recordId
        })
    });

    dojo.addClass(dialog.domNode, 'p4cms-history');

    // the history grid's row menu doesn't get destroyed when
    // the dialog is destroyed - this will cause id conflicts
    // if the dialog is opened again - therefore, we manually
    // destroy it when the dialog is hidden.
    dojo.connect(dialog, 'onHide', function(){
        var menu = dijit.byId('dijitmenu-history-grid');
        if (menu) {
            menu.destroyRecursive();
        }
    });

    dialog.show();
};

p4cms.history.loadHistoryToolbar = function(type, id, version, container) {
    // if our history toolbar isn't present, add it
    if (!dojo.query('.history-toolbar', container).length) {
        var pane = new dojox.layout.ContentPane({
            'class': 'history-toolbar',
            'id':    'history-toolbar'
        });
        pane.startup();
        dojo.place(pane.domNode, container, 'only');

        pane.set('href', p4cms.url({
            'module':   'history',
            'action':   'toolbar',
            'type':     type,
            'id':       id,
            'version':  version
        }));
    }
};