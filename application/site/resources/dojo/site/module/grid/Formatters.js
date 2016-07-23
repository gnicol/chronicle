// summary:
//      Site module grid field formatting functions.

dojo.provide("p4cms.site.module.grid.Formatters");
dojo.require("dojo.NodeList-html");
dojo.require("dojo.html");
dojo.require("dojo.parser");
dojo.require("dijit.form.DropDownButton");
dojo.require("dijit.form.Button");
dojo.require("p4cms.ui.Dialog");
dojo.require("p4cms.ui.FormDialog");

p4cms.site.module.grid.Formatters = {
    // generate the contents of the 'Module' column for the module grid.
    module: function(item, row) {
        if (!item) {
            return;
        }

        var grid      = p4cms.site.module.grid.instance;
        var values    = grid.getItemValues(row);
        var container = new dijit.layout.ContentPane({'class': 'module'});

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

        if (values.dependencies.length) {
            var dependencies = dojo.create('div', {
                'class': 'dependencies'
            }, info);
            dojo.create('span', {
                'class':   'heading',
                innerHTML: 'Dependencies:'
            }, dependencies);

            var list = dojo.create('ul', null, dependencies);
            values.dependencies.forEach(function (dependency) {
                var className   = dependency.satisfied ? 'satisfied' : 'missing';
                var status      = dependency.satisfied ? '&#10003;'   : '&#10007;';
                var label       = dependency.label;
                var type        = dependency.type.charAt(0).toUpperCase() + dependency.type.slice(1);
                var versions    = dependency.versions.join(', ');

                var html        = '<li class="' + className + '">'
                                + '<span class="status">'    + status + '</span>'
                                + '<span class="package">'   + grid.escapeHtml(label) + '</span>'
                                + '<span class="type">'      + grid.escapeHtml(type) + '</span>'
                                + '<span class="versions">(' + grid.escapeHtml(versions) + ')</span>';

                list.innerHTML += html;
            });
        }

        var buttons = dojo.create('div', {'class': 'buttons'}, info);
        if (values.configurable) {
            var configureButton = new dijit.form.Button({
                label:  'Configure',
                style:  'margin-left: 0; margin-right: 5px;',
                onClick: dojo.hitch(values, function() {
                    var dialog = new p4cms.ui.FormDialog({
                        title:          values.label + ' Configuration',
                        urlParams:      values.configRouteParams,
                        onSaveSuccess:  function() {
                            // override dialog's native method to disable hiding
                            // the dialog after form is saved to prevent users from
                            // doing other actions before the page is reloaded
                            // also nuke the dialog's hide() method to prevent
                            // hiding the dialog by pressing the Esc key
                            dialog.hide = function(){};
                            window.location.reload();
                        }
                    });

                    dialog.show();
                })
            });
            dojo.place(configureButton.domNode, buttons);
        }

        var button;
        if (!values.core) {
            if (values.enabled) {
                button = new dijit.form.DropDownButton({
                    label:   'Disable',
                    dropDown: new p4cms.ui.ConfirmTooltip({
                        content:             'Are you sure that you want to disable the ' + grid.escapeHtml(values.label) + ' module?',
                        actionButtonOptions: { label: 'Disable Module' },
                        actionSingleClick:   true,
                        onConfirm:           dojo.hitch(values, function() {
                            // xhr delete entry.
                            dojo.xhrPost({
                                url:        p4cms.url({
                                    module:     'site',
                                    controller: 'module',
                                    action:     'disable'
                                }),
                                content:    {moduleName: this.name},
                                load:       dojo.hitch(this, function() {
                                    window.location.href = p4cms.url({
                                        module:     'site',
                                        controller: 'module',
                                        action:     'index'
                                    });
                                }),
                                error:       dojo.hitch(this, function() {
                                    var notice = new p4cms.ui.Notice({
                                        message:  "Failed to disable '" + this.label + "' module.",
                                        severity: "error"
                                    });

                                    //re-enable 'module-disable' button
                                    dijit.byId(button.dropDown.domNode.id + '-button-action').enable();
                                })
                            });
                        })
                    })
                });
                dojo.place(button.domNode, buttons);
            } else {
                button = new dijit.form.DropDownButton({
                    label:   'Enable',
                    dropDown: new p4cms.ui.ConfirmTooltip({
                        content:             'Are you sure that you want to enable the ' + grid.escapeHtml(values.label) + ' module?',
                        actionButtonOptions: { label: 'Enable Module' },
                        actionSingleClick:   true,
                        onConfirm:           dojo.hitch(values, function() {
                            // xhr delete entry.
                            dojo.xhrPost({
                                url:        p4cms.url({
                                    module:     'site',
                                    controller: 'module',
                                    action:     'enable'
                                }),
                                content:    {moduleName: this.name},
                                load:       dojo.hitch(this, function() {
                                    window.location.href = p4cms.url({
                                        module:     'site',
                                        controller: 'module',
                                        action:     'index'
                                    });
                                }),
                                error:      dojo.hitch(this, function() {
                                    var notice = new p4cms.ui.Notice({
                                        message:  "Failed to enable '" + this.label + "' module.",
                                        severity: "error"
                                    });

                                    //re-enable 'module-enable' button
                                    dijit.byId(button.dropDown.domNode.id + '-button-action').enable();
                                })
                            });
                        })
                    })
                });
                dojo.place(button.domNode, buttons);
            }
        }

        return container;
    },

    // generate the contents of the 'Maintained By' column for the module grid.
    maintainer: function(item, row) {
        if (!item) {
            return;
        }

        var grid   = p4cms.site.module.grid.instance,
            values = grid.getItemValues(row);

        if (!values.maintainer) {
            return;
        }
        values     = values.maintainer;

        var html   = "";
        if (values.name) {
            html += '<span class="name">'
                 +  grid.escapeHtml(values.name) + '</span>';
        }
        if (values.email) {
            html += '<span class="email"><a href="mailto:'
                 +  encodeURI(values.email) + '">'
                 +  grid.escapeHtml(values.email) + '</a></span>';
        }
        if (values.url) {
            html += '<span class="url"><a href="'
                 +  grid.escapeHtml(values.url) + '">'
                 +  grid.escapeHtml(values.url) + '</a></span>';
        }

        html = '<div class="maintainer">' + html + '</div>';

        return html;
    },

    // generate the status column and 'actions' drop-down menu for nodule
    // entries in the module grid.
    status: function(item, row) {
        if (!item) {
            return;
        }

        var grid      = p4cms.site.module.grid.instance;
        var values    = grid.getItemValues(row);
        var className = 'status ' + values.status.toLowerCase();
        var html      = '<span class="' + className + '">'
                      + grid.escapeHtml(values.status) + '</span>';

        return html;
    }
};