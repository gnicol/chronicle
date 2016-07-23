// summary
//      extend DateTextBox to open the dropdown when the field is
//      focused
dojo.provide("p4cms.ui.DateTextBox");

dojo.require("dijit.form.DateTextBox");

dojo.declare("p4cms.ui.DateTextBox", [dijit.form.DateTextBox], {
    onFocus: function() {
        this.inherited(arguments);

        // only open the drop down on focus if
        // we aren't in the middle of a click
        if (!dijit._justMouseDowned) {
            this.openDropDown();
        }
    }    
});