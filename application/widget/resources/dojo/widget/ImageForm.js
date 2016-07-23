// summary:
//      This dijit works particularly in symbio with 'Widget_Form_ImageWidget' form,
//      where it adds the following features/enhancements:
//       - adds buttons to select image from the content and to upload
//         a new image
//       - moves imageSource radio buttons beside image source elements
//         (content/url)
//       - autoloads image dimensions when image source is changed
//       - dynamically updates image width or height if the other one
//         is changed
//         according to the aspect ration of the original size
//       - adds classes to some elements to allow styling via css
//         (imageSource radio, alignment radio).

dojo.provide("p4cms.widget.ImageForm");
dojo.require("dojox.layout.ContentPane");
dojo.require("dijit.form.Button");
dojo.require("p4cms.content.SelectDialog");
dojo.require("p4cms.ui.FormDialog");
dojo.require("p4cms.ui.Notice");

dojo.declare("p4cms.widget.ImageForm", [dojox.layout.ContentPane], {
    _deferredSize:      null,
    _imageStack:        [],
    _urlValue:          null,
    _browseButton:      null,
    _newImageButton:    null,

    postCreate: function() {
        // adjust some form elements (add classes/properties, move them in dom etc.)
        this._initFormElements();

        // allow only numeric input in width, height and margin fields
        dojo.query('input[name*=imageWidth], input[name*=imageHeight], input[name*=margin]', this.domNode)
            .connect('onkeypress', function(event) {
                // only allow numeric and control keys
                if (event.charCode > 57) {
                    event.preventDefault();
                }
            }
        );

        // connect to update width/height when full size is selected
        dojo.query("select[name*=sizeType]", this.domNode)
            .connect('onchange', dojo.hitch(this, function(e){
                this._updateWidthHeightReadOnly(e.target.value === 'full');
                if (e.target.value === 'full') {
                    this._setImageDimensions();
                }
            })
        );

        // connect to update dimensions when one is changed (according to lockRatio)
        // or when lockRatio is checked
        dojo.query("input[name*=imageWidth]", this.domNode)
            .connect('onkeyup', dojo.hitch(this, function(e){
                this._updateDimensions('width');
            })
        );
        dojo.query("input[name*=imageHeight]", this.domNode)
            .connect('onkeyup', dojo.hitch(this, function(e){
                this._updateDimensions('height');
            })
        );
        dojo.query("[type=checkbox][name*=lockRatio]", this.domNode)
            .connect('onchange', dojo.hitch(this, function(e){
                if (e.target.checked) {
                    this._updateDimensions();
                }
            })
        );

        // add buttons to select image from existing content and to add a new image
        var contentField = dojo.query('input[name*=contentTitle]', this.domNode);
        if (contentField.length) {
            var browseButton   = this._getBrowseButton();
            var newImageButton = this._getNewImageButton();

            dojo.place(browseButton.domNode,    contentField[0],        'after');
            dojo.place(newImageButton.domNode,  browseButton.domNode,   'after');

            // make content title element read only
            this._setAttr('contentTitle',  'readOnly', true);
        }

        // populate image width/height
        if (!this.getValue('imageWidth') && !this.getValue('imageWeight')) {
            this._setImageDimensions();
        }
    },

    // helper method to get value of the given form element
    getValue:  function (elementName) {
        return this._getAttr(elementName, 'value');
    },

    // helper method to set value on the given form element
    setValue:  function (elementName, value) {
        return this._setAttr(elementName, 'value', value);
    },

    // called when image source has changed
    onImageChange: function() {
        this._setImageDimensions();
    },

    // initialize/tweak some form elements
    _initFormElements: function() {
        // set with/height elements as read-only
        this._updateWidthHeightReadOnly();

        // attach radio buttons to the image source inputs
        this._moveSourceRadio();

        // attach dimansion units to the form elements related to dimensions/size
        this._attachElementUnits();

        // add class to dd element wrapping lock ratio radio
        var lockRatioElement = dojo.query('input[type=checkbox][name*=lockRatio]', this.domNode);
        if (lockRatioElement.length) {
            dojo.addClass(lockRatioElement[0].parentNode, 'lock-ratio-radio');
        }

        // add class to dd element wrapping as background radio
        var asBgElement = dojo.query('input[type=checkbox][name*=asBackground]', this.domNode);
        if (asBgElement.length) {
            dojo.addClass(asBgElement[0].parentNode, 'as-background-radio');
        }

        // tweak alignment radio element to make it styleable via css:
        //  - add 'align-<ALIGN>' class to the each radio input,
        //    where <ALIGN> is capitalized value of the input element
        //    (i.e. left, right, center etc.)
        //  - add 'alignment-radio' class to the wrapping dd element
        //  - remove all <br> elements inside the wrapping dd element
        var alignmentRadio = dojo.query('input[type=radio][name*=alignment]', this.domNode);
        dojo.forEach(alignmentRadio, function(inputElement) {
            if (inputElement.parentNode) {
                dojo.addClass(
                    inputElement.parentNode,
                    'align-' + inputElement.value
                );
            }
        });
        if (alignmentRadio.length) {
            var ddElement = alignmentRadio[0].parentNode.parentNode;
            if (ddElement) {
                dojo.addClass(ddElement, 'alignment-radio');
                dojo.forEach(dojo.query('br', ddElement), function (brElement){
                    dojo.destroy(brElement);
                });
            }
        }
    },

    // set given image data on the associated form elements
    _setImageContent: function (imageEntryData) {
        // set content id and content title
        var contentId    = imageEntryData.contentId    || '';
        var contentTitle = imageEntryData.contentTitle || '';

        this.setValue('contentId',    contentId);
        this.setValue('contentTitle', contentTitle);

        // call onImageChange to update image dimensions
        this.onImageChange();
        return;
    },

    // populate height/width elements with the original image size
    _setImageDimensions: function() {
        // early exit if we don't have image uri
        if (!this._getImageUri()) {
            return;
        }

        // populate width/height elements with original image size
        this._getImageSize().then(
            dojo.hitch(this, function(data) {
                this.setValue('imageWidth',  data.width);
                this.setValue('imageHeight', data.height);
                dojo.query("input[name*=lockRatio]", this.domNode).attr("checked", true);
            }),
            dojo.hitch(this, function(error){
                // error callback - clear width/height if we are unable to detect image size
                this.setValue('imageHeight', '');
                this.setValue('imageWidth',  '');

                var message = new p4cms.ui.Notice({
                    message:    "Unable to determine image size: " + (error.message || 'Unknown error.'),
                    severity:   "error"
                });
            })
        );
    },

    // return image uri - either internal (i.e. pointing to the content entry) or remote url
    _getImageUri: function () {
        var isLocal  = dojo.attr(
            dojo.query('input[name*=imageSource][value=content]', this.domNode)[0], 'checked'
        ),
            isRemote = dojo.attr(
            dojo.query('input[name*=imageSource][value=remote]', this.domNode)[0],  'checked'
        );

        if (isLocal && this.getValue('contentId')) {
            return p4cms.url({
                module: 'content',
                action: 'image',
                id:     this.getValue('contentId')
            });
        } else if (isRemote && this.getValue('imageUrl')) {
            return this.getValue('imageUrl');
        }

        // cannot determine image uri
        return null;
    },

    // compute width or height according to the aspect ratio of the priginal image
    // when the other dimension has changed and lockRatio is true
    _updateDimensions: function (referenceDimension) {
        // if ratio is not locked, nothing to do
        if (!this._isRatioLocked()) {
            return;
        }

        var width  = parseInt(this.getValue('imageWidth'),  10),
            height = parseInt(this.getValue('imageHeight'), 10);

        // set default reference dimension if not provided
        referenceDimension = referenceDimension || 'width';

        // if the value of referenced dimension is empty, clear the other one and return
        if (referenceDimension === 'width' && !width) {
            this.setValue('imageHeight', '');
            return;
        } else if (referenceDimension === 'height' && !height) {
            this.setValue('imageWidth', '');
            return;
        }

        // update width/height to keep same aspect ratio as the original
        this._getImageSize().then(
            dojo.hitch(this, function(data){
                // calculate ratio of the original image and exit if ratio is not a real number
                var ratio = data.width / data.height || null;
                if (!ratio) {
                    return;
                }

                if (referenceDimension === 'width') {
                    this.setValue('imageHeight', Math.round(width / ratio) || '');
                } else {
                    this.setValue('imageWidth',  Math.round(height * ratio) || '');
                }
            })
        );
    },

    // move image source radio buttons beside the image source input elements
    _moveSourceRadio: function () {
        // attach image source radio buttons to the content/url input fields
        var contentLabel = dojo.query('label[for*=contentTitle]',                this.domNode);
        var urlLabel     = dojo.query('label[for*=imageUrl]',                    this.domNode);
        var contentRadio = dojo.query('input[name*=imageSource][value=content]', this.domNode);
        var urlRadio     = dojo.query('input[name*=imageSource][value=remote]',  this.domNode);

        if (!contentLabel.length || !contentRadio.length || !urlLabel.length || !urlRadio.length) {
            return;
        }
        dojo.place(contentRadio[0], contentLabel[0], 'before');
        dojo.place(urlRadio[0],     urlLabel[0],     'before');

        // add classes to radio buttons to allow styling via css
        dojo.forEach([contentRadio[0], urlRadio[0]], function (node) {
            dojo.addClass(node, 'image-source-radio');
        });

        // remove original radio element labels
        dojo.destroy(dojo.query('dt[id*=imageSource-label]',   this.domNode)[0]);
        dojo.destroy(dojo.query('dd[id*=imageSource-element]', this.domNode)[0]);

        // connect to select radio when attached input field is changed
        var selectContentRadio = dojo.hitch(this, function() {
            dojo.query('input[name*=imageSource][value=content]', this.domNode)[0].click();
        });
        dojo.connect(this._getBrowseButton().domNode,   'onclick', selectContentRadio);
        dojo.connect(this._getNewImageButton().domNode, 'onclick', selectContentRadio);

        dojo.query('input[name*=imageUrl]', this.domNode).connect(
            'onfocus',
            dojo.hitch(this, function(){
                urlRadio[0].click();
            }
        ));

        // update form when image source or external image url are changed
        contentRadio.connect('onchange', this, 'onImageChange');
        urlRadio.connect(    'onchange', this, 'onImageChange');
        dojo.query('input[name*=imageUrl]', this.domNode).connect('onchange', this, 'onImageChange');
    },

    // attach 'pixels' text to width/height/margin element
    _attachElementUnits: function() {
        dojo.forEach(
            dojo.query('input[name*=imageWidth], input[name*=imageHeight], input[name*=margin]', this.domNode),
            function(element){
                dojo.create('span', {innerHTML: 'pixels', 'class': 'units-label'}, element, 'after');
            }
        );
    },

    // return reference to the content browse button (create the button if necessary)
    _getBrowseButton: function() {
        if (!this._browseButton) {
            this._browseButton = new dijit.form.Button({
                'label':    'Browse',
                'class':    'button-small content-source-button',
                'onClick':  dojo.hitch(this, function() {
                    var dialog = new p4cms.content.SelectDialog({
                        browseOptions:  {"type[types][]": ["Assets/image"]}
                    });

                    dialog.getSelection().then(dojo.hitch(this, function(selection) {
                        this._setImageContent({
                            contentId:      selection.id,
                            contentTitle:   selection.title
                        });
                    }));
                })
            });
        }

        return this._browseButton;
    },

    // return reference to the new image button (create the button if necessary)
    _getNewImageButton: function() {
        if (!this._newImageButton) {
            this._newImageButton = new dijit.form.Button({
                'label':    'New Image',
                'class':    'button-small add-button content-source-button',
                'onClick':  dojo.hitch(this, function() {
                    var formIdPrefix = dojo.query("form").length;
                    var dialog = new p4cms.ui.FormDialog({
                        'class':        'content-add-dialog',
                        'title':        'New Image',
                        'urlParams':    {
                            module:         'content',
                            action:         'form',
                            contentType:    'image',
                            formIdPrefix:   formIdPrefix
                        },
                        'dataFormat':   'dojoio'
                    });
                    dojo.connect(dialog, "onSaveSuccess", dojo.hitch(this, function(data) {
                        if (data.isValid) {
                            this._setImageContent(data);
                        }
                    }));
                    dialog.show();
                })
            });
        }

        return this._newImageButton;
    },

    // returns a deferred result object that will update with the image's
    // width and height when they have been loaded
    _getImageSize: function() {
        var uri = this._getImageUri();

        // if the image requested is already in flight, return
        // else if some other request is in flight, cancel it
        if (this._urlValue === uri && this._isInFlight(uri)) {
            return this._deferredSize;
        } else if (this._deferredSize
            && this._deferredSize.imageUrl !== uri
            && this._isInFlight(uri)
        ) {
            this._deferredSize.cancel();
            delete this._imageStack[this._deferredSize.imageUrl];
        }

        this._deferredSize           = new dojo.Deferred();
        this._deferredSize.imageUrl  = uri;
        this._urlValue               = uri;

        // if the uri is empty, return blank data, otherwise
        // if we already have the size on the stack, do the callback now
        // else we need to add it and grab its size
        if (!uri) {
            this._deferredSize.callback({
                width:  null,
                height: null
            });
        }
        else if (this._imageStack[uri]) {
            this._deferredSize.callback(this._imageStack[uri]);
        } else {
            this._imageStack[uri] = false;
            var img               = new Image(),
                deferred          = this._deferredSize;

            img.onload            = dojo.hitch(this, function() {
                // check to make sure it's still in flight
                if (this._isInFlight(uri)) {
                    this._imageStack[uri] = {
                        width:  img.width,
                        height: img.height
                    };
                    deferred.callback(this._imageStack[uri]);
                }
            });
            // clean up on error
            img.onerror          = dojo.hitch(this, function() {
                if (this._isInFlight(uri)) {
                    delete this._imageStack[uri];
                }
                deferred.errback(new Error("Image failed to load."));
            });

            img.src = uri;
        }

        return this._deferredSize;
    },

    _isInFlight: function (uri) {
        return this._imageStack[uri] === false;
    },

    // make width and height elements read-only according to sizeType option or if forced
    // by the param
    _updateWidthHeightReadOnly: function(locked) {
        // get value from the sizeType element value if value is not provided in the param
        locked = locked || dojo.query("select[name*=sizeType]", this.domNode)[0].value === 'full';

        this._setAttr('imageWidth',  'readOnly', locked);
        this._setAttr('imageHeight', 'readOnly', locked);
    },

    // helper function - get specified attribute of the given element
    _getAttr:   function (elementName, attr) {
        var element = dojo.query('[name*=' + elementName + ']', this.domNode);
        return element.length
            ? dojo.attr(element[0], attr)
            : '';
    },

    // helper function - set specified attribute of the given element
    _setAttr:   function (elementName, attr, value) {
        var element = dojo.query('[name*=' + elementName + ']', this.domNode);
        if (element.length) {
            dojo.attr(element[0], attr, value);
        }
    },

    // helper function - return true if dimensions ratio is locked, false otherwise
    _isRatioLocked:  function() {
        var checkbox = dojo.query('[type=checkbox][name*=lockRatio]', this.domNode);
        return checkbox.length
            ? dojo.attr(checkbox[0], 'checked')
            : false;
    }
});