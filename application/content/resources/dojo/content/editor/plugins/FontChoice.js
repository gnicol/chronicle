// summary
//      Override the default fontChoice plugin to gain access to templateString.
//
// IMPORTANT
// The template string is mostly copied from dijit._editor.plugins.FontChoice.
// We have added an extra wrapper span to the input element so that we can
// include a native tooltip. We've also enabled the placeholder on the
// filtering select, and have added a search action in the onClick listener.
// Also set invalidMessage to a blank string to prevent error tooltip
//
// @todo Updated when Dojo is updated (last copied from version 1.6.1)
//       Don't forget to add the new features mentioned above.
//
dojo.provide("p4cms.content.editor.plugins.FontChoice");

dojo.subscribe(dijit._scopeName + ".Editor.getPlugin", null, function(o) {
    switch (o.args.name) {
        case "fontName": case "fontSize": case "formatBlock":
            o.plugin = new p4cms.content.editor.plugins.FontChoice({
                command:    o.args.name,
                plainText:  o.args.plainText || false,
                templateString:
                    "<span style='white-space: nowrap' class='dijit dijitReset dijitInline toolbarDropdown' >" +
                        "<label class='dijitLeft dijitInline' for='${selectId}'>${label}</label>" +
                        "<span title='${label}'>" +
                            "<input dojoType='dijit.form.FilteringSelect' " +
                             "onClick='p4cms.content.editor.plugins.FontChoice.click(arguments[0], this);' " +
                             "required='false' labelType='html' labelAttr='label' searchAttr='name' " +
                             "invalidMessage='' placeHolder='${label}' tabIndex='-1' id='${selectId}' " +
                             "dojoAttachPoint='select' value='' />" +
                        "</span>" +
                    "</span>"
            });
            break;
    }
});

dojo.require('dijit._editor.plugins.FontChoice');

dojo.declare("p4cms.content.editor.plugins.FontChoice", dijit._editor.plugins.FontChoice);

p4cms.content.editor.plugins.FontChoice.click = function(event, scope) {
    // only handle clicks on the input box itself
    if (dojo.hasClass(event.target, 'dijitInputInner')) {
        if(!scope._isShowingNow){
            scope._startSearchAll();
        }
    }
};