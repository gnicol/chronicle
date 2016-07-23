// summary:
//      very simple light box presentation of a given href - works best with images,
//      but also works for arbitrary pages (falls back to an iframe) - in the case of
//      non-image content, if it is on another domain it cannot be sized automatically
//      and will default to 90% of the viewport unless a width/height are specified.

dojo.provide("p4cms.ui.LightBox");
dojo.require("dijit._Widget");
dojo.require("dojo.NodeList-traverse");

dojo.declare("p4cms.ui.LightBox", dijit._Widget,
{
    href:           '',
    loaded:         false,
    frame:          null,
    underlay:       null,
    closeButton:    null,
    prevButton:     null,
    nextButton:     null,
    width:          null,
    height:         null,
    opacity:        0.5,
    duration:       2000,
    opener:         null,
    listNode:       null,
    prevFrames:     null,

    startup: function() {
        // auto-detect list node if unset and opener is given.
        if (this.listNode === null && this.opener) {
            this.listNode = new dojo.NodeList(this.opener).closest('ul')[0];
        }

        this.prevFrames = [];
        this._makeFrame();
        this._makeUnderlay();
        this._makeControls();

        // show the content.
        this.open();
    },

    open: function(){
        // showing the underlay will indirectly show the frame contents.
        this._showUnderlay();
    },

    close: function(){
        var elements = [
            this.frame,
            this.underlay,
            this.closeButton,
            this.prevButton,
            this.nextButton
        ];
        dojo.forEach(elements, function(element){
            if (element) {
                p4cms.ui.hide(element);
            }
        });
    },

    prev: function(){
        var prev = this._getAdjacent(-1);
        if (prev) {
            this.setHref(prev.href);
        }
    },

    next: function(){
        var next = this._getAdjacent(1);
        if (next) {
            this.setHref(next.href);
        }
    },

    setHref: function(href) {
        var onLoad = dojo.connect(this, 'onLoad', dojo.hitch(this, function() {
            dojo.forEach(this.prevFrames, function(frame) {p4cms.ui.hide(frame);});
            this.prevFrames = [];
            this._showFrame();
            dojo.disconnect(onLoad);
        }));
        this.href = href;
        this._makeFrame();
    },

    // neutral point for listeners to connect to.
    // called when image or iframe content is laoded.
    onLoad: function(){
        this.loaded = true;
    },

    // create an image (or an iframe) to contain the content
    // we hide it initially and place it into the body to load it
    // if it fails to load as an image, we'll try to load it into
    // an iframe instead.
    _makeFrame: function(type){
        type = type || 'img';

        var frame = dojo.create(type, {
            'src':   this.href,
            'class': 'lightbox-frame'
        }, dojo.body());

        // keep a reference to any old frames
        if (this.frame) {
            this.prevFrames.push(this.frame);
        }
        this.frame = frame;

        dojo.style(frame, {
            visibility: 'hidden',
            position:   'fixed',
            zIndex:     1001
        });

        var loadHandler = dojo.hitch(this, function() {
            // don't fire onload if our frame is no longer
            // the current frame
            if (frame === this.frame) {
                this.onLoad();
            }
        });

        // trigger onLoad method when the content loads.
        // some browsers won't fire onload if the image is already
        // loaded elsewhere on the page - so we look for this case
        // by checking the 'complete' property and fire onload manually.
        if (frame.tagName.toLowerCase() === 'img' && frame.complete) {
            loadHandler();
        } else {
            // dojo.connect doesn't work with IE 7 and 8 in this case
            // therefore we need to use IE's native attachEvent instead.
            // see: http://bugs.dojotoolkit.org/ticket/9609
            if (frame.attachEvent) {
                frame.attachEvent('onload', loadHandler);
            } else {
                dojo.connect(frame, 'onload',  this, loadHandler);
            }
        }

        // if we encounter an error loading the content into an
        // image element, try to load it into an iframe instead.
        if (frame.tagName.toLowerCase() === 'img') {
            dojo.connect(frame, 'onerror', dojo.hitch(this, function() {
                // make sure frame is current frame
                if (frame === this.frame) {
                    this._makeFrame('iframe');
                }
            }));
        }

        return frame;
    },

    _makeUnderlay: function(){
        this.underlay = dojo.create('div', {
            'class': 'lightbox-underlay'
        }, dojo.body());

        dojo.style(this.underlay, {
            position:   'fixed',
            zIndex:     1000,
            top:        0,
            left:       0
        });

        // size the overlay to the viewport now and if window resizes.
        this._sizeUnderlay();
        dojo.connect(window, 'onresize', this, '_sizeUnderlay');

        // close lightbox when the underlay is clicked.
        dojo.connect(this.underlay, 'onclick', this, 'close');
    },

    _makeControls: function(){
        // make close button.
        this.closeButton = dojo.create('div', {
            'class': 'lightbox-close'
        }, dojo.body());
        dojo.style(this.closeButton, {
            visibility: 'hidden',
            position:   'fixed',
            zIndex:     1003
        });

        // close when the button is clicked.
        dojo.connect(this.closeButton, 'onclick', this, 'close');

        // if we don't have a list-node, all done.
        if (!this.listNode) {
            return;
        }

        // make a previous image button.
        this.prevButton = dojo.create('div', {
            'class':    'lightbox-prev',
            innerHTML:  '<span class="icon"></span>'
        }, dojo.body());
        dojo.style(this.prevButton, {
            visibility: 'hidden',
            position:   'fixed',
            zIndex:     1002
        });

        // make a next image button.
        this.nextButton = dojo.create('div', {
            'class':    'lightbox-next',
            innerHTML:  '<span class="icon"></span>'
        }, dojo.body());
        dojo.style(this.nextButton, {
            visibility: 'hidden',
            position:   'fixed',
            zIndex:     1002
        });

        // go back/forward when clicked.
        dojo.connect(this.prevButton, 'onclick', this, 'prev');
        dojo.connect(this.nextButton, 'onclick', this, 'next');
    },

    _sizeUnderlay: function(){
        var viewport = dojo.window.getBox();
        dojo.style(this.underlay, {
            width:  (viewport.w * 2) + 'px',
            height: (viewport.h * 2) + 'px'
        });
    },

    // fade-in the underlay
    _showUnderlay: function(){
        dojo.style(this.underlay, 'display', 'none');
        p4cms.ui.show(this.underlay, {
            // underlay takes 1/3 of fade in time, frame takes 2/3's.
            duration:  Math.round(this.duration * (1/3)),
            end:       this.opacity,
            onEnd:     dojo.hitch(this, function(){
                // display the content frame (make sure it has loaded first).
                if (this.loaded) {
                    this._showFrame();
                } else {
                    dojo.connect(this, 'onLoad', this, '_showFrame');
                }
            })
        });
    },

    _getContentSize: function(){
        // if an explicit width/height have been specified, use them.
        if (this.width && this.height) {
            return {w: this.width, h: this.height};
        }

        // make sure the frame has layout
        if (dojo.style(this.frame, 'display') === 'none') {
            dojo.style(this.frame, {visibility: 'hidden', display: 'block'});
        }

        // attempt to determine size of content.
        try {
            if (this.frame.tagName.toLowerCase() === 'img') {
                return dojo.marginBox(this.frame);
            } else {
                return {
                    w: this.frame.contentWindow.document.body.scrollWidth,
                    h: this.frame.contentWindow.document.body.scrollHeight
                };
            }
        } catch (error) {
            // likely cross-domain iframe.
        }

        return {w: null, h: null};
    },

    // size and position the content frame.
    _sizeFrame: function(){
        // don't let width/height exceed 90% of viewport.
        var viewport  = dojo.window.getBox();
        var maxWidth  = viewport.w * 0.9;
        var maxHeight = viewport.h * 0.9;

        // determine size of content - if we can't, use max.
        var contentSize = this._getContentSize();
        var frameWidth  = contentSize.w && contentSize.w <= maxWidth  ? contentSize.w : maxWidth;
        var frameHeight = contentSize.h && contentSize.h <= maxHeight ? contentSize.h : maxHeight;

        // if frame is an image, preserve its proportions - check which dimension scaled
        // down the most and compute the other dimension from that scaling factor.
        if (this.frame.tagName.toLowerCase() === 'img' && contentSize.w && contentSize.h) {
            var widthScale  = frameWidth  / contentSize.w;
            var heightScale = frameHeight / contentSize.h;
            if (widthScale < heightScale) {
                frameHeight = Math.round(widthScale  * contentSize.h);
            } else {
                frameWidth  = Math.round(heightScale * contentSize.w);
            }
        }

        this.frame.width  = frameWidth  + "px";
        this.frame.height = frameHeight + "px";

        // position the frame in the middle of the viewport.
        var left = Math.round((viewport.w - frameWidth) / 2);
        var top  = Math.round((viewport.h - frameHeight) / 2);
        dojo.style(this.frame, {
            left:   left + "px",
            top:    top  + "px",
            width:  frameWidth  + "px",
            height: frameHeight + "px"
        });

        // position close button in top-right corner.
        if (dojo.style(this.closeButton, 'display') === 'none') {
            dojo.style(this.closeButton, {
                display: 'block',
                visibility: 'hidden'
            });
        }
        dojo.style(this.closeButton, {
            right: (left - Math.round(dojo.coords(this.closeButton).w / 2)) + 'px',
            top:   (top  - Math.round(dojo.coords(this.closeButton).h / 2)) + 'px'
        });

        // size prev/next buttons to occupy the full height of the frame and
        // 1/3rd of the width on the left and right respectively.
        if (this.prevButton) {
            dojo.style(this.prevButton, {
                left:       left + 'px',
                top:        top  + 'px',
                width:      Math.round(frameWidth / 3) + 'px',
                height:     frameHeight + 'px'
            });
        }
        if (this.nextButton) {
            dojo.style(this.nextButton, {
                right:      left + 'px',
                top:        top  + 'px',
                width:      Math.round(frameWidth / 3) + 'px',
                height:     frameHeight + 'px'
            });
        }
    },

    _showFrame: function(){
        // size/position the content frame now and if window resizes.
        this._sizeFrame();

        dojo.connect(window, 'onresize', this, '_sizeFrame');

        // underlay takes 1/3 of fade in time, frame takes 2/3's.
        var duration = Math.round(this.duration * (2/3));

        // fade in the frame.
        dojo.style(this.frame, 'display', 'none');
        dojo.style(this.frame, 'visibility', 'visible');
        p4cms.ui.show(this.frame, {duration: duration});

        // list the buttons to control visibility of as an array of arrays
        // first element is the button, second is a show/hide flag.
        // we hide the prev/next button on the first/last image respectively.
        var buttons = [
            [this.closeButton, true],
            [this.prevButton,  this._getAdjacent(-1)],
            [this.nextButton,  this._getAdjacent(1)]
        ];
        dojo.forEach(buttons, function(button){
            if (button[0] && button[1]) {
                dojo.style(button[0], 'display', 'none');
                dojo.style(button[0], 'visibility', 'visible');
                p4cms.ui.show(button[0], {duration: duration});
            } else if (button[0]) {
                dojo.style(button[0], 'display', 'none');
            }
        });
    },

    _getAdjacent: function(offset){
        if (!this.listNode) {
            return;
        }

        var all      = dojo.query('li > a', this.listNode);
        var current  = all.filter(dojo.hitch(this, function(node){return node.href === this.href;}))[0];
        var index    = dojo.indexOf(all, current);
        var adjacent = all[index + offset];

        return adjacent || null;
    }
});
