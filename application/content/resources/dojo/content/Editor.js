// summary:
//      An extended dijit.Editor customized for use with p4cms content.
//

dojo.provide("p4cms.content.Editor");

// toggle config options to suppress the editor's hidden textarea.
// @todo remove when http://bugs.dojotoolkit.org/ticket/9901 fixed.
dojo.config.allowXdRichTextSave = false;
dojo.config.useXDomain          = true;
dojo.require("dijit.Editor");
dojo.config.useXDomain          = false;

// @todo remove the custom html requirement when dojo 1.8 lands
dojo.require('p4cms.content.editor.html');
dojo.require("dijit.Toolbar");
dojo.require('dijit.TooltipDialog');
dojo.require('p4cms.content.editor.plugins.AlwaysShowToolbar');
dojo.require('p4cms.content.editor.plugins.FontChoice');
dojo.require('p4cms.content.editor.plugins.TextColor');
dojo.require('p4cms.content.editor.plugins.ViewSource');
dojo.require('p4cms.content.editor.plugins.PrettyPrint');
dojo.require('dojox.editor.plugins.ToolbarLineBreak');
dojo.require('p4cms.content.editor.plugins.Image');
dojo.require('p4cms.content.editor.plugins.Link');
dojo.require('p4cms.content.editor.plugins.BranchifyUrls');
dojo.require('p4cms.content.editor.plugins.Paste');
dojo.require('dojox.editor.plugins.NormalizeStyle');

