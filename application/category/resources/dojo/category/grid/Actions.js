dojo.provide("p4cms.category.grid.Actions");

dojo.require("p4cms.ui.ConfirmDialog");
dojo.require("p4cms.ui.Notice");

p4cms.category.grid.Actions = {
    onClickView: function() {
        var rowIndex         = dijit.byId('dijitmenu-categories-grid').rowIndex;
        var values           = p4cms.category.grid.instance.getItemValues(rowIndex);
        window.location.href = p4cms.baseUrl + values.uri;
    },

    onClickEdit: function() {
        var rowIndex         = dijit.byId('dijitmenu-categories-grid').rowIndex;
        p4cms.category.grid.Utility.openFormDialog('edit', {
            category: p4cms.category.grid.instance.getItemValue(rowIndex, 'id')
        });
    },

    onClickDelete: function() {
        var rowIndex = dijit.byId('dijitmenu-categories-grid').rowIndex;
        var values   = p4cms.category.grid.instance.getItemValues(rowIndex);
        var urlParms = {
            module:     'category',
            controller: 'manage',
            action:     'delete'
        };
        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Delete Category',
            content:             'Are you sure you want to delete the "' + values.title + '" category?',
            actionButtonOptions: { label: 'Delete Category' },
            actionSingleClick:   true,
            onConfirm: function() {
                // xhr delete entry.
                dojo.xhrPost({
                    url:        p4cms.url(urlParms),
                    content:    {id: values.id, format: 'json'},
                    handleAs:   'json',
                    load:       function(response) {
                        p4cms.category.grid.instance.refresh();
                        var notice = new p4cms.ui.Notice({
                            message:  "Category '" + response.category.title + "' has been deleted.",
                            severity: "success"
                        });
                        dialog.hide();
                    },
                    error:      function() {
                        // construct error message (append details if available).
                        var errorMessage = p4cms.ui.getXhrErrorMessage(arguments);
                        var message = "Unexpected error when trying to delete '"
                                    + values.title + "' category"
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

    // handles moving of rows in the grid
    // takes an array of the rowIndexes to move
    // and the index of the target row to place rows before
    onMoveRows: function (rowsToMove, targetObj) {
        var targetPos   = targetObj.pos,
            params      = {'_csrfToken': p4cms.ui.csrfToken},
            grid        = p4cms.category.grid.instance,
            // this grid only handles dragging one row
            source      = grid.getItemValues(rowsToMove[0]),
            target      = grid.getItemValues(targetPos),
            inSelfMsg   = "Cannot place within self";

        if (source.id) {
            params.parent   = target && target.id;

            // set parent to null if we are placing at the very top and not dropping in
            params.parent   = (targetPos === 0 && !targetObj.dropIn ? null : params.parent);

            if (target) {
                // determine if target is a child of source
                var base    = target,
                    store   = grid.store,
                    isChild;
                while (base && base.parentId && !isChild) {
                    isChild = (base.parentId === source.id);
                    base = store._itemsByIdentity[base.parentId];
                    base = base && base.i;
                }
                if (isChild) {
                    var errorNotice = new p4cms.ui.Notice({
                        message:    inSelfMsg,
                        severity:   "error"
                    });
                }

                // do nothing if target is the source,
                // source is already immediately under target, or is a target is child of source
                if (target.id === source.id || target.id === source.parentId || isChild) {
                    return;
                }
            }

            // xhr rearrange categories
            grid.pluginMgr.getPlugin("p4cms-dnd").enabled = false;
            this.showMessage(this.loadingMessage);
            dojo.xhrPost({
                url:        p4cms.url({
                    module:     'category',
                    controller: 'manage',
                    action:     'move',
                    category:   source.id,
                    format:     'json'
                }, 'category-manage'),
                content:    params,
                handleAs:   'json',
                load:       dojo.hitch(this, function(response) {
                    var notice = new p4cms.ui.Notice({
                        message:    "Categories Moved.",
                        severity:   "success"
                    });

                    this.selection.clear();

                    // renable dnd on fetch complete
                    var fetchComplete, fetchError, fetchHandler = function() {
                        this.pluginMgr.getPlugin("p4cms-dnd").enabled = true;
                        this.disconnect(fetchComplete);
                        this.disconnect(fetchError);
                    };
                    fetchComplete       = this.connect(this, '_onFetchComplete', fetchHandler);
                    fetchError          = this.connect(this, '_onFetchError', fetchHandler);

                    // refresh the list
                    this.refresh();
                }),
                error:      dojo.hitch(this, function(event, response) {
                    var errors = dojo.fromJson(response.xhr.responseText).errors,
                        errorMessage, key, message;
                    // Show the last error
                    for (key in errors) {
                        if (errors.hasOwnProperty(key)) {
                            errorMessage = errors[key];
                        }
                    }

                    if (key === 'parent' && errorMessage && String(errorMessage).match('is within source')) {
                        message = inSelfMsg;
                    }

                    // construct error message (append details if available).
                    errorMessage = errorMessage || p4cms.ui.getXhrErrorMessage(arguments);
                    message             = message || "Unexpected error when trying to move categories"
                                        + (errorMessage ? ": <br />" + errorMessage : '.');
                    var notice          = new p4cms.ui.Notice({
                            message:    message,
                            severity:   "error"
                        });
                    grid.pluginMgr.getPlugin("p4cms-dnd").enabled = true;
                    this.showMessage();
                })
            });
        }
    }
};