dojo.provide("p4cms.content.editor.plugins.Link");
dojo.require("dijit._editor.plugins.LinkDialog");
dojo.require("dijit._editor.selection");
dojo.require("dijit._editor.range");
dojo.require("p4cms.content.SelectDialog");
dojo.require("p4cms.ui.Router");

dojo.declare("p4cms.content.editor.plugins.Link",
    dijit._editor.plugins.LinkDialog,
    {
        // controls the toolbar icon, currently uses provided dijit name & prefix
        iconClassPrefix:    'dijitEditorIcon',
        iconName:           'CreateLink',

        // tag used in the output template
        tag:                'a',

        // used as the hover text for the icon in the edit bar, as well as the
        // title of the dialog
        title:              'Create Link',
        updateTitle:        'Update Link',

        // used to define the form identifier in the initButton method.
        // used to fill in the dialogFormTemplate
        // separated for class extendability
        formClass:          "linkForm",

        // used to store the most recent url that we will push to our htmlTemplate
        urlValue:           null,

        // default values used in the dialog form, overridden by child classes
        additionalButtons:  "",
        additionalSources:  "",

        // specify default options for the filter on the browse button's select dialog
        browseOptions:      "''",

        htmlTemplate:       null,

        htmlAttrs:          null,

        selectedText:       "",
        currentDesc:        "",

        // defines the basic form template used in the dialog
        dialogFormTemplate: [
            '<div jsId="{{formIdentifier}}" id="{{formIdentifier}}" ',
            'dojoType = "dijit.form.Form" class="{{formClass}}">',
            '<dl class="zend_form_dojo">',
            '{{content}}',
            '</dl>',
            '</div>'
        ].join(''),

        // Declare the content of the dialog here, instead of going through a MVC structure
        // to keep the plugin contained to this file.
        sourceLegend:           'Link Source',
        sourceContentTemplate:  [
            '<dd class="display-group">',
            '<fieldset class="sourceContainer">',
            '<legend>{{sourceLegend}}</legend>',
            '<input type="hidden" name="contentId" />',
            '<input type="hidden" name="contentUri" />',
            '<dl>',
            '<dt>',
            '<input type="radio" name="contentSource" value="content" />',
            '<label>Content</label>',
            '</dt>',
            '<dd>',
            '<input type="text" name="contentTitle" disabled="disabled" value="No content selected.">',
            '<button dojoType="dijit.form.Button" type="button" class="button-small">',
            'Browse',
            '<script type="dojo/connect" event="onClick">',
            'var dialog = new p4cms.content.SelectDialog();',
            'dialog.browseOptions = {{browseOptions}};',
            'dialog.getSelection().addCallback(function(selection) {',
            'dojo.query("input[name=contentId]").attr("value", selection.id);',
            'dojo.query("input[name=contentUri]").attr("value", selection.uri);',
            'if ( dojo.query("input[name=useTitle]").attr("checked")[0] \n',
            '    || !dojo.query("input[name=description]").attr("value")[0] ){\n',
            'dojo.query("input[name=description]").attr("value", selection.title); \n',
            '}',
            'dojo.query("input[name=contentTitle]").attr("value", selection.title);',
            'dojo.query("input[name=contentSource][value=content]")[0].click();',
            'dojo.publish("{{formIdentifier}}-select-content", [{contentId: selection.id, contentSource: "content"}]);',
            '});',
            '</script>',
            '</button>',
            '{{additionalButtons}}',    // bring in any additional buttons at this point
            '</dd>',
            '<dt>',
            '<input type="radio" name="contentSource" value="external" />',
            '<label>URL</label>',
            '</dt>',
            '<dd>',
            '<input type="text" name="url" />',
            '</dd>',
            '{{additionalSources}}',    // bring in any additional data sources at this point
            '</dl>',
            '</fieldset>',
            '</dd>'
        ].join(''),

        optionContentTemplate: [
            '<dd class="display-group"><fieldset>',
            '<legend>Link Properties</legend>',
            '<dl>',
            '<dt><label>Displayed Text</label></dt>',
            '<dd><input type="text" name="description" />',
            '<br>',
            '<label class="check-label"><input type="checkbox" name="useTitle">Use Content Title</label>',
            '</dd>',
            '<dt class="content-action-label"><label>Action</label></dt>',
            '<dd class="content-action-element">',
            '<select name="contentAction">',
            '<option selected="selected" value="view">Go To Page</option>',
            '<option value="image">View Image</option>',
            '<option value="download">Download File</option>',
            '</select>',
            '</dd>',
            '<dt><label>Open In</label></dt>',
            '<dd>',
            '<select name="targetSelect">',
            '<option selected="selected" value="_self">Current Window</option>',
            '<option value="_blank">New Window</option>',
            '<option value="_top">Top Window</option>',
            '<option value="_parent">Parent Window</option>',
            '</select>',
            '</dd>',
            '<dt><label>CSS Class</label></dt>',
            '<dd><input type="text" name="cssClass" /></dd>',
            '</dl>',
            '</fieldset></dd>'
        ].join(''),

        // list of elements to enable on form submit
        enableElements: ['contentTitle', 'description'],

        _initButton: function() {
            // summary:
            // Adds an icon to the Editor toolbar that when clicked allows for insertion/control
            // Overrides the parent's button creation method.
            this._uniqueId = dijit.getUniqueId(this.editor.id);

            // uniquify the form identifier, in case there are multiple editors on the page
            this.formIdentifier = this._uniqueId + this.formClass;

            this.htmlAttrs = {
                '_djrealurl'    : 'urlInput',
                'target'        : 'targetSelect',
                'class'         : 'cssClass'
            };

            this.button = new dijit.form.Button({
                label:      this.title,
                showLabel:  false,
                iconClass:  this.iconClassPrefix + " " + this.iconClassPrefix + this.iconName,
                tabIndex:   "-1",
                onClick:    dojo.hitch(this, '_launchDialog', '')
            });

            this.connect(this.button.domNode, 'onmousedown', function() {
                this.selectedText = this.fromSelection('getSelectedText');
            });

            this._connectTagEvents();
        },

        // query commands are disabled for this plugin so we need
        // to handle our own button state
        updateState: function() {
            var disabled = this.get("disabled");
            if (this.button) {
                var enabled = !disabled;
                if (this.enabled !== enabled) {
                    this.enabled = enabled;
                    this.button.set('disabled', !enabled);
                }
            }

            this.inherited(arguments);
        },

        _buildContent: function() {
            // stitch content together from templates
            this.dialogContent = dojo.replace(
                this.dialogFormTemplate,
                {
                    formIdentifier:     this.formIdentifier,
                    formClass:          this.formClass,
                    content:            this.sourceContentTemplate + this.optionContentTemplate
                },
                /\{\{([^}]+)\}\}/g
            );

            this.dialogContent = dojo.replace(
                this.dialogContent,
                {
                    formIdentifier:     this.formIdentifier,
                    additionalButtons:  this.additionalButtons,
                    additionalSources:  this.additionalSources,
                    browseOptions:      this.browseOptions,
                    sourceLegend:       this.sourceLegend
                },
                /\{\{([^}]+)\}\}/g
            );

            this.dialogContent = dojo.replace(
                this.dialogContent,
                {
                    formIdentifier:     this.formIdentifier
                },
                /\{\{([^}]+)\}\}/g
            );
        },

        // getTagElement uses code from dojo's LinkDialog's _onOpenDialog method to populate the current data
        // @todo update when dojo is updated, last updated dojo 1.6.1
        getTagElement: function() {
            // If the caret is currently in a URL then populate the URL's info into the dialog.
            var a;
            if (dojo.isIE < 9){
                // IE is difficult to select the element in, using the range unified
                // API seems to work reasonably well.
                var sel     = dijit.range.getSelection(this.editor.window),
                    range   = sel.getRangeAt(0);
                a           = range.endContainer;

                if (a.nodeType === 3) {
                    // Text node, may be the link contents, so check parent.
                    // This plugin doesn't really support nested HTML elements
                    // in the link, it assumes all link content is text.
                    a = a.parentNode;
                }

                if (a && (a.nodeName && a.nodeName.toLowerCase() !== this.tag)) {
                    // Stll nothing, one last thing to try on IE, as it might be 'img'
                    // and thus considered a control.
                    a = this.fromSelection("getSelectedElement", [this.tag]);
                }
            } else {
                a = this.fromSelection("getAncestorElement", [this.tag]);
            }

            // if we are in our target element, select and return the element
            var tag = ((a && a.tagName) ? a.tagName.toLowerCase() : "");
            if (tag === this.tag) {
                this.selectElement(a);
                return a;
            }

            return null;
        },

        _onDblClick: function(e) {
            // summary:
            // Function to define a behavior on double clicks on the element
            // type this dialog edits to select it and pop up the editor
            // dialog.
            // Extended to grab the event target and launch the dialog.
            // e: Object
            // The double-click event.
            if (e && e.target) {
                var t   = e.target;
                var tg  = t.tagName? t.tagName.toLowerCase() : "";
                if(tg === this.tag) {
                    this.selectElement(t);
                    this.editor.onDisplayChanged();
                    setTimeout(dojo.hitch(this, function() {
                        // Focus shift outside the event handler.
                        // IE doesn't like focus changes in event handles.
                        this.button.set("disabled", false);
                        this._launchDialog(t);
                    }), 10);
                }
            }
        },

        // handles firing the selection of the element
        selectElement: function (element) {
            this.fromSelection("selectElement", [element]);
        },

        // Fire off the main dialog which allows the selecting of content
        // current: Object
        //      The currently selected tag object, or null.
        _launchDialog: function(current) {
            current = current || this.getTagElement();

            // ensure the editor doesn't blur; we have to do this early
            // as mearly setting up the dialog can cause trouble
            this.editor.ignoreBlur = true;

            // create a dialog and set it up if not already present
            if (typeof this.dialog === 'undefined') {
                this._buildContent();
                this.dialog = new p4cms.ui.Dialog({
                    content: this.dialogContent,
                    style:   'p4cms-ui',
                    title:   (current) ? this.updateTitle : this.title
                });

                // make dialog buttons
                this.dialog.addButton(this.dialog.insertButton = new dijit.form.Button({
                    "label":    (current) ? "Update" : "Insert",
                    "class":    'preferred',
                    "onClick":  dojo.hitch(this, this._parseForm)
                    })
                );
                this.dialog.addButton(new dijit.form.Button({
                    "label":    "Cancel",
                    "onClick":  dojo.hitch(this.dialog, 'hide')
                }));

                // ensure we re-focus the editor and unflag ignoreBlur when done
                dojo.connect(this.dialog, 'onHide', this, function() {
                    this.editor.focus();
                    this.editor.ignoreBlur = false;
                });

                dojo.query('input[name=url]', this.dialog.domNode).connect(
                    'onfocus',
                    this,
                    function(e)
                    {
                        dojo.query(
                            'input[name=contentSource][value=external]',
                            this.dialog.domNode
                        )[0].click();

                        // if the input contains the default text, select it
                        if (e.target.value === 'http://') {
                            window.setTimeout(
                                function() {
                                    e.target.select();
                                },
                                50
                            );
                        }
                    }
                );

                // track changes in the content and url
                dojo.subscribe(this.formIdentifier + "-select-content", this, 'onContentChange');
                dojo.query('input[name=url]', this.dialog.domNode)
                        .connect('onchange', this, 'formToContentChange');
                dojo.query('input[name=contentSource]', this.dialog.domNode)
                        .connect('onclick', this, 'formToContentChange');
            } else {
                this.dialog.insertButton.set('label', (current ? "Update" : "Insert"));
                this.dialog.set('title', (current ? this.updateTitle : this.title));
            }

            this.resetForm();
            var dialogForm = dojo.byId(this.formIdentifier);
            this._setupForm(dialogForm, current);
            this.formToContentChange();

            // if there is a content id in the form, get the content title from
            // the server and show the dialog; otherwise, show the dialog immediately.
            var contentId = dojo.query('input[name=contentId]', dialogForm)[0];
            if (contentId && (contentId.value !== '')) {
                var url = p4cms.url({
                    module: 'content',
                    action: 'view',
                    format: 'json',
                    id:     contentId.value
                });
                dojo.xhrGet({
                    url:        url + '?fields[]=title',
                    handleAs:   'json',
                    load:       dojo.hitch(this, function(response) {
                        // if we get the title, replace it and show the dialog
                        if (response.fields.title) {
                            this.setContentTitle(dialogForm, response.fields.title);
                        }
                        this.dialog.show();
                    }),
                    error:      dojo.hitch(this, function(){
                        // show the dialog if any error happens
                        this.dialog.show();
                    })
                });
            } else {
                this.dialog.show();
            }
        },

        // sets the content title to the provided value; checks the useTitle checkbox
        setContentTitle: function(dialogForm, title) {
            var contentTitle = dojo.query('input[name=contentTitle]', dialogForm)[0];
            if (contentTitle) {
                dojo.attr(contentTitle, 'value', title);
            }
            if (this.useTitle) {
                dojo.attr(this.useTitle, 'checked', (title === this.currentDesc));
            }
        },

        // resets the form's input states
        resetForm: function() {
            if (!this.useTitle) {
                this.useTitle = dojo.query("input[name=useTitle]", this.dialog.domNode)[0];
                this.connect(this.useTitle, 'onclick', 'titleCheckChange');
            }
            dojo.attr(this.useTitle, 'checked', false);
            this.currentDesc = "";
        },

        // Taken from the setValue function in the dijit editor LinkDialog plugin
        // @todo update when dojo updates
        // Last updated: dojo 1.6.1
        normalizeIESelection: function() {
            var selection   = dijit.range.getSelection(this.editor.window),
                range       = selection.getRangeAt(0),
                container   = range.endContainer;
            if (container.nodeType === 3) {
                // Text node, may be the link contents, so check parent.
                // This plugin doesn't really support nested HTML elements
                // in the link, it assumes all link content is text.
                container = container.parentNode;
            }
            if (container && (container.nodeName && container.nodeName.toLowerCase() !== this.tag)) {
                // Stll nothing, one last thing to try on IE, as it might be 'img'
                // and thus considered a control.
                container = this.fromSelection("getSelectedElement", [this.tag]);
            }
            if (container && (container.nodeName && container.nodeName.toLowerCase() === this.tag)) {
                // Okay, we do have a match.  IE, for some reason, sometimes pastes before
                // instead of removing the targetted paste-over element, so we unlink the
                // old one first.  If we do not the <a> tag remains, but it has no content,
                // so isn't readily visible (but is wrong for the action).
                if(this.editor.queryCommandEnabled("unlink")){
                    // Select all the link childent, then unlink.  The following insert will
                    // then replace the selected text.
                    this.fromSelection("selectElementChildren", [container]);
                    this.editor.execCommand("unlink");
                }
            }
        },

        // returns the result of dijit._editor.selection.[name](args) but scoped
        // to the editor's window
        fromSelection: function(name, args) {
            return dojo.withGlobal(this.editor.window, name, dijit._editor.selection, args);
        },

        // overridden to use createLink instead of insertHTML
        //  innerHTML destroys the formatting, while createLink
        //  preserves it
        setValue: function(args){
            this._onCloseDialog();
            // make sure values are properly escaped, etc.
            args = this._checkValues(args);

            if (dojo.isIE < 9) {
                this.normalizeIESelection();
            }

            // Only webkit can create a link from a collapsed selection
            // for other browsers, create the link ourselves unless
            // content is actually selected
            var sel     = dijit.range.getSelection(this.editor.window),
                range   = sel.getRangeAt(0),
                elem    = this.fromSelection('getSelectedElement'), usePaste;
            if (!dojo.isWebkit && (range.collapsed || (elem && elem.nodeName.toLowerCase() === this.tag))) {
                if(elem) {
                    this.editor.execCommand('delete');
                    range = sel.getRangeAt(0);
                }

                elem = this.editor.document.createElement("a");
                elem.setAttribute('href', args.urlInput);
                elem.innerHTML = args.textInput || args.urlInput;
                // insertNode wasn't added to IE until version 9
                // we will handle IE < 9 using pasteHTML
                if (dojo.isIE < 9) {
                    usePaste = true;
                } else {
                    range.insertNode(elem);
                }
            } else {
                this.editor.execCommand('createlink', args.urlInput);

                // grab the link we just created
                sel         = dijit.range.getSelection(this.editor.window);
                var next    = sel.anchorNode && sel.anchorNode.nextSibling,
                    atEnd   = next && sel.anchorNode.nodeType === 3
                        && sel.anchorOffset === sel.anchorNode.length;
                // dojo doesn't always detect that IE9 has selected the link,
                // even though the range is clearly limited to the link
                // grab the link here if it is in the selection
                // otherwise we should either now have the link or the link
                // text selected
                if (atEnd && next.nodeName.toLowerCase() === this.tag) {
                    elem = next;
                } else {
                    range   = sel.getRangeAt(0);
                    elem    = range.endContainer;
                    if (elem.nodeName === "#text" ) {
                        elem = elem.parentNode;
                    }
                }
            }

            // apply attributes
            var attrs = {}, attr;
            if (elem && elem.nodeName && elem.nodeName.toLowerCase() === this.tag) {
                for (attr in this.htmlAttrs) {
                    if (this.htmlAttrs.hasOwnProperty(attr) && args[this.htmlAttrs[attr]]) {
                        attrs[attr] = args[this.htmlAttrs[attr]];
                    }
                }
                dojo.attr(elem, attrs);

                if (args.textInput) {
                    elem.innerHTML = args.textInput;
                }

                // use the paste method to insert our link for IE
                // when other methods did not work
                if (dojo.isIE && usePaste) {
                    var insertRange = this.editor.document.selection.createRange();
                    insertRange.pasteHTML(elem.outerHTML);
                    insertRange.select();
                } else {
                    this.fromSelection("selectElement", [elem]);
                }
                this.fromSelection("collapse", [false]);
            }
        },

        // grabs the current form and calls an onContentChange
        formToContentChange:  function() {
            var form = dojo.formToObject(this.formIdentifier);
            this.onContentChange(form);
        },

        // handles changing the form structure and setting the url whenever the link source changes
        onContentChange: function(formData) {
            this.urlValue = this.getUrlValue(formData);
            var descNode        = dojo.query("input[name=description]", this.dialog.domNode)[0],
                titleElement    = new dojo.NodeList(this.useTitle).closest('label')[0],
                actionElement   = dojo.query('.content-action-label, .content-action-element', this.domNode);

            if (formData.contentSource === 'content') {
                dojo.attr(this.useTitle,  'disabled', false);
                dojo.style(titleElement,  'display', '');
                actionElement.style(      'display', '');
                dojo.style(actionElement, 'display', '');
                dojo.attr(descNode, 'disabled', dojo.attr(this.useTitle, 'checked'));
            } else {
                dojo.attr(this.useTitle,  'disabled', true);
                dojo.style(titleElement,  'display', 'none');
                actionElement.style(      'display', 'none');
                dojo.attr(descNode, 'disabled', false);
            }
        },

        titleCheckChange: function() {
            var title = dojo.query('input[name=contentTitle]', this.dialog.domNode).attr('value')[0],
                descNode = dojo.query("input[name=description]", this.dialog.domNode)[0];

            if (dojo.attr(this.useTitle, 'checked')) {
                dojo.attr(descNode, {
                    'value':    title,
                    'disabled': true
                });
            } else {
                dojo.attr(descNode, {
                    'value':    this.currentDesc || title,
                    'disabled': false
                });
            }
        },

        _loadFormData: function(current) {
            // if a tag object is selected, load it to the form, taking default values
            // into consideration.
            // if no tag object is selected, load the currently selected text as the
            // link description
            // current: Object
            //      The currently selected tag object, or null.
            var formData = {
                contentTitle:   '',
                contentId:      '',
                contentUri:     '',
                url:            'http://',
                contentSource:  'content',
                cssClass:       '',
                targetSelect:   '_self'
            };

            // if we have a currently selected link, populate formData with
            // the values from the link
            if (current) {
                formData = this._loadCurrent(current, formData);
            }
            // otherwise load the selected text
            else {
                formData.description = this.selectedText;
            }
            this.currentDesc = formData.description;

            return formData;
        },

        _setupForm: function(dialogForm, current) {
            // loads the form data from the current object, then sets the form
            // values from the data.
            // dialogForm:  Object
            //      the form to load
            // current:     Object
            //      the object to load into the form
            var i, identifier, formData = this._loadFormData(current);

            // reset the url value
            this.urlValue = this.getUrlValue(formData);

            // set values to form
            for (identifier in formData) {
                if (formData.hasOwnProperty(identifier)) {
                    var dialogFormFields = dojo.query('input[name=' + identifier + ']', dialogForm);
                    // radio buttons share the same name, so handle them differently
                    if (dialogFormFields.length > 1 && dialogFormFields[0].getAttribute('type') === 'radio') {
                        // loop through available radio elements
                        for (i = 0 ; i < dialogFormFields.length; i++) {
                            var element = dialogFormFields[i];
                            if (element.type === 'radio') {
                                // the value in formData will be the id of the element to check
                                if (element.value === formData[identifier]) {
                                    dojo.attr(element, 'checked', true);
                                }
                                else {
                                    dojo.removeAttr(element, 'checked');
                                }
                            }
                        }
                    }
                    else if (dialogFormFields.length === 1) {    // handle dijits
                        var dijitInput = dijit.getEnclosingWidget(dialogFormFields[0]);
                        if (dijitInput.get('name') === identifier) {
                            dijitInput.set('value', formData[identifier]);
                        }
                        else {                                  // handle text and other inputs
                            dialogFormFields.attr('value', formData[identifier]);
                        }
                    }
                    else {                                      // handle select case
                        dialogFormFields = dojo.query('select[name=' + identifier + ']', dialogForm);
                        if (dialogFormFields.length === 1) {
                            dialogFormFields.attr('value', formData[identifier]);
                        }
                    }
                }
            }

            // re-disable this element (set to false on form submit)
            dojo.query('input[name=contentTitle]', dialogForm).attr('disabled', true);
        },

        // get the url value from the passed data
        getUrlValue: function(data) {
            var url = null;
            if (dojo.isString(data)) {
                url = this.isInvalidUrl(data) ? null : data;
            } else if (dojo.isObject(data)) {
                if (data.contentSource === 'content') {
                    // for the view action, we can use the custom url as its contained in
                    // the selection passed from the datagrid; however, we don't know the
                    // custom url for other actions (if there are any), so we construct
                    // the url via p4cms.url()
                    if (data.contentAction === 'view' && data.contentUri) {
                        url = data.contentUri;
                    } else if (data.contentId !== '' && data.contentAction) {
                        url = this._getContentUri(data.contentId, data.contentAction);
                    }
                } else if (data.contentSource === 'external' && !this.isInvalidUrl(data.url)) {
                    url  = data.url;
                }
            }
            return url;
        },

        // A URL is invalid if it only contains "//", or "abc://"
        // this is because some browsers will not call image.onload or
        // image.onerror if image.src is set to one of these inputs
        isInvalidUrl: function(url) {
            var regex = /^\/\/+$|^[a-z]+:\/\/+$/i;
            return dojo.trim(url).search(regex) >= 0;
        },

        _buildValue: function(formData) {
            // build the value passed to the saveValue method from the submitted form data
            // formData:    Object
            //      The form data to assemble into the value
            var linkData = {
                urlInput:       '',
                targetSelect:   formData.targetSelect,
                textInput:      formData.description,
                cssClass:       formData.cssClass
            };

            // populate the image source
            linkData.urlInput = this.urlValue || '';
            if (formData.contentSource === 'content' && formData.contentId !== '') {
                this.htmlAttrs['data-contentID']     = 'contentId';
                linkData.contentId                   = formData.contentId;
                this.htmlAttrs['data-contentAction'] = 'contentAction';
                linkData.contentAction               = formData.contentAction;
                this.htmlAttrs['data-contentUri']    = 'contentUri';
                linkData.contentUri                  = formData.contentUri;
            }

            return linkData;
        },

        _parseForm: function() {
            // Contains the logic used for parsing the form data into a format
            // that the parent setValue method can use.

            //disabled elements are not returned by formToObject, enable a list of elements
            var i;  // for jslint
            for (i = 0 ; i < this.enableElements.length; i++) {
                var elementName = this.enableElements[i];
                dojo.query('input[name=' + elementName + ']', this.domNode).attr('disabled', false);
            }

            // populate the image attributes from the form
            var formData    = dojo.formToObject(this.formIdentifier);
            this.urlValue   = this.getUrlValue(formData);
            var contentData = this._buildValue(formData);

            // trigger parent setValue, which handles generating and inserting html
            if (contentData.urlInput !== "") {
                this.setValue(contentData);
                return;
            }

            // no valid url for image, alert.
            var notice = new p4cms.ui.Notice({
                message: 'Nothing has been selected to insert.',
                severity: 'error'
            });
        },

        _loadCurrent: function(currentLink, formData) {
            // Populates form elements from the current tag by inspecting the
            // tag object.
            // currentLink      Object
            //      the current link data
            // formData         Object
            //      array of default form data
            formData                =  this._loadCurrentUrl(currentLink, formData, 'href');
            formData.description    =  currentLink.innerHTML;
            formData.cssClass       =  dojo.attr(currentLink, 'class');
            formData.targetSelect   =  dojo.attr(currentLink, 'target');

            return formData;
        },

        _loadCurrentUrl: function(link, data, attribute) {
            // loads a current hyperlink into an object
            // link         String
            //      the string containing the html text
            // data         Object
            //      the object to load the object into
            // attribute    String
            //      the tag that contains the url attribute of the text

            var url       = dojo.attr(link, attribute);
            var contentId = dojo.attr(link, 'data-contentid');
            if (contentId) {
                data.contentId      = contentId;
                data.contentTitle   = new dojo._Url(url).path;
                data.contentSource  = 'content';
                data.contentUri     = dojo.attr(link, 'data-contenturi');
                data.contentAction  = dojo.attr(link, 'data-contentaction');
            } else {
                data.url            = url;
                data.contentSource  = 'external';
            }

            return data;
        },

        _onCloseDialog: function() {
            // summary:
            // Handler for close event on the dialog
            this.dialog.hide();
            this.editor.focus();
        },

        // defines the source url of the link that is being embedded
        _getContentUri: function(id, action) {
            // id must be a string for p4cms.url to function properly
            if (!id) {
                id = '';
            }
            return p4cms.url({module: 'content', action: action || 'view', id: id});
        }
    }
);

dojo.subscribe(dijit._scopeName + ".Editor.getPlugin", null, function(o) {
    if (o.plugin){return;}
    switch (o.args.name){
        case "link":
            o.plugin = new p4cms.content.editor.plugins.Link();
            break;
    }
});
