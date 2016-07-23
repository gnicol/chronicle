// summary:
//      wrapper class around dijit.popup and adds some functionality
//      allows you to specify constrainToTarget option on open that will
//      ensure the dialog is viewable onscreen while the target is being
//      scrolled

dojo.provide("p4cms.ui.popup");

dojo.require("p4cms.ui.base");

// @todo use new dojo.abstract methods when dojo 1.7 is included
// keep out of the global namespace
(function() {
    // patch popup open to create a fixed position if target is fixed
    // @todo remove once dojo adds support for position fixed
    // also patch open to move popups when scrollroot scrolls
    // @todo remove when http://bugs.dojotoolkit.org/ticket/5777 is fixed
    var oldPopupOpen = dijit.popup.open;
    dijit.popup.open = function(args) {
        var wrapper;
        if (args.around) {
            wrapper = this._createWrapper(args.popup);
            if (p4cms.ui.withinPosition(args.around, 'fixed')) {
                dojo.style(wrapper, 'position', 'fixed');
            }
        }

        var result = oldPopupOpen.apply(this, arguments);

        if (args.around) {
            var widget          = args.popup,
                scrollingNodes  = p4cms.ui.popup.findScrollingParents(args.around);

            // disconnect any previous handlers
            dojo.forEach(widget.scrollHandlers, widget.disconnect, widget);

            if (scrollingNodes && scrollingNodes.length) {
                var orient = args.orient || (
                    (args.parent ? args.parent.isLeftToRight() : dojo._isBodyLtr()) ?
                    {'BL':'TL', 'BR':'TR', 'TL':'BL', 'TR':'BR'} :
                    {'BR':'TR', 'BL':'TL', 'TR':'BR', 'TL':'BL'});

                // reposition the wrapper node when the scrollRoot scrolls
                widget.scrollHandlers = [];
                dojo.forEach(scrollingNodes, function(scrollNode) {
                    widget.scrollHandlers.push(widget.connect(scrollNode, 'onscroll', function() {
                        // reposition if we are in the expected visible state
                        // otherwise disconnect the handler
                        if (args.around && this.domNode && dojo.style(wrapper, "display") !== 'none') {
                            // calculate overflow in 2 directions
                            var elPos   = dojo.position(scrollNode),
                                nodePos = dojo.position(args.around),
                                t       = nodePos.y - Math.max(elPos.y, 0),
                                bot     = t + nodePos.h - elPos.h;

                            // beyond top: < 0 or beyond bottom: > 0
                            if (t < 0 || bot > 0) {
                                p4cms.ui.popup.moveOffScreen(widget);
                            } else {
                                dijit.placeOnScreenAroundElement(wrapper, args.around, orient, widget.orient ?
                                    dojo.hitch(widget, "orient") : null);
                                wrapper.style.display = "";
                                wrapper.style.visibility = "visible";
                                widget.domNode.style.visibility = "visible";
                            }
                        } else {
                            dojo.forEach(widget.scrollHandlers, widget.disconnect, widget);
                        }
                    }));
                }, this);
            }
        }

        return result;
    };

    // patch hide to disconnect any scroll handlers
    var oldPopupHide = dijit.popup.hide;
    dijit.popup.hide = function(widget) {
        dojo.forEach(widget.scrollHandlers, widget.disconnect, widget);

        return oldPopupHide.apply(this, arguments);
    };
}());

