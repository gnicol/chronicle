// summary:
//      Support for in-place editing of content.

dojo.provide("p4cms.content.SelectDialog");
dojo.require("p4cms.ui.Dialog");
dojo.require("dijit.form.Button");

dojo.declare("p4cms.content.SelectDialog", p4cms.ui.Dialog,
{
    /**
     * Browse options are passed to the browse content action.
     * For example:
     *  {
     *    'search[query]':    'foobar',
     *    'type[types][]':    ['image','file']
     *  }
     */
    browseOptions:  null,
    href:           '',
    clearOnHide:    true,

    constructor: function() {
        this.inherited(arguments);

        if (!this.href) {
            this.href = p4cms.url({
                module: 'content',
                action: 'browse',
                format: 'partial'
            });
        }
        if (!this.title) {
            this.title = 'Select Content';
        }
    },

    postCreate: function() {
        this.inherited(arguments);
        dojo.addClass(this.domNode, 'select-content');
    },

    _setup: function() {
        if (this.browseOptions) {
            this.href = this.href + '?' + dojo.objectToQuery(this.browseOptions);
        }

        this.inherited(arguments);
    },

    onLoad: function() {
        this.inherited(arguments);

        var contentGrid = p4cms.content.grid.instance;

        // create buttons for this dialog
        var cancelButton = new dijit.form.Button({
            "label":    "Cancel",
            "id":       this.domNode.id + "-button-cancel",
            "onClick":  dojo.hitch(this, 'hide')
        });
        var selectButton = new dijit.form.Button({
            "label":    "Select",
            "class":    "preferred",
            "id":       this.domNode.id + "-button-select",
            "onClick":  dojo.hitch(this, function() {
                var selection = contentGrid.selection.getSelected();

                if (!selection) {
                    var notice = new p4cms.ui.Notice({
                        message:    'Please select a content entry and try again.',
                        severity:   'error'
                    });
                    return;
                }

                // if doing a single select, just return the first item's info.
                // this is for backwards compatibility - multiple select was
                // added later and earlier callers only expect one item.
                selection = contentGrid.selectionMode === 'single'
                    ? selection.pop().i
                    : selection;

                this.deferred.callback(selection);
                this.hide();
            })
        });

        // place buttons into the dialog
        this.addButton(selectButton);
        this.addButton(cancelButton);

        // connect grid row double-click to select content entry
        dojo.connect(contentGrid, 'onRowDblClick', null, function(){
            selectButton.onClick();
        });
    },

    /*
     * This is the prefered method of using the SelectDialog
     * Caller should 'addCallback' to the returned dojo.Deferred object to be notified
     * of selection.
     */
    getSelection: function() {
        this.deferred = new dojo.Deferred();

        this.show();

        return this.deferred;
    }
});
