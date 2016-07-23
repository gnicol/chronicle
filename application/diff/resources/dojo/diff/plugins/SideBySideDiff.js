// summary
//      diff mode plugin for side-by-side comparison of html or images

dojo.provide("p4cms.diff.plugins.SideBySideDiff");

dojo.require("p4cms.diff.plugins.DiffPlugin");

dojo.declare("p4cms.diff.plugins.SideBySideDiff", p4cms.diff.plugins.DiffPlugin,
{
    // config:
    parentSizePane:         null,
    syncScrollbars:         true,

    // millisecond delay after a scroll event before we
    // listen to scroll events from other scrollbars
    scrollDelay:            100,

    containers:             null,
    handlers:               null,
    scrollDeferred:         null,

    // lifecycle
    // define complex objects
    postMixInProperties: function() {
        this.inherited(arguments);
        this.handlers = [];
    },

    // lifecycle
    // set our domNode
    buildRendering: function() {
        this.inherited(arguments);
        this.domNode = dojo.query('.side-by-side', this.getDiffElement().domNode)[0];
    },

    // lifecycle
    // add our mode to the parent diffElement
    postCreate: function() {
        this.inherited(arguments);

        this.containers = {
            left:   dojo.query('.side-by-side-container .left', this.getDiffElement().domNode)[0],
            right:  dojo.query('.side-by-side-container .right', this.getDiffElement().domNode)[0]
        };

        if (dojo.isFunction(this.getDiffElement().addMode)) {
            this.getDiffElement().addMode(this, 'side-by-side', this.domNode, 'Side By Side');
        }
    },

    // lifecycle
    // do any calls and queries that are dependant on the diff or viewer to be
    // started
    startup: function() {
        this.inherited(arguments);

        if (!this.parentSizePane) {
            this.parentSizePane = dojo.query('#'+this.getDiffElement().id).closest('.diff-comparison-pane')[0];
        }

        this.resize();
        this.handlers.push(
            dojo.connect(this.getDiffElement().getViewer(), '_sizeForDialog', this, 'resize')
        );

        this.connectScrollbars();
    },

    // calculate the max-height before showing scrollbars
    resize: function() {
        var i;
        var box = dojo._getContentBox(this.parentSizePane);
        var newHeight = Math.floor(box.h * 0.8);
        var wrappers = dojo.query('td .wrapper', this.domNode);
        for (i = 0; i < wrappers.length; i++) {
            dojo.style(wrappers[i], 'max-height', newHeight + 'px');
        }
    },

    // one-time creation of scroll handlers
    connectScrollbars: function() {
        var leftScroller  = dojo.query('.wrapper', this.containers.left)[0];
        var rightScroller = dojo.query('.wrapper', this.containers.right)[0];

        var leftHandle  = dojo.connect(leftScroller, 'scroll', this,
                dojo.partial(this.syncContainerScroll, leftScroller, rightScroller));
        var rightHandle = dojo.connect(rightScroller, 'scroll', this,
                dojo.partial(this.syncContainerScroll, rightScroller, leftScroller));

        // add handlers to be removed upon destroy
        this.handlers.push(leftHandle, rightHandle);
    },

    // sync the two scrollers that are passed in
    // the inactiveScroller is scrolled to the same as the activeScroller
    // this method is expected to be called multiple times during a scroll
    syncContainerScroll: function(activeScroller, inactiveScroller) {
        // If this event is firing while we are in the middle of a scroll
        // ensure that only the events of the scrollbar the user is moving
        // are acted upon
        if (this.syncScrollbars && (!this.scrollDeferred ||
                this.scrollDeferred.scrollTarget === activeScroller)) {
            // keeps track of our active scroller for the duration of a scroll
            this.trackDeferredScroll(activeScroller);

            // sync
            if (activeScroller.scrollTop !== inactiveScroller.scrollTop) {
                inactiveScroller.scrollTop = activeScroller.scrollTop;
            }
            if (activeScroller.scrollLeft !== inactiveScroller.scrollLeft) {
                inactiveScroller.scrollLeft = activeScroller.scrollLeft;
            }
        }
    },

    // tracks the current scroll action
    // cancels any previously set tracker
    trackDeferredScroll: function(activeScroller) {
        if (this.scrollDeferred) {
            this.scrollDeferred.cancel();
        }
        this.scrollDeferred = new dojo.Deferred();
        this.scrollDeferred.addCallback(dojo.hitch(this, this.endDeferredScroll));

        // save the target so we know what our active scroller is
        this.scrollDeferred.scrollTarget = activeScroller;

        setTimeout(dojo.partial(function (callback) {
            try {
                callback();
            } catch (error) {
                // it likely has already been cancelled
                // TODO: we should never swallow errors, log somewhere
            }
        }, this.scrollDeferred.callback), this.scrollDelay);
    },

    // reset the tracker
    endDeferredScroll: function() {
        this.scrollDeferred = null;
    },

    // lifecycle
    // disconnect our handlers
    destroy: function() {
        this.inherited(arguments);
        var i;

        for (i = 0; i < this.handlers.length; i++) {
            dojo.disconnect(this.handlers[i]);
        }
    }
});