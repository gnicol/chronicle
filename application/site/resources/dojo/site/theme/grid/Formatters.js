// summary:
//      Site theme grid field formatting functions.

dojo.provide("p4cms.site.theme.grid.Formatters");

dojo.require("dijit.layout.ContentPane");
dojo.require("p4cms.ui.ConfirmTooltip");
dojo.require("dijit.form.DropDownButton");
dojo.require("dijit.form.Button");

p4cms.site.theme.grid.Formatters = {
    // generate the contents of the 'Theme' column for the module grid.
    theme: function(item, row) {
        if (!item) {
            return;
        }

        var grid      = p4cms.site.theme.grid.instance;
        var values    = grid.getItemValues(row);
        var container = new dijit.layout.ContentPane({'class': 'theme'});

        var icon      = dojo.create('span', {
            'class':    'icon',
            innerHTML:  '<img src="' + encodeURI(values.icon) + '" />'
        });
        dojo.place(icon, container.domNode);

        var info      = dojo.create('div',  { 'class': 'info' }, container.domNode);
        var name      = dojo.create('span', {
            'class':   'name',
            innerHTML: grid.escapeHtml(values.label)
        });
        dojo.place(name, info);

        if (values.version) {
            var version = dojo.create('span', {
                'class':   'version',
                innerHTML: 'v' + grid.escapeHtml(values.version)
            });
            dojo.place(version, info);
        }

        if (values.description) {
            var description = dojo.create('div', {
                'class':   'description',
                innerHTML: grid.escapeHtml(values.description)
            });
            dojo.place(description, info);
        }

        var button;
        var buttons = dojo.create('div', {'class': 'buttons'}, info);
        if (!values.active) {
            button = new dijit.form.DropDownButton({
                label:   'Apply',
                dropDown: new p4cms.ui.ConfirmTooltip({
                    content:             'Are you sure that you want to apply the ' + grid.escapeHtml(values.label) + ' theme?',
                    actionButtonOptions: { label: 'Apply' },
                    actionSingleClick:   true,
                    onConfirm:           dojo.hitch(this, function() {
                        p4cms.site.theme.grid.Utility.apply(
                            values.name,
                            values.label,
                            button.dropDown.domNode.id + '-button-action'
                        );
                    })
                })
            });
            dojo.place(button.domNode, buttons);
        }

        return container;
    },

    // generate the contents of the 'Maintained By' column for the theme grid.
    maintainer: function(item, row) {
        if (!item) {
            return;
        }

        var grid   = p4cms.site.theme.grid.instance,
            values = grid.getItemValues(row);
        if (!values.maintainer) {
            return;
        }
        values = values.maintainer;

        var html = [];
        html.push('<div class="maintainer">');
        if (values.name) {
            html.push('<span class="name">' + grid.escapeHtml(values.name) + '</span>');
        }
        if (values.email) {
            html.push('<span class="email"><a href="mailto:' + encodeURI(values.email) + '">' + grid.escapeHtml(values.email) + '</a></span>');
        }
        if (values.url) {
            html.push('<span class="url"><a href="' + grid.escapeHtml(values.url) + '">' + grid.escapeHtml(values.url) + '</a></span>');
        }
        html.push('</div>');

        return html.join("\n");
    }
};