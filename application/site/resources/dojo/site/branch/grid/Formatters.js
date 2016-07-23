// summary:
//      Branch grid field formatting functions.

dojo.provide("p4cms.site.branch.grid.Formatters");

p4cms.site.branch.grid.Formatters = {
    name: function(item, row) {
        var grid    = p4cms.site.branch.grid.instance;
        var values  = grid.getItemValues(row);
        var classes = [];
        var name    = values.name;

        // get depth of the current item
        var depth = values.depth;
        if (depth !== undefined) {
            classes.push('indented', 'depth-' + (depth <= 20 ? depth + 1: 'deep'));
        }

        // obligatory item (items that don't match the query but needed to be
        // present as with any matching entry we display all its parents)
        if (values.obligatory) {
            classes.push('obligatory');
        }

        // add a class based on type
        classes.push(values.type);

        // we assume anything that isn't a site is a branch.
        if (values.type !== 'site') {
            classes.push('branch');

            // add class for active branch
            if (values.isActive) {
                classes.push('branch-active');
                name += ' (active)';
            }
        }

        // add class for active site
        if (values.type === 'site' && values.id === p4cms.site.active.siteId) {
            classes.push('site-active');
            name += ' (active)';
        }

        return "<div class='" + classes.join(' ') + "'>"
            + "<div class='icon'></div>"
            + "<span class='name'>"
            + name
            + "</span>"
            + "</div>";
    },

    owner: function (item, row) {
        var grid    = p4cms.site.branch.grid.instance;
        var values  = grid.getItemValues(row);
        var classes = [];

        // include classes for type and obligatory if needed
        classes.push(values.type);
        if (values.obligatory) {
            classes.push('obligatory');
        }

        // we assume anything that isn't a site is a branch.
        if (values.type !== 'site') {
            classes.push('branch');
        }

        return "<div class='" + classes.join(' ') + "'>"
            + "<span class='owner'>"
            + values.owner
            + "</span>"
            + "</div>";
    },

    branchTooltip: function (itemValues) {
        // get all values from sites/branches grid - we need it to get name of the parent element
        var items = p4cms.site.branch.grid.instance.store._items;

        // does not attach tooltips to sites
        if (itemValues.type === 'site') {
            return '';
        }

        var url         = itemValues.url
                        ? p4cms.ui.escapeHtml(itemValues.url)
                        : '<i>No Url</i>';
        var parentName  = itemValues.parentName
                        ? p4cms.ui.escapeHtml(itemValues.parentName)
                        : '<i>No Parent</i>';
        var description = itemValues.description
                        ? p4cms.ui.escapeHtml(itemValues.description).replace(/(\r\n|\n\r|\r|\n)/g, '<br>')
                        : '<i>No Description</i>';

        return "<dl>"
             + "<dt class=branch-url>Url</dt>"
             +  "<dd class=branch-url>" + url + "</dd>"
             + "<dt class=branch-parent>Parent</dt>"
             + "<dd class=branch-parent>" + parentName + "</dd>"
             + "<dt class=branch-description>Description</dt>"
             + "<dd class=branch-description>" + description + "</dd>"
             + "</dl>";
    }
};