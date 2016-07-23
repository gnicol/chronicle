dojo.provide("p4cms.search.base");

dojo.require("p4cms.ui.TooltipDialog");

// function to present search tooltip dialog.
p4cms.search.prompt = function(link) {
    // create the search dialog if necessary.
    if (!p4cms.search.searchDialog) {
        var dialog = new p4cms.ui.TooltipDialog({
            href:           p4cms.url({
                module:         'search',
                format:         'partial',
                formIdPrefix:   'tooltip'
            }),
            executeScripts: true
        });
        dojo.addClass(dialog.domNode, 'p4cms-search-dialog');
        p4cms.search.searchDialog = dialog;
    }

    // wrap link text in span to use for positioning the dialog
    // we do this to be more resilient to theme styling on the link tag
    var span = dojo.query("span", link)[0]
            || dojo.create('span', {innerHTML: link.innerHTML}, link, 'only');

    // attach dialog to this link and display.
    p4cms.search.searchDialog.attachToElement(link, {around: span}, true);
    p4cms.search.searchDialog.openAroundElement(link, {around: span});

    // remove onclick so this code only fires once per link.
    dojo.removeAttr(link, 'onClick');
};