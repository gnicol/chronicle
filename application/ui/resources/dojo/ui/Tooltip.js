// summary:
//      A tooltip with our custom CSS class
//      and support for loading content via href

dojo.provide("p4cms.ui.Tooltip");
dojo.require("dijit.Tooltip");
dojo.require("dojox.layout.ContentPane");

dojo.declare("p4cms.ui.Tooltip", [dijit.Tooltip], {
    aroundNode:     null,
    contentPane:    null,
    href:           '',

    // ensure that when we startup, the master tooltip is created in the required class.
    constructor: function() {
        this.inherited(arguments);

        // if we already have a master tooltip, but it's the wrong kind, kill it.
        if (dijit._masterTT && dijit._masterTT.declaredClass !== 'p4cms.ui._MasterTooltip') {
            dijit._masterTT.destroy();
            dijit._masterTT = null;
        }

        if (!dijit._masterTT) {
            dijit._masterTT = new p4cms.ui._MasterTooltip();
        }

        if (!this.aroundNode) {
            this.aroundNode = this.attachNode;
        }
    },

    // extended to:
    //  - utilize aroundNode
    //  - add support for css classes on the content
    //  - support pulling content from an href (via a content pane).
    open: function(target) {
        target = this.aroundNode || target;

        // if tooltip content is via href - use a content pane to load it
        // and defer opening the tooltip until the content pane has loaded.
        if (this.href && !this.contentPane) {
            this._loadHref(target);
        } else {
            if (!this.label) {
                return;
            }

            // wrap tooltip contents in a div with tooltip classes
            // preserve original contents and restore afterwards
            var originalLabel = this.label;
            this.label = "<div class=\"" + this.get('class') + "\">" + this.label + "</div>";
            this.inherited(arguments);
            this.label = originalLabel;
        }
    },

    // load tooltip contents from an href into a content pane
    // and open tooltip -- not intended to be called externally
    _loadHref: function(target) {
        this.contentPane = new dojox.layout.ContentPane({preload: true});
        this.contentPane.set('href', this.href);

        // open the tooltip when the content pane loads
        this.connect(this.contentPane, 'onLoad', function(){
           this.label = this.contentPane.get('content');
           this.open(target);
        });
    }
});

dojo.declare("p4cms.ui._MasterTooltip", [dijit._MasterTooltip], {
    extraClasses:   'p4cms-ui',

    // COPY/PASTE of parent's orient so that we can inject 'extraClasses'.
    // @todo Update this method when we upgrade dojo (last copied from 1.6.1)
    orient: function(/*DomNode*/ node, /*String*/ aroundCorner, /*String*/ tooltipCorner, /*Object*/ spaceAvailable, /*Object*/ aroundNodeCoords){
        // summary:
        //		Private function to set CSS for tooltip node based on which position it's in.
        //		This is called by the dijit popup code.   It will also reduce the tooltip's
        //		width to whatever width is available
        // tags:
        //		protected
        this.connectorNode.style.top = ""; //reset to default

        //Adjust the spaceAvailable width, without changing the spaceAvailable object
        var tooltipSpaceAvaliableWidth = spaceAvailable.w - this.connectorNode.offsetWidth;

        node.className = "dijitTooltip " + this.extraClasses + " " +
            {
                "BL-TL": "dijitTooltipBelow dijitTooltipABLeft",
                "TL-BL": "dijitTooltipAbove dijitTooltipABLeft",
                "BR-TR": "dijitTooltipBelow dijitTooltipABRight",
                "TR-BR": "dijitTooltipAbove dijitTooltipABRight",
                "BR-BL": "dijitTooltipRight",
                "BL-BR": "dijitTooltipLeft"
            }[aroundCorner + "-" + tooltipCorner];

        // reduce tooltip's width to the amount of width available, so that it doesn't overflow screen
        this.domNode.style.width = "auto";
        var size = dojo.contentBox(this.domNode);

        var width = Math.min((Math.max(tooltipSpaceAvaliableWidth,1)), size.w);
        var widthWasReduced = width < size.w;

        this.domNode.style.width = width+"px";

        //Adjust width for tooltips that have a really long word or a nowrap setting
        if(widthWasReduced){
            this.containerNode.style.overflow = "auto"; //temp change to overflow to detect if our tooltip needs to be wider to support the content
            var scrollWidth = this.containerNode.scrollWidth;
            this.containerNode.style.overflow = "visible"; //change it back
            if(scrollWidth > width){
                scrollWidth = scrollWidth + dojo.style(this.domNode,"paddingLeft") + dojo.style(this.domNode,"paddingRight");
                this.domNode.style.width = scrollWidth + "px";
            }
        }

        // Reposition the tooltip connector.
        if(tooltipCorner.charAt(0) === 'B' && aroundCorner.charAt(0) === 'B'){
            var mb = dojo.marginBox(node);
            var tooltipConnectorHeight = this.connectorNode.offsetHeight;
            if(mb.h > spaceAvailable.h){
                // The tooltip starts at the top of the page and will extend past the aroundNode
                var aroundNodePlacement = spaceAvailable.h - (aroundNodeCoords.h / 2) - (tooltipConnectorHeight / 2);
                this.connectorNode.style.top = aroundNodePlacement + "px";
                this.connectorNode.style.bottom = "";
            }else{
                // Align center of connector with center of aroundNode, except don't let bottom
                // of connector extend below bottom of tooltip content, or top of connector
                // extend past top of tooltip content
                this.connectorNode.style.bottom = Math.min(
                    Math.max(aroundNodeCoords.h/2 - tooltipConnectorHeight/2, 0),
                    mb.h - tooltipConnectorHeight) + "px";
                this.connectorNode.style.top = "";
            }
        }else{
            // reset the tooltip back to the defaults
            this.connectorNode.style.top = "";
            this.connectorNode.style.bottom = "";
        }

        return Math.max(0, size.w - tooltipSpaceAvaliableWidth);
    },

    // extends show to defer presentation until all images are loaded.
    show: function(innerHTML,  aroundNode, position, rtl) {
        // return if the aroundNode is no longer part of the page
        var nodeList = new dojo.NodeList(aroundNode);
        if (!nodeList.closest('body').length) {
            return;
        }

        this.inherited(arguments);

        // if content contains images, wait until images are loaded.
        var images = dojo.query('img', this.domNode);
        if (images.length) {

            // hide tooltip while images are loading.
            dojo.style(this.domNode, 'visibility', 'hidden');

            // open tooltip when all of the images are loaded.
            var loaded = 0;
            images.connect('onload', dojo.hitch(this, function(){
                if (++loaded >= images.length && this.isShowingNow) {
                    var pos     = (position && position.length)
                                ? position : dijit.Tooltip.defaultPosition,
                        align   = dijit.getPopupAroundAlignment(pos, !rtl);

                    //make sure the target still exists
                    var nodeList = new dojo.NodeList(aroundNode);
                    if (!nodeList.closest('body').length) {
                        return;
                    }

                    dijit.placeOnScreenAroundElement(this.domNode, aroundNode,
                        align, dojo.hitch(this, "orient"));

                    // fade it in.
                    dojo.style(this.domNode, {
                        visibility: 'visible',
                        opacity:    0
                    });
                    this.fadeIn.play();
                }
            }));
        }
    }
});