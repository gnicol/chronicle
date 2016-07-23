// summary:
//      Turns a given dom node into a drop target for content file uploads.

dojo.provide("p4cms.content.dnd.DropZone");
dojo.require("p4cms.content.dnd.Uploader");

dojo.declare("p4cms.content.dnd.DropZone", null,
{
    nodes:  [],
    node:   null,
    csrf:   '',

    constructor: function(args){
        dojo.safeMixin(this, args);

        // don't register drop-zone if browser doesn't support dnd uploads.
        if (!this.isSupported()) {
            return;
        }

        // refuse to register the same node twice.
        if (dojo.indexOf(this.nodes, this.node) !== -1) {
            return;
        } else {
            this.nodes.push(this.node);
        }

        // prevent normal browser behavior when dragging over drop zone.
        dojo.connect(this.node, 'dragenter', function(e) { dojo.stopEvent(e); });
        dojo.connect(this.node, 'dragover',  function(e) { dojo.stopEvent(e); });

        // if user drops a file, process it.
        dojo.connect(this.node, 'drop', dojo.hitch(this, 'onDrop'));
    },

    onDrop: function(e) {
        dojo.stopEvent(e);

        // nothing to do if no files in transfer event.
        var files;
        if (e.dataTransfer.files.length) {
            files = e.dataTransfer.files;
        } else {
            return;
        }

        // instantiate a new uploader to handle the dropped files.
        return this.getUploader(e, files, this.csrf);
    },

    getUploader: function(event, files, csrf) {
        return new p4cms.content.dnd.Uploader({
            event: event,
            files: files,
            csrf:  this.csrf
        });
    },

    // dnd uploads require the form data api
    isSupported: function() {
        /*global FormData*/
        return typeof(FormData) !== 'undefined';
    }
});
