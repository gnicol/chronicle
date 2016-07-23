// summary:
//      Support for system module; handles loading of md5 sums serially and asynchronously.

dojo.provide('p4cms.system');

dojo.require("dijit.layout.TabContainer");
dojo.require("dijit.layout.ContentPane");
dojo.require("p4cms.ui");

p4cms.system.xhrList = [];

/**
 * Adds an item to the list of indicators to populate with md5 data.
 */
p4cms.system.addItem = function (item) {
    p4cms.system.xhrList.push(item);
};

/**
 * Gets the next item on the list and, if available, populates it.
 */
p4cms.system.next = function() {
    var item = p4cms.system.xhrList.shift();
    if (item) {
        p4cms.system.fetchStatus(item);
    }
};

/**
 * Populates the next indicator on the list via xhr.
 */
p4cms.system.fetchStatus = function(item) {
    dojo.xhrGet({
        url: p4cms.url({
            'module'        : 'system',
            'controller'    : 'index',
            'action'        : 'md5',
            'format'        : 'json',
            'target'        : item.target,
            'type'          : item.type
        }),
        load: function(json) {
            var data = dojo.fromJson(json),
                node = dojo.byId(item.target + '-status');
            dojo.replaceClass(node, data.displayClass, 'loading');

            var i, p = dojo.query('p.response', node)[0],
                html = [];
            for (i in data.details) {
                if (data.details.hasOwnProperty(i)) {
                    html.push(p4cms.ui.escapeHtml(data.details[i]));
                }
            }
            p.innerHTML = html.join("<br/>\n");
            setTimeout(p4cms.system.next, 5);
        }
    });
};