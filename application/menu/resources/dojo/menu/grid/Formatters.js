// summary:
//      Menu grid field formatting functions.

dojo.provide("p4cms.menu.grid.Formatters");

p4cms.menu.grid.Formatters = {
    label: function(item, row) {
        if (!item) {
            return;
        }

        var grid = p4cms.menu.grid.instance;
        var values = grid.getItemValues(row);

        // get depth of the current item
        var depth = values.depth;

        // add a depth-specific class
        var classes = [
            'menu',
            'indented',
            'depth-' + (depth <= 20 ? depth : 'deep'),
            'type-' + values.typeLabel.toLowerCase().replace(/[^a-z0-9]/i, '-')
        ];

        // if this row is a menu item add a class
        if (values.menuItemId) {
            classes.push('menu-item');
        }

        // obligatory item (items that don't match the query but needed to be
        // present as with any matching entry we display all its parents)
        if (values.obligatory) {
            classes.push('obligatory');
        }

        return "<div class='" + classes.join(' ') + "'>"
             + "<div class='icon'></div>"
             + "<span class='title'>" + item + "</span>"
             + "</div>";
    },

    type: function (item, row) {
        if (!item) {
            return;
        }

        var container = dojo.create('div');
        var itemBox   = dojo.create('div', {innerHTML: item}, container);

        // add extra class if item is obligatory
        if (p4cms.menu.grid.instance.getItemValues(row).obligatory) {
            dojo.addClass(itemBox, 'obligatory');
        }

        return container.innerHTML;
    }
};