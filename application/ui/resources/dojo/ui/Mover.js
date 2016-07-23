dojo.provide("p4cms.ui.Mover");

dojo.require("dojo.dnd.Mover");

// Patch dojo's Mover to also allow fixed positioning
// @todo update when Dojo is updated
// last updated in Dojo 1.6.1
// @todo remove when http://bugs.dojotoolkit.org/ticket/8679 is fixed
dojo.extend(dojo.dnd.Mover, {
    onFirstMove: function(e) {
        var s = this.node.style, l, t, h = this.host;
        switch(s.position){
            case "relative":
            case "absolute":
            case "fixed":
                // assume that left and top values are in pixels already
                l = Math.round(parseFloat(s.left)) || 0;
                t = Math.round(parseFloat(s.top)) || 0;
                break;
            default:
                s.position = "absolute";        // enforcing the absolute mode
                var m = dojo.marginBox(this.node);
                // event.pageX/pageY (which we used to generate the initial
                // margin box) includes padding and margin set on the body.
                // However, setting the node's position to absolute and then
                // doing dojo.marginBox on it *doesn't* take that additional
                // space into account - so we need to subtract the combined
                // padding and margin.  We use getComputedStyle and
                // _getMarginBox/_getContentBox to avoid the extra lookup of
                // the computed style.
                var b = dojo.doc.body;
                var bs = dojo.getComputedStyle(b);
                var bm = dojo._getMarginBox(b, bs);
                var bc = dojo._getContentBox(b, bs);
                l = m.l - (bc.l - bm.l);
                t = m.t - (bc.t - bm.t);
                break;
        }
        this.marginBox.l = l - this.marginBox.l;
        this.marginBox.t = t - this.marginBox.t;
        if(h && h.onFirstMove){
            h.onFirstMove(this, e);
        }

        // Disconnect onmousemove and ontouchmove events that call this function
        dojo.disconnect(this.events.shift());
        dojo.disconnect(this.events.shift());
    }
});