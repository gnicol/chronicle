// summary:
// Populates notification areas if they are not already populated.
//
// Example usage:
// dojo.require('p4cms.ui.Notifications');
// var area1 = new p4cms.ui.Notifications();
// var area2 = new p4cms.ui.Notifications({
//     containerId:     'another-notice-area',
//     containerClass:  'area-2-class',
//     timeout:         15000,
// });

dojo.provide('p4cms.ui.Notifications');
dojo.require('dijit._Widget');
dojo.require('p4cms.ui.Notice');
dojo.require('p4cms.ui.Router');

dojo.declare('p4cms.ui.Notifications', dijit._Widget,
{
    // default containerId to contain notifications
    containerId: 'p4cms-ui-notices',

    containerClass: 'p4cms-ui',

    // duration for notices, in milliseconds
    timeout: 3000,

    // preference for background offset handling
    adjustBackground: true,

    // private timer for duration
    _timer: null,

    constructor: function(params) {

        // merge params with this instance
        dojo.mixin(this, params);

        var node = dojo.byId(this.containerId);

        if (p4cms.ui.disableNotifications) {
            if (node) {
                dojo.style(dojo.byId(this.containerId), 'display', 'none');
            }
            return;
        }

        // adjust the node's position if the toolbar does not exist
        if (node && !this.isToolbarActive() && this.containerId === 'p4cms-ui-notices') {
            dojo.style(node, 'top', '0px');
        }
    },

    isToolbarActive: function() {
        return p4cms.ui.toolbar && !p4cms.ui.disableManageToolbar &&
            p4cms.ui.toolbar.getPrimaryToolbar();
    }
});