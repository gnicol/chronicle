dojo.provide("p4cms.ui.parser");
dojo.require("dojo.parser");

// keep out of the global namespace
(function() {
    var oldParseInst = dojo.parser.instantiate;
    dojo.parser.instantiate = function(nodes, mixin, args) {
        nodes = dojo.filter(nodes || [], function(obj) {
            // if obj doesn't have a fastpath type, keep it and let dojo handle it
            // otherwise, filter out nodes that have not been loaded
            return obj && (!obj.type || dojo.getObject(obj.type));
        }, this);
        return oldParseInst.apply(this, arguments);
    };
}());