// summary:
//      Support for editable fields in content entries.

dojo.provide("p4cms.content.Element");
dojo.require("p4cms.ui.EditableElement");
dojo.require("p4cms.ui.TooltipDialog");
dojo.require("p4cms.ui.Notice");
dojo.require("dojo.NodeList-traverse");

dojo.declare("p4cms.content.Element", p4cms.ui.EditableElement,
{
    group:          "content.element",

    contentId:      "",
    elementName:    "",
    required:       false,
    elementLabel:   "",
    editDialog:     null,
    formHandlers:   null,
    clickHandler:   null,
    originalValue:  null,
    validatedValue: null,
    hasValidated:   false,
    entry:          null,
    allowInline:    true,
    editAroundNode: null,

    getEntry: function() {
        if (this.entry) {
            return this.entry;
        }

        // attempt to find entry by proximity in the dom first.
        // else fallback to looking via the content id.
        var entry = new dojo.NodeList(this.domNode).closest('[dojoType=p4cms.content.Entry]')[0];
        if (entry) {
            this.entry = dijit.byNode(entry);
        } else {
            var id = "content-entry-" + this.contentId;
            this.entry = dijit.byId(id);
        }

        return this.entry;
    },

    getEditDialog: function() {
        if (!this.editDialog) {
            this.editDialog = new p4cms.ui.TooltipDialog();
            dojo.addClass(this.editDialog.domNode, 'content-element-tooltip');
            dojo.connect(this, "onBlur", this, 'stopEdit');

            // override tooltip onKey function to detect tab and advance
            // to the next element as appropriate
            var originalOnKey = this.editDialog._onKey;
            this.editDialog._onKey = dojo.hitch(this, function() {
                return this.handleTabKey.apply(this, arguments)
                    || originalOnKey.apply(this.editDialog, arguments);
            });
        }

        return this.editDialog;
    },

    openEditDialog: function() {
        var dialog = this.getEditDialog();

        p4cms.ui.popup.open({
            popup:              dialog,
            around:             this.editAroundNode || this.domNode,
            parent:             this,
            constrainToTarget:  true,
            orient:             {'TL':'BL', 'TR':'BR', 'BL':'TL'},
            onCancel:           dojo.hitch(this, 'stopEdit')
        });

        // reduce zIndex on popup so site-toolbar will be above us
        dojo.style(dialog.domNode.parentNode, 'zIndex', '899');

        var inputs = dojo.query('input, textarea', dialog.containerNode);
        if (inputs[0]) {
            inputs[0].select();
        }
    },

    repositionEditDialog: function() {
        if (this.editDialog && this.getEditDialog().isOpen()) {
            this.openEditDialog();
        }
    },

    startEdit: function() {
        if (this.editStarted) {
            return;
        }
        // consider element focused.
        this.focus();
        this.editStarted = true;

        // put a placeholder above form partial.
        var placeholder = dojo.create('span', {id: this.elementName + '-placeholder'});
        dojo.place(placeholder, this.getFormPartial(), 'before');

        // move form partial into a temp form in our tooltip dialog.
        // neuter on-submit so enter key won't submit temp form.
        var tempForm = dojo.create('form');
        tempForm.onsubmit = function() {return false;};
        dojo.place(this.getFormPartial(), tempForm, 'only');
        dojo.place(tempForm, this.getEditDialog().containerNode, 'only');

        // open the edit dialog.
        this.openEditDialog();
    },

    stopEdit: function() {
        if (!this.isFocused()) {
            return;
        }

        // consider element blurred (clears focus from highlight).
        this.blur();
        this.editStarted = false;

        // close the edit dialog
        dijit.popup.close(this.getEditDialog());

        // move form partial back to form.
        dojo.place(this.getFormPartial(), dojo.byId(this.elementName + '-placeholder'), 'replace');

        // ensure we validate the field even if the form
        // input hasn't changed or blurred.
        this.validateField();
    },

    getFormPartial: function() {
        return dojo.byId('content-form-' + this.elementName);
    },

    // get the parent form of the moment (this is a temp form
    // when the form partial is in a tooltip dialog).
    getParentForm: function() {
        return dojo.query('#' + this.getFormPartial().id).closest('form')[0];
    },

    getFormValue: function() {
        var values = dojo.formToObject(this.getParentForm());

        // accomodate element names with array notation.
        if (values[this.elementName] === undefined) {
            return values[this.elementName + '[]'];
        }

        return values[this.elementName];
    },

    getDisplayValue: function() {
        return this.domNode.innerHTML;
    },

    validateField: function() {
        // if value has not changed since last validate, nothing to do.
        // json encode to compare array-like objects by value not reference.
        var value = this.getFormValue();
        if (this.hasValidated
            && dojo.toJson(this.validatedValue) === dojo.toJson(value)
        ) {
            return;
        }

        // record that we've validated the current value of this field
        // do this prior to xhr to guard against overlapping calls.
        this.hasValidated   = true;
        this.validatedValue = value;

        // prepare xhr post data
        // (use array notation for value where appropriate)
        var data            = {},
            valueKey        = dojo.isArrayLike(value) ? 'value[]' : 'value';
        data[valueKey]      = value;
        data.contentType    = this.getEntry().contentType;
        data.contentId      = this.getEntry().contentId;
        data.field          = this.elementName;

        dojo.xhrPost({
            url:            p4cms.url({
                module: 'content',
                action: 'validate-field'
            }),
            content:        data,
            validatedValue: value,
            handleAs:       'json',
            load:           dojo.hitch(this, 'validateFieldLoadHandler'),
            error:          dojo.hitch(this, 'validateFieldErrorHandler')
        });
    },

    validateFieldLoadHandler: function(data, ioArgs) {
        // if the form value has changed since we initiated
        // the validation, ignore the response - it's no longer valid.
        // json encode to compare array-like objects by value not reference.
        var value = ioArgs.args.validatedValue;
        if (dojo.toJson(value) !== dojo.toJson(this.getFormValue())) {
            // if it doesn't look like any other validation has
            // occurred, clear the hasValidated flag since we're
            // ignoring the response (for this value).
            if (dojo.toJson(value) === dojo.toJson(this.validatedValue)) {
                this.hasValidated = false;
            }
            return;
        }

        this.showErrorNotices(data);
        this.updateErrorHighlight(data);
        this.updateFormErrors(data);
        this.updateDisplayValue(data);

        // for efficiency; the validation includes the data needed
        // to check for conflicts; allow entry to handle this.
        this.getEntry().conflictHandler.handleResponse(data);
    },

    validateFieldErrorHandler: function(response, ioArgs) {
        // don't bother showing error messages
        // if request was cancelled by the browser
        if (ioArgs.xhr.status === 0) {
            return;
        }

        // dojo won't json decode the response for us on error
        // we try to take care of that here ourselves
        var data;
        try {
            data = dojo.fromJson(ioArgs.xhr.responseText);
        } catch (err) {
            data = {};
        }

        // construct error message (append details if available).
        var details = typeof data.message !== 'undefined' ? data.message : '';
        var message = "Unexpected error trying to validate "
                    + this.getLabel()
                    + (details ? ": <br />" + details : '.'),
            notice  = new p4cms.ui.Notice({
                message:    message,
                severity:   "error"
            });
    },

    showErrorNotices: function(data) {
        var error;
        for (error in data.errors) {
            if (data.errors.hasOwnProperty(error)) {
                var message = this.getLabel() + ': ' + data.errors[error],
                    notice  = new p4cms.ui.Notice({
                        name:       "content-element-" + this.elementName + "-" + error,
                        message:    message,
                        severity:   "error",
                        sticky:     false
                    });
            }
        }
    },

    updateErrorHighlight: function(data) {
        var errorClass = 'editable-element-border-error';
        if (!data.isValid) {
            this.addHighlightClass(errorClass);
        } else {
            this.removeHighlightClass(errorClass);
        }
    },

    updateFormErrors: function(data) {
        var formPartial = this.getFormPartial();

        // remove any existing errors.
        dojo.query('ul.errors', formPartial).forEach(
            function(node) {dojo.destroy(node);}
        );

        // if no errors, just re-position dialog (for in-place mode).
        if (data.isValid) {
            this.repositionEditDialog();
            return;
        }

        // create new error list.
        var error, ul = dojo.create('ul', {'class': 'errors'});
        for (error in data.errors) {
            if (data.errors.hasOwnProperty(error)) {
                dojo.create('li', {innerHTML: data.errors[error]}, ul);
            }
        }

        // place errors before description (if element has one)
        // or as a last child of dd tag; if neither is possible,
        // place errors inside element's node
        var descriptions = dojo.query('p.description', formPartial);
        if (descriptions.length) {
            dojo.place(ul, descriptions.pop(), 'before');
        } else {
            var ddNodes = dojo.query('dd', formPartial);
            dojo.place(ul, ddNodes.length ? ddNodes[0] : formPartial);
        }

        // re-position dialog (for in-place mode).
        this.repositionEditDialog();
    },

    updateDisplayValue: function(data) {
        // nothing to do if display value has not changed.
        if (this.domNode.innerHTML === data.displayValue) {
            return;
        }

        this.domNode.innerHTML = dojo.isString(data.displayValue)
            ? data.displayValue
            : '';

        // execute any inline scripts.
        dojo.query("script[type='text/javascript']", this.domNode).forEach(
            function(node) {
                // monkey patch document.write to capture output.
                var output     = '';
                /*jslint evil:true */
                var docWrite   = document.write;
                document.write = function(s) {output += s;};

                // run script.
                eval(node.text);

                // restore doc write.
                document.write = docWrite;
                /*jslint evil:false */

                // replace script node w. doc-write output.
                dojo.place(dojo.doc.createTextNode(output), node, "after");
            }
        );

        // insert placeholder if appropriate.
        this.insertPlaceholder();

        // disable onclick's in link elements so that clicking them
        // opens the tooltip dialog rather than some unexpected action.
        this.disableLinks();

        this.refresh();

        this.onUpdateDisplayValue(data);
    },

    // called after updateDisplayValue(), useful for connecting to do actions
    // when display value of this element is updated
    // @todo - this is a work-around to allow connecting callbacks executed after
    // updateDisplayValue() for the inline editor elements as their original
    // updateDisplayValue() method is overriden by the p4cms.content.Editor
    onUpdateDisplayValue: function(data) {
    },

    enableEditMode: function() {
        // return without allowing parent to highlight us if
        // the associated form element is disabled
        if (this.getElementNode().disabled) {
            return;
        }

        // make element look 'clickable'.
        dojo.style(this.domNode, 'cursor', 'pointer');

        // click should open edit dialog.
        this.clickHandler = dojo.connect(this.domNode, 'onclick', this, function(event) {
            event.preventDefault();
            this.startEdit();

            return false;
        });

        // disable onclick's in link elements so that clicking them
        // opens the tooltip dialog rather than some unexpected action.
        this.disableLinks();

        // insert placeholder if appropriate.
        this.insertPlaceholder();

        // validate value on change/blur.
        this.connectFormHandlers();

        this.inherited(arguments);
    },

    disableEditMode: function() {
        // remove cursor style.
        dojo.style(this.domNode, 'cursor', 'auto');

        // click should no longer open dialog.
        dojo.disconnect(this.clickHandler);

        // remove placeholder text.
        this.removePlaceholder();

        // re-enable links.
        this.enableLinks();

        this.inherited(arguments);
    },

    // extend highlight drawing to present a 'required' asterisk.
    drawHighlight: function() {
        this.inherited(arguments);
        if (this.required) {
            var requiredAsterisk = dojo.create("div");
            dojo.addClass(requiredAsterisk, 'p4cms-ui required-asterisk');
            this.requiredAsterisk = requiredAsterisk;

            dojo.place(requiredAsterisk, this.bordersRootNode || dojo.body());

            var box = this.paddingBox();
            var leftPosition = box.x - 4.5;
            var topPosition = box.y - 6;

            dojo.style(requiredAsterisk, {
                left:   leftPosition  + "px",
                top:    topPosition  + "px",
                zIndex: 200
            });

        }
    },

    // extend parent to destroy asterisk.
    clearHighlight: function() {
        if (this.requiredAsterisk) {
            dojo.destroy(this.requiredAsterisk);
        }
        this.inherited(arguments);
    },

    // return node with id equals element name if it exists,
    // otherwise return containing node
    getElementNode: function() {
        return (dojo.byId(this.elementName) || this.getFormPartial());
    },

    getPlaceholderText: function() {
        // use explicit placeholder text from form element if present.
        var partial = this.getFormPartial();
        if (partial) {
            var placeholders = dojo.query('[placeholder]', partial);
            if (placeholders.length) {
                return dojo.attr(placeholders[0], 'placeholder');
            }
        }

        // fallback to element label.
        return this.getLabel();
    },

    insertPlaceholder: function() {
        this.removePlaceholder();

        // insert placeholder text if no value.
        var valueNode = this.getValueNode();
        if (!this.getFormValue() && !valueNode.innerHTML.length) {
            dojo.create('span', {
                'class':            'value-placeholder',
                innerHTML:          this.getPlaceholderText()
            }, valueNode);
        }
    },

    removePlaceholder: function() {
        dojo.query("span.value-placeholder", this.getValueNode())
            .forEach(function(node) {
                dojo.destroy(node);
            }
        );
    },

    getValueNode: function() {
        var nodes = dojo.query(".value-node", this.domNode);
        if (nodes.length) {
            return nodes[0];
        } else {
            return this.domNode;
        }
    },

    onFormChange: function() {
        this.validateField();
    },

    onFormBlur: function() {
        this.validateField();
    },

    connectFormHandlers: function() {
        // only setup form handlers once.
        if (this.formHandlers) {
            return;
        }

        // setup change/blur handlers for all element form controls.
        this.formHandlers = {};
        dojo.query("input, button, select, textarea", this.getFormPartial()).forEach(
            dojo.hitch(this, function(node) {

                // only consider form controls with this element name.
                var name = this.elementName;
                if (node.name !== name && node.name !== name + '[]') {
                    return;
                }

                this.formHandlers = {
                    onChange:   dojo.connect(node, 'onchange', this, 'onFormChange'),
                    onBlur:     dojo.connect(node, 'onblur',   this, 'onFormBlur')
                };
            })
        );
    },

    disconnectFormHandlers: function() {
        if (!this.formHandlers) {
            return;
        }

        var handler;
        for (handler in this.formHandlers) {
            if (this.formHandlers.hasOwnProperty(handler)) {
                dojo.disconnect(this.formHandlers[handler]);
            }
        }

        this.formHandlers = null;
    },

    hasChanged: function() {
        return dojo.toJson(this.originalValue) !== dojo.toJson(this.getFormValue());
    },

    getLabel: function() {
        return this.elementLabel || this.elementName;
    },

    setOriginalValue: function(value) {
        this.originalValue = value;
    },

    // Handler for Tab events coming from dialog
    handleTabKey: function(event) {
        if (event.keyCode !== dojo.keys.TAB) {
            return true;
        }

        var siblings      = this.getEntry().getElements();
        var eventTarget   = event.target;
        var currentIndex  = dojo.indexOf(siblings, this);
        var canTab        = {back:true, forward:true};
        var elementTarget = null;

        if (this.editDialog) {
            if (eventTarget !== this.editDialog._firstFocusItem) {
                canTab.back = false;
            }
            if (eventTarget !== this.editDialog._lastFocusItem) {
                canTab.forward = false;
            }
        }

        // tab back / else tab forward
        if (canTab.back && event.shiftKey) {
            // goto previous / else goto last element
            if (currentIndex > 0) {
                elementTarget = siblings[currentIndex-1];
            } else {
                elementTarget = siblings[siblings.length-1];
            }
        } else if (canTab.forward && !event.shiftKey) {
            // goto first element / else goto next element
            if (currentIndex === siblings.length-1) {
                elementTarget = siblings[0];
            } else {
                elementTarget = siblings[currentIndex+1];
            }
        }

        if (elementTarget) {
            this.stopEdit();
            elementTarget.startEdit();
            dojo.stopEvent(event);
            return false;
        }
    },

    // refresh the elements borders
    refresh: function(root) {
        // if any images are present in the provided root; refresh edit mode when they load
        dojo.query("img", root || this.domNode).forEach(function(imgNode) {
            if (!imgNode.cmsLoad && !imgNode.loaded) {
                imgNode.cmsLoad = dojo.connect(imgNode, 'onload', function() {
                    // do a setTimeout to allow the browser to complete rendering
                    window.setTimeout(function() {
                        dojo.publish('p4cms.ui.refreshEditMode');
                    }, 0);
                });
            }
        });

        dojo.publish('p4cms.ui.refreshEditMode');
    },

    disableLinks: function() {
        dojo.query('a', this.domNode).forEach(function(link){
            if (link.onclick) {
                link.onclickdisabled = link.onclick;
                link.onclick         = null;
            }
        });
    },

    enableLinks: function() {
        dojo.query('a', this.domNode).forEach(function(link){
            if (link.onclickdisabled) {
                link.onclick         = link.onclickdisabled;
                link.onclickdisabled = null;
            }
        });
    }
});
