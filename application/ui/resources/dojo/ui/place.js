dojo.provide("p4cms.ui.place");

// Override dijit._place to deal with fixed position nodes
// @todo update with dojo, last updated dojo 1.6.1
dijit._place = function(node, choices, layoutNode, aroundNodeCoords) {
    // get {x: 10, y: 10, w: 100, h:100} type obj representing position of
    // viewport over document
    var view = dojo.window.getBox();

    // if node position is fixed, reset view to pos 0
    if (dojo.style(node, 'position') === 'fixed') {
        view.t = 0;
        view.l = 0;
    }

    // This won't work if the node is inside a <div style="position: relative">,
    // so reattach it to dojo.doc.body.   (Otherwise, the positioning will be wrong
    // and also it might get cutoff)
    if (!node.parentNode || String(node.parentNode.tagName).toLowerCase() !== "body") {
        dojo.body().appendChild(node);
    }

    var best = null;
    dojo.some(choices, function(choice) {
        var corner      = choice.corner,
            pos         = choice.pos,
            overflow    = 0;

        // calculate amount of space available given specified position of node
        var spaceAvailable = {
            w: corner.charAt(1) === 'L' ? (view.l + view.w) - pos.x : pos.x - view.l,
            h: corner.charAt(1) === 'T' ? (view.t + view.h) - pos.y : pos.y - view.t
        };

        // configure node to be displayed in given position relative to button
        // (need to do this in order to get an accurate size for the node, because
        // a tooltip's size changes based on position, due to triangle)
        if (layoutNode) {
            var res = layoutNode(node, choice.aroundCorner, corner, spaceAvailable, aroundNodeCoords);
            overflow = typeof res === "undefined" ? 0 : res;
        }

        // get node's size
        var style           = node.style,
            oldDisplay      = style.display,
            oldVis          = style.visibility;
        style.visibility    = "hidden";
        style.display       = "";
        var mb              = dojo.marginBox(node);
        style.display       = oldDisplay;
        style.visibility    = oldVis;

        // coordinates and size of node with specified corner placed at pos,
        // and clipped by viewport
        var startX  = Math.max(view.l, corner.charAt(1) === 'L' ? pos.x : (pos.x - mb.w)),
            startY  = Math.max(view.t, corner.charAt(0) === 'T' ? pos.y : (pos.y - mb.h)),
            endX    = Math.min(view.l + view.w, corner.charAt(1) === 'L' ? (startX + mb.w) : pos.x),
            endY    = Math.min(view.t + view.h, corner.charAt(0) === 'T' ? (startY + mb.h) : pos.y),
            width   = endX - startX,
            height  = endY - startY;

        overflow += (mb.w - width) + (mb.h - height);

        if (!best|| overflow < best.overflow) {
            best = {
                corner:         corner,
                aroundCorner:   choice.aroundCorner,
                x:              startX,
                y:              startY,
                w:              width,
                h:              height,
                overflow:       overflow,
                spaceAvailable: spaceAvailable
            };
        }

        return !overflow;
    });

    // In case the best position is not the last one we checked, need to call
    // layoutNode() again.
    if (best.overflow && layoutNode) {
        layoutNode(node, best.aroundCorner, best.corner, best.spaceAvailable, aroundNodeCoords);
    }

    // And then position the node.   Do this last, after the layoutNode() above
    // has sized the node, due to browser quirks when the viewport is scrolled
    // (specifically that a Tooltip will shrink to fit as though the window was
    // scrolled to the left).
    //
    // In RTL mode, set style.right rather than style.left so in the common case,
    // window resizes move the popup along with the aroundNode.
    var l = dojo._isBodyLtr(),
        s = node.style;
    s.top = best.y + "px";

    s[l ? "left" : "right"] = (l ? best.x : view.w - best.x - best.w) + "px";

    return best;
};

// Override placeOnScreenAround node to use fixed position if node is fixed
// @todo update when dojo is udpated, last updated dojo 1.6.1
dijit.placeOnScreenAroundNode = function(node, aroundNode, aroundCorners, layoutNode) {
    // get coordinates of aroundNode
    aroundNode          = dojo.byId(aroundNode);
    var isFixed         = dojo.style(node, 'position') === 'fixed',
        aroundNodePos   = dojo.position(aroundNode, !isFixed);

    // place the node around the calculated rectangle
    return dijit._placeOnScreenAroundRect(node,
        aroundNodePos.x, aroundNodePos.y, aroundNodePos.w, aroundNodePos.h,     // rectangle
        aroundCorners, layoutNode);
};

// @todo update when dojo is udpated, last updated dojo 1.6.1
dijit.placementRegistry.unregister("node");
dijit.placementRegistry.register("node",
    function(n, x) {
        return typeof x === "object" &&
            typeof x.offsetWidth !== "undefined" && typeof x.offsetHeight !== "undefined";
    },
    dijit.placeOnScreenAroundNode
);