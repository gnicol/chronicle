// summary:
//      Support for editable widgets.

dojo.provide("p4cms.widget.Widget");
dojo.require("dijit.form.Form");
dojo.require("dijit.form.Button");
dojo.require("dojo.io.iframe");
dojo.require("p4cms.ui.Dialog");
dojo.require("p4cms.ui.ConfirmTooltip");
dojo.require("dijit.Toolbar");
dojo.require('p4cms.ui.Notice');

dojo.declare("p4cms.widget.Widget", p4cms.ui.EditableElement,
{
    group:          'widget',
    widgetContext:  null,
    clickHandler:   null,
    widgetType:     '',

    // open config dialog when user clicks a widget in edit mode.
    enableEditMode: function() {
        this.inherited(arguments);

        // make element look 'clickable'.
        dojo.style(this.domNode, 'cursor', 'pointer');

        // click should open edit dialog.
        this.clickHandler = dojo.connect(this.domNode, 'onclick', this, function(event) {
            event.preventDefault();
            this.configure();

            return false;
        });
    },

    disableEditMode: function() {
        this.inherited(arguments);
        dojo.disconnect(this.clickHandler);
    },

    // show config dialog form.
    configure: function() {
        this.getConfigDialog().show();
    },

    getConfigDialog: function() {
        var dialog = dijit.byId(this.domNode.id + '-config-dialog');
        if (!dialog) {
            dialog = new p4cms.ui.Dialog({
                id:             'widget-' + this.getWidgetId() + '-config-dialog',
                title:          'Configure ' + this.getWidgetDialogTitle(),
                'class':        'p4cms-ui config-widget config-region-' + this.getRegionName()
                              + ' config-widget-' + this.widgetType.replace('/', '-'),
                executeScripts: true,
                refreshOnShow:  true,
                href:           p4cms.url({
                    module:         'widget',
                    controller:     'index',
                    action:         'configure',
                    region:         this.getRegionName(),
                    widget:         this.getWidgetId(),
                    format:         'partial',
                    widgetContext:  dojo.toJson(this.widgetContext)
                }),
                onShow:         dojo.hitch(this, 'interest'),
                onHide:         dojo.hitch(this, 'disinterest')
            });

            dialog.onExecute = dojo.hitch(this, 'saveConfig');
        }

        dialog.onLoad = dojo.hitch(this, 'setupConfigDialog');

        return dialog;
    },

    setupConfigDialog: function() {
        var dialog = this.getConfigDialog();

        // wire-up cancel and submit buttons.
        dijit.byId(this.domNode.id + '-config-cancel').onClick =
            function() {dialog.hide();};

        // force reloaded dialogs to reposition themselves
        dialog.layout();
    },

    getWidgetId: function() {
        var id = this.domNode.id;
        return id.substring(id.indexOf("-")+1);
    },

    getWidgetDialogTitle: function() {
        return dojo.attr(this.domNode, 'widgetDialogTitle');
    },

    getRegionName: function() {
        return dojo.attr(this.domNode, 'regionName');
    },

    getRegion: function() {
        return dijit.byId('region-' + this.getRegionName());
    },

    getOrder: function() {
        return parseInt(dojo.attr(this.domNode, 'order'), 10);
    },

    doDelete: function() {
        dojo.xhrPost({
            url: p4cms.url({
                module: 'widget',
                action: 'delete',
                format: 'json'
            }),
            content: {
                region: this.getRegionName(),
                widget: this.getWidgetId()
            },
            load: dojo.hitch(this, function() {
                var region = this.getRegion();
                this.getContentPane().destroyRecursive();
                region.sortWidgets();
                var notice = new p4cms.ui.Notice({
                    message: 'Widget deleted.',
                    severity: 'success'
                });
                dojo.publish("p4cms.ui.refreshEditMode");
            }),
            error: function() {
                var notice = new p4cms.ui.Notice({
                    message:    'Failed to delete widget.',
                    severity:   'error'
                });
            }
        });
    },

    getToolbar: function() {
        var toolbar = dojo.byId(this.domNode.id + '-toolbar');
        if (!toolbar) {
            toolbar = dojo.create('div', {
                id:         this.domNode.id + '-toolbar',
                style:      'display: none',
                "class":    'widget-toolbar'
            });

            // make config button.
            var button = new dijit.form.Button({
                'class':    'config-widget',
                value:      'Configure',
                showLabel:  false,
                iconClass:  'configIcon',
                onClick:    dojo.hitch(this, "configure")
            });
            dojo.place(button.domNode, toolbar);

            // make delete button.
            button = new dijit.form.DropDownButton({
                id:         'widget-' + this.getWidgetId() + '-delete',
                'class':    'delete-widget',
                value:      'Delete',
                showLabel:  false,
                iconClass:  'deleteIcon'
            });
            button.dropDown = new p4cms.ui.ConfirmTooltip({
                content:             'Are you sure you want to delete this widget?',
                actionButtonOptions: { label: 'Delete Widget' },
                onConfirm:           dojo.hitch(this, function() {
                    button.closeDropDown();
                    this.doDelete();
                }),
                onOpen:              dojo.hitch(this, 'interest'),
                onClose:             dojo.hitch(this, 'disinterest')
            });
            dojo.place(button.domNode, toolbar);

            // register increased/decreased interest on mouse-enter/leave
            dojo.connect(toolbar, 'onmouseenter', this, function(){this.interest();});
            dojo.connect(toolbar, 'onmouseleave', this, function(){this.disinterest();});

            // redraw toolbar when edit mode refreshed.
            dojo.connect(this, 'refreshEditMode', function(){
                if (dojo.style(toolbar, 'display') !== 'none') {
                    this.showToolbar();
                }
            });
        }

        // always place the toolbar to ensure it's in the right place
        // it is possible for the bordersRootNode to change
        dojo.place(toolbar, this.bordersRootNode || dojo.body());

        return toolbar;
    },

    getContentPane: function() {
        return dijit.byNode(this.domNode.parentNode);
    },

    showToolbar: function() {
        var toolbar = this.getToolbar();
        // make the toolbar visible, so the marginBox call below gets dimensions
        dojo.style(toolbar, "display", "block");

        // position toolbar in top-right corner of the element.
        var pad  = this._constrainBox(this.paddingBox());
        var left = pad.x + pad.w - Math.abs(dojo.marginBox(toolbar).w);
        dojo.style(toolbar, {
            top:        pad.y + "px",
            left:       left + "px"
        });
    },

    hideToolbar: function() {
        dojo.style(this.getToolbar(), "display", "none");
    },

    saveConfig: function() {
        dojo.io.iframe.send({
            url:        p4cms.url({
                module: 'widget',
                action: 'configure',
                format: 'dojoio'
            }),
            form:       this.domNode.id + '-config-form',
            handleAs:   'text',
            load:       dojo.hitch(this, function(response) {

                // if form contains errors, refresh dialog content
                // else display notification message and reload widget.
                var contentNode = dojo.create('div', {innerHTML: response});
                if (dojo.query("ul.errors", contentNode).length > 0) {

                    // refresh dialog content
                    this.getConfigDialog().set('content', response);

                } else {
                    // hide dialog
                    this.getConfigDialog().hide();
                    dojo.publish('p4cms.widget.Widget.saved', [this]);

                    // display notification message
                    var notice = new p4cms.ui.Notice({
                        message:    "Widget configuration saved.",
                        severity:   'success'
                    });

                    // reload the widget.
                    this.reload();
                }

            }),
            error:      function() {
                var notice = new p4cms.ui.Notice({
                    message:    'Failed to save widget configuration.',
                    severity:   'error'
                });
            }
        });
    },

    // extend parent method to look for child widgets in the domNode
    // instead of containerNode as containerNode of this widget is empty
    getChildren: function(){
        return this.domNode ? dijit.findWidgets(this.domNode) : [];
    },

    // reload this widget from the server.
    reload: function() {
        var pane = this.getContentPane();
        pane.onLoad = dojo.hitch(this.getRegion(), 'sortWidgets');

        // append reload parameter so that widget controllers
        // may optionally take steps to avoid caching issues.
        var href = this.getHref() + "?reload=true";

        this.destroyRecursive();
        pane.setHref(href);
    },

    // extend parent to destroy highlight borders that are no longer
    // inside the containing element
    destroyRecursive: function() {
        this.clearHighlight();
        this.inherited(arguments);
    },

    // get the href to the server-side resource for this widget.
    getHref: function() {
        return this.getRegion().getWidgetHref(this.getWidgetId(), dojo.attr(this.domNode, 'widgetContext'));
    },

    // any interest/disinterest in a widget also contributes
    // to interest/disinterest in the containing region.
    interest: function(){
        this.inherited(arguments);
        this.getRegion().interest();
    },

    disinterest: function(){
        this.inherited(arguments);
        this.getRegion().disinterest();
    },

    onInterest: function(){
        this.inherited(arguments);
        this.showToolbar();
    },

    onDisinterest: function(){
        this.inherited(arguments);
        this.hideToolbar();
    },

    // clean up lingering dijits.
    uninitialize: function() {
        // cleanup toolbar widgets and then the toolbar
        dojo.forEach(dijit.findWidgets(this.getToolbar()), function (button) {
            button.destroyRecursive();
        });
        dojo.destroy(this.getToolbar());

        this.getConfigDialog().destroyRecursive();
        this.inherited(arguments);
    }
});