// use dojo.delegate to extend the dijit.popup object and override some of it's functions
p4cms.ui.popup = dojo.delegate(dijit.popup, {
    // calls dijit.popup.open and makes calls to createConstraint
    open: function(args) {
        // remove the popup from the stack if it is already there
        // in order to avoid a loop later in popup.open that closes
        // all the popups on the stack
        this.removeFromStack(args.popup);
        var openVal = dijit.popup.open(args);

        if (args.constrainToTarget && p4cms.ui.toolbar && p4cms.ui.toolbar.getPrimaryToolbar()) {
            // offset it under the toolbars
            var offset = p4cms.ui.toolbar.getPrimaryToolbar().getSize(true).h;
            this.createConstraints(args.popup, args.around, args.orient, offset);
        }

        return openVal;
    },

    // override close to include option to only close self if there are
    // higher popups
    close: function(popup, limitToSelf) {
        var stack = dijit.popup._stack, i;

        // find and move popup to top of stack if it is not already there
        if (limitToSelf && stack.length > 0 && popup !== stack[stack.length-1].widget) {
            // look backwards through the stack until we find our popup
            for (i = stack.length-1; i >= 0; i--) {
                if (stack[i].widget === popup) {
                    var stackOn = stack[i];
                    stack.splice(i, 1);
                    stack.push(stackOn);
                    break;
                }
            }
        }

        dijit.popup.close(popup);
    },

    // remove the popup from the popup stack
    removeFromStack: function(popup) {
        var stack = dijit.popup._stack, i;
        if (stack.length > 0) {
            // look backwards through the stack until we find our popup
            for (i = stack.length-1; i >= 0; i--) {
                if (stack[i].widget === popup) {
                    stack.splice(i, 1);
                    break;
                }
            }
        }
    },

    // create scroll listener to triger constraint checks
    createConstraints: function(widget, aroundNode, orient, offset) {
        // only create one constraint listener per open widget
        widget.disconnect(widget.constraint);

        var timer = 0;
        // use the widget to connect so the listener is removed on destroy
        widget.constraint = widget.connect(window, 'onscroll', function () {
            if (timer) {
                clearTimeout(timer);
            }

            timer = setTimeout(function() {
                if (widget.transition && widget.transition.status() === 'playing') {
                    widget.transition.stop();
                    widget.dirtyTransition = true;
                }
                p4cms.ui.popup.checkConstraints(widget, aroundNode, orient, offset);
            }, 100);
        });

        // check the initial constraints
        p4cms.ui.popup.checkConstraints(widget, aroundNode, orient, offset);
    },

    // check to see if aroundNode is being scrolled, and reposition the
    // dialog to be onscreen
    checkConstraints: function(widget, aroundNode, orient, offset) {
        if (!widget.constraint || !widget.domNode) {
            return;
        }

        offset      = offset || 0;
        aroundNode  = dojo.byId(aroundNode);
        var wrapper = p4cms.ui.popup._createWrapper(widget);

        // find current orientation
        var above = dojo.hasClass(widget.domNode, 'dijitTooltipAbove');

        // calculate the viewable area
        var aroundNodePos   = dojo.position(aroundNode, true),
            nodeBox         = dojo.marginBox(wrapper),
            view            = dojo.window.getBox(),
            viewTop         = view.t + offset,
            removeNextMove  = true,
            top             = aroundNodePos.y - (above ? nodeBox.h : 0) - viewTop,
            bottom          = aroundNodePos.y + aroundNodePos.h - (above ? nodeBox.h : 0) - viewTop;

        // top < 0 within top
        // bottom > 0 within bottom
        // If we haven't already fixed the position, do it now
        if (top < 0 && bottom > 0) {
            // animate the transition
            // slide the box to the new location, then make it fixed
            if (dojo.style(wrapper, 'position') !== 'fixed') {
                widget.transition = dojo.fx.slideTo({
                    node:       wrapper,
                    left:       parseInt(wrapper.style.left, 10),
                    top:        view.t + offset,
                    units:      'px',
                    duration:   250,
                    onEnd:      function() {
                        dojo.style(wrapper, {'position':'fixed', 'top': offset + 'px'});
                    }
                });
                widget.transition.play();
            } else {
                dojo.style(wrapper, {'position':'fixed', 'top': offset + 'px'});
            }
        } else if (top >= 0 || bottom <= 0) {
            // if we have fixed the position, now we want to restore it to
            // normal operation
            if (dojo.style(wrapper, 'position') === 'fixed') {
                // save current position
                var origLeft    = wrapper.style.left,
                    origTop     = wrapper.style.top;

                // use dijit's place onscreen method to move the node to where is should be shown
                dijit.placeOnScreenAroundElement(wrapper, aroundNode, orient,
                    widget.orient ? dojo.hitch(widget, "orient") : null);

                // grab the new location, then restore the oldLocation
                // and hopefully the browser didn't notice a change yet
                var pos = dojo.position(wrapper, true);
                wrapper.style.left  = origLeft;
                wrapper.style.top   = origTop;

                // slide node into it's new position
                widget.transition = dojo.fx.slideTo({
                    node:       wrapper,
                    left:       parseInt(pos.x, 10),
                    top:        parseInt(pos.y, 10),
                    units:      'px',
                    duration:   250
                });
                widget.transition.play();

                // slideTo changes the position back to
                // absolute, (in dojo 1.5) so our widget is no longer fixed
            } else if (widget.dirtyTransition) {
                // we may have previously grabbed the position mid slide if we had
                // to stop a transition, reset the location
                dijit.placeOnScreenAroundElement(wrapper, aroundNode, orient,
                        widget.orient ? dojo.hitch(widget, "orient") : null);
                delete widget.dirtyTransition;
            }
        }
    },

    // Find the scrolling parents
    // code in this method is based on the same scroll tree climbing done in
    // dojo.window.scrollIntoView
    findScrollingParents: function(node) {
        // catch unexpected/unrecreatable errors that dojo has noted, returning null isn't a dealbreaker
        try {
            node        = dojo.byId(node);
            var doc     = node.ownerDocument || dojo.doc,
                body    = doc.body || dojo.body(),
                html    = doc.documentElement || body.parentNode,
                isIE    = dojo.isIE,
                isWK    = dojo.isWebKit;

            // if already at the root, give up early
            if (node === body || node === html) {
                return null;
            }

            var backCompat      = doc.compatMode === 'BackCompat',
                clientAreaRoot  = (isIE >= 9 && node.ownerDocument.parentWindow.frameElement)
                    ? ((html.clientHeight > 0 && html.clientWidth > 0 && (body.clientHeight === 0
                    || body.clientWidth === 0 || body.clientHeight > html.clientHeight
                    || body.clientWidth > html.clientWidth)) ? html : body)
                    : (backCompat ? body : html),
                scrollRoot      = isWK ? body : clientAreaRoot,
                el              = node.parentNode,
                isFixed         = function(el) {
                    return ((isIE && backCompat)
                        ? false
                        : (dojo.style(el, 'position').toLowerCase() === "fixed"));
                };

            // nothing to do if fixed
            if (isFixed(node)) {
                return null;
            }

            // walk the tree
            var scrollingParents = [];
            while (el) {
                if (el === body) {
                    break;
                }

                var fixedPos    = isFixed(el),
                    overflowY   = dojo.style(el, 'overflowY'),
                    overflowX   = dojo.style(el, 'overflowX');

                // some browsers won't return a full value from 'overflow' so we need to
                // grab the specific overflow types
                overflowY   = overflowY && (overflowY.toLowerCase() === 'auto'
                    || overflowY.toLowerCase() === 'scroll');
                overflowX   = overflowX && (overflowX.toLowerCase() === 'auto'
                    || overflowX.toLowerCase() === 'scroll');

                if (overflowY || overflowX) {
                    scrollingParents.push(el);
                }

                el = (el !== scrollRoot) && !fixedPos && el.parentNode;
            }

            return scrollingParents;
        } catch(error) {/* ignore errors */}

        return null;
    }
});
