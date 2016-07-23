dojo.provide('p4cms.mobile.widget.RegionProxy');

dojo.require('p4cms.widget.Region');

dojo.declare('p4cms.mobile.widget.RegionProxy', p4cms.widget.Region, {
    // lifecycle
    // extended to capture some styling events
    startup: function() {
        this.inherited(arguments);

        // hook into some region events
        var sourceRegion = this.getSourceRegion();

        // Widgets only call dis/interest on the sourceRegion, connect to those
        // events to keep interest highlighting in sync across all RegionProxies
        this.connect(sourceRegion, 'interest', 'interest');
        this.connect(sourceRegion, 'disinterest', 'disinterest');
    },

    // returns the region that this is proxying to
    getSourceRegion: function() {
        return dijit.byId('region-' + this.getRegionName());
    },

    // extended to move the button out of the region
    getAddButton: function() {
        if (this.addButton) {
            return this.addButton;
        }

        var button = this.inherited(arguments);
        dojo.place(button.domNode, this.domNode.parentNode);

        return button;
    },

    // overridden to proxy to the sourceRegion
    getAddDialog: function() {
        return this.getSourceRegion().getAddDialog();
    }
});

// We need to add the Region's add button to the toolbar's ignore list because
// we have moved it out of the region, which was previously ignored
dojo.subscribe('p4cms.ui.toolbar.ignoreFilters.populate', function(menu, event, filters) {
    // if not in edit mode, there is nothing here to ignore
    if (p4cms.ui.inEditMode.widget !== true) {
        return;
    }

    filters.push('.add-widget');
});