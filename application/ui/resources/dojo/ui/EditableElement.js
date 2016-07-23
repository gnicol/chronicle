// summary:
//      Support for editable elements.

dojo.provide("p4cms.ui.EditableElement");
dojo.require("dijit._Widget");
dojo.require("dijit._Templated");
dojo.require("dojo.fx");

dojo.declare("p4cms.ui.EditableElement", dijit._Widget,
{
    // Implementor _must_ specify group
    group:              '',

    borders:            {},
    isHighlighted:      false,
    maskBlocks:         {},
    isMasked:           false,
    maskResizeHandler:  null,
    maskPadding:        10,
    borderPadding:      2,
    minHeight:          10,
    originalMinHeight:  null,
    enableHandler:      null,
    disableHandler:     null,
    refreshHandler:     null,
    inEditMode:         false,
    interestLevel:      0,
    heightForced:       false,
    bordersRootNode:    null,
    _isFocused:         false,

    constructor: function() {
        // toggle edit mode as appropriate.
        this.enableHandler  = dojo.subscribe(
            "p4cms." + this.group + ".enableEditMode",  this, "enableEditMode");
        this.disableHandler = dojo.subscribe(
            "p4cms." + this.group + ".disableEditMode", this, "disableEditMode");
        this.refreshHandler = dojo.subscribe(
            "p4cms.ui.refreshEditMode", this, "refreshEditMode");

        // if highlight or mask was drawn prior to resources being
        // loaded, it may be misplaced - redraw just to be safe.
        dojo.connect(window, 'onload', this, 'refreshMask');
        dojo.connect(window, 'onload', this, 'refreshHighlight');
    },

    startup: function() {
        // connect up mouse over/out handlers
        this.connect(this.domNode, "onmouseenter", "mouseEnter");
        this.connect(this.domNode, "onmouseleave", "mouseLeave");

        // if our group is already in edit mode but we are not, enable edit mode.
        if (p4cms.ui.inEditMode[this.group] && !this.inEditMode){
            this.enableEditMode();
        }
    },

    // highlight editable elements when edit mode is enabled.
    enableEditMode: function() {
        // instance specific flag to gate edit mode
        this.inEditMode = true;

        // ensure element has a height.
        this.forceHeight();

        if (!this.isHighlighted){
            this.drawHighlight();
        }

        dojo.publish('p4cms.' + this.group + '.editModeEnabled', [this]);
    },

    // remove highlight and event connections when edit mode is disabled.
    disableEditMode: function() {
        // instance specific flag to gate edit mode
        this.inEditMode = false;

        // restore original height.
        this.restoreHeight();

        if (this.isHighlighted){
            this.clearHighlight();
        }
        if (this.mouseover){
            this.disconnect(this.mouseover);
        }
        if (this.mouseout){
            this.disconnect(this.mouseout);
        }

        dojo.publish('p4cms.' + this.group + '.editModeDisabled', [this]);
    },

    // refresh edit mode - called when refresh event is published.
    refreshEditMode: function() {
        if (!this.inEditMode){
            return;
        }
        this.forceHeight();
        this.refreshHighlight();
    },

    forceHeight: function() {
        // do nothing if tall enough already.
        if (this.heightForced || this.paddingBox().h >= this.minHeight) {
            return;
        }

        this.originalMinHeight = dojo.style(this.domNode, "minHeight");
        dojo.style(this.domNode, "minHeight", this.minHeight + "px");
        this.heightForced = true;

        dojo.publish("p4cms.ui.refreshEditMode");
    },

    restoreHeight: function() {
        this.heightForced = false;
        dojo.style(this.domNode, "minHeight", this.originalMinHeight || "");
    },

    // highlight the editable element.
    drawHighlight: function() {

        // border the element with individual divs
        // (top, right, left, bottom) so as not to obscure the element.
        var borders = {
            top:    dojo.create("div"),
            right:  dojo.create("div"),
            bottom: dojo.create("div"),
            left:   dojo.create("div")
        };
        this.borders = borders;

        // style and position the borders around the
        // padded box dimensions of the editable element
        // don't position borders off screen (constrain box).
        var side;
        var box     = this.paddingBox();
        box         = this._constrainBox(box);
        for (side in borders) {
            if (borders.hasOwnProperty(side)) {
                var border = borders[side];

                dojo.addClass(border, "p4cms-ui editable-element-border editable-element-border-" + side);
                dojo.style(border, "position", "absolute");
                dojo.place(border, this.bordersRootNode || dojo.body());

                // position the border on one of the sides of the element.
                // use the padded dimensions of the element.
                var pad = this.borderPadding;
                switch(side)
                {
                    case 'top':
                        dojo.style(border, {
                            left:   box.x - pad + "px",
                            top:    box.y - pad - dojo._getMarginSize(border).h/2 + "px",
                            width:  box.w + pad*2 + "px"
                        });
                        break;
                    case 'right':
                        dojo.style(border, {
                            left:   box.x + pad + box.w - dojo._getMarginSize(border).w/2 + "px",
                            top:    box.y - pad + "px",
                            height: box.h + pad*2  + "px"
                        });
                        break;
                    case 'bottom':
                        dojo.style(border, {
                            left:   box.x - pad + "px",
                            top:    box.y + pad + box.h - dojo._getMarginSize(border).h/2 + "px",
                            width:  box.w + pad*2 + "px"
                        });
                        break;
                    case 'left':
                        dojo.style(border, {
                            left:   box.x - pad - dojo._getMarginSize(border).w/2 + "px",
                            top:    box.y - pad + "px",
                            height: box.h + pad*2 + "px"
                        });
                        break;
                }
            }
        }

        this.isHighlighted = true;
    },

    // remove highlight.
    clearHighlight: function() {
        var side;
        for (side in this.borders) {
            if (this.borders.hasOwnProperty(side)) {
                dojo.destroy(this.borders[side]);
            }
        }
        this.isHighlighted = false;
    },

    // redraw the highlight if it exists.
    refreshHighlight: function() {
        if (this.isHighlighted){

            // preserve css classes.
            var side, cssClasses = [];
            for (side in this.borders) {
                if (this.borders.hasOwnProperty(side)) {
                    cssClasses[side] = dojo.attr(this.borders[side], 'class');
                }
            }

            this.clearHighlight();
            this.drawHighlight();

            // restore css classes.
            for (side in this.borders) {
                if (this.borders.hasOwnProperty(side)) {
                    dojo.attr(this.borders[side], 'class', cssClasses[side]);
                }
            }
        }
    },

    // get the position and dimensions of the element including padding.
    paddingBox: function() {
        var coords  = dojo.coords(this.domNode, true),
            content = dojo._getBorderBox(this.domNode);

        return {
            l: coords.l,
            t: coords.t,
            x: coords.x,
            y: coords.y,
            w: content.w,
            h: content.h
        };
    },

    // 'Interest' in a editable element is similar to focus.
    //
    // At certain times (such as hover) we infer that the user
    // is interested in the element and as such we may want to
    // take certain actions (such as decorate the element).
    // Interest can be increased or decreased as the user opens
    // an element's configuration options or closes them.
    //
    // Tracking interest explicitly allows us to add or remove
    // decoration (for example) at the appropriate times.
    // The actual value of the interestLevel is not useful
    // outside of this class - consider it a implementation
    // detail (it is like a reference counter).
    interest: function(){
        this.interestLevel = this.interestLevel >= 0
            ? ++this.interestLevel
            : 1;
        if (this.interestLevel === 1) {
            this.onInterest();
        }
    },

    disinterest: function(){
        window.setTimeout(dojo.hitch(this, function(){
            this.interestLevel = this.interestLevel >= 0
                ? --this.interestLevel
                : -1;
            if (this.interestLevel === 0) {
                this.interestLevel = -1;
                this.onDisinterest();
            }
        }), 50);
    },

    // hook to this event to take action when user expresses
    // interest in this editable element.
    onInterest: function(){
        // add hover class to borders.
        this.addHighlightClass('editable-element-border-hover');
    },

    // hook to this event to take action when user loses
    // interest in this editable element.
    onDisinterest: function(){
        // remove hover class to borders.
        this.removeHighlightClass('editable-element-border-hover');
    },

    mouseEnter: function() {
        // consider mouse-enter increased interest.
        if (p4cms.ui.inEditMode[this.group]) {
            this.interest();
        }
    },

    mouseLeave: function() {
        // consider mouse-leave decreased interest.
        if (p4cms.ui.inEditMode[this.group]) {
            this.disinterest();
        }
    },

    isFocused: function() {
        return this._isFocused;
    },

    focus: function() {
        this._isFocused = true;

        // add focus class to borders.
        if (p4cms.ui.inEditMode[this.group]) {
            this.addHighlightClass('editable-element-border-focus');
        }
    },

    blur: function() {
        this._isFocused = false;

        // remove focus class to borders.
        if (p4cms.ui.inEditMode[this.group]) {
            this.removeHighlightClass('editable-element-border-focus');
        }
    },

    addHighlightClass: function(className) {
        // add class to borders.
        var side;
        for (side in this.borders) {
            if (this.borders.hasOwnProperty(side)) {
                dojo.addClass(this.borders[side], className);
            }
        }
    },

    removeHighlightClass: function(className) {
        // remove class from borders.
        var side;
        for (side in this.borders) {
            if (this.borders.hasOwnProperty(side)) {
                dojo.removeClass(this.borders[side], className);
            }
        }
    },

    drawMask: function(noFade) {
        // connect to resize event.
        if (!this.maskResizeHandler) {
            this.maskResizeHandler = this.connect(
                window, "onresize", 'refreshMask'
            );
        }

        // surround the element with individual divs
        // (top, right, left, bottom) so as not to obscure the element.
        var maskBlocks = {
            top:    dojo.create("div"),
            right:  dojo.create("div"),
            bottom: dojo.create("div"),
            left:   dojo.create("div")
        };
        this.maskBlocks = maskBlocks;
        var side, anims = [];

        // style and position the mask blocks around the
        // padded box dimensions of the editable element.
        var box = this.paddingBox();
        for (side in maskBlocks) {
            if (maskBlocks.hasOwnProperty(side)) {
                var block = maskBlocks[side];

                dojo.addClass(block, "p4cms-ui editable-entry-mask editable-entry-mask-" + side);
                dojo.style(block, {
                    position:           "absolute",
                    backgroundColor:    '#000',
                    opacity:            0.6,
                    zIndex:             890 // the toolbar is 900
                });
                dojo.place(block, dojo.body());

                // position the block on one of the sides of the element.
                // use the padded dimensions of the element.
                var pad = this.maskPadding;
                switch(side)
                {
                    case 'top':
                        dojo.style(block, {
                            left:   (box.x - pad) + "px",
                            top:    0,
                            width:  (box.w + pad * 2) + "px",
                            height: (box.y - pad) + "px"
                        });
                        break;
                    case 'right':
                        var left = box.x + box.w;
                        dojo.style(block, {
                            left:   (left + pad) + "px",
                            top:    0,
                            height: dojo.body().scrollHeight + "px",
                            width:  (dojo.body().scrollWidth - left - pad) + "px"
                        });
                        break;
                    case 'bottom':
                        var top = box.y + box.h;
                        dojo.style(block, {
                            left:   (box.x - pad) + "px",
                            top:    (top + pad) + "px",
                            width:  (box.w + pad * 2) + "px",
                            height: (dojo.body().scrollHeight - top - pad) + "px"
                        });
                        break;
                    case 'left':
                        dojo.style(block, {
                            left:   0,
                            width:  (box.x - pad) + "px",
                            top:    0,
                            height: dojo.body().scrollHeight + "px"
                        });
                        break;
                }

                anims.push(
                    dojo.animateProperty({
                        node: block,
                        properties: {
                            'opacity': {end: 0.6, start: 0.0}
                        }
                    })
                );
            }
        }

        // skip animation (e.g. refresh case).
        if (!noFade) {
            dojo.fx.combine(anims).play();
        }

        this.isMasked = true;
    },

    clearMask: function(noFade) {
        var side, anims = [];

        for (side in this.maskBlocks) {
            if (this.maskBlocks.hasOwnProperty(side)) {
                if (noFade) {
                    dojo.destroy(this.maskBlocks[side]);
                } else {
                    var anim = dojo.animateProperty({
                        node: this.maskBlocks[side],
                        properties: {
                            'opacity': {start: 0.6, end: 0.0}
                        }
                    });
                    dojo.connect(anim, 'onEnd', dojo.destroy);
                    anims.push(anim);
                }
            }
        }

        // skip animation (e.g. refresh case).
        if (!noFade) {
            dojo.fx.combine(anims).play();
        }

        this.isMasked = false;
    },

    refreshMask: function() {
        if (this.isMasked){
            this.clearMask(true);
            this.drawMask(true);
        }
    },

    uninitialize: function() {
        this.clearMask(true);
        this.clearHighlight();
        dojo.unsubscribe(this.enableHandler);
        dojo.unsubscribe(this.disableHandler);
        dojo.unsubscribe(this.refreshHandler);
    },

    // constrain box to viewable/scrollable area.
    _constrainBox: function(box) {
        // limit x/y to client width/height (w. 5px margin)
        var maxX = dojo.body().clientWidth  - 5;
        var maxY = dojo.body().clientHeight - 5;

        // don't make box bigger than it was.
        maxX  = maxX < (box.x + box.w) ? maxX : (box.x + box.w);
        maxY  = maxY < (box.y + box.h) ? maxY : (box.y + box.h);

        box.x = box.x > maxX ? maxX : (box.x < 5 ? 5 : box.x);
        box.y = box.y > maxY ? maxY : (box.y < 5 ? 5 : box.y);
        box.w = (box.w + box.x) > maxX ? (maxX - box.x) : box.w;
        box.h = (box.h + box.y) > maxY ? (maxY - box.y) : box.h;

        return box;
    }
});
