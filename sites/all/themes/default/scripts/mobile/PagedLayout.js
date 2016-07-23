// summary:
//      Object that requires a source node reference, and a target node reference, and
//      builds columnized pages in the target node, using the content within the source node

dojo.provide('p4cms.mobile.PagedLayout');

dojo.require('p4cms.mobile.columnizer');

dojo.declare('p4cms.mobile.PagedLayout', null, {
    // config object that override the defaults for the first page
    pageOneExtraConfig: null,
    book:               null,
    sourceNode:         null,
    targetNode:         null,
    columnizeMinWidth:  768,
    columns:            2,
    outputAllOnFail:    true,
    preserveSourceNode: true,
    _origColumns:       null,

    // lifecycle
    constructor: function(args) {
        // apply the config options
        dojo.safeMixin(this, args);

        this._origColumns = this.columns;
        this.columns = dojo.window.getBox().w < this.columnizeMinWidth ? 1 : this._origColumns;

        this.build();
        this.initListeners();
    },

    // entrypoint for building pages the first time
    build: function() {
        // expand potential queries
        this.sourceNode = (dojo.isString(this.sourceNode)
                        ? dojo.query(this.sourceNode, this.targetNode)[0] : this.sourceNode);

        this.targetNode = dojo.byId(this.targetNode);

        // set a page-group attribute for linking created pages to the source page
        this.sourcePageNode = this.sourcePageNode || dojo.query('.mblView', this.targetNode)[0];
        dojo.attr(this.sourcePageNode, 'page-group', this.sourcePageNode.id);

        this.expandPage(this.getInputNode());
    },

    // returns a node that is safe to move contents from
    getInputNode: function() {
        // clone the sourceNode if we need to preserve the original content
        if (this.preserveSourceNode) {
            return dojo.clone(this.sourceNode);
        }

        return this.sourceNode;
    },

    // connect listeners
    initListeners: function() {
        // rebuild pages on window resize
        this.windowBox      = dojo.window.getBox();
        this.resizeListener = dojo.connect(window, 'onresize', this, function() {
            window.clearTimeout(this.resizeHandler);
            this.resizeHandler = window.setTimeout(dojo.hitch(this, function() {
                var newBox = dojo.window.getBox();

                // ignore event if size hasn't changed
                if (this.windowBox.w === newBox.w && this.windowBox.h === newBox.h) {
                    return;
                }

                this.columns = dojo.window.getBox().w < this.columnizeMinWidth ? 1 : this._origColumns;
                this.windowBox = newBox;
                this.expandPage(this.getInputNode());
            }), 500);
        });
    },

    // returns the p4cms.mobile.Page in the current page-group at the passed index, or creates a new one
    getPage: function(index) {
        // reuse existing pages
        var pages = this.getPages();
        if (pages[index]) {
            return dijit.byNode(pages[index]);
        }

        var page = new p4cms.mobile.Page().placeAt(this.targetNode);
        dojo.attr(page.domNode, 'page-group', this.sourcePageNode.id);
        page.startup();

        this.createPageContent(page);

        return page;
    },

    // returns NodeList of all pages that share this pageGroup
    getPages: function() {
        return dojo.query('.mblView[page-group="'+this.sourcePageNode.id+'"]', this.targetNode);
    },

    // creates content containers within the page during page creation
    createPageContent: function(page) {
        // inner content wrapper.
        dojo.create('div', {'class': 'mblViewContent body'}, page.domNode);
    },

    // returns the node within the page where content will be placed
    getPageTargetNode: function(page) {
        return dojo.query('.mblViewContent', page.domNode)[0];
    },

    // build the first or specified page using the passed source contents
    expandPage: function(contents, pageIndex) {
        if(contents.childNodes.length < 1) {
            return;
        }

        pageIndex = pageIndex || 0;

        // make a new page if no target given.
        var page = this.getPage(pageIndex);
        var config = {
            targetNode:         this.getPageTargetNode(page),
            columns:            this.columns,
            outputAllOnFail:    this.outputAllOnFail
        };

        // apply any custom config data
        if (this.pageOneExtraConfig && pageIndex === 0) {
            dojo.mixin(config, this.pageOneExtraConfig);
            config.targetNode = (dojo.isString(config.targetNode)
                              ? dojo.query(config.targetNode, this.targetNode)[0]
                              : config.targetNode);
        }

        setTimeout(dojo.hitch(this, function(){
            // make target rendered but hidden (save display & visibility)
            // display pages so we can measure their size
            var visibility  = dojo.style(page.domNode, 'visibility'),
                display     = dojo.style(page.domNode, 'display');
            dojo.style(page.domNode, {
                visibility: 'hidden',
                display:    ''
            });

            var originalContents    = contents.innerHTML,
                maxHeight           = Math.round(dojo.contentBox(config.targetNode).h);

            // in webkit, when the target node has an odd number height, the column divs created
            // in the target node with 100% height will be 1px higher then the targe node.
            // the issue is, with percentages in webkit, they refuse to further divide 1px remainders,
            // leaving a 1px discrepancy.
            // See: https://code.google.com/p/chromium/issues/detail?id=149547
            //      https://code.google.com/p/chromium/issues/detail?id=121342
            // to fix it, we add 1 here.
            if (dojo.isWebKit) {
                maxHeight++;
            }

            p4cms.mobile.columnizer.columnize(contents, config.targetNode, config.columns, maxHeight,
                dojo.hitch(this, function(extra) {
                    // if there is more content, add it to the document
                    // else we are done and we should cleanup unused pages
                    if (extra) {
                        // if columnizer didn't columnize any output, spit out all output
                        // else add new page for the output that didn't fit in the columns
                        if (config.outputAllOnFail && extra.innerHTML === originalContents) {
                            var lastColumn  = dojo.query('.p4cms-column', config.targetNode);
                            lastColumn      = lastColumn[lastColumn.length-1];
                            dojo.place(extra, lastColumn);
                        } else {
                            this.expandPage(extra, pageIndex+1);
                            return;
                        }
                    }

                    this.onColumnizeComplete(pageIndex+1);
                })
            );

            // restore saved display and visibility if the page's state hasn't
            // changed from what we set earlier
            if (dojo.style(page.domNode, 'visibility') === 'hidden') {
                dojo.style(page.domNode, {
                    visibility:  visibility,
                    display:     display
                });
            }
        }), 0);
    },

    // removes pages starting at the index and continuing till the end
    removePages: function(pageIndex) {
        // remove any existing pages we didn't use
        var pages   = this.getPages(),
            index   = pageIndex,
            cleanup = function(node) {
                if (dijit.byNode(node)) {
                    dijit.byNode(node).destroy();
                }
            };

        for(index; index < pages.length; index++) {
            // if we are removing the shown page, show the last page
            if (pages[index] === dojox.mobile.currentView.domNode) {
                this.getPage(pageIndex-1).show();
            }

            // destroy the page (cleanup any dijits first)
            dojo.query("[widgetid]", pages[index]).forEach(cleanup);
            dijit.byNode(pages[index]).destroy();
        }
    },

    // called when current columnization is completed
    // passes the number of pages that were columnized
    onColumnizeComplete: function(numberOfPages) {
        // remove any extra pages that were not used
        this.removePages(numberOfPages);

        dojo.query('.content-element', this.sourceNode).forEach(
            function(node) {
                var sourceElement = dijit.byNode(node);
                if (sourceElement) {
                    var query = '.mblView .content-element[id="' + sourceElement.id + '"]';
                    dojo.query(query, this.book.domNode).forEach(function(node){
                        // reconfigure node to run as a element proxy.
                        dojo.removeAttr(node, 'id');
                        dojo.attr(node, {
                            dojoType:   'p4cms.mobile.content.ElementProxy',
                            proxyTo:    sourceElement.id
                        });
                        dojo.parser.instantiate([node]);
                    });

                    // we don't support inline editing in this theme
                    // (everything has to open in a tooltip/dialog).
                    sourceElement.allowInline = false;

                    // original element need not draw highlights.
                    sourceElement.drawHighlight = function(){};

                    // re-paginate when element display value changes.
                    sourceElement.paginate = sourceElement.paginate
                        || dojo.connect(sourceElement, 'onUpdateDisplayValue', this.book.layout, 'build');

                    // patch source element to handle case where around node is unset
                    // or not visible on the active page (happens when tabbing).
                    this._patchElementForAroundNode(sourceElement);

                    // do some extra work on editor elements
                    if (dojo.hasClass(sourceElement.domNode, 'content-element-type-editor')) {
                        this._patchEditorElement(sourceElement);
                    }
                }
            }, this
        );
    },

    _patchEditorElement: function(element) {
        if (element.isPatchedForEditor) {
            return;
        }

        var layout = this;
        dojo.safeMixin(element, {
            isPatchedForEditor: true,

            // override parent to work with modal dialog instead of tooltip
            openEditDialog: function() {
                this.getEditDialog().show();
            },

            // clear the method - it only makes sense for tooltips
            repositionEditDialog: function() {},

            // need to patch start/stop edit so we don't move the form partial
            // the editor does not work if it gets moved in the DOM.
            startEdit: function() {
                // target the correct proxy element and switch page if needed.
                layout.updateEditAroundNode(this);

                if (this.editStarted) {
                    return;
                }

                // consider element focused
                this.focus();
                this.editStarted = true;

                // open the edit dialog
                this.openEditDialog();
            },

            stopEdit: function() {
                if (!this.isFocused()) {
                    return;
                }

                // consider element blurred (clears focus from highlight)
                this.blur();
                this.editStarted = false;

                // close the dialog (if its open)
                if (this.editDialog.open) {
                    this.editDialog.hide();
                }
            },

            getEditDialog: function() {
                if (!this.editorDialog) {
                    // get the original editor element
                    var editorNode = dojo.query('[dojoType=p4cms.content.Editor]', this.getFormPartial());
                    var formEditor = dijit.byNode(editorNode[0]);

                    // create the new dialog to show the editor in
                    var dialog = new p4cms.ui.Dialog({
                        title:  this.getLabel(),
                        onHide: dojo.hitch(this, 'stopEdit')
                    });

                    // add editor to the dialog
                    this.dialogEditor = new p4cms.content.Editor({
                        plugins:                formEditor.plugins,
                        height:                 Math.floor(dijit.getViewport().h * 0.75) + 'px',
                        _placeCursorAtStart:    true
                    }, dialog.containerNode, 'only');

                    // extract the element description and add it to the dialog
                    var description = dojo.query('p.description', this.getFormPartial())[0],
                        container   = dojo.create('form', {'class': 'editor-element-info'}),
                        dd          = dojo.create('dd', null, container);
                    if (description && description.innerHTML) {
                        dojo.place(dojo.clone(description), dd);
                        dojo.place(container, dialog.containerNode, 'first');
                    }

                    // size editor's width to take 80% of the viewport, but not more than 900px
                    dojo.style(dojo.query('.dijitEditorIFrameContainer', dialog.domNode)[0], {
                            width: Math.min(Math.floor(dijit.getViewport().w * 0.8), 900) + 'px'}
                    );

                    // re-sync editor content when dialog is opened
                    dojo.connect(dialog, 'onShow', dojo.hitch(this, function() {
                        this.dialogEditor.set('value', formEditor.get('value'));
                    }));

                    // connect to update entry's editor value when dialog is closed
                    dojo.connect(dialog, 'onHide', dojo.hitch(this, function() {
                        formEditor.set('value', this.dialogEditor.get('value'));
                        formEditor.onChange();
                    }));

                    this.editDialog = dialog;
                }

                return this.editDialog;
            }
        });
    },

    _patchElementForAroundNode: function(element) {
        if (element.isPatchedForAroundNode) {
            return;
        }

        var layout = this;
        dojo.safeMixin(element, {
            isPatchedForAroundNode: true,

            // target the correct proxy element and switch page if needed.
            startEdit: function() {
                layout.updateEditAroundNode(this);
                return this.inherited(arguments);
            }
        });
    },

    // triggers a slide transition from the current view, to the provided page
    transitionToPage: function(page) {
        if (page === dojox.mobile.currentView) {
            return;
        }

        // if there is no current view, simply show the page
        if (!dojox.mobile.currentView) {
            page.show();
            return;
        }

        // determine slide direction based on page index
        var pages       = this.getPages(),
            direction   = pages.indexOf(page.domNode) - pages.indexOf(dojox.mobile.currentView.domNode);

        dojox.mobile.currentView.performTransition(page.id, (direction > 0 ? 1 : -1), 'slide', this, function() {
            dojo.publish('p4cms.ui.refreshEditMode');
        });
    },

    updateEditAroundNode: function(element){
        var page = this.book.getCurrentPage().domNode;
        var node = element.editAroundNode;

        //  if we don't have a edit around node, or it is not on the current
        //  page, go hunting for it (look on the current page first).
        if (!node || !dojo.isDescendant(node, page)) {
            node = dojo.query('[proxyTo=' + element.id + ']', page)[0]
                || dojo.query('[proxyTo=' + element.id + ']', this.book.domNode)[0];
        }

        // if we have a around node, ensure we're looking at the right page.
        if (node) {
            page = new dojo.NodeList(node).closest('.mblView')[0];
            if (page && dijit.byNode(page)) {
                this.transitionToPage(dijit.byNode(page));
            }
        }

        element.editAroundNode = node;
    }
});