// summary
//      Extend the default AlwaysShowToolbar plugin to account for images that don't have
//      dimensions until they are loaded
//

dojo.provide("p4cms.content.editor.plugins.AlwaysShowToolbar");

dojo.require("dijit._editor.plugins.AlwaysShowToolbar");

dojo.declare("p4cms.content.editor.plugins.AlwaysShowToolbar", dijit._editor.plugins.AlwaysShowToolbar,
{
    // extend to ignore update height when iframe is hidden
    // parent will otherwise crash
    _updateHeight: function() {
        if (dojo.style(this.editor.iframe, 'display') === 'none') {
            return;
        }

        var lastHeight = this._lastHeight;
        this.inherited(arguments);

        // only perform additonal actions if height has changed
        if (lastHeight !== this._lastHeight) {
            // if any images are present in this element; update our height when they load
            dojo.query("img", this.editor.document.body).forEach(function(imgNode) {
                if (!imgNode.cmsUpdateLoad && !imgNode.loaded) {
                    imgNode.cmsUpdateLoad = dojo.connect(imgNode, 'onload', this, function(){
                        this._updateHeight();
                    });
                }
            }, this);

            setTimeout(function() {
                dojo.publish('p4cms.ui.refreshEditMode');
            }, 0);
        }
    }
});