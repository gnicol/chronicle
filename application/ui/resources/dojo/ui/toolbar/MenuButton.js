// summary:
//      Dijit for menu items within the toolbar
//      used to take advantage of the dojo parser to
//      pull in the desired attributes for creating the menus
dojo.provide('p4cms.ui.toolbar.MenuButton');

dojo.require('dijit._Widget');
dojo.require('dijit._Templated');
dojo.require('dijit._Contained');
dojo.require('dijit._CssStateMixin');

dojo.declare('p4cms.ui.toolbar.MenuButton', [dijit._Widget, dijit._Templated, dijit._Contained, dijit._CssStateMixin], {
    baseClass:              "menu-button",
    templateString:         '<${wrapTag} tabIndex="-1" onclick="${onClick}" dojoAttachPoint="focusNode" dojoAttachEvent="onclick:_onClick">'
                            + '<span class="menu-handle" dojoAttachPoint="containerNode"></span></${wrapTag}>',

    iconNode:               null,

    menuAlign:              'left',
    wrapTag:                'span',
    displayContext:         '',
    href:                   '',
    target:                 '',
    checked:                false,
    closeOnBlur:            false,
    canToggle:              false,
    hasDrawer:              false,
    label:                  '',
    iconClass:              '',

    // declare onClick as a string so that it behaves like
    // a native browser click event on anchor elements.
    onClick:                'return true;',

    attributeMap: dojo.delegate(dijit._Widget.prototype.attributeMap, {
        href:           {node:'domNode', type:'attribute'},
        target:         {node:'domNode', type:'attribute'},
        title:          {node:'domNode', type:'attribute'},
        tabIndex:       {node:'domNode', type:'attribute'},
        'class':        'containerNode'
    }),

    // lifecycle
    create: function(params, srcNodeRef) {
        // derive some of our flags from the src node
        if (srcNodeRef) {
            this.canToggle  = (!!dojo.attr(srcNodeRef, 'onActivate')
                            && !!dojo.attr(srcNodeRef, 'onDeactivate'))
                            || this.canToggle;
            this.hasDrawer  = !!dojo.attr(srcNodeRef, 'onDrawerLoad')
                            || this.hasDrawer;
        }

        this.inherited(arguments);
    },

    // override the state class for IE to give give
    // the node to finish its focus before changing
    // its class
    _setStateClass: function(attr) {
        if (dojo.isIE && attr === 'focused') {
            setTimeout(dojo.hitch(this, this._setStateClass), 0);
            return;
        }
        this.inherited(arguments);
    },

    // lifecycle
    buildRendering: function() {
        if (this.href) {
            this.wrapTag = 'a';
        }

        this.inherited(arguments);

        this.iconNode = dojo.create('span', {'class' : 'menu-icon '
            + this.iconClass, role: 'presentation'}, this.domNode, 'first');

        if (this.label) {
            this.containerNode.innerHTML = this.label;
        }

        var role = 'menuitem';
        if (this.canToggle) {
            role = 'menuitemcheckbox';
        }

        dijit.setWaiRole(this.domNode, role);

        if (this.hasDrawer) {
            dijit.setWaiState(this.domNode, 'haspopup', 'true');
        }
    },

    // widget doesn't hook up focus to it's nodes
    // do it ourselves
    focus: function() {
        dijit.focus(this.domNode);
    },

    // called whenever the drawer is opened
    // defined to enable pulling from parser
    onDrawerLoad: function(container) {},

    // called when menu is activated
    // defined to enable pulling from parser
    onActivate: function() {},

    // called when menu is deactivated
    // defined to enable pulling from parser
    onDeactivate: function() {},

    // called whenever a menu button is clicked
    // use this instead of 'onClick'
    _onClick: function() {},

    // override contained class to give proper siblings
    _getSibling: function(dir){
        var index = this.getIndexInParent(),
            offset = (dir === 'next' ? 1 : -1);

        if (index+offset < 0 || index+offset >= this.getChildren().length) {
            return null;
        }

        return this.getParent().getChildren()[index+offset];
    },

    _setCheckedAttr: function(checked){
        dijit.setWaiState(this.domNode, "checked", checked);
        this._set("checked", checked);
    }
});

// Support adding dropdowns to the menu button with DropDownMenuButton
dojo.provide('p4cms.ui.toolbar.DropDownMenuButton');
dojo.require('dijit._HasDropDown');
dojo.require('p4cms.ui.Menu');

dojo.declare('p4cms.ui.toolbar.DropDownMenuButton', [p4cms.ui.toolbar.MenuButton, dijit._HasDropDown], {
    autoWidth: false,

    // lifecycle
    startup: function() {
        this.inherited(arguments);

        // make this menu read only if it has no enabled menuItems
        if (!this.hasMenusItems(true)) {
            dojo.removeClass(this.domNode, 'dijitDownArrowButton');
            this.readOnly = true;
        }
    },

    // returns true if the dropdown has menu items
    // add flag to only count enabled items
    hasMenusItems: function(onlyEnabled) {
        var dropdown = this.getDropDown();
        if (dropdown && dropdown.hasChildren()) {
            if (onlyEnabled) {
                var i, children = dropdown.getChildren();
                for (i = 0; i < children.length; i++) {
                    if (children[i].disabled === false) {
                        return true;
                    }
                }
            } else {
                return true;
            }
        }

        return false;
    },

    // returns the dropdown menu, or tries to find it near us in the dom
    getDropDown: function() {
        if (this.dropDown) {
            return this.dropDown;
        }

        var menuNodes = dojo.query('.dijitMenu', this.domNode.parentNode || this.domNode);
        return menuNodes[0] && dijit.byNode(menuNodes[0]);
    }
});