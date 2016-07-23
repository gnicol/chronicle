// summary:
//      This class is a custom plugin that intercepts all paste events on the editor
//      and determines what to do with them
//      It includes a Rich Text mode and a Plain Text mode
//      In plain text mode, all styles and tags are automatically stripped from the paste
//      In rich text mode, a normal paste will occur, or if MS Word content is detected,
//      the option to filter word content is presented

dojo.provide("p4cms.content.editor.plugins.Paste");

dojo.require("dojo.string");
dojo.require("dijit._editor._Plugin");
dojo.require("dijit.form.Button");
dojo.require("dojox.html.format");
dojo.require("p4cms.ui.Menu");
dojo.require("dijit.CheckedMenuItem");

dojo.declare("p4cms.content.editor.plugins.Paste", dijit._editor._Plugin, {
    iconClassPrefix:    "editorIcon",
    useDefaultCommand:  false,

    // taken from Dojo 1.8 pasteFrom word plugin
    // @todo, always update to trunk filters when we update dojo
    // _filters: [private] Array
    //              The filters is an array of regular expressions to try and strip out a lot
    //              of style data MS Word likes to insert when pasting into a contentEditable.
    //              Prettymuch all of it is junk and not good html.  The hander is a place to put a function
    //              for match handling.  In most cases, it just handles it as empty string.  But the option is
    //              there for more complex handling.
    _filters: [
        // Meta tags, link tags, and prefixed tags
        {regexp: /(<meta\s*[^>]*\s*>)|(<\s*link\s* href="file:[^>]*\s*>)|(<\/?\s*\w+:[^>]*\s*>)/gi, handler: ""},
        // Style tags
        {regexp: /(?:<style([^>]*)>([\s\S]*?)<\/style>|<link\s+(?=[^>]*rel=['"]?stylesheet)([^>]*?href=(['"])([^>]*?)\4[^>\/]*)\/?>)/gi, handler: ""},
        // MS class tags and comment tags.
        {regexp: /(class="Mso[^"]*")|(<!--(.|\s){1,}?-->)/gi, handler: ""},
        // blank p tags
        {regexp: /(<p[^>]*>\s*(\&nbsp;|\u00A0)*\s*<\/p[^>]*>)|(<p[^>]*>\s*<font[^>]*>\s*(\&nbsp;|\u00A0)*\s*<\/\s*font\s*>\s<\/p[^>]*>)/ig, handler: ""},
        // Strip out styles containing mso defs and margins, as likely added in IE and are not good to have as it mangles presentation.
        {regexp: /(style="[^"]*mso-[^;][^"]*")|(style="margin:\s*[^;"]*;")/gi, handler: ""},
        // Scripts (if any)
        {regexp: /(<\s*script[^>]*>((.|\s)*?)<\\?\/\s*script\s*>)|(<\s*script\b([^<>]|\s)*>?)|(<[^>]*=(\s|)*[("|')]javascript:[^$1][(\s|.)]*[$1][^>]*>)/ig, handler: ""},
        // Word 10 odd o:p tags.
        {regexp: /<(\/?)o\:p[^>]*>/gi, handler: ""}
    ],

    // setup our two toolbar buttons, rich paste and plain paste
    _initButtons: function() {
        this._filters = this._filters.slice(0);

        // build a dropdown menu of the paste mode options
        var menu        = new p4cms.ui.Menu({'class':'editor-menu'}),
            options     = [],
            doToggle    = function(checked) {
                var i;
                for (i =0; i < options.length; i++) {
                    if (options[i] !== this) {
                        options[i].set('checked', !checked);
                    }
                }
            },
            onClick     = function(e) {
                // catch menu item toggling itself off
                // and enforce being checked
                if(!this.disabled && !this.checked){
                    this.set("checked", true);
                    this.onChange(this.checked);
                }
            };

        this.richModeItem = new dijit.CheckedMenuItem({
            label:      'Standard Paste Mode',
            iconClass:  this.iconClassPrefix + " " + this.iconClassPrefix + "Paste",
            onChange:   doToggle,
            onClick:    onClick,
            checked:    true
        });
        this.plainModeItem = new dijit.CheckedMenuItem({
            label:      'Plain Text Paste Mode',
            iconClass:  this.iconClassPrefix + " " + this.iconClassPrefix + "Paste "
                        + this.iconClassPrefix + "PlainPaste",
            onChange:   doToggle,
            onClick:    onClick
        });

        menu.addChild(this.richModeItem);
        menu.addChild(this.plainModeItem);
        options.push(this.richModeItem, this.plainModeItem);

        this.button = new dijit.form.DropDownButton({
            label:      'Select Paste Mode',
            showLabel:  false,
            'class':    this.iconClassPrefix,
            iconClass:  this.iconClassPrefix + " " + this.iconClassPrefix + "Paste",
            tabIndex:   "-1",
            dropDown:   menu
        });

        // swap button icon to represent the current mode
        this.plainModeItem.watch('checked', dojo.hitch(this, function(name, oldChecked, newChecked) {
            dojo.toggleClass(this.button.iconNode, this.iconClassPrefix + "PlainPaste", newChecked);
        }));

        // restore editor focus whenever the dropdown closes
        this.editor.connect(this.button, 'closeDropDown', function() {
            this.focus();
        });
    },

    // plugin lifecycle
    updateState: function() {
        this.button.set("disabled", this.get("disabled"));
    },

    // plugin lifecycle
    setEditor: function(editor) {
        this.editor = editor;
        this._initButtons();

        // listen to paste events
        this.editor.onLoadDeferred.addCallback(dojo.hitch(this, function(){
            this.connect(this.editor.editNode, "onpaste", this.onPaste);
            this.editor._pasteImpl = dojo.hitch(this, this.onPaste);
        }));
    },

    // event handler called whenever something is pasted into the editor
    onPaste: function(event) {
        if (!event || this.ignorePaste) {
            return true;
        }

        var isRich      = !this.plainModeItem.get('checked'),
            clipboard   = event.clipboardData || this.editor.window.clipboardData,
            selectionObject, clipboardNode;

        // if a w3 data transfer object is available, and we are not in rich text mode,
        // paste in the plain text from the appropriate data transfer api
        // else let firefox paste into a new controlled div that we can pull the text out of
        // this is because firefox doesn't have clipboardData yet
        if (clipboard) {
            if (!isRich) {
                dojo.stopEvent(event);

                // adjust the api call for IE vs w3 standard
                var type = (event.clipboardData ? 'text/plain' : 'Text');
                this._paste(clipboard.getData(type).replace(/(\n|\r\n|\r)/g, '<br>'));
            } else if (event.clipboardData) {
                // w3 standards condition for rich text paste
                var w3Content = clipboard.getData('text/html');
                if (this.containsWordFormat(w3Content)) {
                    dojo.stopEvent(event);
                    clipboardNode   = this.createClipboardNode(w3Content);
                    var filter      = this._filter(clipboardNode.innerHTML);
                    dojo.destroy(clipboardNode);
                    this._paste(filter);
                } else {
                    // normal paste, be sure to update the editor
                    setTimeout(dojo.hitch(this.editor, 'onDisplayChanged'), 0);
                }
            } else if (window.clipboardData) {
                // IE condition for rich text paste
                selectionObject = this.moveSelectionToClipboardNode();
                var ieContent = selectionObject.node.innerHTML;

                this.restoreSelection(selectionObject);

                if (this.containsWordFormat(ieContent)) {
                    dojo.stopEvent(event);
                    this._paste(this._filter(ieContent));
                } else {
                    // normal paste, be sure to update the editor
                    setTimeout(dojo.hitch(this.editor, 'onDisplayChanged'), 0);
                }
            }
        } else {
            // FF hasn't added the Data Transfer object the paste event yet
            // see https://bugzilla.mozilla.org/show_bug.cgi?id=407983
            // paste into hidden div
            selectionObject     = this.moveSelectionToClipboardNode();
            clipboardNode       = selectionObject.node;

            var getPaste = dojo.hitch(this, function(type) {
                    var noteText = type !== 'text'
                        ? clipboardNode.innerHTML
                        : this.getTextFromNode(clipboardNode).replace(/(\n|\r\n|\r)/g, '<br>');

                    this.restoreSelection(selectionObject);

                    return noteText;
            });
            setTimeout(dojo.hitch(this, function() {
                if (!isRich) {
                    this._paste(getPaste('text'));
                } else {
                    var ffContent = getPaste('html');
                    if (this.containsWordFormat(ffContent)) {
                        this._paste(this._filter(ffContent));
                    } else {
                        this._paste(ffContent);
                    }
                }
            }), 0);
        }

        return true;
    },

    // method for moving cursor to a hidden node to allow us to
    // capture the paste in non-compliant browsers
    moveSelectionToClipboardNode: function() {
        var sel             = dijit.range.getSelection(this.editor.window),
            oldRange        = sel.getRangeAt(0),
            clipboardNode   = this.createClipboardNode('\uFEFF\uFEFF'), range;

        if (window.clipboardData) {
            // IE condition
            range = this.editor.document.body.createTextRange();
            range.moveToElementText(clipboardNode);
            this.ignorePaste = true;
            range.execCommand('Paste');
            this.ignorePaste = false;
        } else {
            // Firefox Condition
            range = dijit.range.create(this.editor.window);
            range.setStart(clipboardNode.firstChild, 0);
            range.setEnd(clipboardNode.firstChild, 2);
            sel.removeAllRanges();
            sel.addRange(range);
        }

        return {selection: sel, range: oldRange, node: clipboardNode};
    },

    // grabs the text contents from the clipboard node that is passed
    getTextFromNode: function(node) {
        var range   = dijit.range.create(this.editor.window),
            sel     = dijit.range.getSelection(this.editor.window);

        range.selectNode(node);
        sel.removeAllRanges();
        sel.addRange(range);

        // easy way to grab the text contents is to tostring the selection
        return sel.toString();
    },

    // method for restoring editor cursor after paste has gone into
    // the hidden node
    restoreSelection: function(config) {
        config.selection.removeAllRanges();
        config.selection.addRange(config.range);
        dojo.destroy(config.node);
    },

    // creates hidden node for clipboard data
    createClipboardNode: function(content) {
        return dojo.create('div', {
            innerHTML:  content,
            style:      {
                position:       'fixed',
                whiteSpace:     'pre',
                width:          '1px',
                height:         '1px',
                left:           '-1000px'
            }
        }, this.editor.editNode);
    },

    // detect whether content contains word formatting
    containsWordFormat: function(content) {
        return content && /(class=\"?Mso|style=\"[^\"]*\bmso\-|w:WordDocument)/.test(content);
    },

    // returns a ms word filtered version of the content
    _filter: function(content) {
        content = dojox.html.format.prettyPrint(content);

        // Apply all the filters to remove MS specific injected text.
        var i;
        for(i = 0; i < this._filters.length; i++){
            var filter  = this._filters[i];
            content     = content.replace(filter.regexp, filter.handler);
        }

        return content;
    },

    // inserts the content into the editor
    _paste: function(content) {
        // Format it again to make sure it is reasonably formatted as
        // the regexp applies will have likely chewed up the formatting.
        content = dojox.html.format.prettyPrint(content);

        // Paste it in.
        this.editor.execCommand("inserthtml", content);
    }
});

// Register this plugin.
dojo.subscribe(dijit._scopeName + ".Editor.getPlugin", null, function(o) {
    if (o.plugin) {
        return;
    }
    var name = o.args.name.toLowerCase();
    if (name === "paste") {
        o.plugin = new p4cms.content.editor.plugins.Paste();
    }
});