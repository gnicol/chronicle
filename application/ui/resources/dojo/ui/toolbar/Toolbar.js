// summary:
//      Toolbar controls the displaying of the menus it is supplied,
//      including handling drawer pull outs and menu activation
dojo.provide('p4cms.ui.toolbar.Toolbar');

dojo.require('dijit._Widget');
dojo.require('dijit._KeyNavContainer');
dojo.require("p4cms.ui.DockRegion");
dojo.require('dojo.fx');
dojo.require('p4cms.ui.toolbar.MenuButton');
dojo.require("dojo.NodeList-traverse");
dojo.require("dojo.NodeList-manipulate");

dojo.declare('p4cms.ui.toolbar.Toolbar', [dijit._Widget, dijit._Templated, dijit._KeyNavContainer], {

    scopeClass:     "p4cms-ui",
    baseClass:      "manage-toolbar",
    height:         36,

    templateString: '<div class="${scopeClass}" tabIndex="${tabIndex}">'
                   +'<div class="${baseClass}-container" dojoAttachPoint="containerNode"></div></div>',

    displayContext: '',

    // config
    //      controls direction drawers are opened from the toolbar
    // options 'below', 'above'
    orientDrawers:  'below',
    dockConfig:     null,

    menuNode:       null,
    activeButton:   null,

    // lifecycle
    postCreate: function() {
        this.inherited(arguments);
        this.connectKeyNavHandlers([dojo.keys.LEFT_ARROW],[dojo.keys.RIGHT_ARROW]);
    },

    // lifecycle
    buildRendering: function() {
        this.inherited(arguments);

        dojo.style(this.domNode, {height: this.height + 'px'});
        dijit.setWaiRole(this.domNode, 'menubar');
        dojo.attr(this.domNode, 'accesskey', 'm');

        // grab top level menu
        this.menuNode = dojo.query('> ul', this.containerNode);

        if (this.menuNode.length < 1) {
            this.menuNode = new dojo.NodeList(dojo.create('ul', null, this.containerNode));
        }
    },

    // lifecycle
    startup: function() {
        dojo.removeClass(this.domNode, 'toolbar-disabled');

        this.inherited(arguments);

        if (p4cms.ui.disableManageToolbar) {
            dojo.style(this.domNode, 'display', 'none');
            return;
        }

        // initialize the button management
        this.initButtons(this.menuNode.children());

        // set as the primary so that any toolbar actions
        // are routed through this object
        p4cms.ui.toolbar.setPrimaryToolbar(this);

        this.dockConfig = dojo.mixin({height: this.height, name: 'toolbar'}, this.dockConfig);
        this.dockRegion = this.dock(this.dockConfig, this.dockRegion);
        dojo.style(this.dockRegion.domNode, {width: '100%'});

        // markup may explicity hide toolbar to prevent it from
        // flashing while the page loads, but we've got it from here.
        dojo.style(this.domNode, 'display', '');

        // override getDistance to measure the current height of the toolbar
        // including the drawers
        this.dockRegion.getDistance = dojo.hitch(this, function() {
            this._setDockDisplayForSize();
            var distance = this.getSize(true).h;
            this._restoreLastDockDisplay();

            return distance;
        });

        this.orientDrawers = (this.dockRegion.position === 'south'
                           ? 'above' : 'below');

        dojo.publish('p4cms.ui.toolbar.created',        [this]);
        dojo.publish('p4cms.ui.toolbar.contextChange',  [this]);

        this.startupKeyNavChildren();
    },

    // register the top level menus as buttons within this toolbar
    initButtons: function(menuList) {
        this.leftGroup  = dojo.create('span', {'class' : 'left-group'},
            this.menuNode[0]);
        this.rightGroup = dojo.create('span', {'class' : 'right-group'},
            this.menuNode[0]);

        menuList.forEach(function(menuNode, index) {
            // find the menu buton
            var menuButton = dijit.byNode(dojo.query('.menu-button', menuNode)[0]);

            // filter out content that doesn't belong in this context
            var context = menuButton.displayContext;
            if (context && context !== this.displayContext) {
                this.removeMenuButton(menuButton, true);
                return;
            }

            this.addMenuButton(menuButton, menuNode);
        }, this);
    },

    // remove a menuButton from the toolbar
    removeMenuButton: function(menuButton, destroy) {
        var menuNode = new dojo.NodeList(menuButton.domNode).closest('li')[0];

        // only destroy if menu belongs to this toolbar
        if (dojo.isDescendant(menuNode, this.domNode)) {
            if (destroy) {
                if (menuButton.drawer) {
                    dojo.destroy(menuButton.drawer);
                }
                menuButton.destroy();
            }
            dojo.destroy(menuNode);
        }
    },

    // add a new menuButton to the toolbar
    addMenuButton: function(menuButton, menuNode) {
        // if we don't have a menu node create one
        if (!menuNode) {
            var nodes   = new dojo.NodeList(menuButton.domNode).wrap('<li></li>');
            menuNode    = menuButton.domNode.parentNode;
        }

        // add classes to the button's menu node for identification
        dojo.addClass(menuNode, menuButton.iconClass + '-menu-node menu-node');

        // move menu to it's proper alignment
        if (menuButton.menuAlign === 'right') {
            dojo.place(menuNode, this.rightGroup);
        } else {
            dojo.place(menuNode, this.leftGroup);
        }

        // create toolbar drawer if this menu node contains additional menus
        // or if the button includes a drawer
        var subMenu = dojo.query('ul', menuNode)[0];
        if (subMenu || menuButton.hasDrawer) {
            menuButton.drawer = this.createDrawer(menuButton, subMenu);
        }

        if (subMenu) {
            // Remove empty headings
            dojo.query('.type-heading', subMenu).forEach(function(node) {
                if (dojo.query('ul', node.parentNode).length < 1) {
                    dojo.destroy(node.parentNode);
                }
            });

            // hide empty menus
            if (dojo.query('li', subMenu).length < 1) {
                dojo.style(menuButton.domNode, 'display', 'none');
            }
        } else if (!menuButton.hasDrawer && menuButton.getDropDown) {
            menuButton.dropDown = menuButton.getDropDown();
        }

        // register close on blur
        if (menuButton.closeOnBlur) {
            this.registerCloseOnBlur(menuButton);
        }

        // only toggle buttons that are not dropdowns
        if (!menuButton.dropDown) {
            menuButton.connect(menuButton, '_onClick',
                dojo.hitch(this, 'buttonToggle', menuButton));
        }
    },

    // creates a toolbar drawer and places any provided content within the drawer
    createDrawer: function(button, content) {
        var drawer  = dojo.create('div', {'class':button.iconClass
                        + '-toolbar-drawer toolbar-drawer', style:{height:'0px'}}, this.domNode),
            pane    = dojo.create('div', {'class':'toolbar-pane'}, drawer);

        dijit.setWaiRole(pane, 'navigation');

        if (content) {
            // add class so we can style the pane differently if
            // it has predefined content
            dojo.addClass(pane, 'toolbar-sub-menu');
            dojo.place(content, pane);
            dojo.create('br', {style:{clear: 'both'}}, pane);
        }

        return drawer;
    },

    // hides the buttons with a particular class
    hideButton: function(buttonClass) {
        this.setButtonDisplay(buttonClass, true);
    },

    // shows the buttons with a particular class
    showButton: function(buttonClass) {
        this.setButtonDisplay(buttonClass);
    },

    // manages the display property of the button with the passed class
    // second parameter is a boolean that controls whether we are setting
    // the display to hidden or not
    setButtonDisplay: function(buttonClass, hide) {
        var display = hide ? 'none' : '',
            button  = this.getButtonByClass(buttonClass);

        if (button) {
            dojo.style(button.domNode, 'display', display);
        }

        if (hide) {
            this.deactivateButtonByClass(buttonClass);
        }
    },

    // triggers the sequence of activating the new button
    // and deactivating any previous buttons.
    buttonToggle: function(menuButton) {
        // setup a dom action sequence
        this.actions = [];
        var active = this.activeButton;
        // deactivate current active
        this.deactivateButton();

        if (active !== menuButton) {
            // set button as active if toggleable
            this.activateButton(menuButton);
        }

        // execute the sequence of actions and
        // end the sequence
        // wrap in setTimeout to work around an IE7 problem,
        // see this.openDrawer() for details
        setTimeout(dojo.hitch(this, function() {
            if (this.actions.length) {
                dojo.fx.chain(this.actions).play();
                this.actions = null;
            }
        }), 0);
    },

    // used to deactivate the current active button
    deactivateButton: function() {
        if (this.activeButton) {
            this.activeButton.onDeactivate();
            // close drawer if we have one
            if (this.activeButton.drawer) {
                this.closeDrawer(this.activeButton.drawer);
            }
            this.activeButton.set('checked', false);
            this.activeButton = null;
        }
    },

    // used to activate the provided button
    activateButton: function(menuButton) {
        // activate button
        menuButton.onActivate();

        // if button has a drawer or includes the canToggle flag, allow it to lock
        if (menuButton.drawer || menuButton.canToggle) {

            this.activeButton = menuButton;
            this.activeButton.set('checked', true);

            // open drawer
            if (this.activeButton.drawer) {
                // run drawer callback
                var container = dojo.query('div.toolbar-pane', this.activeButton.drawer)[0];
                this.activeButton.onDrawerLoad(container);

                this.openDrawer(this.activeButton.drawer);
            }
        }
    },

    // opens the toolbar drawer
    openDrawer: function(drawer) {
        // include onEnd listener in animation
        // wrap the doAnimation() method in setTimeout to work around a problem
        // in IE7 where the drawer pane height is calculated incorrectly because
        // the ul element is rendered vertically at first.
        setTimeout(dojo.hitch(this, function() {
            this.doAnimation(drawer, function() {
                // fix any lingering height issue that may have resulted
                // from the height being computed during other
                // animations
                dojo.style(drawer, 'height', 'auto');
            });
        }), 0);
    },

    // closes the toolbar drawer
    closeDrawer: function(drawer) {
        // reverse the animation
        this.doAnimation(drawer, null, true);
    },

    // does the animating of sliding the drawer open
    // by turning on the reverse flag, the animation will
    // close the drawer.
    doAnimation: function(drawer, onEnd, reverse) {
        this._setDockDisplayForSize();

        var duration    = 200,
            pane        = dojo.query('.toolbar-pane', drawer)[0],
            height      = dojo._getMarginSize(pane).h;

        // if the page contains no content, skip showing it.
        if (dojo.query('*', pane).length < 1) {
            height = 0;
            duration = 1;
            onEnd = null;
        }

        // grab the position and create animation
        var position    = this.calculateDrawerPosition(drawer, height, reverse),
            anim        =  dojo.fx.combine([
                dojo.animateProperty({
                    node:       drawer,
                    duration:   duration,
                    properties: position.drawer
                }),
                dojo.animateProperty({
                    node:       pane,
                    duration:   duration,
                    properties: position.pane,
                    onEnd: onEnd || function() {}
                })
            ]);

        this._restoreLastDockDisplay();

        // If we are queing up actions, add to the queue
        // else play the animation right away
        if (this.actions) {
            this.actions.push(anim);
        } else {
            anim.play();
        }
    },

    // calculates new drawer position using the drawer height,
    // direction of animation, and the desired orientation of the drawer (above or below)
    calculateDrawerPosition: function(drawer, drawerHeight, reverse) {
        // grab toolbar height to use in offset
        var toolbarHeight   = dojo.contentBox(this.domNode).h,
            drawerPosition  = {
                height: {
                    start:  (reverse ? drawerHeight : 0),
                    end:    (reverse ? 0 : drawerHeight),
                    unit:   'px'
                }
            },
            panePosition    = {},
            orient          = 'top';

        switch(this.orientDrawers) {
            case 'above':
                orient = 'bottom';
                break;
        }

        panePosition[orient] = {
            start:  (reverse ? 0 : -drawerHeight),
            end:    (reverse ? -drawerHeight : 0),
            unit:   'px'
        };

        drawerPosition[orient] = {
            start:  toolbarHeight,
            end:    toolbarHeight,
            unit:   'px'
        };

        return {drawer: drawerPosition, pane: panePosition};
    },

    // returns the toolbar size
    // set flag to include current drawer size in the calculation
    getSize: function(withDrawer) {
        var size = dojo.contentBox(this.domNode);

        if (withDrawer && this.activeButton && this.activeButton.drawer) {
            var drawerSize = dojo._getMarginSize(this.activeButton.drawer);
            size.h += drawerSize.h;
            size.w += drawerSize.w;
        }

        return size;
    },

    // add a click listener to deactivate an active button when the user clicks elsewhere
    // in the browser window.
    registerCloseOnBlur: function(button) {
        this.connect(button, 'onActivate', function() {
            button.bodyClickHandler = this.connect(dojo.body(), 'onclick', function(event) {
                var target = event.target || event.srcElement;
                if (this.activeButton === button && target && target.parentNode) {
                    setTimeout(dojo.hitch(this, function() {
                        var ignore = true;
                        try {
                           ignore = this.toolbarIngoreBlur(event);
                        } catch (exception) {
                            // if we have an exception, don't close, we don't
                            // want to block the user, they can close manually
                        }

                        // don't blur if we found a ignored element
                        if (!ignore) {
                            this.deactivateButton();
                        }
                    }), 100);
                }
            });
        });

        this.connect(button, 'onDeactivate', function() {
            if (button.bodyClickHandler) {
                this.disconnect(button.bodyClickHandler);
                delete button.bodyClickHandler;
            }
        });
    },

    // hooks in a list of queries to ingore
    toolbarIngoreBlur: function(event) {
        var target = event.target || event.srcElement;

        // ignore clicks within this toolbar
        var ignore  = (target === this.domNode);

        if (!ignore) {
            // create filter queries for addition nodes to ignore
            var filters = ['.p4cms-dock', '.toolbar-popup-menu', '.dijitDialog',
                '.dijitTooltipDialog', '.dijitDialogUnderlay'];

            // publish to allow other modules to add queries
            dojo.publish('p4cms.ui.toolbar.ignoreFilters.populate',
                [this, event, filters]);

            var node = target,
                root = dojo.body();

            // walk up the dom tree, running our filters on each node,
            // until we reach the root or we find an ignored node
            while (!ignore && node !== root && node.nodeType === 1) {
                var i;
                for (i = 0; i < filters.length; i++) {
                    if (dojo._filterQueryResult([node], filters[i], root).length) {
                        ignore = true;
                        break;
                    }
                }

                if (!node.parentNode) {
                    ignore = true;
                }

                node = node.parentNode;
            }
        }

        return ignore;
    },

    // get button by class
    getButtonByClass: function(cls) {
        var found = dojo.query("." + this.baseClass + "-" + cls
                + '-menu-node .menu-button', this.domNode)[0];

        return (found ? dijit.byNode(found) : null);
    },

    // open the button with provided class
    activateButtonByClass: function(cls) {
        var found = this.getButtonByClass(cls);
        if (found && (!this.activeButton || this.activeButton !== found)) {
            this.buttonToggle(found);
        }
    },

    // close the button with the provided class
    deactivateButtonByClass: function(cls) {
        var found = this.getButtonByClass(cls);
        if (found && this.activeButton && this.activeButton === found) {
            this.deactivateButton();
        }
    },

    // dock the toolbar to the specified dock region
    dock: function (dockConfig, dockRegion) {
        if (!dockRegion) {
            dockRegion = new p4cms.ui.DockRegion(dockConfig);
            dojo.place(dockRegion.domNode, dojo.body());
            dockRegion.startup();
        }

        dockRegion.addNode(this.domNode);

        return dockRegion;
    },

    _setDockDisplayForSize: function() {
        this._lastDockDisplay       = dojo.style(this.dockRegion.containerNode, 'display');
        this._lastDockVisibility    = dojo.style(this.dockRegion.containerNode, 'visibility');

        // exit early if the dock is already available for sizing
        if (this._lastDockDisplay !== 'none') {
            return;
        }

        dojo.style(this.dockRegion.containerNode, {
            display:        'block',
            visibility:     'hidden'
        });
    },

    _restoreLastDockDisplay: function() {
        dojo.style(this.dockRegion.containerNode, {
            display:        this._lastDockDisplay,
            visibility:     this._lastDockVisibility
        });
    },

    // overridden to grab menubutton widgets
    _getSiblingOfChild: function(child, dir){
        var index = this.getIndexOfChild(child),
            offset = (dir>0 ? 1 : -1);

        if (index+offset < 0 || index+offset >= this.getChildren().length) {
            return null;
        }

        return this.getChildren()[index+offset];
    },

    // overridden to fire onClick when accessible keys are pressed
    _onContainerKeypress: function(evt) {
        this.inherited(arguments);

        // the escape key should always deactivate any active button
        if (evt.keyCode === dojo.keys.ESCAPE && this.activeButton) {
            dojo.stopEvent(evt);
            this.focusMenuElement(this.activeButton.domNode);
            p4cms.ui.trigger(this.activeButton.domNode, 'click');
        } else if (this.focusedChild) {
            // add keyboard focus class
            this.focusMenuElement(document.activeElement);

            // if key target is our focusedChild, perform triggers
            // else if it's within our focusedChild's drawer, navigate within the drawer
            if (evt.target === this.focusedChild.domNode) {
                var isTrigger   = evt.keyCode === dojo.keys.ENTER || evt.charCode === dojo.keys.SPACE,
                    downClosed  = evt.keyCode === dojo.keys.DOWN_ARROW
                                && this.activeButton !== this.focusedChild && this.focusedChild.drawer,
                    upOpened    = evt.keyCode === dojo.keys.UP_ARROW
                                && this.activeButton === this.focusedChild && this.focusedChild.drawer;

                // determine if a trigger action is required
                if (isTrigger || downClosed || upOpened) {
                    dojo.stopEvent(evt);
                    p4cms.ui.trigger(this.focusedChild.domNode, 'click');
                    isTrigger = true;
                }

                var isOpen = this.focusedChild.drawer && this.activeButton === this.focusedChild;

                // Focus the first element if we have performed a keyboard open,
                // or if we have used the down arrow while the drawer is open
                if (!upOpened && (isTrigger || evt.charOrCode === dojo.keys.DOWN_ARROW) && isOpen) {
                    dojo.stopEvent(evt);
                    this.focusMenuElement(dijit.getFirstInTabbingOrder(this.focusedChild.drawer));
                }
            } else if (this.focusedChild.drawer) {
                var nav = p4cms.ui.getSiblingTabNavigable(evt.target, this.focusedChild.drawer);
                switch (evt.keyCode) {
                    case dojo.keys.DOWN_ARROW:
                        dojo.stopEvent(evt);
                        // focus next element
                        // if there is no next, focus the first one in the tabbing order
                        this.focusMenuElement(nav.next || dijit.getFirstInTabbingOrder(this.focusedChild.drawer));
                        break;
                    case dojo.keys.UP_ARROW:
                        dojo.stopEvent(evt);
                        // focus previous element,
                        // if there is no previous, move back up to the focusedChild
                        this.focusMenuElement(nav.previous || this.focusedChild.domNode);
                        break;
                }
            }
        }
    },

    // add a custom class to differentiate
    // keyboard focus from mouse focus
    focusMenuElement: function(element) {
        if (element) {
            dijit.focus(element);
            if(!dojo.hasClass(element, 'keyboard-focus')) {
                dojo.addClass(element, 'keyboard-focus');
                this.connect(element, 'onblur', function() {
                    dojo.removeClass(element, 'keyboard-focus');
                });
           }
        }
    }
});

// static methods used to access the primary toolbar
dojo.mixin(p4cms.ui.toolbar, {
    primaryToolbar: null,
    setPrimaryToolbar: function(toolbar) {
        // primary toolbar is the first toolbar to use this setter
        if (!p4cms.ui.toolbar.primaryToolbar) {
            p4cms.ui.toolbar.primaryToolbar = toolbar;
        }
    },
    getPrimaryToolbar: function() {
        return p4cms.ui.toolbar.primaryToolbar;
    },
    getButtonByClass: function (cls) {
        return p4cms.ui.toolbar.primaryToolbar.getButtonByClass(cls);
    },
    activateButtonByClass: function(cls) {
        p4cms.ui.toolbar.primaryToolbar.activateButtonByClass(cls);
    },
    deactivateButtonByClass: function(cls) {
        p4cms.ui.toolbar.primaryToolbar.deactivateButtonByClass(cls);
    }
});
