// summary:
//      Makes buttons first-class citizens of dialogs.
//      To be consistent with markup produced by zend-form
//      we put them inside a #buttons-element container.

dojo.provide("p4cms.ui._DialogMixin");

dojo.declare("p4cms.ui._DialogMixin", null, {

    // convenience method to append to dialog content.
    addContent: function(content, pos) {
        dojo.place(content, this.scrollNode || this.containerNode, pos);
    },

    addButton: function(button) {
        dojo.place(button.domNode, this.getButtonFieldset());
    },

    getButtonContainer: function() {
        var container = dojo.query('> #buttons-element', this.containerNode)[0];
        if (!container) {
            container = dojo.create("div", {"id": "buttons-element"});
            dojo.place(container, this.containerNode);
        }

        return container;
    },

    getButtonFieldset: function() {
        var container = this.getButtonContainer();
        var fieldset  = dojo.query('fieldset', container)[0];
        if (!fieldset) {
            fieldset = dojo.create("fieldset", {"class": "buttons"});
            dojo.place(fieldset, container);
        }

        return fieldset;
    },

    isOpen: function() {
        // Does the node even exist in the body?
        if (!dojo.isDescendant(this.domNode, dojo.body())) {
            return false;
        }

        // If closed using dijit.popup, the wrapper will have display none
        if (this.domNode.parentNode && dojo.style(this.domNode.parentNode, 'display') === 'none') {
            return false;
        }

        return dojo.style(this.domNode, 'visibility') === 'visible'
            && dojo.style(this.domNode, 'display')    !== 'none';
    },

    getOpener: function() {
        var opener = dojo.attr(this.domNode.parentNode, 'dijitpopupparent');
        return opener ? dijit.byId(opener) : null;
    },

    hasOpener: function() {
        return Boolean(this.getOpener());
    }
});