dojo.declare("p4cms.content.Editor", dijit.Editor, {
    proxyId:        '',
    bodyCssText:    '',
    inheritStyles:  true,
    ignoreBlur:     false,

    constructor: function() {
        // default plugins - defined here to avoid type-issues when mixed in with attributes from the markup
        this.plugins = [
            "formatBlock", "fontName", "fontSize",
            {name: 'p4cms.content.editor.plugins.TextColor', command: "foreColor"},
            "p4cms.content.editor.plugins.Paste",
            "||",
            "bold", "italic", "underline", "strikethrough", "|",
            "justifyLeft", "justifyCenter", "justifyRight", "justifyFull", "|",
            "insertUnorderedList", "insertOrderedList", "indent", "outdent", "|",
            "link", "image", "|", "p4cms.content.editor.plugins.ViewSource",

            // headless plugins.
            {name:'p4cms.content.editor.plugins.AlwaysShowToolbar', _handleScroll: false},
            "p4cms.content.editor.plugins.PrettyPrint",
            "p4cms.content.editor.plugins.BranchifyUrls"
        ];
    },


    // override setValue to exclude webkit condition that includes
    // a &nbsp; in empty editors.
    // The bug it is meant to fix still exists, not being able to
    // set editor value until there is content, or focus, but we don't
    // run into that issue ourselves, so remove the &nbsp;
    setValue: function(html) {
        if (!this.isLoaded) {
                // try again after the editor is finished loading
                this.onLoadDeferred.addCallback(dojo.hitch(this, function(){
                        this.setValue(html);
                }));
                return;
        }
        this._cursorToStart = true;
        if (this.textarea && (this.isClosed || !this.isLoaded)) {
                this.textarea.value=html;
        } else {
                html = this._preFilterContent(html);
                var node = this.isClosed ? this.domNode : this.editNode;
                if( html && dojo.isMoz && html.toLowerCase() === "<p></p>") {
                        html = "<p>&nbsp;</p>";
                }
                node.innerHTML = html;
                this._preDomFilterContent(node);
        }

        this.onDisplayChanged();
        this._set("value", this.getValue(true));
    },

    postMixInProperties: function() {
        this.inherited(arguments);

        // apply a class to the editor iframe body so we can target it.
        this.onLoadDeferred.addCallback(dojo.hitch(this, function() {
            dojo.addClass(this.document.body, 'content-editor');
            var html = this.document.getElementsByTagName("html")[0];
            dojo.addClass(html, 'content-editor');
        }));

        // copy classes from host page if inheritStyles is true.
        if (this.inheritStyles) {
            this._inheritPageClasses();
        }
    },

    postCreate: function() {
        this.inherited(arguments);

        dojo.addClass(this.domNode, ['p4cms-ui', 'content-editor']);
        dojo.addClass(this.toolbar.domNode, ['p4cms-ui', 'content-editor']);
    },

    onChange: function() {
        this.inherited(arguments);
        this._updateProxy('change');
    },

    onBlur: function() {
        if (this.ignoreBlur) {
            return;
        }

        this.inherited(arguments);
        this._updateProxy('blur');
    },

    // extend iframe markup to include arbitrary css text on the
    // dijitEditorBody div (if present - only present when there is
    // no explicit height set)
    _getIframeDocTxt: function() {
        var html = this.inherited(arguments);
        if (this.bodyCssText) {
            var needle = "<div id='dijitEditorBody'";
            html       = html.replace(
                needle, needle + " style=\"" + this.bodyCssText + "\""
            );
        }

        return html;
    },

    _inheritPageClasses: function() {
        this.onLoadDeferred.addCallback(dojo.hitch(this, function() {
            // copy over the body classes so we have a better chance of
            // the css rules working
            dojo.addClass(
                this.document.getElementsByTagName("body")[0],
                dojo.attr(dojo.query('body')[0], 'class')
            );
        }));
    },

    // finding the parent stylesheets is expensive, so only do it once.
    getParentPageStyleSheets: function() {
        if (!this.parentPageStyleSheets) {
            this.parentPageStyleSheets = dojo.query('[type=text/css]',
                    dojo.doc.getElementsByTagName("head")[0]);
        }
        return this.parentPageStyleSheets;
    },

    // extend to allow us to add styles that are present on the top level document
    _applyEditingAreaStyleSheets: function() {
        var text = this.inherited(arguments);

        // copy stylesheets from host page if inheritStyles is true.
        if (this.inheritStyles) {
            var temp     = dojo.doc.createElement('div'),
                elements = this.getParentPageStyleSheets();
            elements.forEach(dojo.hitch(this, function(element) {
                // create temporary parent div so we can copy over the innerHTML
                var clone = dojo.clone(element);
                // workaround IE7 clone issue
                if (element.innerHTML && element.innerHTML !== clone.innerHTML) {
                    var media   = dojo.attr(element, 'media'),
                        pack    = dojo.attr(element, 'package');
                    media       = media ? ' media="'   + media + '"' : '';
                    pack        = pack  ? ' package="' + pack  + '"' : '';

                    text += '<style type="text/css"' + media + pack + '>' + element.innerHTML + '</style>';
                } else {
                    dojo.place(clone, temp);
                    text += temp.innerHTML;
                    dojo.empty(temp);
                }
            }));
        }

        return text;
    },

    // copy value to proxy element and trigger
    // the given event on the proxy element.
    _updateProxy: function(event) {
        var proxy = dojo.byId(this.proxyId);
        if (proxy) {
            proxy.value = this.get('value');
            if (event) {
                p4cms.ui.trigger(proxy, event);
            }
        }
    },

    // override saveContent to only apply if it finds a textArea
    // @todo remove when http://bugs.dojotoolkit.org/ticket/9901 fixed.
    _saveContent: function() {
        var saveTextarea = dojo.byId(dijit._scopeName + "._editor.RichText.value");
        if (saveTextarea) {
            if(saveTextarea.value){
                    saveTextarea.value += this._SEPARATOR;
            }
            saveTextarea.value += this.name + this._NAME_CONTENT_SEP + this.getValue(true);
        }
    },

    // override to also remove trailing BR
    // @todo update with Dojo, last updated dojo 1.6.1
    _removeMozBogus: function(html){
        return html.replace(/\stype="_moz"/gi, '').replace(/\s_moz_dirty=""/gi, '')
            .replace(/_moz_resizing="(true|false)"/gi,'').trim().replace(/<br\s\/>$/gi, '');
    }
});

