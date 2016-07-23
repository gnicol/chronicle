// summary
//      base class for creating a new diff mode
// usage:
//      extend this class to create your own plugin to add additional diff modes
//      to diffElement.
//
//      the diffElement property is passed in at the time of creation
//      if your plugin is adding a new mode to diffElement, it should define a domNode
//      in buildRenderin, then during postCreate, you should call addMode on the diffElement
//      to add any modes that your plugin will provide.
//
//      after you have added modes, diffElement will call your plugin's activateMode
//      functions whenever those modes are activated, you can extend activateMode and deactivateMode
//      to perform additional actions during activation
//
//      getFocusContainer by default, will return the plugin's domNode, override this
//      function in order to specify a different focus element

dojo.provide("p4cms.diff.plugins.DiffPlugin");

dojo.declare("p4cms.diff.plugins.DiffPlugin", dijit._Widget,
{
    // required
    //  the parent diffElement
    diffElement: null,

    isActive:   false,
    isFocused:  false,

    getDiffElement: function() {
        return this.diffElement;
    },

    activateMode: function(modeId, changeModeObj) {
        this.isActive = true;
    },

    deactivateMode: function(modeId, changeModeObj) {
        this.isActive = false;
    },

    getFocusContainer: function(modeId) {
        if (this.domNode) {
            return this.domNode;
        }
        return null;
    },

    onLoseFocus: function(modeId) {
        this.isFocused = false;
    },

    onGainFocus: function(modeId) {
        this.isFocused = true;
    }
});