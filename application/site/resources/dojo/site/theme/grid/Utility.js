// summary:
//      Site theme grid utility functions.

dojo.provide("p4cms.site.theme.grid.Utility");
dojo.require("dojo.cookie");
dojo.require("dojo.html");
dojo.require("dojo.parser");
dojo.require("dijit.form.Button");
dojo.require("p4cms.ui.SingleClickButton");

p4cms.site.theme.grid.Utility = {
    apply: function(name, label, buttonId) {
        dojo.xhrPost({
            url:        p4cms.url({
                module:     'site',
                controller: 'theme',
                action:     'apply'
            }),
            content:    {theme: name},
            load:       function() { window.location.reload(); } ,
            error:      dojo.hitch(this, function() {
                var notice = new p4cms.ui.Notice({
                    message:  "Failed to activate '" + label + "' theme.",
                    severity: "error"
                });

                // re-enable 'apply' button
                if (dijit.byId(buttonId)) {
                    dijit.byId(buttonId).enable();
                }
            })
        });
    }
};
