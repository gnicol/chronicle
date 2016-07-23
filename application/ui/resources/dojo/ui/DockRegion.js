// summary:
//      A fixed position container that accepts a domNode
dojo.provide('p4cms.ui.DockRegion');

dojo.require('p4cms.ui.BackgroundOffset');
dojo.require("dijit._Templated");
dojo.require("dijit._Widget");

dojo.declare('p4cms.ui.DockRegion', [dijit._Widget, dijit._Templated], {
    // required config
    height:     null,

    name:       '',
    zIndex:     900,

    // display options
    position:           'north', // options 'north', 'south'
    displayMethod:      'push',  // options 'overlay', 'push'

    // autoHide options
    autoHide:               false,
    showCloseButton:        false,
    allowHide:              false,
    animType:               'slide', // options 'fade', 'slide'
    hideDelay:              400,
    hiddenTriggerHeight:    10,

    isCollapsed:            false,

    _orient:            'top',
    templateString:     '<div class="p4cms-ui p4cms-dock">'
                        + '<div dojoAttachPoint="containerNode"></div></div>',

    // if initially collapsed, hide region before it renders.
    postCreate: function(){
        this.inherited(arguments);

        // only honor isCollapsed if our toolbar has the potential to be hidden
        this.isCollapsed = this.isCollapsed && (this.allowHide || this.autoHide);

        if (this.isCollapsed) {
            dojo.style(this.containerNode, 'display', 'none');
        }
    },

    // lifecycle
    startup: function() {
        this.inherited(arguments);

        if (this.position === 'south') {
            this._orient = 'bottom';
            dojo.place(this.domNode, dojo.body(), 'last');
        } else {
            dojo.place(this.domNode, dojo.body(), 'first');
        }

        // determine starting height of the dock-region
        // if collapsed, only allow room for hidden trigger
        // otherwise, use explicit height if we have one.
        var height = 'auto';
        if (this.isCollapsed) {
            height = this.hiddenTrigger ? this.hiddenTriggerHeight + 'px' : '0';
        } else if (this.height) {
            height = this.height + 'px';
        }

        // position the dockregion fixed on screen
        var overlayStyle = {
            zIndex:     this.zIndex,
            position:   'fixed',
            height:     height
        };
        overlayStyle[this._orient] = 0;
        dojo.style(this.domNode, overlayStyle);

        // if we are in push mode, and are not autoHiding,
        // increase the background offset right away
        if (this.displayMethod === 'push' && !this.autoHide) {
            p4cms.ui.BackgroundOffset.increase(this.height);
        }

        // setup autohide
        if (this.autoHide) {
            this.allowHide = true;

            // create hiddenTrigger area to listen for events
            // while the dockregion contents are hidden
            this.hiddenTrigger = dojo.create('div', {
                style: {
                    width:      '100%',
                    position:   'absolute',
                    height:     this.hiddenTriggerHeight + 'px'
                }
            }, this.domNode, 'first');

            dojo.style(this.hiddenTrigger, this._orient, '0');

            // connect events
            this.connect(this.domNode,       'onmouseleave', this.collapse);
            this.connect(this.containerNode, 'onmouseenter', this.expand);
            this.connect(this.hiddenTrigger, 'onmouseenter', this.expand);
        }

        // setup close button
        if (this.showCloseButton) {
            this.allowHide =  true;
            this.closeButton = dojo.create('div', {
                'class':    'p4cms-dock-close-button',
                innerHTML:  '&times;',
                title:      'Hide Toolbar',
                style:      {
                    cursor:     'pointer',
                    position:   'absolute'
                }
            }, this.containerNode, 'first');

            this.connect(this.closeButton, 'onclick', 'toggle');
        }

        if (this.allowHide) {
            // prepare the container for hiding
            dojo.style(this.containerNode, {position: 'relative'});
            dojo.addClass(this.domNode, 'p4cms-dock-collapsible');
            dojo.addClass(this.domNode, 'p4cms-dock-expanded');
        }
    },

    // Add the passed node to the dock region
    addNode: function(node) {
        dojo.place(node, this.containerNode);
    },

    toggle: function(e) {
        if (dojo.style(this.containerNode, 'display') !== 'none'
            && (!this.hideAnimation || this.hideAnimation.status() !== "playing")) {
            this.collapse();
        } else {
            this.expand();
        }
    },

    // expands the dock using the animations setup in the dock config
    expand: function(){
        // stop the hide if we are in the process of hiding
        if (this.hideAnimation && this.hideAnimation.status() === "playing") {
            this.hideAnimation.stop();
        }

        // remember expanded state.
        this._setIsCollapsed(false);

        // grab the animation
        this.showAnimation = this.getAnimation(true);
        this.showAnimation.onEnd = dojo.hitch(this, function() {
            if (this.displayMethod === 'push') {
                if (this.position === 'south') {
                    dojo.style(dojo.body(), 'paddingBottom', this.height + 'px');
                } else {
                    p4cms.ui.BackgroundOffset.increase(this.height);
                }
            }
        });

        // set the starting state
        if(this.hiddenTrigger) {
            dojo.style(this.hiddenTrigger, 'display', 'none');
        }

        dojo.replaceClass(this.domNode, 'p4cms-dock-collapsed', 'p4cms-dock-expanded');
        dojo.style(this.containerNode, 'display', '');
        dojo.style(this.domNode, 'height', this.height + 'px');

        this.showAnimation.play();
    },

    // collapses the dock using the animations setup in the dock config
    collapse: function(){
        // stop the show if we are in the process of showing
        if (this.showAnimation && this.showAnimation.status() === "playing") {
            this.showAnimation.stop();
        }

        // remember collapsed state.
        this._setIsCollapsed(true);

        // grab the animation
        this.hideAnimation = this.getAnimation(false);
        this.hideAnimation.onEnd = dojo.hitch(this, function() {
            if (this.hiddenTrigger) {
                dojo.style(this.hiddenTrigger, 'display', '');
            }
            dojo.replaceClass(this.domNode, 'p4cms-dock-expanded', 'p4cms-dock-collapsed');
            dojo.style(this.containerNode, 'display', 'none');
            dojo.style(this.domNode, 'height', (this.hiddenTrigger ? this.hiddenTriggerHeight : 0) + 'px');
        });

        // set the starting state for the push displayMethod
        if (this.displayMethod === 'push') {
            if (this.position === 'south') {
                dojo.style(dojo.body(), 'paddingBottom', '0px');
            } else {
                p4cms.ui.BackgroundOffset.decrease(this.height);
            }
        }

        this.hideAnimation.play();
    },

    // Returns the appropriate animation, taking a flag to
    // determine whether to show or hide the dock region
    getAnimation: function(isShow) {
        // if set, return fade animation
        if (this.animType === 'fade') {
            return isShow ?
                dojo._fade({node:this.containerNode, start: 0, end:1, duration:350}) :
                dojo.fadeOut({node:this.containerNode, duration: this.hideDelay});
        }

        // default to returning the slide animation
        var distance = this.getDistance(),
            position = {left: {start:0, end: 0}};
        position[this._orient] = {
            start:  isShow ? -distance : 0,
            end:    isShow ? 0 : -distance
        };

        return dojo.animateProperty({
            node:       this.containerNode,
            properties: position,
            duration:   this.hideDelay
        });
    },

    getDistance: function() {
        return this.height;
    },

    // record collapsed state in a cookie.
    _setIsCollapsed: function(isCollapsed) {
        this.isCollapsed = isCollapsed;
        dojo.cookie(
            (this.name || this.id) + '_isCollapsed',
            isCollapsed ? '1' : '0',
            {path: (p4cms.baseUrl || '/')}
        );
    }
});
