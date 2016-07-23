// summary:
//      Extended p4cms.content.SubForm by adding extra functionality specific to save form.

dojo.provide("p4cms.content.SaveSubForm");
dojo.require("p4cms.content.SubForm");

dojo.declare("p4cms.content.SaveSubForm", p4cms.content.SubForm,
{
    updateOnError:      true,
    toolbarButtonClass: 'button-small content-right-button content-save-button',

    // on create call onAfterUpdate to take over the save button's
    // 'onClick' when we are initially created
    create: function(){
        this.inherited(arguments);

        this.onAfterUpdate();
    },

    // take over the save button onClick method to cause the form
    // to 'out of band' submit instead of a traditional page refresh.
    // we have to re-do this everytime this subform is reloaded
    // (happens if a form error is present when save is attempted)
    // as well as on initial creation (taken care of by create method).
    onAfterUpdate: function(){
        this.inherited(arguments);

        // locate the save button dijit
        var saveButton = dojo.query('.content-save-button', this.domNode)[0];
        saveButton     = saveButton ? dijit.byNode(saveButton) : null;

        // if, for some reason, we don't have a save button, or this subform 
        // is not in a content entry (this happens when using the wysiwyg 
        // image plugin), nothing to do.
        if (!saveButton || !this.getContentEntry()) {
            return;
        }

        // set the onClick method. we don't connect as we need to have our
        // return value of false come through to caller.
        saveButton.onClick = dojo.hitch(this, function() {
            this.getContentEntry().save();
            return false;
        });
    },

    updateFormErrors: function (data){
        // set focus to the save drop-down button to prevent from closing
        // the drop-down when content is updated
        var saveButton   = dojo.query('.content-toolbar .content-save-button');
        var saveDropDown = null;
        if (saveButton.length) {
            saveDropDown = dijit.byNode(saveButton[0]);
            saveDropDown.focus();
        }

        this.inherited(arguments);

        // close save drop-down if there are no errors
        var errors = dojo.query('.errors', this.domNode);
        if (!errors.length && saveDropDown) {
            saveDropDown.closeDropDown();
        }
    },

    // when entry is being saved, copy data from this sub form into
    // the content form, so they are included in the parent form when
    // its posted
    onEntrySave: function() {
        if (!this.placeholder) {
            return;
        }

        // get form data object
        var form = this.getParentForm();
        var data = dojo.formToObject(form);

        // insert data into the content form (at the position of the placeholder)
        // as hidden input elements
        dojo.empty(this.placeholder);
        var elementName, value, i;
        for (elementName in data) {
            if (data.hasOwnProperty(elementName)) {
                value = data[elementName];
                if (dojo.isArray(value)) {
                    for (i = 0; i < value.length; i++) {
                        this._copyElementValue(elementName, value[i]);
                    }
                } else {
                    this._copyElementValue(elementName, value);
                }
            }
        }
    },

    // place hidden input with specified name and value into the placeholder
    _copyElementValue: function(name, value) {
        dojo.create(
            'input',
            {
                'type':     'hidden',
                'name':     name,
                'value':    value
            },
            this.placeholder.id
        );
    }
});
