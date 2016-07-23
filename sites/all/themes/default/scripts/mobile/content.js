dojo.provide('p4cms.mobile.content');

dojo.require('p4cms.content');
dojo.require('p4cms.mobile.content.ElementProxy');

// override getActive to check the current page for content Entries
p4cms.content.getActive = function() {
    var entries = new dojo.NodeList(),
        scope   = (dojox.mobile.currentView && dojox.mobile.currentView.domNode) || dojo.body();

    entries     = entries.concat(new dojo.NodeList(scope).closest('[dojoType=p4cms.content.Entry]'));
    entries     = entries.concat(dojo.query('[dojoType=p4cms.content.Entry]', scope));

    if (entries.length !== 1) {
        return null;
    }

    return dijit.byNode(entries[0]);
};

// skip loading the toolbar if edit is going to require a page reload
p4cms.content.oldLoadContentToolbar = p4cms.content.loadContentToolbar;
p4cms.content.loadContentToolbar    = function(container, type) {
    if (type !== 'edit' || p4cms.mobile.bookstack.length === 1) {
        return p4cms.content.oldLoadContentToolbar(container, type);
    }
};

// adjust enable edit group to reload the page prior to starting edit
// if we have a multiple entries in the book stack. Editing doesn't work
// well if more than one content entry is in the dom. Further it can be
// confusing if the user up/down slides whilst editing a page.
p4cms.ui.oldEnableEditGroup = p4cms.ui.enableEditGroup;
p4cms.ui.enableEditGroup    = function(group) {
    if (group !== 'content' || p4cms.mobile.bookstack.length === 1) {
        return p4cms.ui.oldEnableEditGroup(group);
    }

    p4cms.openUrl({
        module:     'content',
        controller: 'index',
        action:     'edit',
        id:         p4cms.content.getActive().contentId
    });
};

// patch the content entry to insert a scrollable layer around the form.
// without this, it would not be possible to scroll in form mode.
(function(){
    var oldCreate = p4cms.content.Entry.prototype.postCreate,
        oldEnter  = p4cms.content.Entry.prototype.enterFormMode,
        oldExit   = p4cms.content.Entry.prototype.exitFormMode;

    p4cms.content.Entry.prototype.postCreate = function(){
        oldCreate.apply(this, arguments);

        var formContainer = dojo.query('.content-form-container', this.domNode)[0];
        this.formScroller = dojo.create('div', null, this.domNode);

        if (formContainer) {
            dojo.place(formContainer, this.formScroller);
            dojo.style(formContainer, 'position', 'static');
            dojo.style(this.formScroller, {
                position:   'absolute',
                display:    'none',
                top:        0,
                left:       0,
                width:      '100%',
                height:     '100%',
                overflowY:  'auto'
            });
        }
    };

    p4cms.content.Entry.prototype.enterFormMode = function(){
        var anim = oldEnter.apply(this, arguments);
        if (anim) {
            dojo.style(this.formScroller, 'display', '');
        }
    };

    p4cms.content.Entry.prototype.exitFormMode = function(){
        var anim = oldExit.apply(this, arguments);
        if (anim) {
            dojo.connect(anim, 'onEnd', dojo.hitch(this, function(){
                dojo.style(this.formScroller, 'display', 'none');
            }));
        }
    };
}());