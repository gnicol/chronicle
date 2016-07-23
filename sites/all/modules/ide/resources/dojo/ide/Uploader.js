// summary:
//      Uploads files into the ide Editor.
//      This uploader is based on the content uploader which provides
//      the bulk of the functionality. We just extend in a few places
//      to interface with the server differently.

dojo.provide("p4cms.ide.Uploader");
dojo.require("p4cms.content.dnd.Uploader");

dojo.declare("p4cms.ide.Uploader", p4cms.content.dnd.Uploader,
{
    editor:         null,
    uploadPath:     '',
    _pubSubBase:    'p4cms.ide.upload.',

    getDialog: function() {
        var dialog = this.inherited(arguments);

        dialog.set('title', 'Upload File');
        dialog.set('class', 'ide-upload p4cms-ui');

        // determine if user dropped file on a specific tree node.
        // if they did, set the upload path to the tree node id.
        if (this.event.srcElement) {
            var widget = dijit.getEnclosingWidget(this.event.srcElement);
            if (widget && widget.declaredClass === 'dijit._TreeNode') {
                if (widget.item.children) {
                    this.uploadPath = widget.item.id || widget.item.$ref;
                } else {
                    this.uploadPath = this.editor._dirname(widget.item.id);
                }
            }
        }

        // incorporate the upload path into the confirmation message.
        dojo.query('.message', dialog.domNode).html(
            'Are you sure you want to upload ' +
            (this.files.length === 1
                ? '"' + this.files[0].name + '"'
                : this.files.length + ' files') +
            ' into the ' +
            (this.uploadPath
                ? 'folder "' + this.uploadPath + '"?'
                : 'top-level folder?')
        );

        return this.dialog;
    },

    prepareFormData: function(file){
        return {
            _csrfToken: this.csrf,
            format:     'json',
            file:       this.uploadPath + '/' + file.name,
            data:       file
        };
    },

    getUploadUrl: function(file){
        return p4cms.url({
            module:     'ide',
            controller: 'index',
            action:     'files',
            format:     'json'
        });
    },

    isSuccess: function(e, data){
        return data !== false;
    }
});