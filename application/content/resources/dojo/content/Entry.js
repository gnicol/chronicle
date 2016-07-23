// summary:
//      Support for in-place editing of content.

dojo.provide("p4cms.content.Entry");
dojo.require("p4cms.content.Element");
dojo.require("p4cms.content.ConflictHandler");
dojo.require("p4cms.content.HiddenElement");
dojo.require("p4cms.ui.BackgroundOffset");
dojo.require("p4cms.ui.EditableElement");
dojo.require("p4cms.ui.Notice");
dojo.require("p4cms.ui.ConfirmDialog");
dojo.require("p4cms.ui.FormDialog");
dojo.require("dojox.layout.ContentPane");

dojo.declare("p4cms.content.Entry", p4cms.ui.EditableElement,
{
    group:                  "content",

    contentId:              "",
    contentVersion:         "",
    headVersion:            "",
    contentType:            "",
    contentTitle:           "",
    deleted:                null,
    allowedPrivileges:      [],
    elements:               null,
    conflictHandler:        null,
    _subFormsRegistry:      null,

    // will contain null, 'inplace' or 'form'
    editMode:           null,

    constructor: function() {
        // create a conflict handler instance
        this.conflictHandler = new p4cms.content.ConflictHandler({entry: this});

        this._subFormsRegistry = [];
    },

    startup: function() {
        this.inherited(arguments);

        // if the form is present, set it up as soon as it is safe.
        // if child dijits made dom changes, those changes might not
        // be in effect yet (dom changes are async).
        if (this.getForm()) {
           setTimeout(dojo.hitch(this, 'setupForm'), 1);
        }
    },

    // add subForm dijit to the sub-forms registry
    registerSubForm: function(subForm) {
        if (subForm) {
            this._subFormsRegistry.push(subForm);
        }
    },

    // display content delete form in the FormDialog with customized
    // _save method that will use "in-band" form submission instead of xhr
    // returns reference to the delete FormDialog
    deleteEntry: function() {
        if (!this.deleteDialog) {
            this.deleteDialog = new p4cms.ui.FormDialog({
                title:      'Delete Content',
                'class':    'content-delete-dialog',
                urlParams:  {
                    module:     'content',
                    action:     'delete',
                    id:         this.contentId
                }
            });

            // insert a confirmation note into the form
            dojo.connect(this.deleteDialog, 'onLoad', dojo.hitch(this, function(){
                // get form node to put the note in
                var node = dojo.query('.delete-confirmation', this.deleteDialog.domNode);
                if (node.length) {
                    node[0].innerHTML = 'Are you sure you want to delete this content entry?';
                }
            }));

            // make the delete dialog do an "in-band" form submission
            this.deleteDialog._save = dojo.hitch(this, function() {
                var form = dojo.query('form', this.deleteDialog.domNode)[0];
                form.method = 'post';
                form.action =  p4cms.url({
                    module: 'content',
                    action: 'delete'
                });
                form.submit();
            });
        }

        this.deleteDialog.show();

        return this.deleteDialog;
    },

    confirmCancel: function() {
        // do not show the dialog if content entry has not been changed
        if (!this.hasChanged()) {
            this.cancel();
            return;
        }

        if (!this.confirmCancelDialog) {
            this.confirmCancelDialog = new p4cms.ui.ConfirmDialog({
                title:               'Discard Changes',
                content:             'Discard changes made to this content entry?',
                actionSingleClick:   false,
                actionButtonOptions: { label: 'Discard Changes' },
                cancelButtonOptions: { label: 'Continue Editing' },
                onConfirm:           dojo.hitch(this, function() {
                    this.confirmCancelDialog.hide();
                    this.cancel();
                })
            });
        }
        this.confirmCancelDialog.show();
    },

    // Perform actions before navigating out of the
    // edit mode when there are unsaved changes.
    promptBeforeUnload: function(evt) {
        evt = evt || window.event;
        if (this.hasChanged() && !dojo.isIE) {
            var returnValue = "Content has been modified. Discard your changes?";

            // neccessary for Firefox
            evt.returnValue = returnValue;

            // neccessary for Chrome
            return returnValue;
        }
    },

    activateFormSubmit: function() {
        // ensure form's submit uses our save routine
        this.connect(this.getForm(), 'onsubmit', function() {
            this.save();
            return false;
        });
    },

    enableEditMode: function() {
        // editing only works if we are the active entry and have elements
        if (p4cms.content.getActive() !== this ||
            dojo.query('[dojotype=p4cms.content.Element]', this.domNode).length === 0
        ) {
            return;
        }

        // if we don't have it already, load the form
        // enter the default edit mode when it has loaded.
        var form = this.getForm();
        if (!form) {
            this.loadForm(dojo.hitch(this, 'enterDefaultMode'));
        } else {
            this.enterDefaultMode();
        }

        // start the conflict handler
        this.conflictHandler.start();

        this.inherited(arguments);
    },

    enterDefaultMode: function() {
        // if the entry isn't actually being edited, exit early
        // could happen if the form is xhr loaded and the user
        // changes edit mode before it loads.
        if (!p4cms.ui.isInEditMode(this.group)) {
            return;
        }

        // enable editing of content elements.
        p4cms.ui.enableEditGroup('content.element');

        this.enterInPlaceMode();
    },

    enterInPlaceMode: function() {
        if (this.editMode === 'inplace') {
            return;
        }

        if (this.editMode) {
            this.exitFormMode();
        }

        this.editMode = 'inplace';
        this.updateSubToolbar();
    },

    exitInPlaceMode: function() {
        this.editMode = null;
    },

    enterFormMode: function() {
        if (this.editMode === 'form') {
            return;
        }

        if (this.editMode) {
            this.exitInPlaceMode();
        }

        this.editMode = 'form';
        this.updateSubToolbar();

        this.createUnderlay();
        this.updateFormMode();
        this.resizeFormModeHandler = dojo.connect(window, "resize", this, "updateFormMode");
        this.scrollFormModeHandler = dojo.connect(window, "scroll", this, "updateFormMode");

        // animate display of pane
        var anim;
        var container = this.getFormContainer();
        dojo.style(container.domNode, "opacity", 0);
        p4cms.ui.show(this._underlay.domNode);
        anim = p4cms.ui.show(container.domNode);

        // focus the first visible element
        var queries = [
            '[id="contentformelements-element"] input',
            '[id="contentformelements-element"] select',
            '[id="contentformelements-element"] textarea'
        ];
        var nodes = dojo.filter(
            dojo.query(queries.join(','), container.domNode),
            function (node) {return dojo.attr(node, 'type') !== 'hidden';}
        );
        if (nodes && nodes[0]) {
            nodes[0].focus();
        }

        return anim;
    },

    // create underlay
    createUnderlay: function() {
        var container = this.getFormContainer();
        var underlayAttrs = {
            dialogId: container.id,
            "class": 'p4cms-ui_underlay'
        };
        var underlay = this._underlay;
        if (!underlay) {
            underlay = this._underlay = new dijit.DialogUnderlay(underlayAttrs);
        } else {
            underlay.set(underlayAttrs);
        }
        dojo.style(this._underlay.domNode, "zIndex", "790");
    },

    updateFormMode: function() {
        var overallWidth = dojo.body().parentNode.clientWidth;
        var left = (overallWidth > 960)
            ? Math.round((overallWidth - 960) / 2)
            : 0;
        var container = this.getFormContainer();
        dojo.style(container.domNode, {
            "display":      "block",
            "position":     "absolute",
            "top":          p4cms.ui.BackgroundOffset.get(),
            "left":         left + "px",
            "width":        "960px",
            "zIndex":       "800"
        });
        this._underlay.show();

        // in case the pane causes a scrollbar to appear, reposition
        overallWidth = dojo.body().parentNode.clientWidth;
        left = (overallWidth > 960)
            ? Math.round((overallWidth - 960) / 2)
            : 0;
        dojo.style(container.domNode, "left", left + "px");
    },

    exitFormMode: function() {
        var anim;
        var container = this.getFormContainer();
        if (container) {
            // animate display of pane
            dojo.style(container.domNode, "opacity", 100);
            anim = p4cms.ui.hide(container.domNode);
            if (this._underlay) {
                p4cms.ui.hide(this._underlay.domNode);
            }
        }
        this.editMode = null;
        dojo.disconnect(this.resizeFormModeHandler);
        dojo.disconnect(this.scrollFormModeHandler);

        return anim;
    },

    disableEditMode: function() {
        this.exitInPlaceMode();
        this.exitFormMode();

        // tell content elements we're done editing.
        p4cms.ui.disableEditGroup('content.element');

        // stop the conflict handler
        this.conflictHandler.stop();

        this.inherited(arguments);
    },

    refreshEditMode: function() {
        if (!p4cms.ui.inEditMode[this.group]) {
            return;
        }
        this.inherited(arguments);
    },

    updateSubToolbar: function() {
        var inPlaceButton, inFormButton;
        if (this.contentId) {
            inPlaceButton = dojo.byId('edit-content-toolbar-button-in-place');
            inFormButton  = dojo.byId('edit-content-toolbar-button-form');
        } else {
            inPlaceButton = dojo.byId('add-content-toolbar-button-in-place');
            inFormButton  = dojo.byId('add-content-toolbar-button-form');
        }
        if (!inPlaceButton || !inFormButton) {
            return;
        }

        if (this.editMode === 'form') {
            dojo.addClass(inFormButton.parentNode.parentNode, 'active');
            dojo.removeClass(inPlaceButton.parentNode.parentNode, 'active');
        }
        if (this.editMode === 'inplace') {
            dojo.addClass(inPlaceButton.parentNode.parentNode, 'active');
            dojo.removeClass(inFormButton.parentNode.parentNode, 'active');
        }

        // show/hide sub-form buttons
        dojo.query('.toolbar-drawer .content-sub-form-button').forEach(function(node) {
            if (this.editMode === 'form') {
                dojo.style(node, 'visibility', 'hidden');
            } else {
                dojo.style(node, 'visibility', '');
            }
        }, this);
    },

    cancel: function() {
        dojo.when(
            this.conflictHandler.stop(),
            dojo.hitch(this, '_cancel'),
            dojo.hitch(this, '_cancel')
        );
    },

    _cancel: function() {
        // ensure we don't get prompted again during redirect
        dojo.disconnect(this.onBeforeUnloadHandler);

        // redirect to entry, or home as appropriate.
        if (this.contentId.length) {
            window.location.href = this.getEntryUri();
        } else {
            window.location.href = p4cms.url();
        }
    },

    save: function() {
        this.conflictHandler.handleSave().then(
            dojo.hitch(this, function() {
                // user confirmed save; ensure our reported head is the
                // latest version we have seen
                var data = this.conflictHandler.getLatestConflict();
                if (data && data.status) {
                    this.headVersion = data.status.Version;
                }

                // do the actual save
                this._save();
            }),
            dojo.hitch(this, '_enableSaveButtons')
        );
    },

    _save: function() {
        var urlParams = {
            module: 'content',
            format: 'dojoio'
        };

        // are we adding or editing?
        if (this.contentId) {
            urlParams.action      = 'edit';
            urlParams.id          = this.contentId;
            urlParams.headVersion = this.headVersion;
        } else {
            urlParams.action      = 'add';
        }

        // notify all registered sub-forms that content is being saved
        dojo.forEach(this._subFormsRegistry, function(subForm){
            subForm.onEntrySave();
        });

        dojo.io.iframe.send({
            url:      p4cms.url(urlParams),
            form:     this.getForm(),
            handleAs: 'json',
            load:     dojo.hitch(this, function(response) {
                var errors, data;
                // detect unexpected errors and throw to kick us over to
                // the error handler (io.iframe can't detect error headers)
                if (typeof response.isValid === 'undefined') {
                    throw response;
                }

                // if save succeeded, redirect to the content entry.
                if (response.isValid) {
                    // ensure we don't get prompted to save when redirecting to entry
                    // we neuter the prompt function because dojo.disconnect() wasn't
                    // working - window was undefined - possibly related to io.iframe
                    this.promptBeforeUnload = function() {};

                    // tell the conflict handler to stop if it is running still
                    // save would have removed us from the opened list but we want
                    // to prevent any additional 'pings' from occuring.
                    this.conflictHandler.stop();

                    // redirect to entry.
                    this.contentId       = response.contentId;
                    window.location.href = response.contentUri || this.getEntryUri();
                    return;
                }

                // if we have a conflict, show our notice
                if (response.isConflict) {
                    // make sure the conflict handler knows about this response
                    // and call back around to 'save' to actually deal with it.
                    this.conflictHandler.conflicts.push(response);
                    this.save();

                    return;
                }

                // communicate form-level errors back to user if we have any.
                if (response.errors.form.length || dojo.objectToQuery(response.errors.elements)) {
                    var messages = response.errors.form.join('<br />'),
                    message      = "Save failed. Form contains error(s)"
                                 + (messages ? ": <br />" + messages : '.'),
                    notice       = new p4cms.ui.Notice({
                        name:       'content-save-error',
                        message:    message,
                        severity:   "error"
                    });
                }

                // update each element to reflect errors.
                var element, elements = this.getElements();
                for (element in elements) {
                    if (elements.hasOwnProperty(element)) {
                        element = elements[element];
                        errors  = response.errors.elements[element.elementName] || [];
                        data    = {
                            isValid:    errors.length === 0,
                            errors:     errors
                        };

                        element.showErrorNotices(data);
                        element.updateErrorHighlight(data);
                        element.updateFormErrors(data);
                    }
                }

                // update each sub-form to reflect errors
                // we pass list of sub-form errors as well as the whole content form (if present
                // in response)
                var newForm;
                var subForm;
                var subForms = this.getSubForms();
                var formDom  = dojo._toDom(response.form);
                for (subForm in subForms) {
                    if (subForms.hasOwnProperty(subForm)) {
                        subForm  = subForms[subForm];
                        errors   = response.errors.elements[subForm.formName] || [];
                        newForm  = dojo.query('dd[formName=' + subForm.formName + ']', formDom)[0];
                        data     = {
                            isValid:    errors.length === 0,
                            errors:     errors,
                            form:       (newForm ? newForm.innerHTML : '')
                        };

                        subForm.showErrorNotices(data);
                        subForm.updateFormErrors(data);
                    }
                }

                // re-enable save button(s)
                this._enableSaveButtons();
            }),
            error:      dojo.hitch(this, function(response) {
                // try to extract useful error message from response.
                // if response has message and type, we assume it comes
                // from the server's error handler (as opposed to dojo).
                var details = '';
                if (typeof response.message !== 'undefined' && typeof response.type !== 'undefined') {
                    details = response.message;
                }

                // construct error message (append details if available).
                var message = "Unexpected error trying to save"
                            + (details ? ": <br />" + details : '.'),
                    notice  = new p4cms.ui.Notice({
                        message:    message,
                        severity:   "error"
                    });

                // re-enable save button(s)
                this._enableSaveButtons();
            })
        });
    },

    getEntryUri: function(params) {
        // let the caller customize url parameters
        params = dojo.mixin(
            {
                module: 'content',
                action: 'view',
                id:     this.contentId
            },
            params
        );

        return p4cms.url(params);
    },

    getElements: function() {
        if(!this.elements) {
            var dijits = [];
            var query  = "[dojoType=p4cms.content.Element],"
                       + "[dojoType=p4cms.content.HiddenElement]";
            dojo.query(query, this.domNode).forEach(
                function(element) {
                    dijits.push(dijit.byNode(element));
                }
            );

            this.elements = dijits;
        }

        return this.elements;
    },

    getElement: function(element) {
        var i, elements = this.getElements();
        for (i in elements) {
            if (elements.hasOwnProperty(i) && elements[i].elementName === element) {
                return elements[i];
            }
        }

        return null;
    },

    loadForm: function(onLoad) {
        // generate the URL to use for form retreival
        var urlParams = {
            module: 'content',
            action: 'form',
            format: 'partial'
        };

        // adding or editing?
        if (this.contentId) {
            urlParams.id            = this.contentId;
            urlParams.version       = this.contentVersion;
        } else {
            urlParams.contentType   = this.contentType;
        }

        // create a new content pane and set its URL to the form address
        // ensure the 'content form ready' message is sent after load
        var container = new dojox.layout.ContentPane();
        container.placeAt(this.domNode)
            .set('onDownloadEnd', dojo.hitch(this, function() {

                this.setupForm();

                // if on-load callback given, invoke it.
                if (dojo.isFunction(onLoad)) {
                    onLoad();
                }
            }))
            .set('class', 'p4cms-ui content-form-container')
            .set('href', p4cms.url(urlParams));

        dojo.style(container.domNode, "display", "none");
    },

    setupForm: function() {
        this.activateFormSubmit();

        // not all content elements are displayed - if an element is not
        // rendered, it won't have a content element dijit instance
        // we need the element dijit instance to handle validation.
        var element;
        var formElement;
        var elements     = this.getElements();
        var formElements = dojo.query('div.content-form-element', this.getForm());
        for (element in formElements) {
            if (formElements.hasOwnProperty(element)) {
                formElement = formElements[element];

                // skip elements without proper ids.
                if (!formElement.id || formElement.id.indexOf('content-form-') !== 0) {
                    continue;
                }

                // skip elements that already have associated dijits.
                var elementName = formElement.id.replace('content-form-', '');
                if (this.elementHasDijit(elementName)) {
                    continue;
                }

                // hidden element - make a hidden content element dijit for it.
                var dijit = new p4cms.content.HiddenElement(
                    {elementName: elementName},
                    dojo.create('div', null, this.domNode)
                );
                dojo.style(dijit.domNode, 'display', 'none');
                dijit.startup();
            }
        }

        // record original values in elements
        elements = this.getElements();
        for (element in elements) {
            if (elements.hasOwnProperty(element)) {
                element = elements[element];
                element.setOriginalValue(element.getFormValue());
            }
        }

        // connect page unload event to warn about unsaved changes
        this.onBeforeUnloadHandler = dojo.connect(
            window,
            "onbeforeunload",
            this,
            "promptBeforeUnload"
        );

        // notify that form is loaded.
        dojo.publish('p4cms.content.form.ready', [this.getForm(), this]);
    },

    elementHasDijit: function(elementName) {
        var matching = dojo.filter(this.getElements(), function(item) {
            return item.elementName === elementName;
        });
        return Boolean(matching.length);
    },

    getForm: function() {
        var forms = dojo.query('.content-form', this.domNode);
        if (typeof forms[0] !== 'undefined') {
            return forms[0];
        }
    },

    getSubForms: function() {
        return this._subFormsRegistry;
    },

    getSubForm: function(name) {
        var found = dojo.filter(this._subFormsRegistry, function(subForm){
            return subForm.formName === name;
        });

        return found.length ? found[0] : null;
    },

    getFormContainer: function() {
        var containers = dojo.query('.content-form-container', this.domNode);
        if (typeof containers[0] !== 'undefined') {
            return dijit.byNode(containers[0]);
        }
    },

    getValues: function() {
        // summary:
        //      get all field values that make up this content entry.
        return dojo.formToObject(this.getForm());
    },

    hasChanged: function() {
        var element, elements = this.getElements();
        for (element in elements) {
            if (elements.hasOwnProperty(element)) {
                if (elements[element].hasChanged()) {
                    return true;
                }
            }
        }

        return false;
    },

    isAdd: function() {
        return this.contentId ? false : true;
    },

    // disable highlighing of content entries.
    drawHighlight: function() {
        return;
    },

    diffHaveAgainst: function(version) {
        return new p4cms.ui.Dialog({
            title:          'Diff',
            destroyOnHide:  true,
            href:           p4cms.url({
                module:     'diff',
                controller: 'index',
                action:     'index',
                format:     'partial',
                type:       'content',
                left:       this.contentId + '#' + this.headVersion,
                right:      this.contentId + '#' + version
            })
        }).show();
    },

    // enable all disabled single-click buttons identified by 'save' label
    _enableSaveButtons: function() {
        dojo.query('.dijitButtonDisabled').forEach(function(node) {
            var button = dijit.byNode(node);
            if (button.declaredClass === 'p4cms.ui.SingleClickButton'
                && button.label.match(/^save$/i)
            ) {
                button.enable();
            }
        });
    }
});