//      Handles content generation status bar.

dojo.provide("p4cms.generation");
dojo.require('p4cms.ui.ProgressBarDialog');
dojo.require('p4cms.ui.ErrorDialog');
dojo.require("dijit.ProgressBar");

p4cms.generation.showStatus = function(processId) {
    // create a dialog if it does not exist
    var progressDialog = new p4cms.ui.ProgressBarDialog({
       title:   "Content Generation"
    });

    // add view content button
    var viewButton = new dijit.form.Button({
        id:         progressDialog.domNode.id + "-button-view",
        label:      "View Content",
        onClick:    function() {
            p4cms.openUrl({
                module:     'content',
                controller: 'manage',
                action:     'index'
            });
        }
    });
    dojo.place(viewButton.domNode, progressDialog.getButtonFieldset(), 'first');

    // create progress bar
    var id = 'progressBar-' + processId;
    progressDialog.addProgressBox(id);

    // show the dialog
    progressDialog.show();

    p4cms.generation.updateProgress(progressDialog, processId);

};

// function to update the progress bar as well as the progress status message
p4cms.generation.updateProgress = function(dialog, processId) {
    // send a request to get the status update
    dojo.xhrGet({
        url: p4cms.url({
            module:     'generation',
            controller: 'index',
            action:     'status',
            format:     'json',
            processId:  processId
        }),
        handleAs:   'json',
        load: function(response){
            if (response) {
                // update progress bar
                var id = 'progressBar-' + processId;
                dialog.updateStatus(id, response.message);

                if (response.done){
                    dialog.updateProgressBar(id, {progress: '100%'});
                    return;
                } else {
                    if (response.count) {
                        dialog.updateProgressBar(id, {
                            maximum:  response.total,
                            progress: response.count
                        });
                    }
                }
            }

            // recheck the status in 1 second
            setTimeout(
                function () {
                    p4cms.generation.updateProgress(dialog, processId);
                },
                1000
            );
        }
    });
};
