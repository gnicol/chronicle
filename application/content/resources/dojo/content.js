// summary:
//      Support for content module.

dojo.provide("p4cms.content");
dojo.require("p4cms.content.grid.Actions");
dojo.require("p4cms.content.grid.Formatters");
dojo.require("p4cms.content.grid.Utility");
dojo.require("p4cms.content.type.grid.Actions");
dojo.require("p4cms.content.type.grid.Formatters");
dojo.require("p4cms.content.type.grid.Utility");
dojo.require("p4cms.content.history.grid.Actions");
dojo.require("p4cms.content.history.toolbar.Actions");
dojo.require("p4cms.content.SubForm");
dojo.require("p4cms.content.SaveSubForm");
dojo.require("p4cms.content.Entry");
dojo.require("p4cms.content.Element");
dojo.require("p4cms.content.Editor");
dojo.require("p4cms.content.ContentSelect");
dojo.require("p4cms.content.dnd.DropZone");
dojo.require("p4cms.ui.Dialog");
dojo.require('p4cms.ui.ConfirmDialog');
dojo.require('p4cms.ui.SingleClickButton');
dojo.require("dijit.layout.TabContainer");
dojo.require("dijit.layout.ContentPane");
dojo.require("dijit.layout.BorderContainer");
dojo.require("p4cms.ui.grid.Form");
dojo.require("p4cms.ui.grid.formatters.ActionsButton");
dojo.require("dijit.form.Button");
dojo.require("dijit.Menu");
dojo.require("p4cms.content.toolbar");

p4cms.content.getActive = function() {
    var entries = dojo.query('[dojotype=p4cms.content.Entry]');
    if (entries.length !== 1) {
        return null;
    }

    return dijit.byNode(entries[0]);
};

p4cms.content.loadHistoryToolbar = function(container) {
    var entry = p4cms.content.getActive();
    if (!entry) {
        return;
    }

    p4cms.history.loadHistoryToolbar('content', entry.contentId, entry.contentVersion, container);
};

// wrapper for loading add/edit content subtoolbar.
p4cms.content.loadContentToolbar = function(container, type) {
    // if content toolbar isn't present, add it
    var containerId = type + '-content-toolbar',
        pane        = dijit.byId(containerId),
        entry       = p4cms.content.getActive();

    // don't show sub-toolbar if there is no entry,
    // or if we are editing and the add button is pressed
    if (!entry || (entry && !entry.isAdd() && type === 'add')) {
        return;
    }

    if (!pane) {
        pane = new dojox.layout.ContentPane({
            'class':        containerId + ' content-toolbar',
            'id':           containerId,
            'currentEntry': entry.contentId
        });

        var buttons = [];

        buttons.push( new dijit.form.Button({
            'label':    'Delete',
            'id':       containerId + '-button-delete',
            'class':    'content-right-button button-small',
            'onClick':  function() {
                p4cms.content.getActive().deleteEntry();
            }
        }));

        buttons.push( new dijit.form.Button({
            'label':    'Cancel',
            'id':       containerId + '-button-cancel',
            'class':    'content-right-button button-small',
            'onClick':  function() {
                p4cms.content.getActive().confirmCancel();
            }
        }));

        buttons.push( new dijit.form.Button({
            'label':    'In-Place Mode',
            'id':       containerId + '-button-in-place',
            'class':    'content-button button-small button-left button-toggle',
            'onClick':  function() {
                p4cms.content.getActive().enterInPlaceMode();
            }
        }));

        buttons.push( new dijit.form.Button({
            'label':    'Form Mode',
            'id':       containerId + '-button-form',
            'class':    'content-button button-small button-right button-toggle',
            'onClick':  function() {
                p4cms.content.getActive().enterFormMode();
            }
        }));

        // place buttons
        dojo.forEach(buttons, function(button) {
            dojo.place(button.domNode, pane.domNode);
        });

        dojo.place(pane.domNode, container, 'only');
    }

    // show delete button only when editing content and if user has permissions
    if (type === 'edit'
        && !entry.deleted
        && entry && dojo.indexOf(entry.allowedPrivileges, 'delete') !== -1
    ) {
        dojo.style(dojo.byId(containerId + '-button-delete'), 'display', '');
    } else {
        dojo.style(dojo.byId(containerId + '-button-delete'), 'display', 'none');
    }

    // update buttons to reflect current edit mode.
    entry.updateSubToolbar();

    // if content form is ready, add sub-form buttons, otherwise
    // subscribe to the topic to place buttons when form is ready
    if (entry.getForm()) {
        p4cms.content.updateSubFormButtons(containerId, entry);
    } else {
        dojo.subscribe('p4cms.content.form.ready', function(form) {
            if (form === entry.getForm()) {
                p4cms.content.updateSubFormButtons(containerId, entry);
            }
        });
    }
};

