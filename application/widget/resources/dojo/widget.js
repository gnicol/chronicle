// summary:
//      Support for widget module.

dojo.provide("p4cms.widget");
dojo.require("p4cms.widget.base");
dojo.require("p4cms.widget.Region");
dojo.require("p4cms.widget.Widget");
dojo.require("p4cms.widget.ImageForm");

// determines if the page has any widgets or regions
p4cms.widget.pageHasWidgets = function() {
    return !!dojo.query('[dojoType=p4cms.widget.Widget], [dojoType=p4cms.widget.Region]').length;
};

// hide widget button if there are no regions or widgets on the page.
dojo.subscribe('p4cms.ui.toolbar.contextChange', function(toolbar) {
    toolbar.setButtonDisplay('widgets', !p4cms.widget.pageHasWidgets());
});

dojo.subscribe('p4cms.ui.toolbar.ignoreFilters.populate', function(menu, event, filters) {
    // if not in edit mode, there is nothing here to ignore
    if (p4cms.ui.inEditMode.widget !== true) {
        return;
    }

    filters.push('.widget-toolbar', '.region', '.widget');
});