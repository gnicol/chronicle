// summary:
//      A tooltip dialog with execute script support.
//
//      The _TooltipDialogBase class provides the execute script support.
//      It contains the bare minimum of changes to dijit.TooltipDialog needed
//      to inject the dojox content pane. This extends that to provide
//      additional customization in a separate class where it is more
//      maintainable.
//

dojo.provide("p4cms.ui.TooltipDialog");
dojo.require("p4cms.ui._TooltipDialogBase");
dojo.require("p4cms.ui._DialogMixin");

dojo.declare("p4cms.ui.TooltipDialog", [p4cms.ui._TooltipDialogBase, p4cms.ui._DialogMixin], {

    postCreate: function() {
        this.inherited(arguments);

        // add css class to identify as p4cms-ui component.
        dojo.addClass(this.domNode, 'p4cms-ui');
    },

    // attach this tooltip dialog to an arbitrary element (e.g. an anchor).
    attachToElement: function(element, params, stopEvent, loadNow) {
        this.connect(element, 'onclick', function(e){
            if (!this.isOpen()) {
                this.openAroundElement(element, params, loadNow);
            } else {
                dijit.popup.close(this);
            }
            if (stopEvent) {
                dojo.stopEvent(e);
            }
        });

        // take advantage of widget onBlur 'magic' to know when to close the popup
        // dijit.focus will not call onBlur on the element for events within the dialog.
        var widget = dijit.byNode(element) || new dijit._Widget(null, element);
        this.connect(widget, '_onBlur', function(){ dijit.popup.close(this); });
    },

    openAroundElement: function(element, params, loadNow) {
        // wait until dialog contents have loaded by default.
        if (!loadNow && this._onShow()) {
            this.connect(this, 'onLoad', function() {
                this.openAroundElement(element, params, loadNow);
            });
            return;
        }

        params = dojo.mixin({
            popup:              this,
            around:             element,
            parent:             element,
            constrainToTarget:  true,
            orient:             dijit.getPopupAroundAlignment(['below', 'above'], this.isLeftToRight()),
            onCancel:           dojo.hitch(this, function(){ dijit.popup.close(this); })
        }, params);

        p4cms.ui.popup.open(params);
    },

    // override to re-add autofocus feature to tooltip dialogs
    onOpen: function() {
        this.inherited(arguments);

        if (this.autofocus) {
            this.focus();
        }
    },

    resize: function()
    {
        this.inherited(arguments);

        // don't clip tooltips.
        dojo.style(this.domNode, 'overflow-x', 'visible');
        dojo.style(this.domNode, 'overflow-y', 'visible');
    }
});