// collect buttons for content sub-forms (one for each) of a given entry
// and place them in the given container
p4cms.content.updateSubFormButtons = function(containerId, entry) {
    var node;
    dojo.forEach(entry.getSubForms(), function(subForm) {
        // skip if button already exists or is not intended to be shown in the toolbar
        var buttonId = containerId + '-button-' + subForm.getFormLegend();
        if (!subForm.showInToolbar || dojo.query('#' + buttonId).length) {
            return;
        }

        // create tooltip dialog to contain sub-form
        var dialog = new p4cms.ui.TooltipDialog({
            'class': 'content-sub-form-dialog'
        });

        // create drop-down button to pop sub-form dialog
        var button = new dijit.form.DropDownButton({
            'label':    subForm.getFormLegend(),
            'id':       buttonId,
            'class':    subForm.toolbarButtonClass || 'button-small content-sub-form-button',
            'dropDown':	dialog
        });

        // patch drop-down open logic to move sub-form
        // into the tooltip dialog before it is displayed.
        var originalOpen = button.openDropDown;
        button.openDropDown = function(){
            subForm.moveToDialog(dialog);
            dojo.hitch(button, originalOpen)();
        };

        // when dialog closes, move sub-form back to parent form.
        dojo.connect(dialog, 'onHide', function(){
            subForm.returnToForm();
            subForm.dialog = null;
        });

        // place button in the container - we place content-right buttons first in
        // the containing box, otherwise, they will be pushed down in IE7
        if (subForm.toolbarButtonClass.indexOf('content-right-button') !== -1) {
            // place button after the last node having 'content-right-button' class;
            // if there are no such nodes, place button at the first position in the containerId
            node = dojo.query('.content-right-button', containerId);
            dojo.place(button.domNode, node[node.length-1] || containerId, node.length ? 'after' : 'first');
        } else {
            dojo.place(button.domNode, containerId, 'last');
        }
    });
};

p4cms.content.startAdd = function() {
    p4cms.ui.toolbar.activateButtonByClass('content-add');
};

p4cms.content.startEdit = function() {
    p4cms.ui.toolbar.activateButtonByClass('content-edit');
};

p4cms.content.startHistory = function() {
    p4cms.ui.toolbar.activateButtonByClass('content-history');
};

p4cms.content.onActivateToolbarAdd = function() {
    // if add already in progress, toggle edit mode.
    var entry = p4cms.content.getActive();
    if (entry && entry.isAdd()) {
        return p4cms.ui.enableEditGroup('content');
    }

    // no add in progress, prompt for new content type.
    var dialog = p4cms.content.grid.Utility.openAddDialog();
    dojo.connect(dialog, 'hide', function() {
        p4cms.ui.toolbar.deactivateButtonByClass('content-add');
    });
};

p4cms.content.onDeactivateToolbarAdd = function() {
    // if add already in progress, toggle edit mode.
    var entry = p4cms.content.getActive();
    if (entry && entry.isAdd()) {
        return p4cms.ui.disableEditGroup('content');
    }
};

// disable Delete button in the content grid footer if no rows are selected
dojo.addOnLoad(function(){
    var gridFooter = dojo.query('.content-grid .grid-footer');
    if (!gridFooter.length) {
        return;
    }

    var deleteButton = dojo.query('.delete-button', gridFooter[0]);
    var grid         = p4cms.content.grid.instance;
    if (deleteButton.length && grid) {
        var button = dijit.byNode(deleteButton[0]);
        // set initial button state
        button.set('disabled', grid.selection.getSelectedCount() === 0);

        // connect to enable Delete button only if there are rows selected
        var footer = dijit.byNode(gridFooter[0]);
        dojo.connect(footer, 'updateSelected', function() {
            button.set('disabled', grid.selection.getSelectedCount() === 0);
        });
    }
});

// when a file upload occurs (via dnd) assume image content type
// if file name ends in .gif, .ico, .jpg, .jpeg, .png, or .svg
dojo.subscribe("p4cms.content.dnd.upload.data", function(file, data) {
    if (file.name.match(/\.(gif|ico|jpg|jpeg|png|svg)$/i)) {
        data.contentType = 'image';
    }
});
