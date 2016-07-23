// summary:
//      Sets the viewport meta tag appropriately for mobile devices.
//
//      If the user is not logged in and the device orientation is
//      portrait adds a viewport metatag to set the width equal to
//      the device width and the initial scale to 1.0.
//      On high resolution mobile devices the viewport is generally
//      reported as ~980px wide which will fail to trigger media 
//      queries targeting lower res devices. By using the device
//      width the media queries take effect. The initial scale of
//      1.0 ensures the page is appropriately zoomed to fill the screen.
//
//      When the device is held in landscape orientation or the user
//      is logged in, we set the width to the device's native viewport 
//      width. Typically landscape orientation provides enough horizontal
//      realestate that the mobile css rules are not required.

dojo.provide('p4cms.ui.mobile.DynamicViewport');

dojo.declare('p4cms.ui.mobile.DynamicViewport', null, {
    initialWidth: null,

    constructor: function() {
        // capture the starting width for later use
        this.intialWidth = dojo.window.getBox().w;
        
        // run when the orientation changes
        dojo.connect(window, "onorientationchange", this, "updateViewportMeta");

        // ensure we do an update when the page first loads
        dojo.addOnLoad(dojo.hitch(this, function() { this.updateViewportMeta(); }));
    },
    
    updateViewportMeta: function() {
        //@todo don't give mobile if logged in
        if (window.orientation === undefined) {
            return;
        }

        // if we can locate an initialized or uninitialized toolbar 
        // we assume the user is logged in; exit early
        if (p4cms.ui.toolbar.getPrimaryToolbar() 
            || dojo.query('[dojoType=p4cms.ui.toolbar.Toolbar]')[0]
        ) {
            return;
        }

        var meta = dojo.query('head meta[name=viewport]')[0];
        if (!meta) {
            meta = dojo.create('meta', {name: 'viewport'}, document.getElementsByTagName("head")[0]);
        }

        if (window.orientation === 0 || window.orientation === 180) {
            dojo.attr(meta, 'content', 'width=device-width, initial-scale=1.0');
        } else {
            dojo.attr(meta, 'content', 'width=' + (this.intialWidth || 980));
        }
    }
});