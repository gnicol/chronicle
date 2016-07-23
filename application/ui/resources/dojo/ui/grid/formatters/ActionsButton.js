dojo.provide('p4cms.ui.grid.formatters.ActionsButton');

// generate 'actions' drop-down menu using the associated
// row-menu dijit for the button drop-down
p4cms.ui.grid.formatters.ActionsButton = function(item, row) {
    if (!item || !this.grid.menus || !this.grid.menus.rowMenu) {
        return;
    }

    var button = new dijit.form.DropDownButton({
        label:      '<span><img src="' + p4cms.baseUrl + '/application/ui/resources/images/icon-dropdown.gif"></span>',
        dropDown:   dijit.byId(this.grid.menus.rowMenu)
    });

    // patch open drop-down method to set the row index on the menu.
    var originalOpen = button.openDropDown;
    button.openDropDown = function(){
        button.dropDown.rowIndex = row;
        dojo.hitch(button, originalOpen)();
    };

    // we don't want to select rows when the button is clicked; prevent propigation
    dojo.connect(button.domNode, 'onclick', button, function(e) {
        dojo.stopEvent(e);
    });

    return button;
};