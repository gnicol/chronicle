// summary:
//      Extend DialogUnderlay so it can be positioned fixed
//      @todo, remove when http://bugs.dojotoolkit.org/ticket/8679 is fixed

dojo.provide("p4cms.ui.DialogUnderlay");

dojo.require("dijit.DialogUnderlay");

dojo.extend(dijit.DialogUnderlay, {
    _setClassAttr: function(clazz) {
        dojo.style(this.domNode, 'position', 'fixed');
        this.node.className = "dijitDialogUnderlay " + clazz;
        this._set("class", clazz);
    },

    layout: function() {
        var is = this.node.style,
            os = this.domNode.style;

        // hide the background temporarily, so that the background itself isn't
        // causing scrollbars to appear (might happen when user shrinks browser
        // window and then we are called to resize)
        os.display = "none";

        // then resize and show
        var viewport    = dojo.window.getBox();
        os.top          = "0px";
        os.left         = "0px";
        is.width        = viewport.w + "px";
        is.height       = viewport.h + "px";
        os.display      = "block";
    }
});