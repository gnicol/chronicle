// summary:
//      Support for conflict and other opened handling when editing data.

dojo.provide("p4cms.content.ConflictHandler");
dojo.require("p4cms.ui.Notice");
dojo.require("p4cms.ui.ConfirmDialog");

dojo.declare("p4cms.content.ConflictHandler", null,
{
    entry:         null,
    pingInterval:  30 * 1000,

    pingTimerId:   null,
    conflicts:     [],
    otherOpened:   null,
    started:       false,
    xhrDeferred:   null,

    constructor: function(options){
        dojo.mixin(this, options);
    },

    start: function() {
        // stop any in flight xhr requests
        if (this.xhrDeferred) {
            this.xhrDeferred.cancel();
        }

        // if we are already started, don't have an entry, or are doing
        // an add there's nothing to do here.
        if (this.started || !this.entry || this.entry.isAdd()) {
            return;
        }

        // query the server with an empty event to just get a look at the
        // current conflict/opened state without influencing anything.
        // if after the initial exchange, the user proceeds with editing,
        // we query the server again and inform it that we are starting.
        this.queryServer().then(dojo.hitch(this, function(data) {
            this.handleResponse(data, true).then(dojo.hitch(this, function() {
                this.started = true;
                this.queryServer('start').then(dojo.hitch(this, function(data) {
                    this.handleResponse(data);
                }));
            }));
        }));
    },

    stop: function() {
        // stop any in flight xhr requests
        if (this.xhrDeferred) {
            this.xhrDeferred.cancel();
        }

        // if we aren't started nothing to do
        if (!this.started) {
            return;
        }

        // stop our timer
        if (this.pingTimerId) {
            window.clearTimeout(this.pingTimerId);
        }

        // tell the server we are done
        return this.queryServer('stop').then(dojo.hitch(this, function(){
            this.started = false;
        }));
    },

    queryServer: function(event) {
        this.xhrDeferred = dojo.xhrPost({
            url:      p4cms.url({
                module: 'content',
                action: 'opened',
                event:  event || '',
                id:     this.entry.contentId
            }),
            handleAs: 'json'
        });

        return this.xhrDeferred;
    },

    handleSave: function() {
        var confirmDeferred = new dojo.Deferred();

        // extract the last conflict
        var data = this.getLatestConflict();

        // if we don't have any conflicts just resolve and return
        if (!data || !data.status) {
            confirmDeferred.resolve();
            return confirmDeferred;
        }

        // different messages for delete v.s. edit
        var message;
        var buttonLabel;
        if (data.status.Action.match(/delete/)) {
            buttonLabel = 'Restore and Save';
            message     = 'User ' + data.change.User + " deleted this entry "
                        + p4cms.ui.timeAgo(data.change.Date) + ".<br><br>"
                        + "Do you want to restore this entry and save your edits?";
        } else {
            buttonLabel = 'Overwrite';
            message     = 'User ' + data.change.User + " edited this entry "
                        + p4cms.ui.timeAgo(data.change.Date) + ".<br>"
                        + "<a href='' onclick='p4cms.content.getActive().diffHaveAgainst("
                        + data.status.Version + "); return false;'>Show their content changes</a>"
                        + "<br><br>Do you want to overwrite their changes with your edits?";
        }

        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Save Conflict',
            actionSingleClick:   true,
            content:             message,
            actionButtonOptions: { label: buttonLabel },
            onConfirm:           dojo.hitch(confirmDeferred, 'resolve'),
            onCancel:            dojo.hitch(confirmDeferred, 'cancel')
        });
        dialog.show();

        return confirmDeferred;
    },

    handleResponse: function(data, initial) {
        // if we aren't started (or at least starting) nothing to do
        if (!this.started && !initial) {
            return;
        }

        // create a deferred which will fire when the response has been
        // fully handled - e.g. after all of the various conflict/opened
        // prompts have been delt with.
        var deferred = new dojo.Deferred();

        // try to deal with the conflict messages. if this completes; we will move
        // on to handling any 'other opened' messages.
        dojo.when(this._handleConflict(data, initial), dojo.hitch(this, function() {

            // if handling other opened completes, resolve our deferred
            dojo.when(this._handleOtherOpened(data, initial), dojo.hitch(this, function() {
                deferred.resolve(data, initial);
            }));
        }));

        // we always want to schedule another ping if everything completed
        deferred.then(dojo.hitch(this, 'schedulePing'));

        return deferred;
    },

    schedulePing: function() {
        // clear any existing timeouts
        clearTimeout(this.pingTimerId);

        // setup a timeout to run the next check
        this.pingTimerId = setTimeout(
            dojo.hitch(this, function() {
                if (!this.started) {
                    return;
                }

                this.queryServer('ping').then(dojo.hitch(this, function(data) {
                    // our handler below will scedule
                    // another ping when it completes
                    this.handleResponse(data);
                }));
            }),
            this.pingInterval
        );
    },

    getLatestConflict: function() {
        if (!this.conflicts.length) {
            return;
        }

        return this.conflicts.slice(-1)[0];
    },

    // if running an intialCheck this can return a dojo.Deferred object
    // if a confirmation dialog ends up being used. otherwise null is
    // generally returned.
    _handleConflict: function(data, initialCheck) {
        // determine the last version we have seen
        var lastSeen = this.entry.headVersion;
        if (this.conflicts.length) {
            lastSeen = this.getLatestConflict().status.Version;
        }

        // if we have seen this version before, no new conflict.
        if (parseInt(data.status.Version, 10) <= parseInt(lastSeen, 10)) {
            return;
        }

        // we have a conflict! store it so we don't reprompt in future
        this.conflicts.push(data);

        // there are two types of conflict that can occur:
        // 1) the head version has been edited/added
        // 2) the head version has been deleted
        //
        // if this is not the initial check (i.e. a ping) we simply
        // want to inform the user that a conflict has occured.
        //
        // if this is the intial check we want to prompt the user to
        // either continue editing or to cancel.
        // if the user was attempting to edit the head revision but an
        // edit conflict has occured, we force a reload prior to editing.
        // if it was a rollback operation we don't force a reload as you
        // weren't acting against the head version already. if head was
        // deleted we also don't force a reload as that could result in
        // a 404 error.
        // if the user clicks cancel we reload the page to exit edit mode.

        var message = '';
        var reload  = false;
        if (data.status.Action.match(/delete/)) {
            message = 'User ' + data.change.User + " deleted this entry "
                    + p4cms.ui.timeAgo(data.change.Date) + ".";
        } else {
            message = 'User ' + data.change.User + " saved a new version of this entry "
                    + p4cms.ui.timeAgo(data.change.Date) + ".<br>"
                    + "<a href='' onclick='p4cms.content.getActive().diffHaveAgainst("
                    + data.status.Version + "); return false;'>"
                    + "Show their content changes</a>";

            // if we were attempting to edit head, we need to reload (on initial check).
            if (this.entry.contentVersion === this.entry.headVersion) {
                reload = true;
            }
        }

        if (initialCheck) {
            var confirmDeferred = new dojo.Deferred();
            var dialog          = new p4cms.ui.ConfirmDialog({
                title:               'Conflict',
                content:             message,
                actionButtonOptions: { label: reload ? 'Edit New Version' : 'Edit Anyway' },
                actionSingleClick:   true,
                cancelSingleClick:   true,
                onConfirm:           dojo.hitch(this, function() {
                    if (!reload) {
                        dialog.hide();

                        // resoslve our deferred as conflicts are handled
                        confirmDeferred.resolve(data, initialCheck);
                        return;
                    }

                    // reload and edit.
                    p4cms.openUrl({
                        module:     'content',
                        action:     'edit',
                        id:         this.entry.contentId
                    });
                }),
                onCancel: dojo.hitch(this, function(){
                    // prevent the dialog from hiding while the page reloads
                    dialog.hide = function(){};

                    // utilize cancel to force a reload to the 'view' action
                    this.entry.cancel();
                })
            });
            dialog.show();

            return confirmDeferred;
        } else {
            var notice  = new p4cms.ui.Notice({
                name:       'content-entry-collision',
                message:    message,
                sticky:     true,
                severity:   "warning"
            });
        }
    },

    // if running an intialCheck this can return a dojo.Deferred object
    // if a confirmation dialog ends up being used. otherwise null is
    // generally returned.
    _handleOtherOpened: function(data, initialCheck) {
        // protect against no opened data
        if (!data.opened) {
            return;
        }

        // remove ourselves from the list
        delete data.opened[p4cms.user.active.id];

        // determine which users started, stopped or are still editing
        this.otherOpened   = this.otherOpened || {};
        var startedEditing = [];
        var stoppedEditing = [];
        var stillEditing   = [];
        var user, message, notice;
        for (user in data.opened) {
            if (data.opened.hasOwnProperty(user)) {
                if (this.otherOpened[user]) {
                    stillEditing.push(user);
                } else {
                    startedEditing.push(user);
                }
            }
        }
        for (user in this.otherOpened) {
            if (this.otherOpened.hasOwnProperty(user)) {
                if (!data.opened[user]) {
                    stoppedEditing.push(user);
                }
            }
        }

        // update our record of the last 'opened' response
        this.otherOpened = data.opened;

        if (initialCheck) {
            // if no one has it open; nothing to do
            if (!startedEditing.length) {
                return;
            }

            if (startedEditing.length === 1) {
                user = startedEditing[0];
                message = "User " + user + " is already editing this entry. They started editing<br>"
                        + p4cms.ui.timeAgo(data.opened[user].startTime) + ". ";

                if (data.opened[user].editTime) {
                    message += "Their last change was "
                            + p4cms.ui.timeAgo(data.opened[user].editTime) + ".";
                } else {
                    message += "They haven't made any changes yet.";
                }
            } else {
                message = "The following users are already editing this entry:";
                message += "<ul>";
                for (user in data.opened) {
                    if (data.opened.hasOwnProperty(user)) {
                        message += "<li>" + user + " (started " + p4cms.ui.timeAgo(data.opened[user].startTime);
                        if (data.opened[user].editTime) {
                            message += ", last change " + p4cms.ui.timeAgo(data.opened[user].editTime) + ")</li>";
                        } else {
                            message += ", no changes yet)</li>";
                        }
                    }
                }
                message += "</ul>";
            }

            var confirmDeferred = new dojo.Deferred();
            var dialog          = new p4cms.ui.ConfirmDialog({
                title:               'Conflict',
                content:             message,
                actionButtonOptions: { label: 'Edit Anyway' },
                actionSingleClick:   true,
                cancelSingleClick:   true,
                onConfirm:           dojo.hitch(this, function() {
                    // resoslve our deferred as other editors are being ignored
                    confirmDeferred.resolve(data, initialCheck);

                    dialog.hide();
                }),
                onCancel: dojo.hitch(this, function(){
                    // prevent the dialog from hiding while the page reloads
                    dialog.hide = function(){};

                    // utilize cancel to force a reload to the 'view' action
                    this.entry.cancel();
                })
            });
            dialog.show();

            return confirmDeferred;
        }

        // notify if any users stopped editing
        message = '';
        if (stoppedEditing.length === 1) {
            message = 'User ' + stoppedEditing[0] + " is no longer editing.<br>";
        } else if (stoppedEditing.length) {
            message = 'The following users are no longer editing: ' + stoppedEditing.join(', ') + ".<br>";
        }
        if (stoppedEditing.length && stillEditing.length) {
            message += 'Presently, ' + stillEditing.length + ' other user' + (stillEditing.length !== 1 ? 's are' : ' is') + ' editing this entry.';
        } else if (stoppedEditing.length) {
            message += 'Presently, you are the only user editing this entry.';
        }
        if (message) {
            notice  = new p4cms.ui.Notice({
                name:       'content-entry-stopped-edit',
                message:    message,
                sticky:     true,
                severity:   "warning"
            });
        }

        // notify if any users started editing
        message = '';
        if (startedEditing.length === 1) {
            message = 'User ' + startedEditing[0] + " started editing this entry.<br>";
        } else if (startedEditing.length) {
            message = 'The following users started editing this entry: ' + startedEditing.join(', ') + ".<br>";
        }
        if (message) {
            notice  = new p4cms.ui.Notice({
                name:       'content-entry-started-edit',
                message:    message,
                sticky:     true,
                severity:   "warning"
            });
        }
    }
});