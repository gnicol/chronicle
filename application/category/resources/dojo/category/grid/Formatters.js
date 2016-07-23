// summary:
//      Category grid field formatting functions.

dojo.provide("p4cms.category.grid.Formatters");

p4cms.category.grid.Formatters = {
    title: function(item, row) {
        if (!item) {
            return;
        }

        var grid    = p4cms.category.grid.instance;
        var values  = grid.getItemValues(row);

        // get depth of the current item
        var depth   = values.depth;

        // add a depth-specific class
        var classes = ['category', 'indented', 'depth-' + (depth <= 20 ? depth : 'deep')];

        // obligatory item (items that don't match the query but needed to be
        // present as with any matching category we display all its parents)
        if (values.obligatory) {
            classes.push('obligatory');
        }

        // add class if the current category contains content entries
        if (values.entries > 0) {
            classes.push('has-content');
        }

        return "<div class='" + classes.join(' ') + "'>"
            + "<span class='title'>" + item + "</span>"
            + "</div>";
    },

    entries: function (item, row) {
        if (typeof item !== "number") {
            return;
        }

        var container = dojo.create('div');
        var itemBox   = dojo.create('div', {innerHTML: item}, container);

        // add extra class if item is obligatory
        if (p4cms.category.grid.instance.getItemValues(row).obligatory) {
            dojo.addClass(itemBox, 'obligatory');
        }
        return container.innerHTML;
    }
};