dojo.provide("p4cms.comment.grid.Actions");

p4cms.comment.grid.Actions = {
    onClickDelete: function() {
        var rowIndex  = dijit.byId('dijitmenu-comment-grid').rowIndex;
        var values    = p4cms.comment.grid.instance.getItemValues(rowIndex);
        var dialog    = new p4cms.ui.ConfirmDialog({
                title:              'Delete Comment',
                content:            'Are you sure you want to delete this comment?',
                actionButtonLabel:  'Delete',
                actionSingleClick:  true,
                onConfirm:          function() {
                    var url = p4cms.url({
                        module:     'comment',
                        controller: 'index',
                        action:     'delete',
                        id:         values.id
                    });

                    dojo.xhrPost({
                        url:    url,
                        load:   function(){
                            p4cms.comment.grid.instance.refresh();
                            var notice = new p4cms.ui.Notice({
                                message:  "Comment deleted.",
                                severity: "success"
                            });
                        },
                        error:  function(){
                            var notice = new p4cms.ui.Notice({
                                message:  "Failed to delete comment.",
                                severity: "error"
                            });
                        },
                        handle: function(){
                            dialog.hide();
                        }
                    });
                }
            });
        dialog.show();
    },

    onClickApprove: function() {
        p4cms.comment.grid.Actions.changeState('approved');
    },

    onShowApprove: function (menuItem) {
        p4cms.comment.grid.Actions.showHideStateChange(menuItem, 'approved');
    },

    onClickReject: function() {
        p4cms.comment.grid.Actions.changeState('rejected');
    },

    onShowReject: function (menuItem) {
        p4cms.comment.grid.Actions.showHideStateChange(menuItem, 'rejected');
    },

    onClickPend: function() {
        p4cms.comment.grid.Actions.changeState('pending');
    },

    onShowPend: function (menuItem) {
        p4cms.comment.grid.Actions.showHideStateChange(menuItem, 'pending');
    },

    changeState: function(state) {
        var rowIndex = dijit.byId('dijitmenu-comment-grid').rowIndex;
        var values   = p4cms.comment.grid.instance.getItemValues(rowIndex);
        var url      = p4cms.url({
            module:     'comment',
            controller: 'index',
            action:     'status',
            id:         values.id,
            state:      state
        });

        dojo.xhrPost({
            url:    url,
            load:   function(){
                p4cms.comment.grid.instance.refresh();
                var notice = new p4cms.ui.Notice({
                    message:  "Comment " + state + ".",
                    severity: "success"
                });
            },
            error:  function(){
                var notice = new p4cms.ui.Notice({
                    message:  "Failed to " + state + " comment.",
                    severity: "error"
                });
            }
        });
    },

    showHideStateChange: function(menuItem, state) {
        var rowIndex  = dijit.byId('dijitmenu-comment-grid').rowIndex;
        var values    = p4cms.comment.grid.instance.getItemValues(rowIndex);
        var display   = values.status === state ? 'none' : '';

        dojo.style(menuItem.domNode, 'display', display);
    },

    onClickView: function() {
        var rowIndex    = dijit.byId('dijitmenu-comment-grid').rowIndex;
        var values      = p4cms.comment.grid.instance.getItemValues(rowIndex);
        window.location = values.content.uri;
    },

    onShowView: function(menuItem) {
        var rowIndex  = dijit.byId('dijitmenu-comment-grid').rowIndex;
        var values    = p4cms.comment.grid.instance.getItemValues(rowIndex);
        var display   = values.content ? '' : 'none';

        dojo.style(menuItem.domNode, 'display', display);
    }
};