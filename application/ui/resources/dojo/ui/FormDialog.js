// summary:
//      Extended p4cms.ui.Dialog with integrated support to display and save forms.
//
//      Dialog's urlParams property is used for loading the initial dialog content where
//      partial context is used if no context was set in urlParams.
//
//      When dialog is executed, it attempts to save the form by posting it to the
//      url defined by urlParams via dojo.xhrPost() call. Json context is used by default
//      (can be overriden by specifying format in urlParams).
//
//      Optionally, form can be sent by using dojo.io.iframe, which is necessary for
//      more complex IO operations like uploading a file. To do this, class parameter
//      dataFormat must be set to 'dojoio'.
//
//      There are two methods, onSaveSuccess() and onSaveError() called when XHR request
//      was or wasn't successful. These functions do the bare minimum things, but are good
//      place to connect custom handlers.
//
//      Recognized fields in the XHR response:
//
//        form    - markup of form to display/save
//        errors  - contains all form errors
//        message - notification message for successful request
//

dojo.provide("p4cms.ui.FormDialog");
dojo.require("p4cms.ui.Dialog");
dojo.require("p4cms.ui.Notice");
dojo.require("p4cms.ui._FormErrorsMixin");
dojo.require("dojo.io.iframe");
dojo.require("dijit.form.Button");

