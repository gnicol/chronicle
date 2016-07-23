//      Handles content wpimport status bar.

dojo.provide("p4cms.wpimport");
dojo.require('p4cms.ui.ProgressBarDialog');
dojo.require('p4cms.ui.ErrorDialog');
dojo.require("dijit.ProgressBar");

p4cms.wpimport.showStatus = function(processId) {
    // create a dialog if it does not exist
    var progressDialog = new p4cms.ui.ProgressBarDialog({
       title:   "Import Content"
    });
    dojo.addClass(progressDialog.domNode, 'wpimport');

    // create progress bar
    var id = 'progressBar-' + processId;
    progressDialog.addProgressBox(id);

    // show the dialog
    progressDialog.show();

    p4cms.wpimport.updateProgress(progressDialog, processId);

};

// function to update the progress bar as well as the progress status message
p4cms.wpimport.updateProgress = function(dialog, processId) {
    // send a request to get the status update
    dojo.xhrGet({
        url: p4cms.url({
            module:     'wpimport',
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
                    // if we're done, show the final message, overwriting the progress bar.
                    dojo.query('div.p4cms-progressBox', dialog.domNode)[0].innerHTML = response.message;
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
                    p4cms.wpimport.updateProgress(dialog, processId);
                },
                1000
            );
        }
    });
};
