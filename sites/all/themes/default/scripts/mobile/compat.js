dojo.provide('p4cms.mobile.compat');

if (!dojo.isWebKit) {

    dojo.require('dojox.mobile.compat');
    dojo.require('dojo.fx.easing');

    dojo.extend(dojox.mobile.View, {
        _doTransition: function(fromNode, toNode, transition, dir){
            var anim;
            this.wakeUp(toNode);
            var s1, s2, originalPosition;

            if (!transition || transition === "none") {
                toNode.style.display    = "";
                fromNode.style.display  = "none";
                toNode.style.left       = "0px";
                this.invokeCallback();
            } else if (transition === "slide" || transition === "cover" || transition === "reveal") {
                var w   = fromNode.offsetWidth;
                s1      = dojo.fx.slideTo({
                    node:       fromNode,
                    duration:   400,
                    left:       -w*dir,
                    top:        dojo.style(fromNode, "top")
                });
                s2      = dojo.fx.slideTo({
                    node:       toNode,
                    duration:   400,
                    left:       0,
                    top:        dojo.style(toNode, "top")
                });

                originalPosition        = toNode.style.position;
                toNode.style.position   = "absolute";
                toNode.style.left       = w*dir + "px";
                toNode.style.display    = "";
                anim                    = dojo.fx.combine([s1,s2]);

                dojo.connect(anim, "onEnd", this, function() {
                    fromNode.style.display  = "none";
                    fromNode.style.left     = "0px";
                    toNode.style.position   = originalPosition;
                    this.invokeCallback();
                });

                anim.play();
            } else if (transition === "slidev" || transition === "coverv" || transition === "reavealv") {
                var h   = fromNode.offsetHeight;
                s1      = dojo.fx.slideTo({
                    node:       fromNode,
                    duration:   400,
                    left:       0,
                    top:        -h*dir
                });
                s2      = dojo.fx.slideTo({
                    node:       toNode,
                    duration:   400,
                    left:       0,
                    top:        0
                });

                originalPosition        = toNode.style.position;
                toNode.style.position   = "absolute";
                toNode.style.top        = h*dir + "px";
                toNode.style.left       = "0px";
                toNode.style.display    = "";
                anim                    = dojo.fx.combine([s1,s2]);

                dojo.connect(anim, "onEnd", this, function() {
                    fromNode.style.display  = "none";
                    toNode.style.position   = originalPosition;
                    this.invokeCallback();
                });

                anim.play();
            } else if(transition === "flip") {
                anim = dojo.fx.flip({
                    node:       fromNode,
                    dir:        "right",
                    depth:      0.5,
                    duration:   400
                });

                originalPosition        = toNode.style.position;
                toNode.style.position   = "absolute";
                toNode.style.left       = "0px";

                dojo.connect(anim, "onEnd", this, function() {
                    fromNode.style.display  = "none";
                    toNode.style.position   = originalPosition;
                    toNode.style.display    = "";
                    this.invokeCallback();
                });

                anim.play();
            } else {
                // other transitions - "fade", "dissolve", "swirl"
                anim = dojo.fx.chain([
                    dojo.fadeOut({
                        node:       fromNode,
                        duration:   600
                    }),
                    dojo.fadeIn({
                        node:       toNode,
                        duration:   600
                    })
                ]);

                originalPosition        = toNode.style.position;
                toNode.style.position   = "absolute";
                toNode.style.left       = "0px";
                toNode.style.display    = "";
                dojo.style(toNode, "opacity", 0);

                dojo.connect(anim, "onEnd", this, function() {
                    fromNode.style.display  = "none";
                    toNode.style.position   = originalPosition;
                    dojo.style(fromNode, "opacity", 1);
                    this.invokeCallback();
                });

                anim.play();
            }

            dojox.mobile.currentView = dijit.byNode(toNode);
        },

        wakeUp: function(node) {
            if (dojo.isIE && !node._wokeup) {
                node._wokeup        = true;
                var disp            = node.style.display;
                node.style.display  = "";
                var nodes           = node.getElementsByTagName("*");

                var i, len;
                for (i = 0, len = nodes.length; i < len; i++) {
                    var val = nodes[i].style.display;
                    nodes[i].style.display = "none";
                    nodes[i].style.display = "";
                    nodes[i].style.display = val;
                }

                node.style.display = disp;
            }
        }
    });

    dojox.mobile.getCssPaths = function(){
        var paths = [];
        var i, j, len;

        // find @import
        var s = dojo.doc.styleSheets;
        for(i = 0; i < s.length; i++){
            if (s[i].href) { continue; }
            var r = s[i].cssRules || s[i].imports;
            if(!r){ continue; }
            for(j = 0; j < r.length; j++){
                if(r[j].href){
                    paths.push(r[j].href);
                }
            }
        }

        // find <link>
        var elems = dojo.doc.getElementsByTagName("link");
        for(i = 0, len = elems.length; i < len; i++){
            if(elems[i].href){
                paths.push(elems[i].href);
            }
        }
        return paths;
    };
}