dojo.provide('p4cms.mobile.FlippableView');

dojo.require('p4cms.mobile.View');
dojo.require('dojox.mobile.FlippableView');

dojox.mobile.FlippableView.extend({
    threshold: 4,
    scrollBar: false,
    propagatable: true,

    init: function() {
        this.inherited(arguments);
        // Creation of keyframes takes a little time. If they are created
        // in a lazy manner, a slight delay is noticeable when you start
        // scrolling for the first time. This is to create keyframes up front.
        if(dojo.isWebKit){
            var i;
            for(i = 0; i < 3; i++){
                this.setKeyframes(null, null, i);
            }
        }
        if (dojox.mobile.hasTranslate3d) {
            dojo.style(this.containerNode, "webkitTransform", "translate3d(0,0,0)");
        }
        this._speed = {x:0, y:0};
    },

    onTouchStart: function(e) {
        var nextView = this._nextView(this.domNode);
        if(nextView){
            nextView.stopAnimation();
        }
        var prevView = this._previousView(this.domNode);
        if(prevView){
            prevView.stopAnimation();
        }

        if(this._conn && (new Date()).getTime() - this.startTime < 500){
            return; // ignore successive onTouchStart calls
        }
        if(!this._conn){
            this._conn = [];
            this._conn.push(dojo.connect(dojo.doc, dojox.mobile.hasTouch ? "touchmove" : "onmousemove", this, "onTouchMove"));
            this._conn.push(dojo.connect(dojo.doc, dojox.mobile.hasTouch ? "touchend" : "onmouseup", this, "onTouchEnd"));
        }

        this._aborted = false;
        if(dojo.hasClass(this.containerNode, "mblScrollableScrollTo2")){
            this.abort();
        }
        this.touchStartX = e.touches ? e.touches[0].pageX : e.clientX;
        this.touchStartY = e.touches ? e.touches[0].pageY : e.clientY;
        this.startTime = (new Date()).getTime();
        this.startPos = this.getPos();
        this._dim = this.getDim();
        this._time = [0];
        this._posX = [this.touchStartX];
        this._posY = [this.touchStartY];

        var t = e.target.tagName;
        if(e.target.nodeType !== 1 || (t !== "SELECT" && t !== "INPUT" && t !== "TEXTAREA" && t !== "EMBED")){
            if (this.propagatable) {
                e.preventDefault();
            } else {
                dojo.stopEvent(e);
            }
        }

        this._locked = false;
    },

    // include scrollable's code here, with upstream updates
    onTouchMove: function(e) {
        if (this._locked) { return; }
        var x = e.touches ? e.touches[0].pageX : e.clientX;
        var y = e.touches ? e.touches[0].pageY : e.clientY;
        var dx = x - this.touchStartX;
        var dy = y - this.touchStartY;
        var to = {x:this.startPos.x + dx, y:this.startPos.y + dy};
        var dim = this._dim;

        dx = Math.abs(dx);
        dy = Math.abs(dy);
        if(this._time.length === 1){ // the first TouchMove after TouchStart
            if((this._v && !this._h && dx >= this.threshold && dx >= dy) || (
               (this._h || this._f) && !this._v && dy >= this.threshold && dy >= dx)){
                this._locked = true;
                return;
            }
            if ((this._v && Math.abs(dy) < this.threshold) || ((this._h || this._f) && Math.abs(dx) < this.threshold)){
                return;
            }
            this.addCover();
            this.showScrollBar();
        }

        var weight = this.weight;
        if(this._v){
            if(to.y > 0){ // content is below the screen area
                to.y = Math.round(to.y * weight);
            }else if(to.y < -dim.o.h){ // content is above the screen area
                if(dim.c.h < dim.d.h){ // content is shorter than display
                    to.y = Math.round(to.y * weight);
                }else{
                    to.y = -dim.o.h - Math.round((-dim.o.h - to.y) * weight);
                }
            }
        }
        if(this._h || this._f){
            if(to.x > 0){
                to.x = Math.round(to.x * weight);
            }else if(to.x < -dim.o.w){
                if(dim.c.w < dim.d.w){
                    to.x = Math.round(to.x * weight);
                }else{
                    to.x = -dim.o.w - Math.round((-dim.o.w - to.x) * weight);
                }
            }
        }
        this.scrollTo(to);

        var max = 10;
        var n = this._time.length; // # of samples
        if(n >= 2){
            // Check the direction of the finger move.
            // If the direction has been changed, discard the old data.
            var d0, d1;
            if(this._v && !this._h){
                d0 = this._posY[n - 1] - this._posY[n - 2];
                d1 = y - this._posY[n - 1];
            }else if(!this._v && this._h){
                d0 = this._posX[n - 1] - this._posX[n - 2];
                d1 = x - this._posX[n - 1];
            }
            if(d0 * d1 < 0){ // direction changed
                // leave only the latest data
                this._time = [this._time[n - 1]];
                this._posX = [this._posX[n - 1]];
                this._posY = [this._posY[n - 1]];
                n = 1;
            }
        }
        if(n === max){
            this._time.shift();
            this._posX.shift();
            this._posY.shift();
        }
        this._time.push((new Date()).getTime() - this.startTime);
        this._posX.push(x);
        this._posY.push(y);
    },

    onTouchEnd: function() {
        if (this._locked) {return false;}
        this.inherited(arguments);
    },

    addCover: function() {
        if(!dojox.mobile.hasTouch && !this.noCover){
            if(!this._cover){
                this._cover = dojo.create("div", null, dojo.doc.body);
                dojo.style(this._cover, {
                    backgroundColor: "#ffff00",
                    opacity: 0,
                    position: "absolute",
                    top: "0px",
                    left: "0px",
                    width: "100%",
                    height: "100%",
                    zIndex: 2147483647 // max of signed 32-bit integer
                });
                    this._ch.push(dojo.connect(this._cover,
                        dojox.mobile.hasTouch ? "touchstart" : "onmousedown", this, "onTouchEnd"));
            }else{
                this._cover.style.display = "";
            }

            this.setSelectable(this._cover, false);
            this.setSelectable(this.domNode, false);
        }
    },

    removeCover: function() {
        if(!dojox.mobile.hasTouch && this._cover){
            this._cover.style.display = "none";
            this.setSelectable(this._cover, true);
            this.setSelectable(this.domNode, true);
        }
    },

    setSelectable: function(node, selectable) {
        // dojo.setSelectable has dependency on dojo.query. Re-define our own.
        node.style.KhtmlUserSelect = selectable ? "auto" : "none";
        node.style.MozUserSelect = selectable ? "" : "none";
        node.onselectstart = selectable ? null : function(){return false;};
    },

    // override resizeView to call onTouchEnd after resizing
    resizeView: function() {
        this._appFooterHeight = (this.fixedFooterHeight && !this.isLocalFooter) ?
            this.fixedFooterHeight : 0;
        this.containerNode.style.paddingTop = this.fixedHeaderHeight + "px";

        // we have been destroyed
        if (!this.domNode) {
            this.onTouchEnd();
            return;
        }

        this.resetScrollBar();
        this.onTouchEnd();
    },

    onFlickAnimationEnd: function(e){
        if(this._scrollBarNodeV){ this._scrollBarNodeV.className = ""; }
        if(this._scrollBarNodeH){ this._scrollBarNodeH.className = ""; }
        if(e && e.animationName && e.animationName.indexOf("scrollableViewScroll") === -1){ return; }
        this.inherited(arguments);
    },

    hideScrollBar: function(){
        var fadeRule;
        if(this.fadeScrollBar && dojo.isWebKit){
            if(!dojox.mobile._fadeRule){
                var node = dojo.create("style", null, dojo.doc.getElementsByTagName("head")[0]);
                node.textContent =
                    ".mblScrollableFadeScrollBar{"+
                    "  -webkit-animation-duration: 1s;"+
                    "  -webkit-animation-name: scrollableViewFadeScrollBar;}"+
                    "@-webkit-keyframes scrollableViewFadeScrollBar{"+
                    "  from { opacity: 0.6; }"+
                    "  to { opacity: 0; }}";
                dojox.mobile._fadeRule = node.sheet.cssRules[1];
            }
            fadeRule = dojox.mobile._fadeRule;
        }
        if(!this.scrollBar){ return; }
        var f = function(bar){
            dojo.style(bar, {
                opacity: 0,
                webkitAnimationDuration: ""
            });
            bar.className = "mblScrollableFadeScrollBar";
        };
        if(this._scrollBarV){
            f(this._scrollBarV);
            this._scrollBarV = null;
        }
        if(this._scrollBarH){
            f(this._scrollBarH);
            this._scrollBarH = null;
        }
    }
});