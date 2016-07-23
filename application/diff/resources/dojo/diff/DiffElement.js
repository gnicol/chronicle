// summary:
//      base class for diffing an element

dojo.provide("p4cms.diff.DiffElement");

dojo.require("dijit.layout.ContentPane");

dojo.declare("p4cms.diff.DiffElement", dijit._Widget,
{
    currentMode:    null,
    plugins:        null,

    // lifecycle
    // define complex objects
    postMixInProperties: function() {
        this.inherited(arguments);
        this.plugins = this.plugins || [];
        this._plugins = [];
        this.modes = {};
        this.modeOrder = [];
    },

    // lifecycle
    // init plugins
    buildRendering: function() {
        this.inherited(arguments);
        this.initPlugins();
    },

    // add any plugins that are defined in our config
    initPlugins: function() {
        // add the plugins
        dojo.forEach(this.plugins, this.addPlugin, this);
    },

    // add the plugin, if it hasn't been initialized, initialize it.
    addPlugin: function(plugin) {
        var Spec, config = {};

        // if config object was specified, keep the config options
        if (plugin && plugin.name && !plugin.getDiffElement) {
            config = plugin;
            plugin = plugin.name;
        }

        if (dojo.isString(plugin)) {
            Spec = dojo.getObject(plugin);
            if (Spec) {
                config = dojo.mixin(config, {diffElement: this});
                plugin = new Spec(config);
            }
        }
        if (plugin && plugin.getDiffElement) {
            this._plugins.push(plugin);
        }
    },

    // lifecycle
    // add any modebuttons we have to the dom
    postCreate: function() {
        this.inherited(arguments);
        var i, button, buttonBody, modeId;

        // only show modebuttons if we have more than one
        if (this.modeOrder.length > 1) {
            // create buttonbar to hold the buttons
            this.buttonBar = new dijit.layout.ContentPane({
                'class':    'button-bar',
                content:    '<div class="button-bar-body"></div>'
            });
            buttonBody = dojo.query('.button-bar-body', this.buttonBar.domNode)[0];

            // add any modebuttons
            for (i = 0; i < this.modeOrder.length; i++) {
                button = this.modes[this.modeOrder[i]].button;
                dojo.style(button.domNode, 'float', 'right');
                dojo.addClass(button.domNode, 'content-button button-small button-toggle');
                if (i === 0) {
                    dojo.addClass(button.domNode, 'button-left');
                } else if (i === this.modeOrder.length-1) {
                    dojo.addClass(button.domNode, 'button-right');
                } else {
                    dojo.addClass(button.domNode, 'button-middle');
                }
                dojo.place(button.domNode, buttonBody, 'first');
            }

            dojo.place(this.buttonBar.domNode, this.domNode, 'first');
        }
    },

    // lifecycle
    // startup children, and set default mode
    startup: function() {
        this.inherited(arguments);
        dojo.forEach(this._plugins, function(plugin) {
            plugin.startup();
        });
        this.setupActiveMode();
    },

    // sets the first mode as the active mode
    // used during startup to set the default mode
    setupActiveMode: function() {
        if (this.modeOrder.length  > 0) {
            this.setActiveMode(this.modeOrder[0]);
        }
    },

    // takes a modeId, and sets the active mode
    setActiveMode: function(modeId) {
        var mode = this.modes[modeId];
        if (!mode) {
            return;
        }
        // create a change event object to be passed to the events
        var modeChange = {old: this.currentMode, change: mode };

        if (mode !== this.currentMode) {
            // deactivate previous mode
            if (this.currentMode) {
                dojo.removeClass(this.currentMode.domNode, 'diff-mode-active');
                this.currentMode.plugin.deactivateMode(modeId, modeChange);
                dojo.removeClass(this.currentMode.button.domNode, 'active');
            }

            // activate new plugin
            this.currentMode = mode;
            dojo.addClass(this.currentMode.domNode, 'diff-mode-active');
            this.currentMode.plugin.activateMode(modeId, modeChange);
            dojo.addClass(this.currentMode.button.domNode, 'active');

            // pass focus if old mode had it
            if (modeChange.old && this.isModeFocused(modeChange.old)) {
                this.passFocus(modeChange.old, modeChange.change);
            }
        }
    },

    // attempt to pass focus from one mode to another if the first has focus
    // each mode is responsible for providing its focus container
    passFocus: function(fromMode, toMode) {
        var focusList = dojo.query('.focus', fromMode.plugin.getFocusContainer(fromMode.id));
        if (focusList.length > 0) {
            focusList.removeClass('focus');
            fromMode.plugin.onLoseFocus(fromMode.id);
            var toFocus = dojo.query('tbody.diff:not(.same)',
                toMode.plugin.getFocusContainer(toMode.id));
            if (toFocus.length > 0) {
                dojo.addClass(toFocus[0], 'focus');
            }
            toMode.plugin.onGainFocus(toMode.id);
        }

        // update navigation to reflect any changes
        this.getViewer().updateDiffNavigation();
    },

    // checks if passed mode is currently focused
    isModeFocused: function(mode) {
        if (mode.plugin.getFocusContainer(mode.id)) {
            var focusList = dojo.query('.focus', mode.plugin.getFocusContainer(mode.id));
            if (focusList.length > 0) {
                return true;
            }
        }
        return false;
    },

    // returns the diff viewer dijit
    getViewer: function() {
        return dijit.byNode(dojo.query("#"+this.id).closest("[dojoType='p4cms.diff.Viewer']")[0]);
    },

    // returns the initialized plugins
    getPlugins: function() {
        return this._plugins;
    },

    // add a mode button to the modebutton config
    addMode: function(plugin, modeId, modeDomNode, buttonText, index) {
        this.modes[modeId] = {
            id:         modeId,
            button:     this.createModeButton(plugin, modeId, buttonText),
            plugin:     plugin,
            domNode:    modeDomNode
        };
        if (index) {
            this.modeOrder[index] = modeId;
        } else {
            this.modeOrder.push(modeId);
        }
    },

    // creates a new button to be used for switching modes
    createModeButton: function(plugin, modeId, buttonText) {
        return new dijit.form.Button({
            label:      buttonText,
            onClick:    dojo.hitch(this, function(plugin, modeId) {
                this.setActiveMode(modeId);
            }, plugin, modeId)
        });
    },

    // returns the diffmode nodes in this diff
    getModeNodeList: function() {
        return dojo.query('.diff-mode', this.domNode);
    },

    // lifecycle
    // prepare for garbage collection
    destroy: function() {
        dojo.forEach(this._plugins, function(p) {
            if (p && p.destroy) {
                p.destroy();
            }
        });
        this._plugins = [];
        this.inherited(arguments);
    }
});