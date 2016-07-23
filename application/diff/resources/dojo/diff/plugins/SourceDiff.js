// summary
//      diff mode plugin for adding the toggle button for sourcemode view

dojo.provide("p4cms.diff.plugins.SourceDiff");

dojo.require("p4cms.diff.plugins.DiffPlugin");

dojo.declare("p4cms.diff.plugins.SourceDiff", p4cms.diff.plugins.DiffPlugin,
{
    // lifecycle
    // set our domNode
    buildRendering: function() {
        this.inherited(arguments);
        this.domNode = dojo.query('.source', this.getDiffElement().domNode)[0];
    },

    // lifecycle
    // create toggleButton and send it to parent diff for rendering
    postCreate: function() {
        this.inherited(arguments);

        if (dojo.isFunction(this.getDiffElement().addMode)) {
            this.getDiffElement().addMode(this, 'source', this.domNode, 'Source');
        }
    }
});