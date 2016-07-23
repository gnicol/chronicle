// summary:
//      Support for user acl grid.

dojo.provide("p4cms.user.acl.grid.Utility");

p4cms.user.acl.grid.Utility = {
    ruleChange: function(input) {
        var vprivilege, query,
            grid        = p4cms.user.acl.grid.instance,
            role        = dojo.attr(input, 'roleId'),
            resource    = dojo.attr(input, 'resourceId'),
            privilege   = dojo.attr(input, 'privilegeId'),
            type        = privilege ? 'privilege' : 'resource',
            rules       = this.getResourceRules(role, resource);

        // record the starting rules right before the first change
        if (this.startRules === undefined) {
            this.setStartRules();
            this.onBeforeUnloadHandler = dojo.connect(
                window,
                "onbeforeunload",
                this,
                "promptBeforeUnload"
            );
        }

        // when a resource is toggled, toggle all of its privileges.
        if (type === 'resource') {

            // update grid.
            query = "input[roleId='" + role + "']"
                  + "[resourceId='" + resource + "']";
            dojo.query(query, grid.domNode).forEach(function(element) {
                if (!element.disabled) {
                    element.checked = input.checked;
                }
            });

            // update rules.
            for (vprivilege in rules) {
                if (rules.hasOwnProperty(vprivilege)) {
                    if (rules[vprivilege] && !rules[vprivilege].disabled) {
                        rules[vprivilege].allowed = input.checked;
                    }
                }
            }

            return;
        }

        // update privilege rule.
        if (rules[privilege] && !rules[privilege].disabled) {
            rules[privilege].allowed = input.checked;
        }

        // get associated resource input.
        query = "input[roleId='" + role + "'][resourceId='" + resource + "'][privilegeId='']";
        var resourceInput = dojo.query(query, grid.domNode)[0];

        // if all enabled privileges for a resource are checked,
        // check the resource; otherwise, clear it.
        for (vprivilege in rules) {
            if (rules.hasOwnProperty(vprivilege)) {
                privilege = rules[vprivilege];
                if (!privilege.allowed && !privilege.disabled) {
                    resourceInput.checked = false;
                    return;
                }
            }
        }

        // still here? resource should be checked.
        resourceInput.checked = 'checked';
    },

    isAllowed: function(role, resource, privilege) {
        var rules = p4cms.user.acl.grid.rules;

        // if no rules for this role, not allowed.
        if (!rules[role]) {
            return false;
        }
        rules = rules[role];

        // if no rules for this resource, not allowed.
        if (!rules[resource]) {
            return false;
        }
        rules = rules[resource];

        // if this is a privilege, return privilege rule.
        if (privilege) {
            return rules[privilege] && rules[privilege].allowed;
        }

        // for a resource to be allowed, all privileges need to be allowed.
        var vprivilege;
        for (vprivilege in rules) {
            if (rules.hasOwnProperty(vprivilege)) {
                if (!rules[vprivilege]
                    || (!rules[vprivilege].allowed && !rules[vprivilege].disabled)
                ) {
                    return false;
                }
            }
        }

        // all privileges are allowed, allow resource.
        return true;
    },

    isDisabled: function(role, resource, privilege) {
        var rules = p4cms.user.acl.grid.rules;

        // check for rules for this role.
        if (!rules[role]) {
            return false;
        }
        rules = rules[role];

        // check for rules for this resource.
        if (!rules[resource]) {
            return false;
        }
        rules = rules[resource];

        // check for entry for this privilege.
        if (!rules[privilege]) {
            return false;
        }

        // return privilege disabled element.
        return rules[privilege].disabled;
    },

    getResourceRules: function(role, resource) {
        var rules = p4cms.user.acl.grid.rules;

        // if no rules for this role, not allowed.
        if (!rules[role]) {
            return false;
        }
        rules = rules[role];

        // if not rules for this resource, not allowed.
        if (!rules[resource]) {
            return false;
        }
        rules = rules[resource];

        return rules;
    },

    save: function() {
        dojo.xhrPost({
            url:        p4cms.url({
                module:     'user',
                controller: 'acl',
                action:     'save'
            }),
            content:    {format: 'json', rules: dojo.toJson(p4cms.user.acl.grid.rules)},
            load:       function() {
                var notice = new p4cms.ui.Notice({
                    message:  'Permissions saved.',
                    severity: 'success'
                });

                // update rules for use in hasChanged
                p4cms.user.acl.grid.Utility.setStartRules();
            },
            error:      function() {
                var notice = new p4cms.ui.Notice({
                    message:  'Failed to save permissions.',
                    severity: 'error'
                });
            }
        });
    },

    hasResourceRow: function(resource) {
        var i, grid = p4cms.user.acl.grid.instance;
        for (i in grid._by_idx) {
            if (grid._by_idx.hasOwnProperty(i)) {
                var item = grid._by_idx[i].item.i;
                if (item.resourceId === resource && item.type === 'resource') {
                    return true;
                }
            }
        }

        return false;
    },

    confirmReset: function() {
        var dialog = new p4cms.ui.ConfirmDialog({
            title:               'Reset Permissions',
            content:             'Are you sure you want to reset permissions to defaults?',
            actionButtonOptions: { label: 'Reset' },
            onConfirm:           function() {
                window.location = p4cms.url({
                    module:     'user',
                    controller: 'acl',
                    action:     'reset'
                });
            }
        });
        dialog.show();
    },

    /**
     * Checks to see if the json version of the current rules matches the original.
     * @return boolean     returns true if the current ruleset has changed from the original
     */
    hasChanged: function() {
        var currentRules = dojo.toJson(p4cms.user.acl.grid.rules);
        
        // return true if this.startRules is defined and if current!= start
        return (this.startRules !== undefined && currentRules !== this.startRules);
    },

    /**
     * Triggers a prompt if the user attempts to navigate away with unsaved changes.
     * @param   evt     unload event
     * @return  string  the confirmation message to display
     */
    promptBeforeUnload: function(evt) {
        if (this.hasChanged()) {
            var returnValue = "Permissions have been modified. Discard your changes?";

            // neccessary for Firefox
            evt.returnValue = returnValue;

            // neccessary for Chrome
            return returnValue;
        }
        return;
    },
    
    /**
     * Sets the start rules variable to the current grid rules.
     */
    setStartRules: function() {
        this.startRules = dojo.toJson(p4cms.user.acl.grid.rules);
    }
};