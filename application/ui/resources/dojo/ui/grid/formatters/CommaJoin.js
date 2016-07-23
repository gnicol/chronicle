dojo.provide('p4cms.ui.grid.formatters.CommaJoin');

// if item is an array, join it on comma.
p4cms.ui.grid.formatters.CommaJoin = function(item, row) {
    if (!item || !item.join) {
        return;
    }

    return item.join(", ");
};