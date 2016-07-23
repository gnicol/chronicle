dojo.provide("p4cms.content.editor.plugins.Image");
dojo.require("p4cms.content.editor.plugins.Link");
dojo.require("p4cms.content.SelectDialog");
dojo.require("dijit.form.NumberTextBox");

dojo.declare("p4cms.content.editor.plugins.Image",
    p4cms.content.editor.plugins.Link,
    {
        htmlTemplate:       '<img src="${urlInput}" _djrealurl="${urlInput}" ${attribs} ' +
                            'class="${cssClass}" alt="${textInput}" />',
        tag:                'img',
        iconName:           'InsertImage',

        // used to define the form identifier in the initButton method.
        // used to fill in the dialogFormTemplate
        // separated for class extendability
        formClass:          "imageForm",

        // used as the hover text for the icon in the edit bar, as well as the
        // title of the dialog
        title:              'Insert Image',
        updateTitle:        'Update Image',

        // specify default options for the filter on the browse button's select dialog
        browseOptions:      '{"type[types][]": ["Assets/image"]}',

        // adjust source form legend
        sourceLegend:       'Image Source',

        imageStack:         null,

        // Declare the content of the dialog here, instead of going through a MVC structure
        // to keep the plugin contained to this file.
        additionalButtons:  [
            '<button dojoType="dijit.form.Button" type="button" class="button-small add-button">',
            'New Image',
            '<script type="dojo/connect" event="onClick">',
            'var formIdPrefix = dojo.query("form").length;',
            'var dialog = new p4cms.ui.FormDialog({',
            '    title: "New Image", ',
            '    urlParams: {module: "content", ',
            '        action: "form", ',
            '        contentType: "image", ',
            '        formIdPrefix: formIdPrefix',
            '    }, ',
            '    dataFormat: "dojoio"',
            '});',
            'dojo.addClass(dialog.domNode, "content-add-dialog");',
            'dojo.connect(dialog, "onSaveSuccess", function(imageDetail) {',
            'dojo.query("input[name=contentId]").attr("value", imageDetail.contentId);',
            'dojo.query("input[name=contentTitle]").attr("value", imageDetail.contentTitle);',
            'if (imageDetail.alt && imageDetail.alt != "") {',
            'dojo.query("input[name=altText]").attr("value", imageDetail.alt);',
            '}',
            'else if (imageDetail.description && imageDetail.description != "") {',
            'dojo.query("input[name=altText]").attr("value", imageDetail.description);',
            '}',
            'else {',
            'dojo.query("input[name=altText]").attr("value", imageDetail.contentTitle);',
            '}',
            'dojo.query("input[name=contentSource][value=external]").removeAttr("checked");',
            'dojo.query("input[name=contentSource][value=content]").attr("checked", true);',
            'dojo.publish("{{formIdentifier}}-select-content", [{contentId: imageDetail.contentId, contentSource: "content"}]);',
            '});',
            'dialog.show();',
            '</script>',
            '</button>'
        ].join(""),

        optionContentTemplate: [
            "<dd class='display-group'>",
            "<fieldset>",
            "<legend>Image Formatting</legend>",
            "<dl>",
            "<dt>",
            "<label>Alt Text</label>",
            "</dt>",
            "<dd>",
            "<input type='text' name='altText' />",
            "</dd>",
            "<dt>",
            "<label>Size</label>",
            "</dt>",
            "<dd>",
            "<select name='sizeType'>",
            "<option value='full'>Full Size</option>",
            "<option value='custom'>Custom Size</option>",
            "</select>",
            "</dd>",
            "<dt>",
            "<label>Width</label>",
            "</dt>",
            "<dd>",
            "<input type='text' disabled='true' name='width' /><span class='units'>pixels</span>",
            "</dd>",
            "<dt>",
            "<label>Height</label>",
            "</dt>",
            "<dd>",
            "<input type='text' disabled='true' name='height' /><span class='units'>pixels</span>",
            "<br />",
            "<label for='lockRatio'>",
            "<input type='checkbox' name='lockRatio' disabled='true' />",
            "<span class='check-label'>Scale Proportionally</span>",
            "</label>",
            "</dd>",
            "<dt>",
            "<label>Margin</label>",
            "</dt>",
            "<dd>",
            "<input type='text' name='margin' /><span class='units'>pixels</span>",
            "</dd>",
            "<dt>",
            "<label>CSS Class</label>",
            "</dt>",
            "<dd>",
            "<input type='text' name='cssClass' />",
            "</dd>",
            "<dt>",
            "<label>Alignment</label>",
            "</dt>",
            "<dd class='alignment-element'>",
            "<ul>",
            "<li>",
            "<label>",
            "<img src='{{IMG_BASE_URL}}icon-align-none.png' alt='icon'/>",
            "<br />",
            "<input type='radio' name='align' value='none'/>",
            "None",
            "</label>",
            "</li>",
            "<li>",
            "<label>",
            "<img src='{{IMG_BASE_URL}}icon-align-left.png' alt='icon'/>",
            "<br />",
            "<input type='radio' name='align' value='left'/>",
            "Left",
            "</label>",
            "</li>",
            "<li>",
            "<label>",
            "<img src='{{IMG_BASE_URL}}icon-align-center.png' alt='icon'/>",
            "<br />",
            "<input type='radio' name='align' value='center'/>",
            "Center",
            "</label>",
            "</li>",
            "<li>",
            "<label>",
            "<img src='{{IMG_BASE_URL}}icon-align-right.png' alt='icon'/>",
            "<br />",
            "<input type='radio' name='align' value='right'/>",
            "Right",
            "</label>",
            "</li>",
            "</ul>",
            "</dd>",
            "</dl>",
            "</fieldset>",
            "</dd>"
        ].join(""),

        // to ensure image size attributes are available for further use (tableau theme, for example), ensure
        // they're enabled
        enableElements: ['contentTitle', 'description', 'width', 'height'],

        _buildContent: function() {
            // bring in alingment urls
            var imgBaseUrl = p4cms.baseUrl + '/application/content/resources/images/';
            this.optionContentTemplate = dojo.replace(
                this.optionContentTemplate,
                {
                    IMG_BASE_URL:       imgBaseUrl,
                    formIdentifier:     this.formIdentifier
                },
                /\{\{([^}]+)\}\}/g
            );

            this.inherited(arguments);
        },

        /**
         * Contains the logic used for parsing the form data into a format
         * that the setValue method can use.
         */
        _buildValue: function(formData) {
            // populate the image attributes from the form
            var imageData = {urlInput: '', attribs: '', textInput: '', cssClass: ''};

            // populate the image source
            imageData.urlInput = this.urlValue || '';
            if (formData.contentSource === 'content' && formData.contentId !== '') {
                imageData.attribs   = 'data-contentId="' + formData.contentId + '" ';
            }

            // set the alt text
            imageData.textInput = formData.altText;

            // set width/height
            var urlParams = {};
            var width     = parseInt(formData.width, 10),
                height    = parseInt(formData.height, 10);
            if (!isNaN(width)) {
                imageData.attribs += 'width="'  + width  + '" ';
                urlParams.width    = width;
            }
            if (!isNaN(height)) {
                imageData.attribs += 'height="' + height + '" ';
                urlParams.height   = height;
            }

            // append urlParams to the image url query string
            var urlParamsQuery = dojo.objectToQuery(urlParams);
            if (urlParamsQuery) {
                imageData.urlInput += new dojo._Url(imageData.urlInput).query
                    ? '&' + urlParamsQuery
                    : '?' + urlParamsQuery;
            }

            // set the css class
            imageData.cssClass = formData.cssClass;

            // handle alignment and margin
            var margin      = parseInt(formData.margin, 10),
                marginStyle = '';
            if (!isNaN(margin)) {
                marginStyle = 'margin: ' + margin + 'px;';
            }
            switch (formData.align)
            {
                case 'none':
                    imageData.attribs += 'style="display: block; ' + marginStyle + '"';
                    break;
                case 'left':
                    imageData.attribs += 'align="left" style="'    + marginStyle + '"';
                    break;
                case 'right':
                    imageData.attribs += 'align="right" style="'   + marginStyle + '"';
                    break;
                case 'center':
                    marginStyle = 'margin-left: auto; margin-right: auto;';
                    if (!isNaN(margin)) {
                        marginStyle += 'margin-top: '    + margin + 'px;'
                                    +  'margin-bottom: ' + margin + 'px;';
                    }
                    imageData.attribs += 'style="float: none; display: block; ' +  marginStyle + '"';
                    break;
            }

            return imageData;
        },

        _launchDialog: function(current) {
            current = current || this.getTagElement();
            // Extends parent to connect custom events which prevent the user from entering
            // non-numeric characters for height, width, and margin fields.

            var newDialog = (typeof this.dialog === 'undefined');

            // reset deferredSize so it always starts null
            this.deferredSize = null;
            this.imageStack = [];

            this.inherited(arguments);

            if (newDialog) {
                dojo.query('input[name=width],input[name=height],input[name=margin]', this.dialog.domNode)
                    .connect(
                        'onkeypress',
                        function(event) {
                            // only allow numeric and control keys
                            if ( event.charCode > 57 ) {
                                event.preventDefault();
                            }
                        }
                    );

                // handlers for image size input changes
                this.connect(this.sizeTypeSelect, 'onchange', 'onSizeTypeChange');
                this.connect(this.lockRatio, 'onclick', 'ratioCheckChange');
                this.connect(this.widthBox, 'onkeypress',
                        dojo.partial(this.onDeferredRatioChange, this.widthBox, this.heightBox));
                this.connect(this.heightBox, 'onkeypress',
                        dojo.partial(this.onDeferredRatioChange, this.heightBox, this.widthBox));
            }
        },

        // overridden to reset the form state
        resetForm: function() {
            // setup commonly used fields
            this.widthBox           = dojo.query("input[name=width]", this.dialog.domNode)[0];
            this.heightBox          = dojo.query("input[name=height]", this.dialog.domNode)[0];
            this.lockRatio          = dojo.query('input[name=lockRatio]', this.dialog.domNode)[0];
            this.sizeTypeSelect     = dojo.query('select[name=sizeType]', this.dialog.domNode)[0];

            // reset input states
            dojo.attr(this.lockRatio, 'checked', false);
            dojo.attr(this.sizeTypeSelect, 'disabled', false);
        },

        // extend setupForm to reset our Ratio handling
        _setupForm: function() {
            this.inherited(arguments);

            // setup Size state
            this.detectSizeType();
        },

        _loadFormData: function(current) {
            var formData = {
                contentTitle:   '',
                contentId:      '',
                altText:        '',
                url:            'http://',
                margin:         '2',
                height:         '',
                width:          '',
                cssClass:       '',
                contentSource:  'content',
                align:          'none'
            };

            // if we have a currently selected image, populate formData with
            // the values from the image
            if (current) {
                formData = this._loadCurrent(current, formData);
            }

            return formData;
        },

        /**
         * Populates form elements from the current image by inspecting the
         * image object.
         * @param  currentImage     the current image
         * @param  formData         array of default form data
         * @return array formData   array of form data with non-defaults overridden
         */
        _loadCurrent: function(currentImage, formData) {
            formData = this._loadCurrentUrl(currentImage, formData, 'src');

            // pull out style so we can default to empty string instead of null
            var style = dojo.attr(currentImage, 'style') || '';

            // IE7 returns an object, get the string
            if (dojo.isObject(style)) {
                style = style.cssText;
            }

            // center alignment is identified by the presence of the auto align in margin-left or
            // "margin: XXpx auto;"
            if (dojo.attr(currentImage, 'align') === 'left') {
                formData.align  = 'left';
            } else if (dojo.attr(currentImage, 'align') === 'right') {
                formData.align  = 'right';
            } else if (style.match(/margin(-left)?:.*auto/i)) {
                formData.align  = 'center';
            } else {
                formData.align  = 'none';
            }

            if (style.match(/margin(-top)?:/i)) {
                formData.margin = dojo.style(currentImage, 'marginTop');
            } else {
                formData.margin = '';
            }

            formData.altText    = dojo.attr(currentImage, 'alt');

            var height          = dojo.attr(currentImage, 'height');
            if (height) {
                formData.height = parseInt(height, 10);
            }

            var width           = dojo.attr(currentImage, 'width');
            if (width) {
                formData.width  = parseInt(width, 10);
            }

            formData.cssClass   = dojo.attr(currentImage, 'class');

            return formData;
        },

        // defines the source url of the image that is being embedded
        _getContentUri: function(id) {
            if(!id) {
                id = '';
            }
            return p4cms.url({module: 'content', action: 'image', id: id});
        },

        // get the url value from the passed data
        getUrlValue: function(data) {
            var url = null;
            if (dojo.isString(data)) {
                url = this.isInvalidUrl(data) ? null : data;
            } else if (dojo.isObject(data)) {
                if (data.contentSource === 'content' && data.contentId !== '') {
                    url  = this._getContentUri(data.contentId);
                } else if (data.contentSource === 'external' && !this.isInvalidUrl(data.url)) {
                    url  = data.url;
                }
            }
            return url;
        },

        // triggers a image size check on the new image,
        // once we have the new image size, update the form
        onContentChange: function(contentObj) {
            var uri             = this.getUrlValue(contentObj),
                urlHasChanged   = this.urlValue !== uri;

            if (uri) {
                var dimensions  = new dojo.NodeList(
                    this.widthBox, this.heightBox, this.sizeTypeSelect, this.lockRatio
                );

                // disable dimension fields if we aren't already in flight
                var disabledList;
                if (!this.isInFlight(this.urlValue)) {
                    disabledList = dimensions.attr('disabled');
                    dimensions.attr('disabled', true);
                }

                var deferred = this.getDeferredImageSize(uri);
                deferred.addCallback(dojo.hitch(this, function(result) {
                    var width       = dojo.attr(this.widthBox, 'value'),
                        height      = dojo.attr(this.heightBox, 'value');

                    // if the url has changed or we are in fullSize mode,
                    //   force update height and width
                    // else only update blank fields
                    if (urlHasChanged || dojo.attr(this.sizeTypeSelect, 'value') === 'full') {
                        dojo.attr(this.widthBox, 'value', result.w);
                        dojo.attr(this.heightBox, 'value', result.h);
                    } else {
                        // if both are blank, update both
                        // else if size is valid ratio, enable ratio
                        if (!width && !height) {
                            dojo.attr(this.heightBox, 'value', result.h);
                            dojo.attr(this.widthBox, 'value', result.w);
                        } else if (this.isProportional(result, {w:parseInt(width, 10),
                                h:parseInt(height, 10)})) {
                            dojo.attr(this.lockRatio, 'checked', true);
                            this.ratioCheckChange();
                        }
                    }

                    // if the url has changed, and we are not in full size mode, activate ratio
                    if (urlHasChanged && dojo.attr(this.sizeTypeSelect, 'value') !== 'full') {
                        dojo.attr(this.lockRatio, 'checked', true);
                        this.ratioCheckChange();
                    }
                }));

                // renable dimenion fields when deferred is finished (error or success)
                deferred.addBoth(dojo.hitch(this, function() {
                    if (disabledList) {
                        dojo.forEach(dimensions, function(item, index) {
                            dojo.attr(item, 'disabled', disabledList[index]);
                        });
                    }
                }));
            }
            this.urlValue = uri;
        },

        // determines if the currentSize is a valid ratio of the original
        isProportional: function(original, current) {
            // return false if input is not valid
            if (isNaN(current.w) || isNaN(current.h)
                    || current.w === 0 || current.h === 0) {
                return false;
            }

            // calculate the ratio equation both ways because we don't know
            // which one it was applied on previously and rounding could have
            // warped the value
            var height   = this.applyRatio(original.h, original.w, current.w),
                width    = this.applyRatio(original.w, original.h, current.h);

            return (width === current.w || height === current.h);
        },

        // takes the ratio between consequent and antecedent, then returns
        // the result of applying that against the input
        applyRatio: function(input, consequent, antecedent) {
            var ratio  = antecedent / consequent;
            return Math.round(ratio * input);
        },

        isInFlight: function (uri) {
            return this.imageStack[uri] === false;
        },

        // returns a deferred result object that will update with the image's
        // width and height when they have been loaded
        getDeferredImageSize: function(uri) {
            // if the image requested is already in flight, return
            // else if some other request is in flight, cancel it
            if (this.urlValue === uri && this.isInFlight(uri)) {
                return this.deferredSize;
            } else if (this.deferredSize && this.deferredSize.imageUrl !== uri
                    && this.isInFlight(uri)) {
                this.deferredSize.cancel();
                delete this.imageStack[this.deferredSize.imageUrl];
            }

            this.deferredSize           = new dojo.Deferred();
            this.deferredSize.imageUrl  = uri;

            // if we already have the size on the stack, do the callback now
            // else we need to add it and grab its size
            if (this.imageStack[uri]) {
                this.deferredSize.callback(this.imageStack[uri]);
            } else {
                this.imageStack[uri] = false;
                var img              = new Image(),
                    deferred         = this.deferredSize;

                img.onload           = dojo.hitch(this, function() {
                    // check to make sure it's still in flight
                    if (this.isInFlight(uri)) {
                        this.imageStack[uri] = {
                            uri:    uri,
                            w:      img.width,
                            h:      img.height
                        };
                        deferred.callback(this.imageStack[uri]);
                    }
                });
                // clean up on error
                img.onerror          = dojo.hitch(this, function() {
                    deferred.reject("Image failed to load");
                    if (this.isInFlight(uri)) {
                        delete this.imageStack[uri];
                    }
                });

                img.src              = uri;
            }
            return this.deferredSize;
        },

        // Handles programatically called ratio change
        ratioCheckChange: function() {
            // if ratio is checked, run a ratio change
            if (dojo.attr(this.lockRatio, 'checked')) {
                this.onRatioChange(this.widthBox, this.heightBox);
            }
        },

        // Call ratio change after a small timeout
        onDeferredRatioChange: function(fromBox, toBox) {
            setTimeout(dojo.hitch(this, 'onRatioChange', fromBox, toBox), 1);
        },

        // Handle ratio changes using the ratio with the fromBox to update the
        // value of the toBox
        onRatioChange: function(fromBox, toBox) {
            if (dojo.attr(this.lockRatio, 'checked')) {
                var original    = this.imageStack[this.urlValue],
                    value       = dojo.attr(fromBox, 'value');

                // if our value is valid, and the image size request isn't still in flight,
                // calculate ratio
                if (!this.isInFlight(this.urlValue) && value && !isNaN(value)) {
                    value       = parseInt(value, 10);
                    var isWidth = dojo.attr(fromBox, 'name') === 'width',
                        output  = this.applyRatio(
                            value,
                            (isWidth ? original.w : original.h),
                            (isWidth ? original.h : original.w)
                        );

                    dojo.attr(toBox, 'value', output);
                }
            }
        },

        // Handle changes to the sizeType drop down
        onSizeTypeChange: function() {
            var value       = dojo.attr(this.sizeTypeSelect, 'value'),
                dimensions  = new dojo.NodeList(this.widthBox, this.heightBox, this.lockRatio);

            dimensions.attr('disabled', value === 'full');

            if (value === 'full') {
                dojo.attr(this.lockRatio, 'checked', true);
                this.ratioCheckChange();

                // if we already have the image, update it's size
                // else go grab it
                var size = this.imageStack[this.urlValue];
                if (size) {
                    dojo.attr(this.heightBox, 'value', size.h);
                    dojo.attr(this.widthBox, 'value', size.w);
                } else {
                    this.formToContentChange();
                }
            }
        },

        // uses the values in the dimension fields to determine
        // what the sizeType should be
        detectSizeType: function() {
            var hasSize = (dojo.attr(this.widthBox, 'value')
                    || dojo.attr(this.heightBox, 'value'));
            // if not height or width defined, we are in full size mode
            // else assume custom
            dojo.attr(this.sizeTypeSelect, 'value', hasSize ? 'custom' : 'full');

            this.onSizeTypeChange();
        },

        // overriden to set the function back to the parent function
        setValue: dijit._editor.plugins.LinkDialog.prototype.setValue,

        _connectTagEvents: function() {
            this.inherited(arguments);
            this.editor.onLoadDeferred.addCallback(dojo.hitch(this, function() {
                // Use onmousedown instead of onclick.  Seems that IE eats the first onclick
                // to wrap it in a selector box, then the second one acts as onclick.  See dojo #10420
                this.connect(this.editor.editNode, "onmousedown", this._selectTag);
            }));
        },

        _selectTag: function(e) {
            if (e && e.target) {
                var t   = e.target;
                var tg  = t.tagName? t.tagName.toLowerCase() : "";
                if (tg === this.tag) {
                    this.selectElement(t);
                }
            }
        },

        // override getTagElement to refine a users selection to the
        // first selected node that matches our tag
        getTagElement: function() {
            var selection, type;
            try {
                selection   = this.editor.window.getSelection();
                type        = dojo.withGlobal(this.editor.window, "getType",
                                dijit._editor.selection, [this.tag]);
            } catch(e) { /* no selection */ }

            // if our selection is a control, loop through all the anchorNode's
            // children looking for a match
            if (selection && type === 'control') {
                var i,
                    childNodes  = selection.anchorNode.childNodes,
                    range       = selection.getRangeAt(0);
                for (i = range.startOffset; (i < childNodes.length && i < range.endOffset); i++) {
                    var node = selection.anchorNode.childNodes[i];
                    if (node.tagName && node.tagName.toLowerCase() === this.tag) {
                        this.selectElement(node);
                        return node;
                    }
                }
            }

            return this.inherited(arguments);
        },

        // Override to handle image selection
        selectElement: function (element) {
            // Fix for webkit browsers image selection
            // https://bugs.webkit.org/show_bug.cgi?id=12250
            if (dojo.isWebKit) {
                element         = dojo.byId(element);
                var selection   = this.editor.window.getSelection();

                selection.setBaseAndExtent(element, 0, element, 1);
            } else {
                this.inherited(arguments);
            }
        }
    }
);

dojo.subscribe(dijit._scopeName + ".Editor.getPlugin", null, function(o) {
    if (o.plugin) { return; }
    switch (o.args.name) {
        case "image":
            o.plugin = new p4cms.content.editor.plugins.Image();
            break;
    }
});