// summary:
//      Support for editable regions.

dojo.provide("p4cms.widget.Region");
dojo.require("p4cms.ui");
dojo.require("dijit.form.Button");

dojo.declare("p4cms.widget.Region", p4cms.ui.EditableElement,
{
    group:          'widget',
    widgetContext:  null,

    getRegionName: function() {
        return dojo.attr(this.domNode, 'regionName');
    },

    // extend parent to show add widget button.
    enableEditMode: function() {
        this.showAddButton();
        this.inherited(arguments);
    },

    // extend disable edit mode to hide add button.
    disableEditMode: function() {
        this.hideAddButton();
        this.inherited(arguments);
    },

    getAddButton: function() {
        if (this.addButton) {
            return this.addButton;
        }

        this.addButton = new dijit.form.Button({
            style:      'display: none;',
            label:      'Add Widget',
            'class':    'add-widget',
            iconClass:  'plusIcon',
            showLabel:  false
        });
        dojo.place(this.addButton.domNode, this.domNode);

        // redraw button when edit mode refreshed.
        this.connect(this, 'refreshEditMode', function(){
            if (dojo.style(this.addButton.domNode, 'display') !== 'none') {
                this.showAddButton();
            }
        });

        return this.addButton;
    },

    getAddDialog: function() {
        var dialog = dijit.byId(this.domNode.id + '-add-dialog');
        if (!dialog) {
            dialog = new p4cms.ui.Dialog({
                id:                 'region-' + this.getRegionName() + '-add-dialog',
                title:              'Add Widget',
                'class':            'add-widget-dialog',
                executeScripts:     true,
                href:               p4cms.url({
                    module:         'widget',
                    controller:     'index',
                    action:         'add',
                    format:         'partial',
                    widgetContext:  dojo.toJson(this.widgetContext),
                    region:         this.getRegionName()
                })
            });

            // activate widget links on load.
            dialog.onLoad = dojo.hitch(this, function() {
                var region = this;
                dojo.query('.widget-types a.add-widget', dialog.domNode).onclick(function() {
                    dialog.hide();
                    region.addWidget(dojo.attr(this, 'widgetType'), dojo.attr(this, 'widgetContext'));
                });
            });
        }

        return dialog;
    },

    getWidgetHref: function(widgetId, widgetContext) {
        var urlParams = {
            module: 'widget',
            region: this.getRegionName(),
            widget: widgetId
        };

        if (widgetContext.length) {
            urlParams.widgetContext = widgetContext;
        }

        return p4cms.url(urlParams);
    },

    showAddButton: function() {
        var button = this.getAddButton();
        dojo.style(button.domNode, {
            display:  'block',
            position: 'absolute'
        });

        // position button in the lower-right corner of the element.
        // but, refuse to position off of the screen (limit top/left)
        var buttonBox  = dojo.marginBox(button.domNode);
        var elementBox = this.paddingBox();
        var maxTop     = dojo.body().clientHeight - buttonBox.h - 10;
        var maxLeft    = dojo.body().clientWidth  - buttonBox.w - 10;
        var top        = elementBox.y + elementBox.h - buttonBox.h/2;
        var left       = elementBox.x + elementBox.w - buttonBox.w/2;

        // don't position button off screen.
        top  = top  > maxTop  ? maxTop  : top;
        left = left > maxLeft ? maxLeft : left;

        dojo.style(button.domNode, {
            top:  top + 'px',
            left: left + 'px'
        });

        // connect button to add dialog.
        button.onClick = dojo.hitch(this, function() {
            this.getAddDialog().show();
        });
    },

    hideAddButton: function() {
        dojo.style(this.getAddButton().domNode, 'display', 'none');
    },

    addWidget: function(widgetType, widgetContext) {
        dojo.xhrPost({
            url: p4cms.url({
                module: 'widget',
                action: 'add',
                format: 'json'
            }),
            handleAs: 'json',
            content: {
                type:           widgetType,
                region:         this.getRegionName(),
                widgetContext:  widgetContext
            },
            load: dojo.hitch(this, function(response) {
                var widgetId = response;
                this.loadWidget(widgetId, widgetContext, dojo.hitch(this, function() {
                    this.getWidget(widgetId).configure();
                }));
            }),
            error: function(response, ioArgs) {
                new p4cms.ui.Dialog({
                    title:   'Error',
                    content: 'The widget could not be added.',
                    'class': 'error'
                }).show();
            }
        });
    },

    // load the given widget id into this region.
    loadWidget: function(widgetId, widgetContext, onLoad) {
        var pane = new dijit.layout.ContentPane({
            href:          this.getWidgetHref(widgetId, widgetContext),
            'class':       'widget-container',
            widgetContext: widgetContext,
            onLoad:  dojo.hitch(this, function() {
                this.sortWidgets();
                if (onLoad) {
                    onLoad();
                }
            })
        });
        dojo.place(pane.domNode, this.domNode, 'last');
        pane.startup();
    },

    // get the dijit for the given widget id.
    getWidget: function(widgetId) {
        return dijit.byId('widget-' + widgetId);
    },

    // put the widgets in sorted order.
    sortWidgets: function() {
        // determine correct order via array sort.
        var widgets = dojo.query('div[dojoType=p4cms.widget.Widget]', this.domNode);
        widgets.sort(function (a, b) {
            var aOrder    = parseInt(dojo.attr(a, 'order'), 10),
                bOrder    = parseInt(dojo.attr(b, 'order'), 10),
                diffOrder = aOrder - bOrder;
            if (diffOrder !== 0) {
                return diffOrder;
            }

            // if orders are equal, compare addTime
            var aAddTime  = parseFloat(dojo.attr(a, 'addTime')),
                bAddTime  = parseFloat(dojo.attr(b, 'addTime'));
            return (aAddTime - bAddTime);
        });

        // adjust widget numbering class and container classes
        var i, regex = new RegExp('widget-[0-9]+', 'g');
        for (i = 0; i < widgets.length; i++) {
            // handle widget numbering classes
            var node = widgets[i].parentNode;
            dojo.replaceClass(node, 'widget-' + (i + 1),
                dojo.attr(node, 'class').match(regex));

            // handle widget container classes
            var newContainerClass = dojo.attr(widgets[i], 'containerClass');
            dojo.replaceClass(node, newContainerClass,
                dojo.attr(node, 'containerClass'));
            dojo.attr(node, 'containerClass', newContainerClass);
        }

        // adjust dom placement
        for (i = 1; i < widgets.length; i++) {
            dojo.place(widgets[i].parentNode, widgets[i-1].parentNode, 'after');
        }

        // if in edit mode, refresh.
        if (p4cms.ui.inEditMode[this.group]) {
            dojo.publish('p4cms.ui.refreshEditMode');
        }
    }
});