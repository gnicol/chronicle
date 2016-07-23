dojo.provide('p4cms.mobile.widget');

dojo.require('p4cms.widget');
dojo.require('p4cms.mobile.widget.RegionProxy');
dojo.require("dojo.NodeList-traverse");

// override widget's page root to point to the current page
p4cms.widget.pageHasWidgets = function() {
    var scope = dojox.mobile.currentView && dojox.mobile.currentView.domNode;
    return !!dojo.query('[dojoType=p4cms.widget.Widget], [dojoType=p4cms.widget.Region]', scope).length;
};

// don't draw borders around regions.
p4cms.widget.Region.extend({
    drawHighlight: function(){}
});

p4cms.widget.Widget.extend({
   startup: function() {
       var page = new dojo.NodeList(this.domNode).closest('.mblView')[0];
       if (page) {
           this.bordersRootNode = page;
       }
       this.inherited(arguments);
   }
});