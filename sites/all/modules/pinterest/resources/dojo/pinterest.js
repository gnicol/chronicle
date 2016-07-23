// summary:
//      Support for pinterest module.

dojo.provide('p4cms.pinterest');

// connects to load/error events for all images contained in the
// passed node. when all images have loaded or errored out the
// node will be 'masonized'
p4cms.pinterest.masonize = function(node) {
    var images     = dojo.query('img', node);
    var loaded     = 0;
    var deferedRun = function() {
        if (++loaded >= images.length) {
            /*global Masonry: false */
            return new Masonry(node);
        }
    };

    // instantly count images that are already loaded
    // otherwise connect to load/error events to defer
    images.forEach(function(image) {
        if (image.complete) {
            deferedRun();
        } else {
            dojo.connect(image, 'load',  deferedRun);
            dojo.connect(image, 'error', deferedRun);
        }
    });
};

// load in the masonary js and apply it to all pinboards after the page
// has loaded. it will fail to parse if we simply include it in head.
dojo.addOnLoad(function() {
    var pinboards = dojo.query('div.content-element-type-pinboard');

    // don't bother to load the masonry plugin if we don't need it.
    if (!pinboards.length) {
        return;
    }

    dojo._loadUri('/sites/all/modules/pinterest/resources/masonry/masonry.min.js');

    // now that the masonry js is present; masonize our pinboards
    pinboards.forEach(function(board) {
        p4cms.pinterest.masonize(board);
    });
});

// enhance any pinboard elements so they will re-apply the masonry effect
// whenever the contents are changed due to edits
dojo.subscribe('p4cms.content.element.editModeEnabled', function(element) {
    var formPartial = element.getFormPartial();

    // ignore elements that don't contain a pinboard
    // element or that we have already patched.
    if (!dojo.hasClass(element.domNode, 'content-element-type-pinboard')
        || element.isPatchedForPinboard
    ) {
        return;
    }

    // flag this element as being patched
    element.isPatchedForPinboard = true;

    // patch updateDisplayValue to re-masonize new contents
    var oldUpdateDisplayValue = element.updateDisplayValue;
    element.updateDisplayValue = function(data) {
        var result = dojo.hitch(element, oldUpdateDisplayValue)(data);
        p4cms.pinterest.masonize(element.domNode);
        return result;
    };
});