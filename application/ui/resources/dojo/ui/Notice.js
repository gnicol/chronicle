// summary:
// Manages the notifications within one or more notification areas.
//
// Example usage:
// dojo.require('p4cms.ui.Notices');
// var errorNotice = new p4cms.ui.Notice({message: 'Error', severity: 'error'});
// var infoNotice = new p4cms.ui.Notice({message: 'A message'});
//
// New notices will create their containers if they do not already exist. Specify
// containerId and containerClass as appropriate. You can also specify
// containerStyles for inline, one-off styling.
//
// Severities:
//  - info
//  - success
//  - warning
//  - error     (sticky by default)

dojo.provide('p4cms.ui.Notice');
dojo.require('dijit._Widget');
dojo.require('dijit._Templated');

dojo.declare('p4cms.ui.Notice', [dijit._Widget, dijit._Templated], {
    // convention defining HTML template resource
    templateString: '<div class="severity-${severity}" '
        + 'dojoAttachEvent="onmouseover:_hoverStart,onmouseout:_hoverEnd,onclick:close">'
        + '<p class="message">${message}</p>'
        + '<div class="footer"></div>'
        + '</div>',


    // the message for this notice
    message: '',

    // severity for this notice
    // This is primarily for CSS styling; notices will have class 'severity-info' by
    // default. Notices having 'error' severity will not be user-dismissable, and will
    // not timeout.
    severity: 'info',

    // duration this notice should be displayed, in milliseconds.
    timeout: 3000,

    // preference for background offset handling
    // If true, adjusts the page background offset so the notice does not obscure page
    // content. If false, the notice may obscure content.
    adjustBackground: true,

    // private DOM object to contain this notice
    _container: null,

    // container node id
    containerId: 'p4cms-ui-notices',

    // default css class name for the notices container.
    containerClass: 'p4cms-ui',

    // private timer object for duration
    _timer: null,

    // private height for this notice
    _height: 0,

    // styling for the notice container, empty to allow CSS file to specify,
    // but exists for per-container styling for ad-hoc notifications.
    containerStyles: { },

    // a name to identify this notice among all notices.
    name: '',

    // make notice stay open (ie. ignore timeout)
    // true for errors by default.
    sticky: false,

    // if a notice name is specified, remove any conflicting notices.
    postMixInProperties: function() {
        if (this.name) {
            this.id = 'p4cms-ui-notice-' + this.name;
            if (dijit.byId(this.id)) {
                dijit.byId(this.id).destroy();
            }
        }
    },

    // construct
    constructor: function(params) {
        // error notices should be sticky by default.
        if (params.severity === 'error') {
            this.sticky = true;
        }

        dojo.mixin(this, params);
        this.createWrapper();
    },

    // locate/create the wrapper to contain the notices.
    createWrapper: function() {
        if (dojo.byId(this.containerId)) {
            this.container = dojo.byId(this.containerId);
            dojo.addClass(this.container, this.containerClass);
        } else {
            this.container = dojo.create('div', {
                'id':       this.containerId,
                'class':    this.containerClass
            });

            dojo.style(this.container, this.containerStyles);
            dojo.place(this.container, dojo.body(), 'last');
        }
    },

    // postCreate happens after templating, which creates this.domNode
    postCreate: function() {
        dojo.style(this.domNode, 'opacity', 0);
        dojo.place(this.domNode, this.container);
        dojo.anim(this.domNode, { opacity: 1 }, 250);
        this.setTimeout();
    },

    // setup timer to auto hide notices, except sticky notices
    setTimeout: function() {
        if (!this.sticky && this.timeout > 0) {
            this.timer = setTimeout(dojo.hitch(this, 'close'), this.timeout);
        }
    },

    // when hovering, disable the timer
    _hoverStart: function() {
        clearInterval(this.timer);
        dojo.addClass(this.domNode, 'hover');
    },

    // when no longer hovering, restart the timer
    _hoverEnd: function() {
        this.setTimeout();
        dojo.removeClass(this.domNode, 'hover');
    },

    // retrieve the node containing the message
    getMessageNode: function() {
        return dojo.query('p.message', this.domNode)[0];
    },

    // behaviour for 'closing' notice
    close: function() {
        clearInterval(this.timer);

        dojo.anim(this.domNode, { opacity: 0 }, 500, null,
            dojo.hitch(this, 'remove')
        );

        return this;
    },

    // remove this notice
    remove: function() {
        clearInterval(this.timer);

        dojo.anim(this.domNode,
            { height: 0, margin: 0 },
            250,
            null,
            dojo.partial(dojo.destroy, this.domNode)
        );
        dojo.destroy(this.domNode);
    },

    // remove notice on destroy.
    destroy: function() {
        this.remove();
        this.inherited(arguments);
    }
});