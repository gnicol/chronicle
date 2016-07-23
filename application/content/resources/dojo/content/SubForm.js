// summary:
//      Support for content sub-forms.

dojo.provide("p4cms.content.SubForm");
dojo.require("dojox.layout.ContentPane");
dojo.require("dojo.NodeList-traverse");
dojo.require("dojo.NodeList-manipulate");
dojo.require("p4cms.ui.Dialog");
dojo.require("p4cms.ui._FormErrorsMixin");

dojo.declare("p4cms.content.SubForm", [dojox.layout.ContentPane, p4cms.ui._FormErrorsMixin],
{
    idPrefix:           '',
    dialog:             null,
    placeholder:        null,
    updateOnError:      false,
    contentEntry:       null,
    showInToolbar:      true,
    toolbarButtonClass: '',

    // when the dijit fires up, let's collect the content id
    // because if the sub-form moves in the dom, we might not
    // be able to find it later.
    postCreate: function(){
        this.inherited(arguments);

        // register this sub-form with the entry object
        // (unless it is nested under another sub-form)
        var entry = this.getContentEntry();
        if (entry && !this.isNestedSubForm()) {
            entry.registerSubForm(this);
        }
    },

    isNestedSubForm: function(){
        var list = new dojo.NodeList(this.domNode.parentNode).closest('.content-sub-form');
        return list.length;
    },

    // update the form and call onAfterUpdate() when finished
    update: function(){
        return this._update().addCallback(dojo.hitch(this, 'onAfterUpdate'));
    },

    // update (reload) the form and return dojo.Deferred object to allow adding actions when update completes
    _update: function() {
        var entry = this.getContentEntry();
        return dojo.xhrPost({
            url:    p4cms.url({
                module:         'content',
                action:         'sub-form',
                form:           this.formName,
                formIdPrefix:   this.idPrefix,
                id:             entry ? entry.contentId : '',
                version:        entry ? entry.contentVersion : ''
            }),
            content: this.getParentFormData(),
            load:   dojo.hitch(this, function(response) {
                this.closeDropDownButtons();
                this.destroyDescendants();
                this.set('content', response);
            }),
            error:  dojo.hitch(this, function() {
                var notice = new p4cms.ui.Notice({
                    message:    'Error occurred updating ' + this.getFormLegend() + ' sub-form.',
                    severity:   'error'
                });
            })
        });
    },

    // called when form update is finished. this is a good place to connect
    // callbacks that will be executed when the form is fully updated
    onAfterUpdate: function(){
    },

    // return content.Entry dijit from parent element in DOM
    getContentEntry: function(){
        if (!this.contentEntry) {
            var list = new dojo.NodeList();
            list.push(this.domNode);
            var entry = list.closest("[dojoType=p4cms.content.Entry]")[0];
            if (entry) {
                this.contentEntry = dijit.byNode(entry);
            }
        }

        return this.contentEntry;
    },

    getParentForm: function() {
        var list = new dojo.NodeList();
        list.push(this.domNode);
        return list.closest('form')[0];
    },

    // return parent form data in javascript object
    getParentFormData: function() {
        // if sub-form is in dialog, temporarily move it back to the parent
        // form so the sub-form data will be contained in the parent form
        if (this.isInDialog()) {
            this.returnToForm();
        }

        var data = dojo.formToObject(this.getParentForm());

        // restore dialog content
        if (this.isInDialog()) {
            this.moveToDialog();
        }

        return data;
    },

    getFormLegend: function() {
        return dojo.query('legend', this.domNode).first().text();
    },

    isInDialog: function() {
        return this.dialog !== null;
    },

    // show sub-form in a dialog
    moveToDialog: function(dialog) {
        this.dialog = dialog || this.dialog;

        // create sub-form placeholder and place it before the sub-form node
        this.placeholder = dojo.create('span', {id: this.id + '-placeholder'});
        dojo.place(this.placeholder, this.domNode, 'before');

        // prepare dialog contents.
        var form = dojo.create('form', {'class': 'p4cms-ui'});
        var content = dojo.create('dl', null, form);
        dojo.addClass(content, 'content-form p4cms-ui');
        dojo.place(this.domNode, content, 'only');

        // place this sub-form into the dialog
        dojo.place(form, this.dialog.containerNode, 'only');
    },

    // move sub-form back into the parent form
    returnToForm: function() {
        // if no placeholder, nowhere to return to.
        if (!this.placeholder) {
            return;
        }

        dojo.place(this.domNode, this.placeholder.id, 'replace');
        this.placeholder = null;
    },

    updateFormErrors: function(data){
        // replace the content of this form with data.form
        // when update-on-error is true - otherwise, let
        // parent handle form error updates.
        if (this.updateOnError && data.form) {
            this.destroyDescendants();
            this.set('content', data.form);
            this.onAfterUpdate();
        } else {
            this.inherited(arguments);
            this.onAfterUpdate();
        }
    },

    // close drop down buttons in the form
    closeDropDownButtons: function() {
        dojo.forEach(dojo.query(".dijitDropDownButton", this.domNode), function(node){
            dijit.byNode(node).closeDropDown();
        });
    },

    // called when entry is being saved
    onEntrySave: function() {
    },

    // extend parent to flatten sub-sub form errors
    _extractErrors: function(data) {
        var errors = this.inherited(arguments);

        if (!errors) {
            return errors;
        }

        // sub-forms, particularly those with sub-sub-forms, have their
        // elements arrayed out deeply. We need to flatten them back down
        // to the actual form input 'name' maintaining the associated errors.
        var formName = this.formName;
        errors.elements = p4cms.ui.flattenObject(
            errors.elements,
            function(keys, value, output) {
                var errorKey = keys.pop();
                var key      = formName + (keys.length ? '[' + keys.join('][') + ']' : '');
                output[key]  = output[key] || {};
                output[key][errorKey] = value;
            }
        );

        return errors;
    }
});
