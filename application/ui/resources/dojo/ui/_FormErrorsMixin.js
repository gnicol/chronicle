// summary:
//      Provides error message handling features for mixing in with forms.

dojo.provide("p4cms.ui._FormErrorsMixin");
dojo.require("p4cms.ui.Notice");

dojo.declare("p4cms.ui._FormErrorsMixin", null, {
    notifyFormErrors:   true,
    formName:           '',
    title:              '',
    unknownSaveError:   "An unkown error occurred saving the form.",

    showErrorNotices: function(data){
        // early exit if an exception occurred.
        if (data.type === 'exception') {
            this._notify(this.unknownSaveError, "error");
            return;
        }

        // early exit if no errors or form error notices disabled.
        var errors = this._extractErrors(data);
        if (!this.notifyFormErrors || !errors) {
            return;
        }

        // first collect all form level errors into our messages array
        var messages = [];
        var error;
        for (error in errors.form) {
            if (errors.form.hasOwnProperty(error)) {
                messages.push(errors.form[error]);
            }
        }

        // add any element level errors to messages
        var element;
        for (element in errors.elements) {
            if (errors.elements.hasOwnProperty(element)
                && dojo.isObject(errors.elements[element])
            ) {
                var elementErrors = errors.elements[element];
                for (error in elementErrors) {
                    if (elementErrors.hasOwnProperty(error)) {
                        messages.push(this.getElementLabel(element) + ": " + elementErrors[error]);
                    }
                }
            }
        }

        // if we have any error messages show them
        if (messages.length) {
            var message = this.getFormLabel() + ':<br><ul><li>'
                        + messages.join('</li><li>') + '</li></ul>';

            this._notify(message, "error");
        }
    },

    // remove any existing errors
    // @todo add support for form level errors
    clearErrors: function(){
        dojo.query('ul.errors', this.domNode).forEach(
            function(node){dojo.destroy(node);}
        );
    },

    // update the form errors by attaching error messages to form elements
    // @todo add support for form level errors
    updateFormErrors: function(data){
        // clear existing errors
        this.clearErrors();

        // early exit if no errors
        var errors = this._extractErrors(data);
        if (!errors) {
            return;
        }

        var element;
        for (element in errors.elements) {
            if (errors.elements.hasOwnProperty(element)) {
                var elementErrors = errors.elements[element];

                // create new error list
                var error, ul = dojo.create('ul', {'class': 'errors'});
                for (error in elementErrors) {
                    if (elementErrors.hasOwnProperty(error)) {
                        dojo.create('li', {innerHTML: elementErrors[error]}, ul);
                    }
                }

                // place errors before description (if element has one)
                // otherwise place errors inside element's node
                var elementNode  = this.getElement(element);
                var descriptions = dojo.query('p.description', elementNode);
                if (descriptions.length) {
                    dojo.place(ul, descriptions.pop(), 'before');
                } else {
                    dojo.place(ul, elementNode);
                }
            }
        }
    },

    getElement: function(element){
        var node = dojo.query('[name="' + element + '"]', this.domNode).closest('dd');

        return node.length ? node[0] : null;
    },

    getElementLabel: function(element){
        var node = this.getElement(element);

        if (!node) {
            return element;
        }

        node = new dojo.NodeList(node)
                       .prev('dt')
                       .query('label')[0];

        return node ? node.innerHTML : element;
    },

    getFormLabel: function(){
        var name = this.title || this.formName || 'Form';
        return name.charAt(0).toUpperCase() + name.slice(1);
    },

    _notify: function(message, severity) {
        if (!message) {
            return;
        }
        var notice = new p4cms.ui.Notice({
            name:       'form-' + this.domNode.id,
            message:    message,
            severity:   severity || null
        });
    },

    // if the passed data contains errors return them, otherwise null
    // there are two conventions for returning errors from the server
    // 1) only element level errors are returned and they are directly
    //    stored on data.errors
    // 2) both form and element level errors are returned and they are
    //    broken out as data.errors.form and data.errors.elements
    // this method will normalize all errors to the second format
    _extractErrors: function(data) {
        if (data.isValid || !data.errors) {
            return null;
        }

        // if we are already broken out into form/element
        // errors simply return the errors hash
        if (dojo.isObject(data.errors.form)
            && dojo.isObject(data.errors.elements)
        ) {
            return data.errors;
        }

        // looks like we only had element errors returned
        // file them under the elements key in our return
        return {
            form:     {},
            elements: data.errors
        };
    }
});