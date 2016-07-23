// summary:
//      Uploads a given set of files as new content entries. Intended
//      to be used with the drag and drop DataTransfer API. Presents a
//      confirmation dialog and then proceeds to upload files in parallel
//      and shows a progress bar.

dojo.provide("p4cms.content.dnd.Uploader");
dojo.require("p4cms.ui.ConfirmDialog");
dojo.require("dijit.ProgressBar");

dojo.declare("p4cms.content.dnd.Uploader", null,
{
    event:          null,
    files:          null,
    csrf:           '',
    dialog:         null,
    uploaded:       null,
    completed:      null,
    _pubSubBase:    'p4cms.content.dnd.upload.',

    constructor: function(args){
        dojo.safeMixin(this, args);
        this.csrf =  this.csrf || p4cms.ui.csrfToken;

        // nothing to do if no files.
        if (!this.files.length) {
            return;
        }

        // show upload confirmation dialog.
        this.getDialog().show();
    },

    getDialog: function() {
        // setup confirmation dialog.
        if (!this.dialog) {
            var message = '<span class=message>'
                        + 'Are you sure you want to upload '
                        + (this.files.length === 1 ? this.files[0].name : this.files.length + ' files')
                        + '?</span> '
                        + '<div class=progress></div>';
            this.dialog = new p4cms.ui.ConfirmDialog({
                title:                  'Upload Content',
                content:                message,
                showSpinner:            false,
                actionButtonOptions:    {label: 'Upload'},
                actionSingleClick:      true,
                onConfirm:              dojo.hitch(this, 'onConfirm'),
                'class':                'content-upload'
            });
        }

        return this.dialog;
    },

    onConfirm: function(){
        this.uploaded  = {};
        this.completed = [];

        // kick off all of the file uploads.
        var i;
        for (i = 0; i < this.files.length; i++) {
            var file;
            var data;
            var http;
            var form;
            var key;

            file = this.files[i];
            file.index = i;

            // prepare form data.
            data = this.prepareFormData(file);

            // allow third parties to influence file upload post data.
            dojo.publish(
                this._pubSubBase + 'data',
                [file, data]
            );

            // setup form object with prepared data.
            // note the following line informs jslint that FormData is a global.
            /*global FormData*/
            form = new FormData();
            for (key in data) {
                if (data.hasOwnProperty(key)) {
                    form.append(key, data[key]);
                }
            }

            // setup http post request
            // - track upload progress
            // - track when file uploads complete
            http = new XMLHttpRequest();
            this.connectProgressHandler(http, file);
            this.connectLoadHandler(http, file);

            http.open('POST', this.getUploadUrl(file));
            http.send(form);
        }
    },

    prepareFormData: function(file){
        return {
            _csrfToken:  this.csrf,
            format:      'json',
            contentType: 'file',
            file:        file,
            title:       file.name
        };
    },

    getUploadUrl: function(file){
        return p4cms.url({
            module:     'content',
            controller: 'index',
            action:     'add'
        });
    },

    connectProgressHandler: function(http, file){
        dojo.connect(http, 'progress', dojo.hitch(this, function(e){
            if (e.lengthComputable) {
                this.uploaded[file.name] = e.loaded;
            }
            this.updateProgress();
        }));
    },

    connectLoadHandler: function(http, file){
      dojo.connect(http, 'load', dojo.hitch(this, function(e){
            this.completed.push(file);
            this.uploaded[file.name] = file.size;
            this.updateProgress();

            // attempt to decode a json response.
            var data = {};
            try {
                data = dojo.fromJson(e.currentTarget.responseText);
            } catch (error) {
                // no json response
            }

            // if upload successful, call uploaded file hook; otherwise report error.
            if (this.isSuccess(e, data)) {
                this.onFileUploaded(file, e, data);
            } else {
                p4cms.ui.Notice({
                    message:  'Failed to upload ' + file.name,
                    severity: 'error'
                });
            }

            // if all files now uploaded, close dialog and call onComplete hook.
            if (this.completed.length >= this.files.length) {
                dojo.publish(this._pubSubBase + 'complete', [this.files]);
                window.setTimeout(dojo.hitch(this.getDialog(), 'hide'), 500);
                this.onComplete(this.files, e, data);
            }
        }));
    },

    isSuccess: function(e, data){
        return e.currentTarget.status === 200 && data.isValid;
    },

    // stubs for sub-classes or third parties to use.
    onFileUploaded: function(file, e, data){
    },
    onComplete: function(files, e, data){
    },

    getProgressBar: function(){
        if (!this.progressBar) {
            this.progressBar = new dijit.ProgressBar({maximum: this.getTotalBytes()});
            this.getDialog().addContent(this.progressBar.domNode);
        }

        return this.progressBar;
    },

    updateProgress: function(){
        this.getProgressBar().update({progress: this.getBytesUploaded()});
    },

    getTotalBytes: function(){
        var i, totalSize = 0;
        for (i = 0; i < this.files.length; i++) {
            totalSize += this.files[i].size;
        }
        return totalSize;
    },

    getBytesUploaded: function(){
        var i, bytes = 0;
        for (i in this.uploaded) {
            if (this.uploaded.hasOwnProperty(i)) {
                bytes += this.uploaded[i];
            }
        }
        return bytes;
    }
});
