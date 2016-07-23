dojo.provide("p4cms.ui.ScrollingTabController");
dojo.require("dijit.layout.ScrollingTabController");

// keep out of the global namespace
(function() {
    var oldResize = dijit.layout.ScrollingTabController.prototype.resize;
    dijit.layout.ScrollingTabController.prototype.resize = function(dim) {
        if(this.domNode.offsetWidth === 0){
            return;
        }

        // work around a dojo bug in IE7
        // see http://bugs.dojotoolkit.org/ticket/15475 for more details.
        this.containerNode.style.height = this.getChildren().length ? "auto" : "0px";
        return oldResize.apply(this, arguments);
    };
}());