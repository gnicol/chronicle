// summary:
//      A file upload dijit with client-side validation of extensions.
//

dojo.provide("p4cms.content.FileUpload");
dojo.require("dijit._Widget");
dojo.require("dijit.form.Button");

dojo.declare("p4cms.content.FileUpload", dijit._Widget, {

    extensions: null,
    minSize:    0,
    maxSize:    0,
    required:   false,

    startup: function() {
        this.inherited(arguments);

        // add classes so dijit can be targetted via css.
        dojo.addClass(this.domNode, 'p4cms-ui file-upload');

        // wrap the file input so that it can be cleared.
        var file    = this.getFileInput();
        var wrapper = dojo.create('span', null, file, 'before');
        dojo.addClass(wrapper, 'file-upload-input');
        dojo.place(file, wrapper);

        // add a clear button.
        var clear = new dijit.form.Button({
            label:      'Clear',
            'class':    'file-upload-clear button-small',
            onClick:    dojo.hitch(this, 'clearFile')
        });
        dojo.place(clear.domNode, wrapper, 'after');

        // when the user selects keep or remove, clear the file input.
        var query = 'input[type=radio][value=keep],input[type=radio][value=remove]';
        dojo.query(query, this.domNode).connect('onchange', this, function() {
            this.clearFile();
        });

        // configure the file input (e.g. connect events)
        this.setupFileInput();
    },

    setupFileInput: function() {
        // when file input changes, do stuff.
        dojo.connect(this.getFileInput(), 'onchange', this, 'onChange');
    },

    // validate the file extension against the list of valid extensions.
    // returns list of error messages or false if no errors.
    validate: function() {

        var i, fileName, fileSize,
            messages            = {},
            input               = this.getFileInput(),
            maxFileSizeLimit    = this.getMaxFileSizeLimit();

        // dont validate if file input field doesn't exist
        if (typeof input === 'undefined') {
            return false;
        }

        // if we are keeping the existing file, nothing to validate.
        if (this.getFileAction() === 'keep') {
            return false;
        }

        // ensure we have a file if field is required
        if (this.required && input.value.length === 0) {
            messages.required = "Value is required and can't be empty";
        }

        // IE7 does not support other validation because it does not support the input.files property
        if(dojo.isIE === 7 || !input.files) {
            // return list of messages if errors, false otherwise.
            return p4cms.ui.keys(messages).length ? messages : false;
        }

        // adjust max size if MAX_FILE_SIZE is present
        if (maxFileSizeLimit !== undefined){
            this.maxSize = this.maxSize
                 ? Math.min(maxFileSizeLimit, this.maxSize)
                 : maxFileSizeLimit;
        }

        // check file size
        if (this.minSize){
            for (i = 0; i < input.files.length; i++) {
                fileSize = input.files[i].fileSize;

                // dependent on browser version this property moves from fileName to name
                fileName = input.files[i].fileName || input.files[i].name;
                if (fileSize && fileSize < this.minSize){
                    messages.size = "Minimum expected size for file " + fileName
                                  + " is (" + this.toByteString(this.minSize)
                                  + ") but (" + this.toByteString(fileSize)
                                  + ") detected.";
                }
            }
        }
        if (this.maxSize){
            for (i = 0; i < input.files.length; i++) {
                fileSize = input.files[i].fileSize;

                // dependent on browser version this property moves from fileName to name
                fileName = input.files[i].fileName || input.files[i].name;
                if (fileSize && fileSize > this.maxSize){
                    messages.size = "Maximum expected size for file " + fileName
                                  + " is (" + this.toByteString(this.maxSize)
                                  + ") but (" + this.toByteString(fileSize)
                                  + ") detected.";
                }
            }
        }

        // check extensions
        if (this.extensions && this.extensions.length > 0) {
            var pattern = "\\.(" + this.extensions.join("|") + ")$",
                regexp  = new RegExp(pattern, "i");
            for (i = 0; i < input.files.length; i++) {
                // dependent on browser version this property moves from fileName to name
                fileName = input.files[i].fileName || input.files[i].name;
                if (!fileName.match(regexp)) {
                    messages.extension = "Invalid file extension ('" + fileName + "'). "
                                       + "Extension must be one of: ("
                                       + this.extensions.join(', ') + ").";
                }
            }
        }

        // return list of messages if errors, false otherwise.
        return p4cms.ui.keys(messages).length ? messages : false;
    },

    getFileInput: function() {
        var inputs = dojo.query("input[type=file]", this.domNode);
        if (inputs.length) {
            return inputs[0];
        }
    },

    // returns the file name
    getFileName: function() {
        return this.getFileInput().value;
    },

    getFileAction: function() {
        var fileInput = this.getFileInput();
        var inputName = fileInput.name + '-existing-file-action';
        return dojo.formToObject(fileInput.form)[inputName];
    },

    onChange: function() {
        var input = this.getFileInput();
        if (input.value.length) {
            dojo.query('input[type=radio][value=replace]', this.domNode)
                .attr('checked', true);
        }
    },

    // clear user's selection
    // can't reset input directly, so we 'reset' the html.
    clearFile: function() {
        // nothing to do if input already clear.
        if (!this.getFileInput().value) {
            return;
        }

        // reset the innerHTML of the file input wrapper.
        var wrapper         = this.getFileInput().parentNode,
            originalHTML    = wrapper.innerHTML;
        wrapper.innerHTML   = originalHTML;

        // configure the new file input.
        this.setupFileInput();

        // trigger file input change event since we
        // changed the value and it won't fire on its own
        p4cms.ui.trigger(this.getFileInput(), 'change');
    },

    getMaxFileSizeLimit: function() {
        var inputs = dojo.query("#MAX_FILE_SIZE", this.domNode);
        if (inputs.length) {
            return inputs[0].value;
        }
    },

    toByteString: function(size) {
        var i, sizes = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        for (i = 0; size >= 1024 && i < 9; i++) {
            size /= 1024;
        }

        return Math.round(size, 2) + sizes[i];
    }
});


