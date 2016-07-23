dojo.provide("p4cms.menu.grid.Actions");

dojo.require("p4cms.ui.FormDialog");

p4cms.menu.grid.Actions = {
    onClickAddMenuItem: function() {
        var rowIndex = dijit.byId('dijitmenu-menus-grid').rowIndex;
        var values   = p4cms.menu.grid.instance.getItemValues(rowIndex);

        p4cms.menu.grid.Utility.openFormDialog(
            'add-item',
            {
                id:     values.menuItemId || '',
                menuId: values.menuId
            },
            ' Item'
        );
    },

    onClickGoToMenuItem: function() {
        var rowIndex = dijit.byId('dijitmenu-menus-grid').rowIndex;
        var values   = p4cms.menu.grid.instance.getItemValues(rowIndex);

        window.location.href = values.href;
    },

    onShowGoToMenuItem: function (menuItem) {
        var rowIndex = dijit.byId('dijitmenu-menus-grid').rowIndex;
        var values   = p4cms.menu.grid.instance.getItemValues(rowIndex);

        menuItem.set("label", "Go To '" + values.label + "'");
        dojo.style(menuItem.domNode, 'display', values.href ? '' : 'none');
    },

    onClickEdit: function() {
        var rowIndex = dijit.byId('dijitmenu-menus-grid').rowIndex;
        var values   = p4cms.menu.grid.instance.getItemValues(rowIndex);

        // handle the edit menu item case
        if (values.menuItemId) {
            p4cms.menu.grid.Utility.openFormDialog(
                'edit-item',
                {
                    id:     values.menuItemId,
                    menuId: values.menuId
                },
                ' Item'
            );
        }

        // handle edit menu
        if (!values.menuItemId) {
            p4cms.menu.grid.Utility.openFormDialog('edit', {id: values.menuId});
        }
    },

    onClickDelete: function() {
        var rowIndex = dijit.byId('dijitmenu-menus-grid').rowIndex;
        var values   = p4cms.menu.grid.instance.getItemValues(rowIndex);
        var params   = {format: 'json'};
        var type     = 'Menu' + (values.menuItemId ? ' Item' : '');

        // set the id and, optionally, menuId params as appropriate
        if (values.menuItemId) {
            params.menuId = values.menuId;
            params.id     = values.menuItemId;
        } else {
            params.id     = values.menuId;
        }

        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Delete ' + type,
            content:             'Are you sure you want to delete the '
                                 + type + ' "' + values.label + '"?',
            actionButtonOptions: { label: 'Delete' },
            onConfirm:           function() {
                // xhr delete entry.
                dojo.xhrPost({
                    url:        p4cms.url({
                        module:     'menu',
                        controller: 'manage',
                        action:     'delete' + (values.menuItemId ? '-item' : '')
                    }),
                    content:    params,
                    handleAs:   'json',
                    load:       function(response) {
                        p4cms.menu.grid.instance.refresh();
                        dialog.hide();

                        var notice = new p4cms.ui.Notice({
                            message:    "Menu " + (values.menuItemId ? 'item' : '') + " deleted.",
                            severity:   "success"
                        });
                    },
                    error:      function() {
                        // construct error message (append details if available).
                        var errorMessage    = p4cms.ui.getXhrErrorMessage(arguments),
                            message         = "Unexpected error when trying to delete "
                                            + type
                                            + (errorMessage ? ": <br />" + errorMessage : '.'),
                            notice          = new p4cms.ui.Notice({
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

    onClickReset: function() {
        var rowIndex = dijit.byId('dijitmenu-menus-grid').rowIndex;
        var values   = p4cms.menu.grid.instance.getItemValues(rowIndex);
        var params   = {format: 'json', id: values.menuId};

        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Reset Menu',
            content:             'Are you sure you want to reset the '
                                 + values.label + ' menu?',
            actionButtonOptions: { label: 'Reset' },
            onConfirm:           function() {
                // xhr reset menu.
                dojo.xhrPost({
                    url:        p4cms.url({
                        module:     'menu',
                        controller: 'manage',
                        action:     'reset'
                    }),
                    content:    params,
                    handleAs:   'json',
                    load:       function(response) {
                        p4cms.menu.grid.instance.refresh();
                        dialog.hide();

                        var notice = new p4cms.ui.Notice({
                            message:    "Menu reset.",
                            severity:   "success"
                        });
                    },
                    error:      function() {
                        // construct error message (append details if available).
                        var errorMessage    = p4cms.ui.getXhrErrorMessage(arguments),
                            message         = "Unexpected error when trying to reset menu"
                                            + (errorMessage ? ": <br />" + errorMessage : '.'),
                            notice          = new p4cms.ui.Notice({
                                message:    message,
                                severity:   "error"
                            });

                        // re-enable reset button
                        dijit.byId(dialog.domNode.id + "-button-action").enable();
                    }
                });
            }
        });

        dialog.show();
    },

    onShowReset: function (menuItem) {
        var rowIndex = dijit.byId('dijitmenu-menus-grid').rowIndex;
        var values   = p4cms.menu.grid.instance.getItemValues(rowIndex);

        dojo.style(menuItem.domNode, 'display', values.isDefaultMenu ? '' : 'none');
    },

    // hooks into the selection function to allow you to prevent
    // selection based on your own criteria
    isSelectionValid: function (type, item, allowNotSelectable) {
        // only allow menu items to be selected
        var value = p4cms.menu.grid.instance.getItemValues(item.row);
        return Boolean(value.menuItemId);
    },

    // handles moving of rows in the grid
    // takes an array of the rowIndexes to move
    // and the index of the target row to place rows before
    onMoveRows: function (rowsToMove, targetObj) {
        var targetPos   = targetObj.pos,
            params      = {'format': 'json', '_csrfToken': p4cms.ui.csrfToken},
            grid        = p4cms.menu.grid.instance,
            source      = grid.getItemValues(rowsToMove[0]),
            inSelfMsg   = "Cannot place within self";
        params.uuid     = source.menuItemId;
        params.menuId   = source.menuId;

        if (params.uuid) {
            // grab a reference to the rows surounding the target drop
            var top             = grid.getItemValues(targetPos-1),
                bottom          = grid.getItemValues(targetPos),
                next            = grid.getItemValues(targetPos+1),
                // default position to be before the target botttom
                target          = bottom,
                position        = 'before';

            // determine if target is a child of source
            var base    = bottom || top,
                index   = targetPos,
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

            // don't use the next object if it is the same as our source
            if (next && next.menuItemId === source.menuItemId) {
                next = null;
            }

            // determine special conditions for positioning
            // note: L = lower than, H = higher than
            var nextIsItem              = (next && next.menuItemId),
                nextLBottom             = (next && next.depth > bottom.depth),
                topLSource              = (top && top.depth > source.depth),
                topHSource              = (top && top.depth < source.depth),
                bottomHTop              = (bottom && top && bottom.depth < top.depth),
                bottomHTopHSource       = (topHSource && bottomHTop),
                topEtSource             = (top && top.depth === source.depth),
                bottomLSource           = (bottom && bottom.depth > source.depth),
                allLSourceTopHBottom    = (topLSource && bottomLSource && top.depth < bottom.depth);

            // position BEFORE NEXT if we do not have a top or we are doing a drop, and the next menu is a menu item
            // position UNDER BOTTOM if we have a bottom, and either we don't have a top, or we are doing a drop
            // position UNDER TOP if both the top and bottom are menus and not menu items
            // position AFTER TOP if we don't have a bottom, or if the top is at the same level, or the the bottom
            //          is higher than the top, both of which are higher than our source, or if both the bottom
            //          and the top are lower than the source, but the top is higher than the bottom
            if ((!top || targetObj.dropIn) && nextIsItem && nextLBottom) {
                position    = 'before';
                target      = next;
            } else if (bottom && (!top || targetObj.dropIn)) {
                position    = 'under';
                target      = bottom;
            } else if (top && bottom && !top.menuItemId && !bottom.menuItemId) {
                position    = 'under';
                target      = top;
            } else if (!bottom || topEtSource || bottomHTopHSource || allLSourceTopHBottom) {
                position    = 'after';
                target      = top;
            }

            // do nothing if target is the source, or is a target is child of source
            if ((target && target.menuItemId === source.menuItemId) || isChild) {
                return;
            }

            params.position = position;
            params.location = target.menuItemId ? target.menuId + '/' + target.menuItemId : target.menuId;

            // xhr reorder menu items.
            grid.pluginMgr.getPlugin("p4cms-dnd").enabled = false;
            this.showMessage(this.loadingMessage);
            dojo.xhrPost({
                url:        p4cms.url({
                    module:     'menu',
                    controller: 'manage',
                    action:     'reorder',
                    id:         params.uuid,
                    menuId:     params.menuId
                }),
                content:    params,
                handleAs:   'json',
                load:       dojo.hitch(this, function(response) {
                    var notice = new p4cms.ui.Notice({
                        message:    "Order Saved.",
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

                    if (key === 'location' && dojo.isObject(errorMessage) && errorMessage.hasOwnProperty('notInArray')) {
                        message = inSelfMsg;
                    }

                    // construct error message (append details if available).
                    errorMessage = errorMessage || p4cms.ui.getXhrErrorMessage(arguments);
                    message             = message || "Unexpected error when trying to reorder menu"
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