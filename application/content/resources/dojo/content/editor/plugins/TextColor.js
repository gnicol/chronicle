// summary:
//      This is an upstream copy of the dojo 1.7.2 TextColor plugin that we
//      have ported back to dojo 1.6.1 for its deferred loading of the
//      color palette dropdown
//      @todo Remove this entire file when dojo is upgraded >= than 1.7.2 and
//      switch the plugin in the editor back to the default textcolor

dojo.provide('p4cms.content.editor.plugins.TextColor');

dojo.require('dijit._editor._Plugin');
dojo.require('dijit.ColorPalette');
dojo.require('dijit.form.DropDownButton');

dojo.declare("p4cms.content.editor.plugins.TextColor", dijit._editor._Plugin, {
    buttonClass:        dijit.form.DropDownButton,
    useDefaultCommand:  false,

    _initButton: function() {
        this.inherited(arguments);

        // Setup to lazy load ColorPalette first time the button is clicked
        var self = this;
        this.button.loadDropDown = function(callback) {
            this.dropDown = new dijit.ColorPalette({
                value: self.value,
                onChange: function(color){
                    self.editor.execCommand(self.command, color);
                }
            });
            callback();
        };
    },

    updateState: function() {
        var _e = this.editor;
        var _c = this.command;
        if (!_e || !_e.isLoaded || !_c.length) {
            return;
        }


        var value = "";
        if (this.button) {
            var disabled = this.get("disabled");
            this.button.set("disabled", disabled);
            if (disabled) { return; }

            try {
                value = _e.queryCommandValue(_c)|| "";
            } catch(e) {
                //Firefox may throw error above if the editor is just loaded, ignore it
            }
        }

        if (value === "") {
            value = "#000000";
        }
        if (value === "transparent") {
            value = "#ffffff";
        }

        if (typeof value === "string") {
            //if RGB value, convert to hex value
            if (value.indexOf("rgb") > -1) {
                    value = dojo.colorFromRgb(value).toHex();
            }
        } else {  //it's an integer(IE returns an MS access #)
            /*jslint bitwise:true*/
            value =((value & 0x0000ff) << 16) | (value & 0x00ff00) | ((value & 0xff0000) >>> 16);
            /*jslint bitwise:false*/
            value = value.toString(16);
            value = "#000000".slice(0, 7 - value.length) + value;
        }

        this.value = value;

        var dropDown = this.button.dropDown;
        if (dropDown && value !== dropDown.get('value')) {
            dropDown.set('value', value, false);
        }
    }
});

// take over the default plugin
dojo.provide('dijit._editor.plugins.TextColor');
dijit._editor.plugins.TextColor = p4cms.content.editor.plugins.TextColor;