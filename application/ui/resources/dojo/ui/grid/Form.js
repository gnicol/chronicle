// summary:
//      A form that is tied to a data grid and automatically
//      updates (filters) the data grid when its inputs change.

dojo.provide("p4cms.ui.grid.Form");
dojo.require("dijit.form.Form");

dojo.declare("p4cms.ui.grid.Form", dijit.form.Form, {

    applyDelay: 250,
    timeout:    null,
    gridId:     '',
    lastQuery:  null,

    startup: function() {
        // apply options on change.
        var selector = "*[autoApply=true], *[autoApply=1]";
        dojo.query(selector, this.domNode).onchange(dojo.hitch(this, 'delayedApply'));
        
        // apply options on click.
        selector = "input[type=checkbox][autoApply=true], "
                     + "input[type=checkbox][autoApply=1], "
                     + "input[type=radio][autoApply=true], "
                     + "input[type=radio][autoApply=1]";
        dojo.query(selector, this.domNode).onclick(dojo.hitch(this, 'delayedApply'));

        // apply options on keyup.
        selector = "input[type=text][autoApply=true], "
                     + "input[type=text][autoApply=1]";
        dojo.query(selector, this.domNode).onkeyup(dojo.hitch(this, 'delayedApply'));
    },

    onSubmit: function() {
        this.apply();
        return false;
    },

    delayedApply: function() {
        window.clearTimeout(this.timeout);
        this.timeout = window.setTimeout(
            dojo.hitch(this, 'apply'),
            this.applyDelay
        );
    },

    apply: function() {
        // ignore if query hasn't changed since last application.
        var query = dojo.formToObject(this.domNode);
        if (dojo.toJson(query) === dojo.toJson(this.lastQuery)) {
            return;
        } else {
            this.lastQuery = dojo.clone(query);
        }

        this.getGrid().filter(query, true);
    },

    getGrid: function() {
        var grid = dijit.byId(this.gridId);
        if (!grid) {
            grid = dojo.getObject(this.gridId);
        }
        return grid;
    }
});