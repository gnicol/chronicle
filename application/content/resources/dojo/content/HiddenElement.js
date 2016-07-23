// summary:
//      Support for hidden content fields in content entries.

dojo.provide("p4cms.content.HiddenElement");
dojo.require("p4cms.content.Element");

dojo.declare("p4cms.content.HiddenElement", p4cms.content.Element,
{
    // explicitly set dojoType attribute on the domNode since
    // hidden elements are generated programatically and won't
    // otherwise have a dojoType, which entry needs to find them.
    startup: function(){
        dojo.attr(this.domNode, 'dojoType', 'p4cms.content.HiddenElement');
        this.inherited(arguments);
    },

    // stub out element methods that expect a visible dom node
    // for example, we can't draw higlights or force height on
    // elements that are not displayed.
    forceHeight: function(){},
    drawHighlight: function(){},
    insertPlaceholder: function(){},
    refreshHighlight: function() {}
});
