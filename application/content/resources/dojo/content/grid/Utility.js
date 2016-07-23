// summary:
//      Content grid utility functions.

dojo.provide("p4cms.content.grid.Utility");
dojo.require("p4cms.ui.Dialog");
dojo.require("p4cms.ui.FormDialog");

p4cms.content.grid.Utility = {
    openAddDialog: function() {
        var dialog = new p4cms.ui.Dialog({
            title:          'Add Content',
            href:           p4cms.url({
                module:     'content',
                action:     'add',
                format:     'partial'
            }),
            executeScripts: true
        });
        dojo.addClass(dialog.domNode, 'p4cms-add-content');
        dialog.show();

        return dialog;
    },
    
    getPreviewHref: function(item) {
        return p4cms.url({
            module:     'content',
            action:     'view',
            format:     'preview',
            id:         item.id,
            version:    item.version
        });
    },

    openDeleteDialog: function(rowIndex) {
        var grid          = p4cms.content.grid.instance;
        var selectedItems = rowIndex !== undefined
            ? [grid.getItem(rowIndex)]
            : grid.selection.getSelected();

        // get list of entries' ids selected in the grid
        var selectedIds = dojo.map(
            selectedItems,
            function(item){return item.i.id;}
        );

        // do nothing if no rows selected
        if (!selectedIds.length) {
            return;
        }

        // declare dialog to use for either following logic branches
        var dialog;

        // if all entries are deleted, present confirmation dialog and exit
        if (!dojo.some(selectedItems, function(item){ return !item.i.deleted; })) {
            var message = "The selected content "
                        + (selectedItems.length > 1 ? 'entries are' : 'entry is')
                        + ' already deleted.';
            dialog = new p4cms.ui.ConfirmDialog({
                title:               'Delete Content',
                actionButtonOptions: { label: 'Ok' },
                showCancelButton:    false,
                content:             message,
                onConfirm:           function(){ dialog.hide(); }
            });
            dialog.show();
            return;
        }

        // prepare url params to load the delete form
        var urlParams = {
            module: 'content',
            action: 'delete'
        };

        // create form dialog
        dialog = new p4cms.ui.FormDialog({
            title:      'Delete Content',
            'class':    'content-delete-dialog',
            urlParams:  urlParams
        });

        // insert note about total number of entries to delete
        dojo.connect(dialog, 'onLoad', function(){
            // get form node to put the note in
            var node = dojo.query('.delete-confirmation', dialog.domNode);
            if (node.length) {
                node[0].innerHTML = 'Are you sure you want to delete the '
                    + (selectedIds.length > 1
                        ? selectedIds.length + ' selected content entries?'
                        : 'selected content entry?');
            }
        });

        // insert list of hidden elements with entries' ids to delete when form is saved
        dojo.connect(dialog, 'onSave', function() {
            var form = dojo.query('form', dialog.domNode);
            if (form.length) {
                // remove all ids input nodes
                dojo.query('input[name*=ids]', form[0]).remove();

                // insert new ids fields
                dojo.forEach(selectedIds, function(id){
                    dojo.create('input', {type: 'hidden', name: 'ids[]', value: id}, form[0]);
                });
            }
        });

        // clear selection and refresh the grid when form is saved
        dojo.connect(dialog, 'onSaveSuccess', function(response) {
            var deletedCount = response.deletedIds.length || 0;
            var notice = new p4cms.ui.Notice({
                message:    'Deleted ' + (deletedCount === 1
                    ? ' 1 content entry.'
                    : deletedCount + ' content entries.'),
                severity:   'success'
            });
            grid.selection.deselectAll();
            grid.refresh();
        });

        // connect to onSaveError to show error message
        dojo.connect(dialog, 'onSaveError', function(response) {
            var notice = new p4cms.ui.Notice({
                message:    response.message || 'An unknown error occurred.',
                severity:   'error'
            });
        });

        dialog.show();
    }
};
