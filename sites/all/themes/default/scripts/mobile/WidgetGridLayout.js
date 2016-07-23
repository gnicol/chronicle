dojo.provide('p4cms.mobile.WidgetGridLayout');

dojo.require('p4cms.mobile.PagedLayout');

dojo.declare('p4cms.mobile.WidgetGridLayout', p4cms.mobile.PagedLayout, {
    columns:            1,
    preserveSourceNode: false,

    // overridden to set the targetNode so it doesn't have to be passed
    build: function() {
        this.targetNode = this.sourcePageNode.parentNode;

        this.inherited(arguments);
    },

    // extended to patch the Region for multi-page widgets
    initListeners: function() {
        this.inherited(arguments);

        // exit if Regions aren't loaded in the user's js
        if (!p4cms.ui.getClass('p4cms.widget.Region')) {
            return;
        }

        var sourceRegion = dijit.byNode(this.sourceNode);
        if (sourceRegion && sourceRegion.isInstanceOf(p4cms.widget.Region)) {
            var layout = this;
            dojo.safeMixin(sourceRegion, {
                // extended to move the add button out of the region
                // otherwise we will try to columnize it
                enableEditMode: function() {
                    dojo.place(this.getAddButton().domNode, this.domNode.parentNode);
                    this.inherited(arguments);
                },

                // extended to merge pages before sorting, and then
                // columnize pages after sorting
                sortWidgets: function() {
                    layout.mergePages();
                    this.inherited(arguments);
                    layout.build();
                },

                // extended to transition to last page when adding
                loadWidget: function(widgetId, widgetContext, onLoad) {
                    layout.lastWidgetAction = {action: 'load', widget: 'widget-' + widgetId};
                    this.inherited(arguments);
                }
            });

            // subscribe to widget saved topic so we have a reference to the last
            // widget that was saved
            dojo.subscribe('p4cms.widget.Widget.saved', this, function(widget) {
                if (widget.getRegionName() !== sourceRegion.getRegionName()) {
                    return;
                }

                // we need to keep a reference to the widgetid, and not the
                // widget, because the widgets destroy themselves on every action,
                // and then are recreated/reloaded
                this.lastWidgetAction = {action:'save', widget: widget.id};
            });
        }
    },

    // overridden to not clone the sourceNode, instead support
    // moving the nodes back and forth between pages when needed
    getInputNode: function() {
        // rebuild initial structure whenever we need the safe node
        this.mergePages();

        return this.inherited(arguments);
    },

    // merges all the content from all the columns across multiple pages back
    // into it's original targetNode, and then destroys the empty columns
    // options it can also call a cleanup on the extra pages
    mergePages: function (cleanup) {
        var pages       = this.getPages(),
            targetNode  = this.getPageTargetNode(pages[0]),
            columns     = pages.query('.region-grid .p4cms-column'),
            clears      = pages.query('.region-grid .p4cms-column-clear');

        columns.children().place(targetNode);
        columns.forEach(dojo.destroy);
        clears.forEach(dojo.destroy);

        // remove all other pages if cleanup is required
        if (cleanup) {
            this.removePages(1);
        }
    },

    // overridden to create custom page content structure
    createPageContent: function(page) {
        // @todo copy initial page structure
        dojo.create('div', {'class': 'region-grid-header'}, page.domNode);
        var regionGrid = dojo.create('div', {
            'class':            dojo.attr(this.sourceNode, 'class'),
            'regionName':       dojo.attr(this.sourceNode, 'regionName'),
            'widgetContext':    dojo.attr(this.sourceNode, 'widgetContext')
        }, page.domNode);

        // if available, create a RegionProxy for editing
        if (p4cms.ui.getClass('p4cms.mobile.widget.RegionProxy')) {
            var region = new p4cms.mobile.widget.RegionProxy({}, regionGrid);
            region.startup();
        }
    },

    // overridden to return a page's region grid as the target node
    getPageTargetNode: function(page) {
        return dojo.query('.region-grid', page.domNode)[0];
    },

    // overridden to have each page update its EditableElement borders
    onColumnizeComplete: function(numberOfPages) {
        this.inherited(arguments);

        // if we have a new widget that was just saved before this sort,
        // navigate to the page that is is on
        if (this.lastWidgetAction && !this.lastWidgetAction.sorted) {
            this.lastWidgetAction.sorted = true;
            // navigate to widget's page
            var widgetPage = this.getParentPage(this.lastWidgetAction.widget);
            this.transitionToPage(widgetPage);
        }

        var pages = this.getPages();
        pages.forEach(function(page) {
            page = dijit.byNode(page);
            if (page) {
                page.rootElementBorders();
            }
        });
    },

    // returns the parent page for any domNode
    getParentPage: function(node) {
        node = dojo.isString(node) ? dojo.query('[widgetid='+node+']', this.sourcePageNode.parentNode)[0] : node;
        return dijit.byNode(new dojo.NodeList(node).closest('.mblView')[0]);
    }
});