// Support in-line editing of content elements.
// When any content element enters edit mode, check if
// it is an editor element and, if so, inline it.
dojo.subscribe('p4cms.content.element.editModeEnabled', function(element) {
    // early exit if element doesn't allow inline editing
    if (!element.allowInline) {
        return;
    }

    var formPartial = element.getFormPartial();

    // ignore elements that don't contain a single editor
    var editors = dojo.query('[dojoType=p4cms.content.Editor]', formPartial);
    if (editors.length !== 1) {
        return;
    }

    // enable cursor handling in the element, and hook up listener to fix
    // image click events on IE and webkit
    var valueNode = element.getValueNode();
    dojo.attr(valueNode, 'contenteditable', 'true');
    element.mouseDownHandler = dojo.connect(element.domNode, 'onmousedown', element, function(event){
        if (event.target && event.target.nodeName.toLowerCase() === 'img') {
            // Fix for webkit browsers image selection
            // https://bugs.webkit.org/show_bug.cgi?id=12250
            // also fix IE, which won't fire an onclick if the image's edit handles load up
            if (dojo.isWebKit) {
                var selection = window.getSelection();
                selection.setBaseAndExtent(event.target, 0, event.target, 1);
            } else if (dojo.isIE) {
                dojo.stopEvent(event);
                dijit._editor.selection.selectElement(event.target);
                // IE7 will never fire the click event on an editable image,
                // go ahead and start the edit
                if (dojo.isIE < 8) {
                    this.startEdit();
                }
                return false;
            }
        }
    });

    // flag this element as setup for in-line use.
    // or return if we have already configured for in-line use.
    if (element.isPatchedForInline) {
        return;
    }
    element.isPatchedForInline = true;

    // create a container to place our editor within
    var inlineContainer = dojo.create('span',
        {
            'class':          'inline-container',
            'style':          {
                'visibility':     'hidden',
                'position':       'absolute',
                'top':            '0px',
                'left':           '0px'
            }
        }, element.domNode);

    // grab our editor dijit.
    var editor = dijit.byNode(editors[0]);

    // patch element for inline use using  safe mixin so that
    // we can use this.inherited calls
    dojo.safeMixin(element, {
        formPartial:        formPartial,
        inlineContainer:    inlineContainer,
        editor:             editor,
        inlineEditor:       null,

        // take over start edit to manage the inline editor
        startEdit: function() {
            var valueNode  = this.getValueNode();
            // mark where the cursor was clicked
            this.markCursor(valueNode, window);

            // if we already have an inline editor;
            // update it's value, show it, and focus
            if (this.inlineEditor) {
                this.inlineEditor.set('value', this.editor.get('value'));
                // add any errors to the tooltip
                this.updateInlineErrors();
                this.showEditor();

                return;
            }

            // determine css styles to explicitly set on the editor body.
            // copy font related styles from value node to editor body.
            var cssText    = "";
            var properties = [
                'color', 'font-family', 'font-size', 'font-style', 'font-weight',
                'letter-spacing', 'quotes', 'text-align', 'text-align-last',
                'text-decoration', 'text-indent', 'text-justify', 'text-shadow',
                'text-transform', 'text-underline-position', 'whitespace', 'word-spacing'
            ];
            dojo.forEach(properties, function(property) {
                cssText += property + ": " + dojo.style(valueNode, property) + "; ";
            });

            // set a min-width on the editor body - this can fix an issue
            // where the auto height expansion feature mis-detects the height
            // of the editor body on 'enable' and then later corrects itself
            // (causing the editor to grow large and then shrink).
            cssText += "min-width: " + dojo.position(valueNode).w + "px;";

            // create toolbar to be placed in the dialog
            var toolbar = new dijit.Toolbar({'class':'content-editor'});
            // create a tooltip and set the content to our toolbar
            var tooltip = new p4cms.ui.TooltipDialog({
                autofocus:  false,
                onClose: dojo.hitch(this, function() {
                    tooltip._editorOpen = false;
                    this.stopEdit();
                }),
                content:    toolbar.domNode
            });

            // create an editor to use inline and proxy over
            // the form editor's value/plugins
            var editorNode      = dojo.create('div', null, this.inlineContainer, 'only');
            this.inlineEditor   = new p4cms.content.Editor({
                plugins:                this.editor.plugins,
                height:                 '',
                bodyCssText:            cssText,
                value:                  this.editor.get('value'),
                toolbar:                toolbar,
                tooltip:                tooltip,
                _placeCursorAtStart:    true
            }, editorNode);
            this.inlineEditor.tooltip.startup();

            // extract the field label, description from
            // the element's form partial and add them to the tooltip.
            var label       = dojo.query('label',         this.formPartial),
                description = dojo.query('p.description', this.formPartial),
                container   = dojo.create('form', {'class': 'editor-element-info'}),
                dt          = dojo.create('dt', null, container),
                dd          = dojo.create('dd', null, container);

            if (label[0])            {
                dojo.place(dojo.clone(label[0]), dt);
            }
            if (description[0])      {
                dojo.place(dojo.clone(description[0]), dd);
            }
            if (dt.innerHTML || dd.innerHTML) {
                dojo.place(container, this.inlineEditor.tooltip.containerNode, 'first');
            }

            // add any errors to the tooltip
            this.updateInlineErrors();

            // pop the inline editor toolbar in a tooltip on focus
            this.connect(this.inlineEditor, 'onFocus', function() {
                // update our elements border to indicate focus
                this.focus();

                if (!this.inlineEditor.tooltip._editorOpen) {
                    // show the tooltip.
                    p4cms.ui.popup.open({
                        popup:              this.inlineEditor.tooltip,
                        around:             this.inlineEditor.domNode,
                        constrainToTarget:  true,
                        parent:             this.inlineEditor,
                        orient:             {'TL':'BL', 'TR':'BR', 'BL':'TL'}
                    });

                    // reduce zIndex on popup so any plugin dialogs can get above us
                    // this needs to be after the tooltip has been opened once
                    // in order for the parent node to be correct
                    dojo.style(this.inlineEditor.tooltip.domNode.parentNode, 'zIndex', '899');
                    this.inlineEditor.tooltip._editorOpen = true;
                }
            });

            // stop editing when the inlineEditor looses focus
            this.connect(this.inlineEditor, 'onBlur', function() {
                // ignore blurs that aren't caused by the mouse in the document
                // keeps the editor open while in other tabs/windows
                if (!dijit._justMouseDowned) {
                    return;
                }

                // The editor's plugins have callbacks on
                // the editor's setContent that are async.
                // Race them and lose for IE
                // close the tooltip toolbar
                setTimeout(dojo.partial(p4cms.ui.popup.close, this.inlineEditor.tooltip, true), 1);
            });

            // advance to next element when tab key pressed
            this.connect(this.inlineEditor, 'onKeyPress', 'handleTabKey');

            // as soon as it's loaded, focus the new editor to pop the tooltip controls
            dojo.addOnLoad(this.inlineEditor, dojo.hitch(this, function(){
                setTimeout(dojo.hitch(this, function() {
                    // show editor because inlineContainer is initially hidden
                    this.showEditor();
                }), 50);
            }));
        },

        // copies errors from the form to the inline editor's tooltip container
        updateInlineErrors: function() {
            var errors          = dojo.query('ul.errors', this.formPartial),
                container       = dojo.query('form.editor-element-info')[0],
                dd              = dojo.query('dd', container)[0],
                inlineErrors    = dojo.query('ul.errors', dd);

            if(inlineErrors[0]) {
                dojo.destroy(inlineErrors[0]);
            }
            if (errors[0]) {
                dojo.place(dojo.clone(errors[0]), dd, 'first');
            }
        },

        stopEdit: function() {
            if (!this.inlineEditor || this.inlineEditor.ignoreBlur) {
                return;
            }

            // consider element blurred.
            this.blur();

            this.hideEditor();
        },

        // saves the current cursor location to the element
        // for the window passed in, and converts the location
        // to be relative to the node passed in the first argument
        markCursor: function(relativeTo, win) {
            this.markedCursor   = null;
            var sel             = dijit.range.getSelection(win),
                range           = sel.getRangeAt(0),
                offset          = [range.startOffset],
                textIndex       = null;

            // exit if selection is not within this element
            if (!dojo.isDescendant(range.startContainer, relativeTo)
                    && range.startContainer !== relativeTo) {
                return;
            }

            // if we aren't offset from the valueNode, adapt the offset
            // so that it is
            if (range.startContainer !== relativeTo) {
                offset = dijit.range.getIndex(range.startContainer, relativeTo).o;

                // if we are dealing with a text node, track the cursor location
                // within the textnode
                if (range.startContainer.nodeType === 3) {
                    textIndex = range.startOffset;
                } else {
                    offset.push(range.startOffset);
                }
            }

            this.markedCursor = {offset:offset, textIndex: textIndex};
        },

        // uses the element's marked cursor to set the selection in the
        // passed window. It uses the passed relativeTo as the parent
        // in the selection relationship, regardless of the orignal marked parent.
        // This allows us to move selection data between editors.
        useMarkedCursor: function(relativeTo, win) {
            if (!this.markedCursor) {
                return;
            }

            var offset          = this.markedCursor.offset,
                textIndex       = this.markedCursor.textIndex,
                containerNode   = relativeTo;
            this.markedCursor = null;

            // run the offsets to find our container node
            // and give up if we can't find a container
            containerNode = dijit.range.getNode(offset, relativeTo);
            if (!containerNode) {
                return;
            }

            // If we are dealing with a viable text node, or an older IE browser,
            // go ahead and manually set the new selection
            // Otherwise select the container node then collapse the selection,
            // this does most of the complex element selection for us, dealing with tables etc
            if(textIndex || dojo.isIE < 9) {
                var sel         = dijit.range.getSelection(win),
                    range       = dijit.range.create(win),
                    validIndex  = (textIndex && containerNode.nodeType === 3 && containerNode.length >= textIndex)
                                ? textIndex
                                : 0;

                range.setStart(containerNode, validIndex);
                range.setEnd(containerNode, validIndex);
                sel.removeAllRanges();
                sel.addRange(range);
            } else {
                dojo.withGlobal(win, "selectElement", dijit._editor.selection, [containerNode, false]);
                dojo.withGlobal(win, 'collapse', dijit._editor.selection, [true]);
            }
        },

        // hide the value node and show the editor
        showEditor: function() {
            this.editorStatus = 'showing';
            var valueNode = this.getValueNode();
            dojo.addClass(this.inlineContainer, 'value-node');

            // we need the editor to contribute size to it's parent when it is
            // showing, switch it from position absolute to position static
            dojo.style(this.inlineContainer, {
                    'position':     'static',
                    'visibility':   'visible'
            });
            dojo.style(valueNode, {
                    'display':      'none',
                    'visibility':   'visible',
                    'position':     'static'
            });

            // delay focus and refresh, gives editor content a chance to render
            this.inlineEditor.onLoadDeferred.addCallback(dojo.hitch(this, function() {
                if (this.editorStatus === 'showing') {
                    this.refresh(this.inlineEditor.document.body);
                    this.inlineEditor.focus();
                    this.useMarkedCursor(this.inlineEditor.editNode, this.inlineEditor.window);
                    this.editorStatus = 'shown';
                }
            }));
        },

        // close the tooltip and bring back the value node
        // sets the editor value and calls onChange which will
        // take care of swapping the editor node for the display node
        hideEditor: function() {
            // don't hide the editor again if it is hidding or already hidden
            if (this.editorStatus === 'hidding' || this.editorStatus === 'hidden') {
                return;
            }
            this.editorStatus = 'hidding';

            // copy value of temp editor back to original and
            // swap the value nodes
            dojo.removeClass(this.inlineContainer, 'value-node');
            this.editor.set('value', this.inlineEditor.get('value'));
            dojo.style(this.getValueNode(), {
                'display':      '',
                'visibility':   'hidden',
                'position':     'absolute'
            });

            // call onchange to validate, refresh display value,
            // proxy to hidden form element, etc.
            this.editor.onChange();
        },

        // complete the hiding of the editor
        // and refresh the element to redraw borders
        completeHide: function() {
            if (this.editorStatus !== 'hidding') {
                this.refresh();
                return;
            }

            dojo.style(this.getValueNode(), {
                'position':     'static',
                'visibility':   'visible'
            });
            dojo.style(this.inlineContainer, {
                'position':     'absolute',
                'visibility':   'hidden'
            });

            this.editorStatus = 'hidden';
            this.refresh();
        },

        // overwrite updateDisplayValue to not destroy our editor
        updateDisplayValue: function(data) {
            var temp    = dojo.create('span', {innerHTML: data.displayValue}),
                value   = dojo.query('.value-node', temp)[0];

            // nothing to do if display value has not changed.
            if (!value || this.getValueNode().innerHTML === value.innerHTML) {
                return;
            }

            this.getValueNode().innerHTML = dojo.isString(value.innerHTML)
                ? value.innerHTML
                : '';

            // insert placeholder text if appropriate.
            this.insertPlaceholder();

            this.onUpdateDisplayValue(data);
        },

        // patch validateField to do nothing if no change has occurred
        validateField: function() {
            // if value has not changed since last validate, return
            if (dojo.toJson(this.validatedValue) === dojo.toJson(this.getFormValue())) {
                this.completeHide();
                return;
            }

            // a change occured; let the original function handle it
            return this.inherited(arguments);
        },

        // patch validateFieldLoadHandler to complete editor hide after it returns
        validateFieldLoadHandler: function(data, ioArgs) {
            this.inherited(arguments);
            this.completeHide();
        },

        // patch disableEditMode to remove element editing
        disableEditMode: function() {
            this.inherited(arguments);

            var valueNode = this.getValueNode();
            dojo.attr(valueNode, 'contenteditable', 'false');
            dojo.disconnect(this.mouseDownHandler);
        }
    });
});