dojo.declare("p4cms.ui.FormDialog", [p4cms.ui.Dialog, p4cms.ui._FormErrorsMixin], {
    urlParams:          [],
    routeName:          null,
    executeScripts:     true,
    refreshOnShow:      true,
    clearOnHide:        true,
    dataFormat:         'json',
    addCancelButton:    true,
    notifyFormErrors:   false,
    saveOnEnter:        true,

    postMixInProperties: function() {
        this.inherited(arguments);

        // assemble url to load the dialog content (form)
        // use partial context if no context has been set in urlParams
        this.href = p4cms.url(this._formatUrlParams('partial'), this.routeName);
    },

    postCreate: function() {
        this.inherited(arguments);

        // add css class to identify as form dialog
        dojo.addClass(this.domNode, 'form-dialog');

        // save form when dialog is executed
        // create new function to have full control over it
        // (there may be connected handlers for automatic dialog
        // closing etc., we don't want that)
        this.onExecute = function() {
            this._save();
        };
    },

    onLoad: function() {
        this.inherited(arguments);

        // add default cancel button
        if (this.addCancelButton) {
            var cancelButton = new dijit.form.Button({
                'label':    'Cancel',
                'class':    'p4cms-ui-cancel'
            });

            // place cancel button as the last child of the buttons element if exists,
            // otherwise place it in button container

            // note: Because buttons-element is not unique, the id selector was
            // returning nothing when scoped in chrome. The attribute selector for
            // id works.
            var buttonsElement = dojo.query('[id=buttons-element] dl', this.domNode)[0];
            if (buttonsElement !== undefined) {
                // wrap cancel in dd to be consistent with other buttons
                var cancelElement = dojo.create('dd', null, buttonsElement);
                dojo.place(cancelButton.domNode, cancelElement);
            } else {
                dojo.place(cancelButton.domNode, this.getButtonContainer());
            }
        }

        // wire-up cancel button(s)
        dojo.query('.p4cms-ui-cancel input[type="button"]', this.domNode).onclick(
            dojo.hitch(this, 'hide')
        );

        // save containing form(s) by Enter key
        this._saveFormOnEnter();
    },

    // called just before form is saved
    onSave: function() {
    },

    // attempt to save the form via XHR
    _save: function() {
        this.onSave();
        var form = dojo.query('form', this.domNode)[0];
        if (!form) {
            this._notify("Dialog contains no form.", "error");
            return;
        }

        // post the form to the server; use diffrent methods depending on dataFormat property
        if (this.dataFormat === 'dojoio') {
            return dojo.io.iframe.send({
                url:        p4cms.url(this._formatUrlParams(this.dataFormat), this.routeName),
                form:       form,
                handleAs:   this.dataFormat,
                load:       dojo.hitch(this, function(data) {
                    var response = dojo.fromJson(data);
                    if (response.errors || response.exception) {
                        this.onSaveError(response);
                    } else {
                        this.onSaveSuccess(response);
                    }
                }),
                error:      dojo.hitch(this, function() {
                    this._notify(this.unknownSaveError, "error");
                    this.enableSingleClickButtons();
                })
            });
        } else {
            return dojo.xhrPost({
                url:        p4cms.url(this._formatUrlParams(this.dataFormat), this.routeName),
                form:       form,
                handleAs:   this.dataFormat,
                load:       dojo.hitch(this, 'onSaveSuccess'),
                error:      dojo.hitch(this, function(response, ioArgs) {
                    try {
                        var data = dojo.fromJson(ioArgs.xhr.responseText);
                        this.onSaveError(data);
                    } catch (error) {
                        this._notify(this.unknownSaveError, "error");
                        this.enableSingleClickButtons();
                    }
                })
            });
        }
    },

    onSaveSuccess: function(response) {
        if (response.message) {
            this._notify(response.message, (response.severity || 'success'));
        }

        // close the dialog
        this.hide();
    },

    onSaveError: function(response) {
        // if form contains errors, refresh dialog content
        if (response.form) {
            this.set('content', response.form);
        } else if (response.errors) {
            this.updateFormErrors(response);
        }

        // attempt to growl errors either way
        this.showErrorNotices(response);

        // re-enable single-click buttons
        this.enableSingleClickButtons();
    },

    enableSingleClickButtons: function() {
        dojo.forEach(dojo.query('fieldset.buttons .dijitButtonDisabled', this.domNode),
            function(node) {
                var button = dijit.byNode(node);
                if (button.declaredClass === 'p4cms.ui.SingleClickButton') {
                    button.enable();
                }
            }
        );
    },

    disableSingleClickButtons: function() {
        dojo.forEach(dojo.query('fieldset.buttons .dijitButton', this.domNode),
            function(node) {
                var button = dijit.byNode(node);
                if (button.declaredClass === 'p4cms.ui.SingleClickButton') {
                    button.disable();
                }
            }
        );
    },

    refreshForm: function(refreshParams) {
        // disable buttons while we're updating.
        this.disableSingleClickButtons();

        // if first param is an event, ignore it.
        /*global Event*/
        if ((typeof Event !== "undefined" && refreshParams instanceof Event)
            || (refreshParams && refreshParams.srcElement !== undefined)) {
            refreshParams = {};
        }

        var url = p4cms.url(
            dojo.mixin(
                this._formatUrlParams('partial'),
                refreshParams || {}
            ),
            this.routeName
        );

        dojo.xhrGet({
            url:    url,
            form:   dojo.query('form', this.domNode)[0],
            load:   dojo.hitch(this, function(response) {
                // re-enable single click buttons - doing this
                // early so the spinner doesn't get orphaned
                this.enableSingleClickButtons();

                if (this.open) {
                    this.set('content', response);
                }
            }),
            error:  dojo.hitch(this, function() {
                // re-enable single click buttons.
                this.enableSingleClickButtons();

                var notice = new p4cms.ui.Notice({
                    message:    'An error occurred refreshing the form.',
                    severity:   'error'
                });
            })
        });
    },

    _saveFormOnEnter: function() {
        // early exit if we don't want to save by the Enter key
        if (!this.saveOnEnter) {
            return;
        }

        // browse through all forms (p4cms.ui.Form widgets) placed inside
        // this dialog and make them submittable by the Enter key
        dojo.forEach(dojo.query('form', this.domNode), function (form) {
            var formDijit = dijit.byNode(form);
            if (formDijit && formDijit.declaredClass === 'p4cms.ui.Form') {
                formDijit.submitOnEnter         = true;
                formDijit.submitButtonContainer = this.domNode;
            }
        }, this);
    },

    // if 'format' param has not been set in urlParams, returns urlParams
    // added by 'format' set to given context, otherwise returns urlParams
    _formatUrlParams: function(context) {
        var params = dojo.clone(this.urlParams);
        if (context && !params.format) {
            params.format = context;
        }
        return params;
    }
});