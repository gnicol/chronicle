dojo.provide('p4cms.mobile.Page');

dojo.require('p4cms.mobile.FlippableView');
dojo.require('p4cms.mobile.WidgetGridLayout');

// A mobile view that supports left/right swipe navigation within
// the same parent, and up/down navigation to other views in the bookStack
dojo.declare("p4cms.mobile.Page", dojox.mobile.FlippableView, {
    swapGroup:      "defaultGroup",
    pageLayout:     null,
    stackable:      true,
    keepScrollPos:  false,

    // lifecycle
    buildRendering: function() {
        this.inherited(arguments);

        this.setSelectable(this.domNode, false);
        dojo.addClass(this.domNode, 'p4cms-swap-view');
    },

    // lifecycle
    startup: function() {
        // grab reference to the current view, because
        // startup might change it
        var currentView = dojox.mobile.currentView;

        // fire onShow hook anytime a new view is shown.
        this.connect(this, 'performTransition', function(){
            if (dojox.mobile.currentView && dojox.mobile.currentView.onShow) {
                dojox.mobile.currentView.onShow();
            }
        });
        this.connect(this, 'show',              function(){
            if (dojox.mobile.currentView && this !== dojox.mobile.currentView
                    && dojox.mobile.currentView.onShow) {
                dojox.mobile.currentView.onShow();
            }
        });
        this.connect(this, 'onTouchEnd',        function(){
            if (dojox.mobile.currentView && !this._locked
                    && this !== dojox.mobile.currentView && dojox.mobile.currentView.onShow) {
                dojox.mobile.currentView.onShow();
            }
        });

        // if we already have a current view, but
        // this object is set to be visible, make
        // sure we transition from old to new
        if (currentView && this._visible) {
            // cancel visibility so we can show it ourselves
            this._visible = false;
            this.inherited(arguments);

            // inherited has a timeout, so we need to put one
            // here so this code is executed after
            setTimeout(dojo.hitch(this, function() {
                //show and transition
                dojo.style(this.domNode, 'display', '');
                this.onStartView();
                currentView.performTransition(this.id, 1, 'fade');
            }), dojo.isIE ? 100 : 0);
        } else {
            this.domNode.style.visibility = "hidden";
            this.inherited(arguments);
        }

        // if Elements are available, move their borders
        this.rootElementBorders();

        if (this.pageLayout && this.pageLayout.type === 'widget-grid') {
            this.layout = new p4cms.mobile.WidgetGridLayout(dojo.mixin({sourcePageNode:this.domNode}, this.pageLayout));
        }

        // let third parties know that a new page has fired up!
        dojo.publish('p4cms.mobile.Page.startup', [this]);
    },

    // adjust all child EditableElements to use this page as the root
    // for their edit borders
    rootElementBorders: function() {
        // EditableElement may not be present depending on user's permissions
        if (!p4cms.ui.EditableElement) {
            return;
        }

        var widgetNodes = dojo.query("[widgetid]", this.domNode);
        widgetNodes.forEach(function(node) {
            var widget = dijit.byNode(node);
            if (widget && widget.isInstanceOf(p4cms.ui.EditableElement)) {
                widget.bordersRootNode = this.domNode;
            }
        }, this);
        dojo.publish("p4cms.ui.refreshEditMode");
    },

    // Stub for third-parties to connect to.
    onShow: function(){},

    // called after the page has been animated into view
    onAfterTransitionIn: function() {
        // if we have access to content entries, toggle
        // edit toolbar menus as we navigate through pages
        if (p4cms.ui.getClass('p4cms.ui.toolbar.Toolbar')) {
            dojo.publish('p4cms.ui.toolbar.contextChange', [p4cms.ui.toolbar.getPrimaryToolbar()]);
        }

        if (p4cms.ui.EditableElement) {
            dojo.publish("p4cms.ui.refreshEditMode");
        }
    },

    // Get the currently displayed swapView
    getShowingView: function() {
        var nodes = dojo.query('.p4cms-swap-view'), i;
        for(i = 0; i < nodes.length; i++){
            if(dojo.style(nodes[i], "display") !== "none") {
                return dijit.byNode(nodes[i]);
            }
        }
    },

    // Moves the current view, and exposes siblings
    // extended to support scrolling vertically
    scrollTo: function(to) {
        if (!this._beingFlipped) {
            this.isFlickStarter = true;
        }
        // if we are the initiator, and set to scroll vertically, start showing the siblings
        // else let the superclass function take over
        if (!this._beingFlipped && this._v) {
            // vertical siblings are found in the bookstack registry, not through the dom.
            var newView, y;

            // if we are moving up, start showing the next bookmark
            // else start showing the previous
            if (to.y < 0) { // we are moving up, start showing the next bookmark
                newView = this._nextBook && this._nextBook.getCurrentPage();
                y       = to.y + this.domNode.offsetHeight;
            } else {
                newView = this._previousBook && this._previousBook.getCurrentPage();
                y       = to.y - this.domNode.offsetHeight;
            }

            // show the sibling if it exists
            if (newView) {
                newView.domNode.style.display   = "";
                newView._beingFlipped           = true;
                newView._enableVerticalMode();
                newView.scrollTo({y: y});
                newView._beingFlipped           = false;
            }

            dojox.mobile.FlippableView.superclass.scrollTo.apply(this, arguments);
        } else {
            this.inherited(arguments);
        }
    },

    // Slide away from this view, to another.
    // extended to support sliding vertically
    slideTo: function(to, duration, easing) {
        // if we are the initiator and set to slide vertically, grab the sibling and slide to it
        // else let the superclass function take over
        if (!this._beingFlipped && this._v) {
            var h       = this.domNode.offsetHeight,
                pos     = this.getPos(),
                newView, newY;

            // if we are attempting to move up, grab the next view
            // else grab the previous view
            if (pos.y < 0) {
                newView = this._nextBook && this._nextBook.getCurrentPage();

                // if you have moved more than a 1/4 of the view height, prep for slide to next view
                // else prep for sliding back to the current view
                if (pos.y < -h/4 && newView) {
                    to.y = -h;
                    newY = 0;
                } else if (newView) {
                    newY = h;
                }
            } else {
                newView = this._previousBook && this._previousBook.getCurrentPage();

                // if you have moved more than a 1/4 of the view height, prep for slide to previous view
                // else prep for sliding back to the current view
                if (pos.y > h/4 && newView) {
                    to.y = h;
                    newY = 0;
                } else if (newView) {
                    newY = -h;
                }
            }

            // slide in/or out the sibling if it exists
            if (newView) {
                newView._beingFlipped = true;
                newView._enableVerticalMode();
                newView.slideTo({y:newY}, duration, easing);
                newView._beingFlipped = false;

                // if moving to another view, set the current view
                if (newY === 0) {
                    dojox.mobile.currentView = newView;
                }
            }

            dojox.mobile.FlippableView.superclass.slideTo.apply(this, arguments);
        } else {
            this.inherited(arguments);
        }
    },

    // extended so that one new transition can queue up while another is taking place
    performTransition: function(moveTo, trasitionDir, transtion, context, method) {
        // don't transition to ourselves
        if (dojo.byId(moveTo) === this.domNode) {
            return;
        }

        // if we are already transitioning, queue up
        if (this.inTransition) {
            var args = arguments;
            this.disconnect(this.transitionListener);
            this.transitionListener = this.connect(this, 'onAnimationEnd', function() {
                this.disconnect(this.transitionListener);
                this.performTransition.apply(this, args);
            });
            return;
        }

        this.inherited(arguments);
    },

    // extended so that touch start events are ignored
    // during an animation
    onTouchStart: function() {
        // return if we are already in transition
        if (this.inTransition) {
            return;
        }

        // init next prev references on each touch start
        var book = this.getBook();
        this._nextView(this.domNode, true);
        this._previousView(this.domNode, true);
        this._nextBook = p4cms.mobile.bookstack.getNext(book);
        this._previousBook = p4cms.mobile.bookstack.getPrevious(book);

        this.inherited(arguments);
    },

    // extended to allow switching into vertical mode
    onTouchMove: function(e) {
        if (this._locked) {
            return;
        }

        var x  = e.touches ? e.touches[0].pageX : e.clientX,
            y  = e.touches ? e.touches[0].pageY : e.clientY,
            dx = x - this.touchStartX,
            dy = y - this.touchStartY;

        dx     = Math.abs(dx);
        dy     = Math.abs(dy);

        // the first TouchMove after TouchStart
        if (this._time.length === 1) {
            // enforce threshold for both directions
            if (dy < this.threshold && dx < this.threshold) {
                return;
            }

            // if there is something on the bookstack to navigate to, enable vertical mode
            if ((p4cms.mobile.bookstack.length > 1) && !this._v && dy > dx) {
                this._enableVerticalMode();
            }
        }

        this.inherited(arguments);
    },

    // extended to disabled vertical mode after touch ends
    onTouchEnd: function() {
        if (this._locked) {
            return;
        }

        this.inherited(arguments);
        this._disableVerticalMode();

        // if we have a next or previous book, disable vertical mode and clear our reference
        if (this._nextBook && this._nextBook.getCurrentPage()) {
            this._nextBook.getCurrentPage()._disableVerticalMode();
        }

        if (this._previousBook && this._previousBook.getCurrentPage()) {
            this._previousBook.getCurrentPage()._disableVerticalMode();
        }

        this._nextBook      = null;
        this._previousBook  = null;
    },

    // extended in order to track current animation state
    onAnimationStart: function() {
        this.inTransition = true;
        this.inherited(arguments);
    },

    // extended to disable verticle mode
    abort: function() {
        this.inherited(arguments);
        this._disableVerticalMode();
        this.isFlickStarter = false;
    },

    // extended to properly remove webkit animations
    stopAnimation: function() {
        if (this.animNodes && this.animNodes.length) {
            if (dojo.isWebKit) {
                var i;
                for (i=0; i < this.animNodes.length; i++) {
                    dojo.style(this.animNodes[i], {
                        webkitTransform: "",
                        webkitAnimationDuration: "",
                        webkitAnimationTimingFunction: ""
                    });
                }
            }
            this.animNodes = [];
        }

        this.inherited(arguments);
    },

    // extend onFlickAnimationEnd to handle hide showing views
    onFlickAnimationEnd: function(e) {
        this.inherited(arguments);

        // if we have transitioned from this view using slide, hide non-current views
        if (this.isFlickStarter) {
            var book            = this.getBook(),
                nextBook        = p4cms.mobile.bookstack.getNext(book),
                previousBook    = p4cms.mobile.bookstack.getPrevious(book);

            dojo.forEach([this, this._nextView(this.domNode), this._previousView(this.domNode),
                    (nextBook && nextBook.getCurrentPage()),
                    (previousBook && previousBook.getCurrentPage())], function(view) {
                if (view && dojox.mobile.currentView !== view) {
                    view.domNode.style.display = "none";
                }
            }, this);

            dojox.mobile.currentView.onAfterTransitionIn();

            this.isFlickStarter = false;
        }
    },

    // extend onAnimationEnd to preserve p4cms classes
    onAnimationEnd: function(e) {
        if (e && e.animationName && e.animationName.indexOf("Out") === -1 && e.animationName.indexOf("In") === -1) {
            this.inTransition = false;
            return;
        }

        var className = this.domNode.className;

        this.inherited(arguments);

        if (this.domNode) {
            this.domNode.className = this._clearedClasses(className);
        }

        this.inTransition = false;
    },

    // enable vertical scrolling on this page
    _enableVerticalMode: function() {
        if (!this._oldDir) {
            this._v         = true;
            this._oldDir    = this.scrollDir;
            this.scrollDir  =  "v";
            this._h         = false;
            this._f         = false;
        }
    },

    // disable vertical scrolling on this page
    _disableVerticalMode: function() {
        if (this._oldDir) {
            this.scrollDir  = this._oldDir;
            this._oldDir    = null;
            this._v         = (this.scrollDir.indexOf('v') > -1 ? true : false);
            this._h         = (this.scrollDir.indexOf('h') > -1 ? true : false);
            this._f         = (this.scrollDir === 'f');
        }
    },

    // overriden
    // Find the next view.
    // If it already knows of one that it previously found, it will return
    // that view unless you set forceUpdate to true, which makes it search again.
    _nextView: function(node, forceUpdate) {
        if (this._nView && !this._nView._destroyed && !forceUpdate) {
            return this._nView;
        }

        return this._siblingView(node, 1, '_nView');
    },

    // overriden
    // Find the previous view.
    // If it already knows of one that it previously found, it will return
    // that view unless you set forceUpdate to true, which makes it search again.
    _previousView: function(node, forceUpdate){
        if (this._pView && !this._pView._destroyed && !forceUpdate) {
            return this._pView;
        }

        return this._siblingView(node, -1, '_pView');
    },

    // Finds a sibling view in the passed direction that share the same swapGroup.
    _siblingView: function(node, direction, cacheAttribute) {
        var mover = direction < 0 ? 'previousSibling' : 'nextSibling',
            n;

        for (n = node[mover]; n; n = n[mover]) {
            if (n.nodeType === 1 && dijit.byNode(n)
                    && dijit.byNode(n).swapGroup === this.swapGroup) {
                this[cacheAttribute] = dijit.byNode(n);
                return this[cacheAttribute];
            }
        }

        return null;
    },

    // extended to track the nodes that were animation on
    _runSlideAnimation: function(from, to, duration, easing, node, idx) {
        this.animNodes = this.animNodes || [];
        this.animNodes.push(node);

        this.inherited(arguments);
    },

    // pulled in from upstream and extended to allow our p4cms
    // classes to remain on elements after animation
    _clearedClasses: function(clsString) {
        if (!clsString) {
            return;
        }

        var classes = [];
        dojo.forEach(dojo.trim(clsString || "").split(/\s+/), function(c) {
            if (c.match(/^mbl\w*View$/) || c.match(/^p4cms-\w*-view$/)) {
                classes.push(c);
            }
        }, this);

        return classes.join(' ');
    },

    // extend the show function to maintain bookStack
    show: function() {
        var view = dojox.mobile.currentView;

        dojo.style(this.domNode, 'visibility', 'visible');
        this.inherited(arguments);
        this.onAfterTransitionIn();
        if (p4cms.ui.EditableElement) {
            dojo.publish("p4cms.ui.refreshEditMode");
        }
    },

    getBook: function() {
        return dijit.byNode(new dojo.NodeList(this.domNode).closest('.mblBook')[0]);
    }
});

// Can be added to any dom node to allow you to trigger transistions on click
//  specify moveTo and transition as attributes of the dom node
dojo.declare("p4cms.mobile.SwapTrigger", dojox.mobile.AbstractItem, {
    // lifecycle
    buildRendering: function() {
        this.inherited(arguments);

        // connect the listener
        if(this.moveTo || this.href || this.url || this.clickable){
            this.connect(this.domNode, "onclick", "onClick");
        }
    },

    // capture onclick and peform transition
    onClick: function(e){
        dojo.stopEvent(e);
        this.transitionTo(this.moveTo, this.href, this.url, this.scene);
    }
});
