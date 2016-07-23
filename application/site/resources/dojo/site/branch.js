// summary:
//      Support for branches.

dojo.provide("p4cms.site.branch");
dojo.require("p4cms.site.branch.grid.Formatters");
dojo.require("p4cms.site.branch.grid.Utility");
dojo.require("p4cms.site.branch.grid.Actions");
dojo.require("p4cms.ui.script");
dojo.require("p4cms.ui.FormDialog");

// dialog to add a branch to the current site.
p4cms.site.branch.addToActive = function() {
    var dialog = p4cms.site.branch.getDialog('add', {
        site:   p4cms.site.active.siteId,
        parent: p4cms.site.active.id,
        notify: true
    });

    // clobber save success to reload the page.
    // we don't connect because we don't want the default
    // behavior of save success (which is to close the dialog)
    dialog.onSaveSuccess = function() {
        window.location.reload();
    };

    // distinguish the add site branch dialog from the add branch
    // dialog invoked from the branch management grid (e.g. so we
    // can hide the site selection field)
    dojo.addClass(dialog.domNode, 'add-to-active');

    dialog.show();
};

// dialog to edit the current branch.
p4cms.site.branch.editActive = function() {
    var dialog = p4cms.site.branch.getDialog('edit', {
        id: p4cms.site.active.id
    });

    dialog.show();
};

// to switch branches we do a jsonp request to the branch
// switch action which ensures we have a valid session on
// the target branch with the same credentials as the
// current branch and verifies the target url works.
p4cms.site.branch.switchTo = function(branch) {
    // if the target branch has no explicit url, try the current url.
    branch.url = branch.url || window.location.protocol + '//' + window.location.host;

    var deferred = p4cms.ui.script.get({
        url:        p4cms.url({
            module:         'site',
            controller:     'branch',
            action:         'switch',
            format:         'json'
        }, null, branch.url),
        jsonp:      'callback',
        timeout:    5000,
        content:    {
            sessionId:      dojo.cookie('PHPSESSID'),
            _csrfToken:     p4cms.ui.csrfToken
        },
        load:       function(data){
            // if the custom url didn't respond with the expected
            // branch throw, error handler will redirect to fallback
            if (data.branch !== branch.basename) {
                throw "Unexpected branch";
            }

            p4cms.openUrl(branch.url);
        },
        error:      function(data){
            // fall back to using the '-branch-' convention on error
            var url = p4cms.baseUrl.replace(/\/+$/, "") + '/-' + branch.basename + '-';

            // if this is the same url we tried earlier, error out.
            var origin = window.location.protocol + '//' + window.location.host;
            if (origin + url === branch.url) {
                var message = "Unable to switch to '" + branch.name + "'.<br>"
                            + "Branch is unreachable.";
                var notice = new p4cms.ui.Notice({message: message, severity: "error"});
                return;
            }

            p4cms.openUrl(url);
        }
    });
};

p4cms.site.branch.pullFrom = function(branch) {
    var dialog = new p4cms.ui.FormDialog({
        title:      'Pull',
        urlParams:  {
            module:     'site',
            controller: 'branch',
            action:     'pull',
            source:     branch.id
        },
        onSaveSuccess: function(response) {
            window.location.reload();
        }
    });

    // ensure we update the form when the source or mode change.
    // update the conflict count onload and when options change.
    // also make clicking on count show a list of details in a tooltip
    dojo.connect(dialog, 'onLoad', function() {
        var form   = dojo.query('form', dialog.domNode)[0];
        var params = dojo.mixin(dojo.formToObject(form), {
            module:     'site',
            controller: 'branch',
            action:     'pull-details',
            format:     'partial'
        });

        // reload the form anytime the source or mode changes.
        dojo.query('select[name=source]', dialog.domNode)
            .connect('onchange', dialog, 'refreshForm');
        dojo.query('input[name=mode]', dialog.domNode)
            .connect('onclick', dialog, 'refreshForm');

        // move quantity column out of checkbox label element
        // so that clicking quantity doesn't toggle checkbox
        dojo.query('.nested-checkbox .count-column', dialog.domNode).
            forEach(function(quantity) {
                dojo.place(quantity, quantity.parentNode, 'after');
            });

        // make counts clickable to show details for groups that have paths.
        dojo.query('.nested-checkbox .has-paths .count', dialog.domNode)
            .forEach(function(count) {
                // if we don't have an associated checkbox, or there is
                // no value, exit early as we don't want to linkify
                var list     = new dojo.NodeList(count).closest('li');
                var checkbox = dojo.query('input[type=checkbox][value]', list[0])[0];
                if (!checkbox || !checkbox.value) {
                    return;
                }

                // limit details to just this checkbox's group
                params.groupId = checkbox.value;

                // linkify the count to show details
                var link = dojo.create('a', {
                    innerHTML: count.innerHTML
                }, count, 'only');

                // attach a tooltip to load details for this checkbox
                var tooltip = new p4cms.ui.TooltipDialog({
                    href:           p4cms.url(params),
                    'class':        'p4cms-branch-pull-details',
                    executeScripts: true
                });
                tooltip.attachToElement(count, {}, true, true);
            });

        // Ensure the conflict count shown at the bottom of the form
        // reflects the number of conflicting items selected for pull.
        // If no conflicting items are selected hide the message.
        var updateConflictCount = function() {
            // note is only present if conflicts exist to be counted
            var note = dojo.query('.conflict-note', dialog.domNode)[0];
            if (!note) {
                return;
            }

            var total = 0;
            dojo.query('.nested-checkbox input[type=checkbox]', dialog.domNode)
                .forEach(function(checkbox) {
                    if (checkbox.checked && !checkbox.disabled) {
                        var list  = new dojo.NodeList(checkbox).closest('li');
                        var count = dojo.query('.conflict-count', list[0])[0];
                        total    += parseInt(count.innerHTML, 10);
                    }
                });

            dojo.query('.conflict-note .count', dialog.domNode)
                .attr('innerHTML', total);

            dojo.query('.conflict-note .items', dialog.domNode)
                .attr('innerHTML', 'item' + (total > 1 ? 's' : ''));

            dojo.style(note, 'display', total ? 'inline' : 'none');
        };

        dojo.query('.nested-checkbox input[type=checkbox]', dialog.domNode)
            .connect('onclick', updateConflictCount);

        updateConflictCount();

        // Disable the pull button if no items are selected
        var updatePullButton = function() {
            var checked = dojo.query('.nested-checkbox input[type=checkbox]:checked', dialog.domNode);
            var button  = dijit.byId('pull-pull', dialog.domNode);
            if (button) {
                button.attr('disabled', !checked.length);
            }
        };

        dojo.query('.nested-checkbox input[type=checkbox]', dialog.domNode)
            .connect('onclick', updatePullButton);

        updatePullButton();
    });

    dialog.show();
};

// helper method to get a add/edit dialog for branches.
p4cms.site.branch.getDialog = function(action, params) {
    // assemble url params
    var urlParams = {
        module:     'site',
        controller: 'branch',
        action:     action
    };

    var dialog = new p4cms.ui.FormDialog({
        'class':    'branch-dialog',
        title:      p4cms.ui.capitalize(action) + ' Branch',
        urlParams:  dojo.mixin(params, urlParams)
    });

    // ensure we update the form when source site changes
    dojo.connect(dialog, 'onLoad', function() {
        dojo.query('select[name=site]', dialog.domNode)
            .connect('onchange', dialog, 'refreshForm');
    });

    return dialog;
};