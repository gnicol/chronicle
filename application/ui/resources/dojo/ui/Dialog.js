// summary:
//      A dialog with execute script support.
//
//      The _DialogBase class provides the execute script support.
//      It contains the bare minimum of changes to dijit.Dialog needed
//      to inject the dojox content pane. This extends that to provide
//      additional customization in a separate class where it is more
//      maintainable.
//

dojo.provide("p4cms.ui.Dialog");
dojo.require("dijit.Dialog");   // ends up bringing over the desired dijit._DialogBase
dojo.require("dojox.layout.ContentPane");
dojo.require("dojox.widget.Standby");
dojo.require("p4cms.ui._DialogMixin");
dojo.require("p4cms.ui.DialogUnderlay");
dojo.require("p4cms.ui.Mover");

dojo.declare("p4cms.ui.Dialog", [dojox.layout.ContentPane, dijit._DialogBase, p4cms.ui._DialogMixin], {

    modal:          true,
    scrollNode:     null,
    clearOnHide:    false,
    destroyOnHide:  false,
    showSpinner:    true,
    maxRatio:       0.9,

    _setup: function() {
        // add css class to identify as p4cms-ui component.
        dojo.addClass(this.domNode, 'p4cms-ui');

        // if this[class] doesn't contain p4cms-ui, add it.
        // this will ensure the class p4cms-ui_underlay is added to the underlay.
        if (dojo.indexOf(this["class"].split(/\s/), 'p4cms-ui') === -1) {
            this["class"] += ' p4cms-ui';
        }

        this.inherited(arguments);

        // ensure that dialog gets resized as appropriate.
        this.connect(this,   "onLoad",   '_size');
    },

    // overridden
    _onLoadHandler: function() {
        this.inherited(arguments);
        if (this.showSpinner) {
            if (this._standby) {
                this._standby.hide();
            } else {
                this.createStandByWidget();
            }

            this.initStandByButtons();
        }
    },

    // create standBy widget if there is not one already existing
    createStandByWidget: function() {
        if (!this._standby) {
            this._standby = new dojox.widget.Standby({
                target:     this.domNode,
                image:      p4cms.baseUrl + '/application/ui/resources/images/loading-64x64.gif',
                color:      'white',
                'class':    'p4cms-ui standby-overlay'
            });
            document.body.appendChild(this._standby.domNode);
            this._standby.startup();
        }
    },

    // find all singleclick buttons and make sure they show and hide the standby
    // spinner when they are enabled/disabled
    initStandByButtons: function() {
        dojo.forEach(dojo.query('fieldset.buttons .dijitButton', this.domNode),
            function(node) {
                var button = dijit.byNode(node);
                if (button.declaredClass === 'p4cms.ui.SingleClickButton') {
                    this.connect(button, 'enable', function() {
                        // hide standby if there is one
                        if (this.showSpinner && this._standby) {
                            this._standby.hide();
                        }
                    });
                    this.connect(button, 'disable', function() {
                        this._standby.show();
                    });
                }
            },
            this
        );
    },

    // overridden
    uninitialize: function() {
        // make sure standby widget hasn't already been destroyed
        if (this._standby && this._standby.domNode) {
            this._standby.destroy();
        }
        this.inherited(arguments);
    },

    // extend show to update dialog underlay.
    show: function() {
        // reset href if refresh is required
        if (this.refreshOnShow && !this.href && dojo.exists("params.href", this)) {
            this.set('href', this.params.href);
        }

        this.inherited(arguments);

        // we use fixed dialogs so we should disconnect the onscroll listener
        dojo.forEach(this._modalconnects, function(listener) {
            if (listener[0] === window && listener[1] === "onscroll") {
               dojo.disconnect(listener);
            }
        });
    },

    // extend _onShow to move the _wasShown set up
    // in order to prevent resize problems in IE7
    // @todo remove when http://trac.dojotoolkit.org/ticket/15010 is fixed
    _onShow: function() {
        this._wasShown = true;
        this.inherited(arguments);
    },

    hide: function() {
        // if there is dialog above, close it first
        var ds  = dijit._dialogStack;
        if (ds[ds.length-1] !== this) {
            var idx = ds.length - 2;
            while (idx >= 0 && ds[idx] !== this) {
                idx--;
            }

            if (idx >= 0) {
                ds[idx+1].hide();
            }
        }

        var deferred = this.inherited(arguments);

        // if (clear|destroy)OnHide are enabled destroy
        // contents when the hide is complete
        deferred.addCallback(dojo.hitch(this, function() {
            if (this.clearOnHide) {
                // destroy all the widgets inside the dialog and empty containerNode
                // after onHide animation finished
                setTimeout(dojo.hitch(this, function() {
                    this.destroyDescendants();
                }), 1);
            }
            if (this.destroyOnHide) {
                // destroy all widgets inside the dialog, and get rid of the dialog
                // after onHide animation finished
                setTimeout(dojo.hitch(this, function() {
                    this.destroyRecursive();
                }), 1);
            }
        }));
    },

    // segregate buttons from the rest of the content - puts content
    // into a separate scroll node, buttons go in a container at the
    // bottom of the dialog so that they are always visible.
    // if passed content is a dijit, no segregation occurs.
    _setContent: function(cont, isFakeContent) {
        if (dojo.isObject(cont) && cont.domNode) {
            return this.inherited(arguments);
        } else {
            cont = "<div class='scrollNode'>" + cont + "</div>";
        }

        this.inherited(arguments);

        this.scrollNode = dojo.query('div.scrollNode', this.containerNode)[0];

        // move buttons outside of scrolling node.
        // note: Because buttons-element is not unique, the id selector was
        // returning nothing when scoped in chrome. The attribute selector for
        // id works.
        dojo.query('[id=buttons-element]', this.scrollNode).place(this.containerNode);

        // update dialog size and position.
        this.layout();
    },

    // when the dialog is sized, make our scroll node scroll
    // instead of the container node.
    // We completely override this currently in order to add the changes from
    // http://bugs.dojotoolkit.org/ticket/14147
    // @todo replace the copied text with a call to inherited when we get dojo 1.8
    _size: function() {
        var scrollNodeOffset = 0;

        // reset the scrollNode size in case we resized
        if (this.scrollNode) {
            dojo.style(this.scrollNode, {
                height: 'auto',
                width:  'auto'
            });
        }

        // START :: DOJO UPSTREAM
        this._checkIfSingleChild();

        // If we resized the dialog contents earlier, reset them back to original size, so
        // that if the user later increases the viewport size, the dialog can display w/out a scrollbar.
        // Need to do this before the dojo.marginBox(this.domNode) call below.
        if(this._singleChild){
            if(this._singleChildOriginalStyle){
                this._singleChild.domNode.style.cssText = this._singleChildOriginalStyle;
            }
            delete this._singleChildOriginalStyle;
        }else{
            dojo.style(this.containerNode, {
                width:"auto",
                height:"auto"
            });

            // calculate the scrollNode offset to use later when
            // applying the size changes to the scroll node
            if (this.scrollNode) {
                scrollNodeOffset = dojo._getContentBox(this.containerNode).h
                                 - dojo._getContentBox(this.scrollNode).h;
            }
        }

        var mb = dojo._getMarginSize(this.domNode);
        // Get viewport size but then reduce it by a bit; Dialog should always have some space around it
        // to indicate that it's a popup.   This will also compensate for possible scrollbars on viewport.
        var viewport = dojo.window.getBox();
            viewport.w *= this.maxRatio;
            viewport.h *= this.maxRatio;
        if(mb.w >= viewport.w || mb.h >= viewport.h){
            // Reduce size of dialog contents so that dialog fits in viewport
            var containerSize = dojo._getMarginSize(this.containerNode),
                w = Math.min(mb.w, viewport.w) - (mb.w - containerSize.w),
                h = Math.min(mb.h, viewport.h) - (mb.h - containerSize.h);

            if(this._singleChild && this._singleChild.resize){
                this._singleChildOriginalStyle = this._singleChild.domNode.style.cssText;
                this._singleChild.resize({w: w, h: h});
            }else{
                dojo.style(this.containerNode, {
                    width: w + "px",
                    height: h + "px",
                    overflow: "auto",
                    position: "relative"    // workaround IE bug moving scrollbar or dragging dialog
                });
            }
        }else{
            if(this._singleChild && this._singleChild.resize){
                this._singleChild.resize();
            }
        }
        // END :: DOJO UPSTREAM

        var height = parseInt(this.containerNode.style.height, 10),
            width  = parseInt(this.containerNode.style.width,  10);

        if (this.scrollNode && (!isNaN(height) || !isNaN(width))) {
            dojo.style(this.scrollNode, {
                height:     (height - scrollNodeOffset) + 'px',
                width:      width  + 'px',
                overflow:   'auto',
                position:   'relative'
            });

            dojo.style(this.containerNode, {
                height: 'auto',
                width:  'auto',
                overflow: 'hidden'
            });

            // add a class to the dialog to allow for different
            // styles when the scroll bar is in effect.
            dojo.addClass(this.domNode, 'scrolling');
        }
    },

    // extend to size dialog after layout
    // @todo remove when dojo 1.8 lands
    // see: http://bugs.dojotoolkit.org/ticket/14147
    layout: function(){
        if(this.domNode.style.display !== "none"){
            this._size();
        }
        this.inherited(arguments);
    },

    // extend on-key to permit key-presses outside of the dialog
    // for non-modal dialogs.
    _onKey: function(evt) {
        if (this.modal && evt.charOrCode){
            var dk   = dojo.keys;
            var node = evt.target;

            // determine if target node is inside dialog.
            while (node) {
                if (node === this.domNode) {
                    return this.inherited(arguments);
                }
                node = node.parentNode;
            }
        }
    },

    // extended to default to position fixed
    // @todo, remove when http://bugs.dojotoolkit.org/ticket/8679 is fixed
    postCreate: function() {
        this.inherited(arguments);
        dojo.style(this.domNode, 'position', 'fixed');
    },

    // overridden to position for fixed when not dragged
    // @todo, remove when http://bugs.dojotoolkit.org/ticket/8679 is fixed
    _position: function() {
        if(!dojo.hasClass(dojo.body(),"dojoMove")){
            var node = this.domNode,
                viewport = dojo.window.getBox(),
                p = this._relativePosition,
                bb = p ? null : dojo._getBorderBox(node),
                l = Math.floor(p ? p.x : (viewport.w - bb.w) / 2),
                t = Math.floor(p ? p.y : (viewport.h - bb.h) / 2);

            dojo.style(node,{
                left: l + "px",
                top: t + "px"
            });
        }
    }
});
