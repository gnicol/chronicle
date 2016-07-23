// summary:
//      Support for search module.

dojo.provide("p4cms.search");
dojo.require('p4cms.search.base');
dojo.require('p4cms.ui.ProgressBarDialog');
dojo.require('p4cms.ui.ErrorDialog');

// function to send request of optimizing lucene search index to the server
// and get status updates, if any.
p4cms.search.optimize = function () {
    // create a dialog if it does not exist
    if (!p4cms.search.optimizeDialog) {
        p4cms.search.optimizeDialog = new p4cms.ui.ProgressBarDialog({
           title:   "Search Optimize"
        });
    }

    dojo.xhrPost({
        url:        p4cms.url({
            module:     'search',
            controller: 'manage',
            action:     'optimize',
            format:     'partial'
        }),
        handleAs:   'json',
        load: function(response){
            // update the progress bar
            p4cms.search.optimizeDialog.show();
            p4cms.search.updateProgress(p4cms.search.optimizeDialog);
        },
        error: function(response){
            var dialog = new p4cms.ui.ErrorDialog({
               content: response.responseText
            });
            dialog.show();
        }
    });
};

// function to send request for rebuilding search index.
p4cms.search.rebuild = function () {
    // create the dialog if not exists already
    if (!p4cms.search.rebuildDialog) {
        p4cms.search.rebuildDialog = new p4cms.ui.ProgressBarDialog({
           title:   "Search Rebuild"
        });
    }

    dojo.xhrPost({
        url:        p4cms.url({
            module:     'search',
            controller: 'manage',
            action:     'rebuild',
            format:     'partial'
        }),
        handleAs:   'json',
        load: function(response){
            // update the progress bar
            p4cms.search.rebuildDialog.show();
            p4cms.search.updateProgress(p4cms.search.rebuildDialog);
        },
        error: function(response){
            var dialog = new p4cms.ui.ErrorDialog({
               content: response.responseText
            });
            dialog.show();
        }
    });
};

// function to update the progress bar as well as the progress status message
p4cms.search.updateProgress = function(dialog) {
    // send a request to get the status update
    dojo.xhrPost({
        url: p4cms.url({
            module:     'search',
            controller: 'manage',
            action:     'status',
            format:     'json'
        }),
        handleAs:   'json',
        load: function(response){
            // if no response, recheck in 1 second
            if (!response) {
                setTimeout(
                    function(){
                        p4cms.search.updateProgress(dialog);
                    },
                    1000
                );
                return;
            }

            // remove old progress bars
            dojo.forEach(dialog.getAllProgressBoxes(), function(node){
                if (!dojo.hasClass(node, 'pid-' + response.pid)) {
                    dojo.destroy(node);
                }
            });

            // create progress bar if doesn't exist
            var id = 'progressBar-' + response.pid;
            if (!dialog.getProgressBox(id)) {
                dialog.addProgressBox(id);

                // add class related to pid so box can be removed when pid changes
                dojo.addClass(dialog.getProgressBox(id), 'pid-' + response.pid);
            }
            dialog.updateStatus(id, response.message);

            // update progress bar
            if (response.done){
                dialog.updateProgressBar(id, {progress: '100%'});
            } else {
                if (response.count) {
                    dialog.updateProgressBar(id, {
                        maximum:  response.total,
                        progress: response.count
                    });
                }

                // recheck the status in 1 second
                setTimeout(
                    function () {
                        p4cms.search.updateProgress(dialog);
                    },
                    1000
                );
            }
        },
        error: function(response){
            var dialog = new p4cms.ui.ErrorDialog({
               content: response.responseText
            });
            dialog.show();
        }
    });
};
