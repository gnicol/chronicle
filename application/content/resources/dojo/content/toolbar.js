dojo.provide("p4cms.content.toolbar");

// subscribe to toolbar.contextChange topic to influence the appearance of toolbar buttons.
dojo.subscribe('p4cms.ui.toolbar.contextChange', function(toolbar) {

    // show edit-content button in the toolbar if and only if
    // there is one and only one content dijit on the page
    // and 'edit' is in allowed privileges list
    var hideEdit    = true;

    // similarly, show history button in the toolbar if and only if
    // there is one and only one content dijit on the page
    // and 'access-history' is in allowed privileges list
    var hideHistory = true;

    // check the dom and modify hide flags if particular privileges are found
    var entry  = p4cms.content.getActive();
    if (entry) {
        var privileges = entry.allowedPrivileges;
        var entryId    = entry.contentId;
        dojo.forEach(privileges, function(privilege) {
            if (privilege === 'edit' && entryId) {
                hideEdit = false;
            }
            if (entryId && privilege === 'access-history') {
                hideHistory = false;
            }
        });
    }

    toolbar.setButtonDisplay('content-edit', hideEdit);
    toolbar.setButtonDisplay('content-history', hideHistory);
});