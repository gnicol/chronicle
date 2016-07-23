// summary:
//      Extended form button dijit to prevent from multi-click by disabling
//      it after first click.

dojo.provide("p4cms.ui.SingleClickButton");
dojo.require("dijit.form.Button");

dojo.declare("p4cms.ui.SingleClickButton", dijit.form.Button, {
    _onButtonClick: function(/*Event*/ e) {
        if (!this.disabled) {
            this.inherited(arguments);
            if (this.domNode) {
                this.disable();
            }
        }
    },

    enable: function() {
        if (this.disabled && this.domNode) {
            this.set('disabled', false);
        }
    },
    
    disable: function() {
        if (!this.disabled && this.domNode) {
            this.set('disabled', true);
        }
    },

    postCreate: function() {
        this.inherited(arguments);
        
        // if the button is in a form, prevent multiple form submissions.
        // Note: any disabled single click button prevents the form from being
        // submitted -- the single click button should *only* be disabled
        // after being clicked.
        // @todo    implement a better way to prevent multiple form submission
        //          -- buttons in a form should not affect it.
        if (this.valueNode && this.valueNode.form) {
            this.connect(
                this.valueNode.form, 
                'onsubmit', 
                dojo.hitch(this, function(e){
                    if (this.disabled) {
                        e.preventDefault();
                    } else if (this.domNode) {
                        // allow form data to be assembled/submitted before disabling the button
                        window.setTimeout(dojo.hitch(this, 'disable'), 0);
                    }
                })
            );
        }
    }
});