// summary:
//      An extended dojox.grid.enhanced.DataSelection:
//      - Updates getSelection() and getSelectionCount() methods to return
//        reference and total number of selected rows on the grid that are
//        accessible

dojo.provide("p4cms.ui.grid.DataSelection");
dojo.require("dojox.grid.enhanced.DataSelection");

dojo.declare("p4cms.ui.grid.DataSelection", dojox.grid.enhanced.DataSelection, {

    // override parent to return a list with references to the non-empty
    // items only;
    // in some cases, when selected rows in the grid become inaccessible
    // due to filtering, previously selected rows are still marked in
    // this.selected array although grid.getItem() returns null on these
    // indexes
    getSelected: function() {
		var i, l = this.selected.length, result = [];
		for(i=0; i<l; i++){
			if(this.selected[i] && this.grid.getItem(i)){
				result.push(this.grid.getItem(i));
			}
		}
		return result;    
    },

    // override parent to count only indexes where grid.getItem() doesn't
    // return null
    getSelectedCount: function() {
        var i, c = 0;
        for(i=0; i<this.selected.length; i++){
            if(this.selected[i] && this.grid.getItem(i)){
                c++;
            }
        }
        return c;
    }
});