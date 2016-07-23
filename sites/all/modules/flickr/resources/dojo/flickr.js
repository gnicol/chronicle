dojo.provide("p4cms.flickr.SlideShow");

dojo.require("dojox.data.FlickrRestStore");
dojo.require("dojox.image.SlideShow");

dojo.declare("p4cms.flickr.SlideShow", [dojox.image.SlideShow],
{
    apikey:         '',
    userid:         '',
    groupid:        '',
    tags:           '',
    widgetId:       '',
    widgetRegion:   '',
    autoLoad:       false,
    pageSize:       10,

    postCreate: function() {
        this.inherited(arguments);

        var imageStore = new dojox.data.FlickrRestStore();
        var request = {
            query: {
                apikey:         this.apikey,
                userid:         this.userid,
                groupid:        this.groupid,
                tags:           this.tags,
                widgetId:       this.widgetId,
                widgetRegion:   this.widgetRegion
            }
        };

        this.setDataStore(imageStore, request);

        // unfix the outerNode's width so that the image can have a border
        this.outerNode.style.width = 'auto';
    },

    // copy of parent method to customize for our custom styling, and to
    // ensure the innerWrapper gets a reasonable height for MSIE
    _fitSize: function(force) {
        if (!this.fixedHeight || force || dojo.isIE) {
            var height = this._currentImage.height + (this.hasNav ? 50 : 0);
            dojo.style(this.innerWrapper, "height", height + "px");
            return;
        }
        dojo.style(this.largeNode, "paddingTop", this._getTopPadding() + "px");
    }
});