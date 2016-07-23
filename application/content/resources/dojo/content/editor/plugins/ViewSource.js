dojo.provide("p4cms.content.editor.plugins.ViewSource");

// Register this plugin before the viewsource plugin is registered
// so that this one takes over the default
dojo.subscribe(dijit._scopeName + ".Editor.getPlugin", null, function(o) {
    var name = o.args.name.toLowerCase();
    if (name ===  "viewsource") {
        o.plugin = new p4cms.content.editor.plugins.ViewSource({
            readOnly:       o.args.hasOwnProperty("readOnly") ? o.args.readOnly : false,
            stripComments:  o.args.hasOwnProperty("stripComments") ? o.args.stripComments : true,
            stripScripts:   o.args.hasOwnProperty("stripScripts") ? o.args.stripScripts : true,
            stripIFrames:   o.args.hasOwnProperty("stripIFrames") ? o.args.stripIFrames : true
        });
    }
});

dojo.require("dijit._editor.plugins.ViewSource");
dojo.require("dijit.form.Textarea");

dojo.declare("p4cms.content.editor.plugins.ViewSource", dijit._editor.plugins.ViewSource, {
    _showSource: function(source) {
        this.inherited(arguments);

        // if we are returning to the normal view, we need to refresh the borders
        if (!source) {
            dojo.publish('p4cms.ui.refreshEditMode');
        }
    },

    _createSourceView: function() {
        this.inherited(arguments);
        // push our connects onto _connects so they get removed at destroy
        // the plugin interface provides this so assumably it has benefits.

        // if the editor doesn't have a height, it is likely auto-growing
        // so we should also autogrow the text area using the textarea widget
        if (!this.editor.height) {
            this.areaWidget = new dijit.form.Textarea({
                baseClass:  '',
                style:      dojo.attr(this.sourceArea, 'style') + "; resize: none;"
            }, this.sourceArea);
            this.sourceArea = this.areaWidget.domNode;

            // override onMouseDown so it doesn't try to focus while you are trying
            // to move around in the textarea. It caused IE to scroll.
            this.areaWidget._onMouseDown = function() {};

            // update the editor whenever input is received
            this._connects.push(dojo.connect(this.areaWidget, '_onInput', this, function() {
                if (this.inputTimer) {
                    clearTimeout(this.inputTimer);
                }
                // update borders at most every 100 milliseconds, this is a visual
                // change so we want it to happen decently fast
                this.inputTimer = setTimeout(dojo.hitch(this, function() {
                    delete this.inputTimer;
                    dojo.publish('p4cms.ui.refreshEditMode');
                }), 100);
            }));
        }

        // proxy our value to the editor on keyup to ensure changes don't get lost
        // we could proxy onChange/Blur for efficiency but this doesn't happen in an
        // acceptable order when using an editor 'inplace'
        this._connects.push(dojo.connect(this.sourceArea, 'onkeyup', this, function() {
            if (this.keyTimer) {
                clearTimeout(this.keyTimer);
            }
            // update editor value at most every 200 milliseconds, this is not
            // a visual change so it can be set slower
            this.keyTimer = setTimeout(dojo.hitch(this, function() {
                delete this.keyTimer;
                this.editor.set('value', this.sourceArea.value);
            }), 200);
        }));

        // On editor's onChange event, update the view source plugin's value. This
        // keeps a form-mode view source editor in sync when updates are made in-place.
        // The attr 'value' update we do on key up won't fire onChange so no endless
        // loops occur.
        this._connects.push(dojo.connect(this.editor, 'onChange', this, function() {
            this.sourceArea.value = this.editor.get('value');
        }));

        // Also keep the inplace editor in sync with updates made in form mode
        // note that we can't use editor.get('value') here because it will just
        // return our current sourceArea value
        // @todo remove when dojo is upgraded to 1.8 : http://bugs.dojotoolkit.org/ticket/14573
        this._connects.push(dojo.connect(this.editor, 'setValue', this, function(value) {
            // don't bother setting the value it hasn't changed
            // browsers like IE will lose the current cursor location while editing otherwise
            if (this.sourceArea.value !== value) {
                this.sourceArea.value = value;
            }
        }));

        // stop the click event from bubbling. Normally clicking in the editor does
        // this and we want the textarea to behave the same (particularly in-place)
        this._connects.push(dojo.connect(this.sourceArea, 'onclick', this, function(e) {
            dojo.stopEvent(e);
        }));
    },

    // overriden to divert to the dijit.form.TextArea's resize
    // if we have a dijit textarea
    _resize: function() {
        if (this.areaWidget) {
            this.areaWidget.resize();
        } else {
            this.inherited(arguments);
        }
    }
});
