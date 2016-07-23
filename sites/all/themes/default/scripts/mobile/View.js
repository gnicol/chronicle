dojo.provide('p4cms.mobile.View');

dojo.require('dojox.mobile');

// Update view with upstream fixes
dojo.extend(dojox.mobile.View, {
    buildRendering: function(){
        this.domNode = this.containerNode = this.srcNodeRef || dojo.doc.createElement("DIV");
        this.domNode.className = "mblView";
        if(parseFloat(navigator.userAgent.split("Android ")[1]) >= 3){ // workaround for android screen flicker problem
            dojo.style(this.domNode, "webkitTransformStyle", "preserve-3d");
        }
        this.connect(this.domNode, "webkitAnimationEnd", "onAnimationEnd");
        this.connect(this.domNode, "webkitAnimationStart", "onAnimationStart");
        var id = location.href.match(/#(\w+)([^\w=]|$)/) ? RegExp.$1 : null;

        this._visible = (this.selected && !id) || this.id === id;

        if(this.selected){
            dojox.mobile._defaultView = this;
        }
    },

    _doTransition: function(fromNode, toNode, transition, dir){
        var rev = (dir === -1) ? " reverse" : "";
        toNode.style.display = "";
        if(!transition || transition === "none"){
            this.domNode.style.display = "none";
            this.invokeCallback();
        }else{
            // set transform origin
            var fromOrigin = "50% 50%";
            var toOrigin = "50% 50%";

            dojo.addClass(fromNode, transition + " out" + rev);
            dojo.addClass(toNode, transition + " in" + rev);

            dojo.style(fromNode, {webkitTransformOrigin:fromOrigin});
            dojo.style(toNode, {webkitTransformOrigin:toOrigin});
        }
        dojox.mobile.currentView = dijit.byNode(toNode);
    },

    onAnimationEnd: function(e) {
        if (e && e.animationName && e.animationName.indexOf("Out") === -1 && e.animationName.indexOf("In") === -1) {
            return;
        }
        var isOut = false;
        if(dojo.hasClass(this.domNode, "out")){
            isOut = true;
            // only hide this node if it wasn't transitioned in
            if (!dojo.hasClass(this.domNode, "in")) {
                this.domNode.style.display = "none";
            }
            dojo.forEach([this._transition,"in","out","reverse"], function(s){
                dojo.removeClass(this.domNode, s);
            }, this);
        }
        if(e.animationName.indexOf("shrink") === 0){
            var li = e.target;
            li.style.display = "none";
            dojo.removeClass(li, "mblCloseContent");
        }
        if(isOut){
            this.invokeCallback();
        }
        // this.domNode may be destroyed as a result of invoking the callback,
        // so check for that before accessing it.
        if (this.domNode) {
            this.domNode.className = "mblView";
        }
    }
});