// summary:
//      Content grid utility functions related to workflow module.

dojo.provide('p4cms.workflow.content.grid.Utility');
dojo.require("p4cms.ui.FormDialog");
dojo.require("p4cms.ui.ConfirmDialog");

p4cms.workflow.content.grid.Utility = {

    openWorkflowDialog: function(rowIndex) {
        var grid = p4cms.content.grid.instance;

        // assemble list of entry ids
        // if rowIndex is passed, list will contain id of the entry in row given by rowIndex,
        // otherwise list will contain ids of all entries that are selected in the grid
        var selectedItems = rowIndex !== undefined
            ? [grid.getItem(rowIndex)]
            : grid.selection.getSelected();

        // get list of entry ids selected in the grid
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

        // if all selected entries are deleted, present confirmation dialog and exit
        if (!dojo.some(selectedItems, function(item){return !item.i.deleted;})) {
            dialog = new p4cms.ui.ConfirmDialog({
                title:               'Change Workflow Status',
                actionButtonOptions: { label: 'Ok' },
                showCancelButton:    false,
                content:             'Workflow states cannot be assigned to deleted content.',
                onConfirm:           function(){dialog.hide();}
            });
            dialog.show();
            return;
        }

        // if selection contains entries with invalid workflow or type,
        // present confirmation dialog and exit
        if(dojo.some(selectedItems, function(item){return !item.i.workflowState;})) {
            dialog = new p4cms.ui.ConfirmDialog({
                title:               'Change Workflow Status',
                actionButtonOptions: { label: 'Ok' },
                showCancelButton:    false,
                content:             'Cannot change workflow status. ' +
                                     'One or more selected entries are in an unknown state.',
                onConfirm:           function(){dialog.hide();}
            });
            dialog.show();
            return;
        }

        // get a list of selected entries converted to their workflows
        // (entries with no workflows are converted to '')
        var selectedWorkflowIds = dojo.map(
            selectedItems,
            function(item){return item.i.workflowId;}
        );

        // filter selected workflows list to keep only unique non-empty items
        var unique = {};
        selectedWorkflowIds = dojo.filter(
            selectedWorkflowIds,
            function(item){
                if (!unique[item] && item !== '') {
                    unique[item] = true;
                    return true;
                } else {
                    return false;
                }
            }
        );

        // if all selected content entries have no workflow, present dialog with
        // a notification only
        if (!selectedWorkflowIds.length) {
            dialog = new p4cms.ui.ConfirmDialog({
                title:               'Change Workflow Status',
                actionButtonOptions: { label: 'Ok' },
                showCancelButton:    false,
                content:             'Selected entries have no workflow.',
                onConfirm:           function(){dialog.hide();}
            });
            dialog.show();
            return;
        }

        // prepare url params to load the workflow form
        var urlParams = {
            module:     'workflow',
            controller: 'content',
            action:     'change-state',
            workflows:  selectedWorkflowIds
        };

        // create form dialog
        dialog = new p4cms.ui.FormDialog({
            title:          'Change Workflow Status',
            'class':        'workflow-dialog',
            urlParams:      urlParams,
            preload:        true,
            destroyOnHide:  true
        });

        // if states radio is empty (for example if selected entries have no common workflow state)
        // don't display the dialog and show notification instead
        dojo.connect(dialog, 'onLoad', function() {
            var states = dojo.query('input[type=radio][name*=state]', dialog.domNode);
            if (!states.length) {
                var warningDialog = new p4cms.ui.ConfirmDialog({
                    title:               'Change Workflow Status',
                    actionButtonOptions: { label: 'Ok' },
                    showCancelButton:    false,
                    content:             'The selected entries do not share any common workflow states.',
                    onConfirm:           function(){
                        warningDialog.hide();
                        dialog.destroyRecursive();
                    }
                });
                warningDialog.show();
                return;
            } else {
                // IE doesn't like showing the dialog during onLoad, dojo
                // must have other things going on after this listener onLoad,
                // break the show out of the onLoad
                setTimeout(function() {
                    dialog.show();
                }, 1);

                // display current server time
                var timeNode = dojo.query('.current-time', dialog.domNode);
                if (timeNode.length) {
                    var timer = p4cms.workflow.showServerTime(timeNode[0]);

                    // connect to clear timer when dialog is closed
                    dojo.connect(dialog, 'onHide', function(){
                        clearInterval(timer);
                    });
                }
            }
        });

        // inject entry ids to change state when form is saved
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
            var changedCount = response.changedEntries.length || 0;
            var notice       = new p4cms.ui.Notice({
                message:    'Changed status on ' + (changedCount === 1
                    ? ' 1 content entry.'
                    : changedCount + ' content entries.'),
                severity:   'success'
            });
            grid.selection.deselectAll();
            grid.refresh();
            return;
        });

        // connect to save error to show warning dialog if there were entries unable to
        // change and allow user to proceed with with changing status for other entries
        dojo.connect(dialog, 'onSaveError', function(response) {
            if (!response.failedEntries || !response.failedEntries.length) {
                // this hould not happen - there was an error but no failed entries
                var notice = new p4cms.ui.Notice({
                    message:    'Unexpected error ocurred.',
                    severity:   'error'
                });
                return;
            }

            // get label of the selected state
            var selectedStatusLabel = '';
            var form = dojo.query('form', dialog.domNode);
            if (form.length) {
                // get label of selected state
                var inputNode = dojo.query('input[type=radio][name*=state][checked=checked]', form[0]);
                if (inputNode.length === 1) {
                    selectedStatusLabel = new dojo.NodeList(inputNode[0].parentElement).text();
                }
            }

            // show warning dialog with number of entries unable to change and
            // hook confirm button to force changing state on other entries
            var confirmDialog = new p4cms.ui.ConfirmDialog({
                title:               'Change Workflow Status',
                actionSingleClick:   true,
                actionButtonOptions: { label: 'Continue' },
                content:             response.failedEntries.length + ' of ' + selectedIds.length
                    + ' content entries selected cannot be changed to the'
                    + (selectedStatusLabel
                        ? ' status "' + selectedStatusLabel + '".'
                        : ' selected status.')
                    + '<br><br>'
                    + 'Do you want to continue with the status change for the other entries?',
                onConfirm:           function() {
                    // modify dialog's params to force processing the transition on entries
                    // where possible and save the dialog again
                    dialog.urlParams = dojo.mixin(dialog.urlParams, {forced: true});
                    dialog._save().then(function(){
                        confirmDialog.hide();
                    });
                }
            });

            confirmDialog.show();
        });
    }
};