// summary:
//      User grid field formatting functions.

dojo.provide("p4cms.user.grid.Formatters");

p4cms.user.grid.Formatters = {
    roles: function(item, row) {
        if (!item) {
            return;
        }

        var roles       = dojo.fromJson(item);
        var container   = dojo.create("div");
        dojo.forEach(roles, function(data) {
            dojo.create("div", {innerHTML: data}, container);
        });
        return container.innerHTML;
    }
};