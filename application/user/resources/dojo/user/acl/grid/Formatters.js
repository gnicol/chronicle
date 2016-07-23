// summary:
//      User acl grid field formatting functions.

dojo.provide("p4cms.user.acl.grid.Formatters");

p4cms.user.acl.grid.Formatters = {
    label: function(item, row) {
        var html,
            resourceId     = this.grid.getItemValue(row, 'resourceId'),
            resourceLabel  = this.grid.getItemValue(row, 'resourceLabel'),
            privilegeLabel = this.grid.getItemValue(row, 'privilegeLabel');

        if (privilegeLabel) {
            html = "<span class=privilegeLabel>" + privilegeLabel + "</span>";
            if (!p4cms.user.acl.grid.Utility.hasResourceRow(resourceId)) {
                html = "<span class=resourceLabel>"  + resourceLabel + ":</span>" + html;
            }
        } else {
            html = "<span class=resourceLabel>" + resourceLabel + "</span>";
        }

        return html;
    },

    rule: function(item, row) {
        var role        = this.field.replace('role-', ''),
            resource    = this.grid.getItemValue(row, 'resourceId'),
            privilege   = this.grid.getItemValue(row, 'privilegeId'),
            options     = this.grid.getItemValue(row, 'options'),
            allowed     = p4cms.user.acl.grid.Utility.isAllowed(role, resource, privilege),
            disabled    = p4cms.user.acl.grid.Utility.isDisabled(role, resource, privilege);

        // don't render checkboxes that are disabled and disallowed
        if (disabled && !allowed) {
            return '';
        }

        // generate checkbox.
        var container   = dojo.create('div'),
            input       = dojo.create("input", {
                type:           'checkbox',
                roleId:         role,
                resourceId:     resource,
                privilegeId:    privilege || "",
                checked:        allowed ? 'checked' : false,
                onClick:        'p4cms.user.acl.grid.Utility.ruleChange(this);',
                disabled:       disabled
            });

        dojo.place(input, container);

        // we set checked explicitly to work around IE7 bug
        if (dojo.isIE === 7) {
            dojo.attr(input, 'checked', allowed ? 'checked' : false);
        }

        return container.innerHTML;
    }
};