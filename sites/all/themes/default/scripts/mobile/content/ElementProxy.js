// summary:
//      Extended p4cms.content.Element to support editing content
//      when paginated inside the p4cms.mobile.Book widget.

dojo.provide('p4cms.mobile.content.ElementProxy');

dojo.require('p4cms.content.Element');
dojo.require("dojo.NodeList-traverse");

dojo.declare("p4cms.mobile.content.ElementProxy", p4cms.content.Element,
{
    proxyTo:            '',
    allowInline:        false,
    originalElement:    null,

    startup: function(){
        var pageNode = new dojo.NodeList(this.domNode).closest('.mblView')[0];
        if (pageNode) {
            this.bordersRootNode = pageNode;
        }

        // de-reference element we are proxying to.
        var element          = dijit.byId(this.proxyTo);
        this.originalElement = element;

        // when the original element is focused/blurred,
        // we want that change reflected here as well.
        this.connect(element, 'focus', 'focus');
        this.connect(element, 'blur',  'blur');

        this.inherited(arguments);

        // detect if we should have an error highlight.
        // we do this after parent runs so that borders exist.
        if (dojo.query('ul.errors', element.getFormPartial()).length) {
            this.addHighlightClass('editable-element-border-error');
        }

        // if we have a content select form control, wire-up
        // this element proxy as another upload drop-zone.
        var contentSelect = dojo.query('.content-select', element.getFormPartial())[0];
        if (contentSelect) {
            contentSelect = dijit.byNode(contentSelect);
            if (contentSelect) {
                contentSelect._setupDropZone(this.domNode);
            }

            // content select updates while dialog is still open, so we
            // need to reposition it around us
            this.originalElement.editAroundNode = this.domNode;
            this.originalElement.repositionEditDialog();
        }
    },

    // proxy startEdit to original element, we want the
    // edit to mostly take place on the original element.
    startEdit: function(){
        this.originalElement.editAroundNode = this.domNode;
        this.originalElement.startEdit();
    },

    getPlaceholderText: function(){
        return this.originalElement.getPlaceholderText();
    },

    // stub out form handlers - let the original element handle these events.
    connectFormHandlers: function() {}
});