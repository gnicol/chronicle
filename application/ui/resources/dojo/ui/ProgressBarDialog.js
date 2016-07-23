// summary:
//      Extended dialog with ability to display progress bar box(es).
//

dojo.provide("p4cms.ui.ProgressBarDialog");
dojo.require("p4cms.ui.Dialog");
dojo.require("dijit.form.Button");
dojo.require("dijit.ProgressBar");

dojo.declare("p4cms.ui.ProgressBarDialog", p4cms.ui.Dialog, {

    postCreate: function(){
        this.inherited(arguments);

        // add css class to identify as progressBar dialog
        dojo.addClass(this.domNode, 'p4cms-ui-progressBarDialog');

        // add close button
        this.addButton(new dijit.form.Button({
            id:         this.domNode.id + "-button-close",
            label:      "Close",
            onClick:    dojo.hitch(this, 'hide')
        }));
    },

    getProgressBox: function(id){
        return dojo.byId(id);
    },

    getAllProgressBoxes: function(){
        return dojo.query('.p4cms-progressBox', this.domNode);
    },

    addProgressBox: function(id){
        // bail if progress bar with given id already exists
        if (this.getProgressBox(id)){
            return;
        }

        var status  = dojo.create("div", {
            "class":    "p4cms-progressBox-status"
        });
        var bar     = dojo.create("div", {
            "class":    "p4cms-progressBox-bar"
        });

        // create progress bar widget and place it into the bar element
        var progressBar = new dijit.ProgressBar({
            id: id + '-progressBar'
        });
        dojo.place(progressBar.domNode, bar);

        // place elements inside progress box
        var progressBox = dojo.create("div", {
            "class":    "p4cms-progressBox",
            id:         id
        });
        dojo.place(status, progressBox);
        dojo.place(bar,    progressBox);

        // place progress box inside the dialog
        this.addContent(progressBox, 'first');
        this.layout();
    },

    updateStatus: function(id, newStatus){
        var box = this.getProgressBox(id);
        if (box) {
            dojo.query('.p4cms-progressBox-status', box)[0].innerHTML = newStatus;
        }
    },

    updateProgressBar: function(id, params){
        var progressBar = dijit.byId(id + '-progressBar');
        if (progressBar) {
            progressBar.update(params);
        }
    }
});