// Support in-place editing of file elements.
dojo.subscribe('p4cms.content.element.editModeEnabled', function(element) {
    var formPartial = element.getFormPartial();

    // ignore elements that don't contain a file upload dijit
    var dijits = dojo.query('[dojoType=p4cms.content.FileUpload]', formPartial);
    if (dijits.length !== 1) {
        return;
    }

    // disable file links when edit mode is enabled.
    dojo.forEach(dojo.query('a', element.domNode), function(link) {
        dojo.attr(link, 'onclick', 'return false;');
    });

    // ignore elements that we have already patched for the file upload dijit
    if (element.isPatchedForFile) {
        return;
    }

    // flag element as having been patched
    element.isPatchedForFile = true;

    // re-enable file links when edit mode is disabled.
    dojo.connect(element, 'disableEditMode', function() {
        dojo.forEach(dojo.query('a', element.domNode), function(link) {
            dojo.removeAttr(link, 'onclick');
        });
    });

    // grab our file-upload dijit.
    var fileDijit = dijit.byNode(dijits[0]);

    // remove the default active stack from the file dialog, and instead listen to mouse
    // events on the document to determine if we should use the stack.
    // This fixes an issue where clicking browse caused the dialog to close
    // because it triggers a window blur event on some browsers.
    var dialog = element.getEditDialog();
    dojo.connect(dialog, 'onFocus', function() {
        // change the stack so the focus doesn't change
        var stack = dijit._activeStack;
        dijit._activeStack = [];
        // supposedly we shouldn't use dojo connect for mouseevents on the
        // document, due to the fact that you can control the bubbling of the events
        // from dojo connect (see dijit/_base/focus.js). So we attach the events
        // manually here. Also, using mousedown instead of onclick so we don't lose out to
        // false returns ex: onclick="return false;"
        var doc = dojo.isIE ? window.document.documentElement : window.document;
        var mousedownListener = function(evt) {
            var target = evt.target || evt.srcElement;
            if (target && !dojo.isDescendant(target, dialog.domNode)) {
                // restore the stack
                dijit._activeStack = stack;
                if (dojo.isIE) {
                    doc.detachEvent('onmousedown', mousedownListener);
                } else {
                    doc.removeEventListener('mousedown', mousedownListener, true);
                }
            }
        };
        if (dojo.isIE) {
            doc.attachEvent('onmousedown', mousedownListener);
        } else {
            doc.addEventListener('mousedown', mousedownListener, true);
        }
    });

    // reconnect element form handlers whenever file input is setup
    // (e.g. cleared) and disable validation on blur as it happens
    // as soon as the user clicks browse.
    dojo.connect(fileDijit, 'setupFileInput', function() {
        element.disconnectFormHandlers();
        element.connectFormHandlers();
        dojo.disconnect(element.formHandlers.onBlur);
    });
    dojo.disconnect(element.formHandlers.onBlur);

    // clobber validateField to avoid server-side validation.
    var originalDisplayValue = element.domNode.innerHTML;
    element.validateField = function() {

        // skip validation if we've already checked this value.
        var value = dojo.toJson({
            file:   fileDijit.getFileInput().value,
            action: fileDijit.getFileAction()
        });
        if (element.hasValidated && value === element.validatedValue) {
            return;
        }

        // record that we have validated this value.
        element.validatedValue = value;
        element.hasValidated   = true;

        // validate the field - if errors is false, value is valid.
        var errors = fileDijit.validate();
        var data   = {
            isValid:        errors === false,
            errors:         errors || {},
            displayValue:   ''
        };

        // clear the field if there is an error
        if (!data.isValid) {
            fileDijit.clearFile();
        }

        // set the display value
        if (fileDijit.getFileName()) {
            var state         = data.isValid ? 'pending' : 'invalid';
            var baseName      = fileDijit.getFileName().replace(/.*[\/\\]/, '');
            data.displayValue = '<span class="value-' + state + '">'
                              + baseName
                              + ' (' + state + ')'
                              + '</span>';
        }

        // if keep existing is selected, restore original display value.
        if (fileDijit.getFileAction() === 'keep') {
            data.displayValue = originalDisplayValue;
        }

        // update presentation post validation.
        this.showErrorNotices(data);
        this.updateErrorHighlight(data);
        this.updateFormErrors(data);
        this.updateDisplayValue(data);
    };
});