// summary:
//      Extended dijit.form.Form with handling of ENTER keypresses for consistent
//      submit button activity across browsers.
//

dojo.provide("p4cms.ui.Form");
dojo.require("dijit.form.Form");

dojo.declare("p4cms.ui.Form", dijit.form.Form, {

    // set to true to turn on the enter key handling
    submitOnEnter:          false,

    // reference to the container where the submit button is placed
    // (null = this widget's domNode)
    submitButtonContainer:  null,

    startup: function() {
        this.inherited(arguments);
        
        // listen to key events on any input fields
        // that belong to this form
        dojo.query('input', this.domNode).forEach(function(item) {
            if (item.form === this.domNode) {
                this.connect(item, 'onkeypress', '_handleEnterKey'); 
            }
        }, this);
    },

    // is called when any key is pressed within one of the input fields;
    // detects if it is the Enter key, and fires a click on the preferred button
    _handleEnterKey: function(e) {
        var key = e.keyCode;
        if (this.submitOnEnter && key === dojo.keys.ENTER) {
            // find a submit button
            var domain          = this.submitButtonContainer || this.domNode,
                submitButton    = dojo.query('input[type="submit"]', domain)[0],
                preferredButton = dojo.query('.dijitButton.preferred input[type="submit"]', domain)[0],
                buttonDijit     = preferredButton ? dijit.getEnclosingWidget(preferredButton) : null;

            // try to find a preferred dijit button
            if (buttonDijit) {
                submitButton = buttonDijit.containerNode;
            }

            // if found, click the button
            if (submitButton  && !dojo.attr(submitButton, 'disabled')) {
                // stop the original event
                dojo.stopEvent(e);
                p4cms.ui.trigger(submitButton, 'click');
            }
        }
    }
});