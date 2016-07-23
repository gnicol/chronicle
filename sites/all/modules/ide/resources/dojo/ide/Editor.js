// summary:
//      A tabbed file editor using 'Ace' http://ace.ajax.org/
//
//      Designed to be paired with a simple REST-like server-side component
//      providing 'file' and 'paths' resources. The file resource is expected
//      to support get for reading a particular file's contents and post for
//      saving. The paths resource should provide a listing of directory
//      contents to populate a tree dijit via get and the ability to create
//      new folders via post.

dojo.provide("p4cms.ide.Editor");
dojo.require("dijit.layout.BorderContainer");
dojo.require("dijit.layout.TabContainer");
dojo.require("dijit.layout.AccordionContainer");
dojo.require("dijit.layout.ContentPane");
dojo.require("dijit.Toolbar");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.DropDownButton");
dojo.require("p4cms.ui.Menu");
dojo.require("dijit.MenuItem");
dojo.require("dijit.MenuSeparator");
dojo.require('dojox.data.JsonRestStore');
dojo.require('dojox.rpc.JsonRest');
dojo.require('dijit.tree.ForestStoreModel');
dojo.require('dijit.Tree');
dojo.require('p4cms.ui.Dialog');
dojo.require('p4cms.ui.ConfirmDialog');
dojo.require('p4cms.ide.Uploader');
dojo.require('p4cms.content.dnd.DropZone');

dojo.declare("p4cms.ide.Editor", [dijit.layout.BorderContainer], {
    top:            null,
    sidebar:        null,
    tabs:           null,
    toolbar:        null,
    buttons:        null,
    tree:           null,
    treeStore:      null,
    treeModel:      null,
    fullScreen:     true,
    maxRecent:      15,
    theme:          "ace/theme/textmate",
    themes:         null,

    postCreate: function(){
        if (dojo.isIE < 9) {
            var dialog = new p4cms.ui.Dialog({
                    content: "The IDE module's editing interface does not work in Internet Explorer "
                           + "versions earlier than 9.<br>It does work in:<ul><li>Internet Explorer 9+</li>"
                           + "<li>Apple Safari 5+</li><li>Google Chrome 18+ (stable channel)</li>"
                           + "<li>Mozilla Firefox 10+</li></ul>"
            });
            dialog.addButton(new dijit.form.Button({
                id:         dialog.domNode.id + "-button-return",
                label:      "Back to Website",
                onClick:    function() { p4cms.openUrl(p4cms.url()); }
            }));
            dojo.connect(dialog, 'onHide', function() { p4cms.openUrl(p4cms.url()); });
            dialog.show();

            return;
        }

        dojo.addClass(this.domNode, 'editor');

        // if running full-screen, make necessary style tweaks.
        if (this.fullScreen) {
            var style = {width: '100%', height: '100%', margin: 0, overflow: 'hidden'};
            dojo.style(this.domNode, style);
            dojo.style(dojo.body(), style);
            dojo.style(dojo.body().parentNode, style);

            // if a toolbar is loaded in overlay mode, adjust top
            // to clear the bottom of the toolbar.
            dojo.subscribe(
                'p4cms.ui.toolbar.created',
                dojo.hitch(this, function(toolbar){
                    if (toolbar.dockRegion.displayMethod === 'overlay') {
                        var top = dojo.style(this.domNode, 'paddingTop');
                        top    += toolbar.getSize().h;
                        dojo.style(this.domNode, 'paddingTop', top + 'px');
                    }
                })
            );
        }

        this._setupLayout();
        this._setupToolbar();
        this._setupTree();
        this._setupDropZone();

        // recall last used theme.
        var theme  = dojo.cookie(this.declaredClass + '.theme');
        this.theme = this._isValidTheme(theme) ? theme : this.theme;
    },

    openFile: function(item){
        var file = item.id;
        var name = item.name;
        var type = item.type;

        // nothing to do if this is a directory.
        if (item.children) {
            return;
        }

        // add file to list of recent items.
        this._addRecentFile(item);

        // if the file is already loaded, bring the tab to the front.
        var found = dojo.filter(
            this.tabs.getChildren(),
            function(tab){return tab.file === file;}
        );
        if (found.length) {
            this.tabs.selectChild(found[0]);
            return;
        }

        // if file is non-text, open it in an iframe.
        if (type && !type.match(/text\/|\/xml|\/x-javascript/)) {
            this.newIframeTab(name, file);
            return;
        }

        // if we have been given data, no need to query server.
        if (item.hasOwnProperty('data')) {
            return this.newAceTab(name, file, item.data);
        }

        // file not yet opened, load it.
        dojo.xhrGet({
            handleAs:   'json',
            url:        p4cms.url({
                module: 'ide',
                action: 'files',
                format: 'json'
            }),
            content:    {
                file: file
            },
            load: dojo.hitch(this, function(data){
                this.newAceTab(name, file, data);
            })
        });
    },

    openPath: function(path) {
        var tree     = this.tree;
        var items    = tree._itemNodesMap;
        var deferred = new dojo.Deferred();

        // nothing to open if path is root "/"
        if (path === "/") {
            deferred.resolve();
            return deferred;
        }

        // split path such that 'foo/bar/baz' produces an array of paths:
        //  'foo'
        //  'foo/bar'
        //  'foo/bar/baz'
        var i;
        var paths = [];
        var parts = path.split('/');
        for (i = 1; i <= parts.length; i++) {
            paths.push(parts.slice(0, i).join('/'));
        }

        // expand each path in turn (going progressively deeper).
        dojo.forEach(paths, function(path){
            deferred.addCallback(function(){
                return tree._expandNode(items[path][0]);
            });
        });

        // kick off deferred chain and return promise.
        deferred.resolve();
        return deferred;
    },

    newAceTab: function(name, file, data){
        var tab = new dijit.layout.ContentPane({
            title:      name,
            'class':    'editor-ace',
            closable:   true,
            tooltip:    file
        });
        this.tabs.addChild(tab);
        this.tabs.selectChild(tab);

        tab.file   = file;
        /*global ace: false */
        tab.editor = ace.edit(tab.containerNode);
        tab.editor.setTheme(this.theme);

        // if we have a special mode for this type of file, use it.
        var mode = this._getMode(file);
        if (mode) {
            tab.editor.getSession().setMode(this._getMode(file));
        }

        if (typeof(data) === 'string') {
            tab.editor.getSession().setValue(data);
        }
    },

    newIframeTab: function(name, file){
        var tab = new dijit.layout.ContentPane({
            title:      name,
            'class':    'editor-iframe',
            closable:   true,
            tooltip:    file
        });
        this.tabs.addChild(tab);
        this.tabs.selectChild(tab);

        tab.file   = file;

        var iframe = dojo.create('iframe',{
            src:         p4cms.url({
                module: 'ide',
                action: 'files',
                file:   file
            }),
            width:       '100%',
            height:      '100%',
            frameborder: 0
        }, tab.domNode);
    },

    saveFile: function(){
        var tab = this.getActiveTab();

        // nothing to do if no active editor.
        if (!tab.editor) {
            return;
        }

        // prompt for file name if none set.
        if (!tab.file) {
            var dialog = this._getSaveAsDialog(tab);
            dialog.show();
            return;
        }

        this._doSave(tab);
    },

    deleteItem: function(item){
        var isFolder = item.children;
        var label    = isFolder ? 'Delete Folder' : 'Delete File';
        var path     = item.id || item.$ref;
        var dialog   = new p4cms.ui.ConfirmDialog({
            title:               "Delete",
            content:             'Are you sure you want to delete the ' +
                                 (isFolder ? 'folder' : 'file') +
                                 ' "' + path + '"',
            actionButtonOptions: {label: label},
            actionSingleClick:   true,
            onConfirm:           dojo.hitch(this, function(){
                // xhr delete item.
                dojo.xhrDelete({
                    url:        p4cms.url({
                        module: 'ide',
                        action: (isFolder ? 'paths' : 'files'),
                        format: 'json'
                    }),
                    content:    (isFolder ? {path: path} : {file: path}),
                    handleAs:   'json',
                    load:       dojo.hitch(this, function(response){
                        var notice;
                        if (response) {
                            notice = new p4cms.ui.Notice({
                                message:  'Deleted "' + path + '".',
                                severity: "success"
                            });
                        } else {
                            notice = new p4cms.ui.Notice({
                                message:    'Failed to delete "' + path + '"' +
                                            '<br>Check file permissions.',
                                severity:   'error'
                            });
                        }
                        dialog.hide();

                        // reload the parent node.
                        this._reloadTreeNode(this._dirname(path));
                    }),
                    error:      function(errorMessage){
                        var notice = new p4cms.ui.Notice({
                            message:    'Failed to delete "' + path + '"' +
                                        '<br>' + errorMessage,
                            severity:   'error'
                        });
                        throw notice.message;
                    }
                });
            })
        });

        dialog.show();
    },

    getActiveTab: function(){
        return this.tabs.selectedChildWidget;
    },

    switchTheme: function(theme){
        dojo.forEach(this.tabs.getChildren(), function(tab){
            if (tab.editor) {
                tab.editor.setTheme(theme);
            }
        });

        this.theme = theme;

        // remember last used theme.
        dojo.cookie(
            this.declaredClass + '.theme',
            theme,
            {path: window.location.href}
        );
    },

    _setupLayout: function(){
        this.top     = new dijit.layout.ContentPane({
            region:     'top',
            'class':    'editor-toolbar'
        }).placeAt(this.domNode);
        this.sidebar = new dijit.layout.ContentPane({
            region:     'leading',
            splitter:   true,
            'class':    'editor-sidebar'
        }).placeAt(this.domNode);
        this.tabs    = new dijit.layout.TabContainer({
            region:     'center',
            'class':    'editor-tabs'
        }).placeAt(this.domNode);

        // tie into onClose to ensure tab's editor is cleaned up properly for IE
        this.connect(this.tabs, 'removeChild', function(child) {
            child.editor.destroy();
        });

        // any time we switch tabs, refresh the tab's editor if
        // one is present - this fixes an issue where ace doesn't
        // render if the tab is loaded while hidden.
        dojo.connect(this.tabs, 'selectChild', function(page){
            if (page.editor) {
                page.editor.resize();
                page.editor.focus();
            }
        });
    },

    _setupToolbar: function(){
        this.toolbar = new dijit.Toolbar({}).placeAt(this.top.domNode);
        this.buttons = {};

        this.buttons.newFile    = new dijit.form.Button({
            label:      'New File',
            iconClass:  'dijitIcon dijitIconFile',
            onClick:    dojo.hitch(this, function(){
                this.newAceTab('Untitled', '', '');
            })
        });

        this.buttons.newFolder  = new dijit.form.Button({
            label:      'New Folder',
            iconClass:  'dijitIcon dijitIconFolderClosed',
            onClick:    dojo.hitch(this, function(){
                this._getNewFolderDialog().show();
            })
        });

        this.buttons.newModule  = new dijit.form.Button({
            label:      'New Package',
            iconClass:  'dijitIcon dijitIconFunction',
            onClick:    dojo.hitch(this, function(){
                this._getNewPackageDialog().show();
            })
        });

        this.buttons.recent     = new dijit.form.DropDownButton({
            label:      'Open Recent',
            iconClass:  'dijitIcon dijitIconFolderOpen',
            dropDown:   this._getRecentMenu()
        });

        this.buttons.save       = new dijit.form.Button({
            label:      'Save File',
            iconClass:  'dijitIcon dijitIconSave',
            onClick:    dojo.hitch(this, 'saveFile')
        });
        this._connectSaveHandler();

        this.buttons.theme      = new dijit.form.DropDownButton({
            label:      'Change Theme',
            iconClass:  'dijitIcon dijitIconApplication',
            dropDown:   this._getThemeMenu()
        });

        // put buttons in the toolbar.
        var i;
        for (i in this.buttons) {
            if (this.buttons.hasOwnProperty(i)) {
                this.toolbar.addChild(this.buttons[i]);
            }
        }
    },

    _setupTree: function(){
        this.treeStore  = new dojox.data.JsonRestStore({
            target:         p4cms.url({}, 'ide-tree'),
            labelAttribute: "name"}
        );

        // note we stub out requeryTop() to avoid excessive
        // calls to load the root of the tree over and over.
        this.treeModel  = new dijit.tree.ForestStoreModel({
            store:          this.treeStore,
            deferItemLoadingUntilExpand: true,
            query:          "root",
            rootId:         "/",
            childrenAttrs:  ["children"],
            _requeryTop:    function(){}
        });

        this.tree       = new dijit.Tree({
            model:          this.treeModel,
            showRoot:       false,
            persist:        true,
            'class':        'editor-tree',
            onDblClick:     dojo.hitch(this, 'openFile')
        }).placeAt(this.sidebar.domNode);

        // build tree node context menu.
        // note, we connect to 'scheduleOpen' to track which node was clicked
        // otherwise we would have no way of knowing (apparently fixed in dojo 1.7)
        var editor      = this;
        this.treeMenu   = new p4cms.ui.Menu({
            targetNodeIds:  [this.tree.id],
            selector:       ".dijitTreeNode"
        });
        var isTreeNode  = function(node) {
            return node && node.isTreeNode;
        };
        dojo.connect(this.treeMenu, '_scheduleOpen', function(target){
            editor.treeMenu.currentNode = dijit.getEnclosingWidget(target);
        });
        this.treeMenu.addChild(new dijit.MenuItem({
            label:      "Delete",
            onShow:     function(){
                this.set('disabled', !isTreeNode(this.getParent().currentNode));
            },
            onClick:    function(){
                var node = this.getParent().currentNode;
                editor.deleteItem(node.item);
            }
        }));
        this.treeMenu.addChild(new dijit.MenuItem({
            label:      "Copy",
            onShow:     function(){
                this.set('disabled', !isTreeNode(this.getParent().currentNode));
            },
            onClick:    function(){
                var node = this.getParent().currentNode;
                editor._getCopyDialog(node.item).show();
            }
        }));
        this.treeMenu.addChild(new dijit.MenuSeparator());
        this.treeMenu.addChild(new dijit.MenuItem({
            label:      "New Folder",
            onShow:     function(){
                this.set('disabled', !isTreeNode(this.getParent().currentNode));
            },
            onClick:    function(){
                var node = this.getParent().currentNode;
                var path = node.item.id || node.item.$ref;
                editor._getNewFolderDialog(path + "/").show();
            }
        }));
    },

    _setupDropZone: function(){
        this.dropZone = new p4cms.content.dnd.DropZone({
            node:        this.domNode,
            getUploader: dojo.hitch(this, function(event, files, csrf){
                var uploader = new p4cms.ide.Uploader({
                    editor:         this,
                    event:          event,
                    files:          files,
                    csrf:           this.dropZone.csrf,
                    onFileUploaded: dojo.hitch(this, function(file, e, data){
                        var path = uploader.uploadPath;
                        this._reloadTreeNode(path);
                        this.openFile({
                            id:   this._trimSlashes(path + '/' + file.name),
                            name: file.name,
                            type: file.type
                        });
                    })
                });
                return uploader;
            })
        });
    },

    _reloadTreeNode: function(id){
        var tree   = this.tree;
        var store  = this.treeStore;
        var fullId = store.target + (this._trimSlashes(id) || 'root');
        var isRoot = fullId === store.target + 'root';

        // if item does not exist, nothing to reload.
        if (!store._index[fullId]) {
            return;
        }

        // clear item's children and reset loader callback
        // the presence of a loader callback tells the store
        // it needs to load the item from the service.
        var item = store._index[fullId];
        item._loadObject = dojox.rpc.JsonRest._loader;
        if (isRoot){
            // empty out the root array, but preserve references.
            item.length = 0;
        } else {
            delete item.children;
            delete store._index[fullId + '#children'];
        }

        // reload.
        // the tree automatically notices if sub nodes change
        // but we have to explicitly update the root node.
        store.loadItem({item: item, onItem: function(item){
            if (isRoot) {
                tree.rootNode.setChildItems(item);
            }
        }});
    },

    _connectSaveHandler: function(){
        dojo.connect(this.domNode, 'onkeydown', dojo.hitch(this, function(e){
            var ch = String.fromCharCode(e.keyCode).toLowerCase();
            if (ch === 's' && this._isCommandKeyEvent(e)) {
                dojo.stopEvent(e);
                this.saveFile();
            }
        }));
    },

    _isCommandKeyEvent: function(e){
        if ((dojo.isMac && e.metaKey) || (!dojo.isMac && e.ctrlKey)) {
            return true;
        }
    },

    _addRecentFile: function(item){
        var cookie = this.declaredClass + '.recent';
        var recent = dojo.fromJson(dojo.cookie(cookie)) || [];
        recent.push({id: item.id, name: item.name, type: item.type, time: Date.now()});

        // sort, unique and trim the recent file list.
        recent.sort(function(a, b){
            return b.time - a.time;
        });
        recent = dojo.filter(recent, function(value, index, values){
            return !index || value.id !== values[index - 1].id;
        });
        recent = recent.slice(0, this.maxRecent);

        dojo.cookie(
            cookie,
            dojo.toJson(recent),
            {path: window.location.href}
        );

        // update recent file menu.
        this.buttons.recent.dropDown = this._getRecentMenu();
    },

    _getRecentMenu: function(){
        var cookie = this.declaredClass + '.recent';
        var recent = dojo.fromJson(dojo.cookie(cookie)) || [];
        var menu   = new p4cms.ui.Menu();

        dojo.forEach(recent, dojo.hitch(this, function(item){
            menu.addChild(new dijit.MenuItem({
                label:      item.name,
                title:      item.id,
                onClick:    dojo.hitch(this, 'openFile', item)
            }));
        }));

        return menu;
    },

    _getThemeMenu: function(){
        var themes = this._getThemes();
        var menu   = new p4cms.ui.Menu();
        dojo.forEach(themes, dojo.hitch(this, function(theme){
            menu.addChild(new dijit.MenuItem({
                label:      theme[0],
                onClick:    dojo.hitch(this, 'switchTheme', theme[1])
            }));
        }));

        return menu;
    },

    _getThemes: function(){
        return [
            ["Chrome",                  "ace/theme/chrome"],
            ["Clouds",                  "ace/theme/clouds"],
            ["Cobalt",                  "ace/theme/cobalt"],
            ["Crimson Editor",          "ace/theme/crimson_editor"],
            ["Dawn",                    "ace/theme/dawn"],
            ["Eclipse",                 "ace/theme/eclipse"],
            ["idleFingers",             "ace/theme/idle_fingers"],
            ["krTheme",                 "ace/theme/kr_theme"],
            ["Merbivore",               "ace/theme/merbivore"],
            ["Merbivore Soft",          "ace/theme/merbivore_soft"],
            ["Mono Industrial",         "ace/theme/mono_industrial"],
            ["Monokai",                 "ace/theme/monokai"],
            ["Pastel on dark",          "ace/theme/pastel_on_dark"],
            ["Solarized Dark",          "ace/theme/solarized_dark"],
            ["Solarized Light",         "ace/theme/solarized_light"],
            ["TextMate",                "ace/theme/textmate"],
            ["Twilight",                "ace/theme/twilight"],
            ["Tomorrow",                "ace/theme/tomorrow"],
            ["Tomorrow Night",          "ace/theme/tomorrow_night"],
            ["Tomorrow Night Blue",     "ace/theme/tomorrow_night_blue"],
            ["Tomorrow Night Bright",   "ace/theme/tomorrow_night_bright"],
            ["Tomorrow Night 80s",      "ace/theme/tomorrow_night_eighties"],
            ["Vibrant Ink",             "ace/theme/vibrant_ink"]
        ];
    },

    _isValidTheme: function(theme){
        var matches = dojo.filter(this._getThemes(), function(item) {
            if (theme === item[1]) {
                return true;
            }
        });
        return !!matches.length;
    },

    _doSave: function(tab){
        return dojo.xhrPost({
            handleAs:   'json',
            url:        p4cms.url({
                module: 'ide',
                action: 'files',
                format: 'json'
            }),
            content: {
                file: tab.file,
                data: tab.editor.getSession().getValue()
            },
            load:       function(data){
                var notice;
                if (data !== false) {
                    notice = new p4cms.ui.Notice({
                        message:    'Saved ' + tab.file,
                        severity:   'success'
                    });
                } else {
                    notice = new p4cms.ui.Notice({
                        message:    'Failed to save ' + tab.file +
                                    '<br>Check file permissions.',
                        severity:   'error'
                    });
                    throw notice.message;
                }
            }
        });
    },

    _getSaveAsDialog: function(tab){
        var dialog = new p4cms.ui.Dialog({
            title:   'Save As',
            'class': 'form-dialog'
        });

        dialog.set('content',
            '<form class="p4cms-ui">'
          + ' <dl>'
          + '  <dt>'
          + '   <label for="name" class="required">Name</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <input type="text" name="name" id="name" size="40">'
          + '  </dd>'
          + '  <dt>'
          + '   <label for="path" class="optional">Path</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <input type="text" name="path" id="path" value="/" size="40">'
          + '  </dd>'
          + '  <dd id="buttons-element" class="display-group">'
          + '   <fieldset id="fieldset-buttons" class="buttons">'
          + '    <dl>'
          + '     <dd id="save-element">'
          + '      <input class="preferred" name="save" value="Save" '
          + '       type="submit" label="Save" disabled="disabled" '
          + '       dojoType="dijit.form.Button">'
          + '     </dd>'
          + '     <dd id="cancel-element">'
          + '      <input name="cancel" value="Cancel" '
          + '       type="button" label="Cancel" '
          + '       dojoType="dijit.form.Button">'
          + '     </dd>'
          + '    </dl>'
          + '   </fieldset>'
          + '  </dd>'
          + ' </dl>'
          + '</form>'
        );

        // disable save button if there is no name.
        dojo.query('input[name=name]', dialog.domNode)
            .connect('keyup', function(){
                dijit.byNode(dojo.query('.dijitButton.preferred', dialog.domNode)[0])
                     .set('disabled', !this.value.length);
            });

        // close dialog on cancel.
        dojo.query('input[name=cancel]', dialog.domNode)
            .connect('onclick', dialog, 'hide');

        // attempt to save file on execute.
        dojo.connect(dialog, 'onExecute', dojo.hitch(this, function(){
            var form   = dojo.query('form', dialog.domNode)[0],
                values = dojo.formToObject(form),
                path   = this._trimSlashes(values.path),
                name   = this._trimSlashes(values.name),
                id     = this._trimSlashes(path + '/' + name);

            // set tab filename from form input.
            // save reads filename from tab object.
            tab.file = id;

            this._doSave(tab).then(
                dojo.hitch(this, function(){
                    // set file tab title to new file name.
                    tab.set('title', name);

                    // add new file to list of recent items.
                    this._addRecentFile({id: id, name: name});

                    // reload the parent node.
                    this._reloadTreeNode(path);
                }),
                function(){
                    tab.file = null;
                }
            );
        }));

        return dialog;
    },

    _getNewFolderDialog: function(path){
        var dialog = new p4cms.ui.Dialog({
            title:   'New Folder',
            'class': 'form-dialog'
        });

        dialog.set('content',
            '<form class="p4cms-ui">'
          + ' <dl>'
          + '  <dt>'
          + '   <label for="name" class="required">Name</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <input type="text" name="name" id="name" size="40">'
          + '  </dd>'
          + '  <dt>'
          + '   <label for="path" class="optional">Path</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <input type="text" name="path" id="path" value="/" size="40">'
          + '  </dd>'
          + '  <dd id="buttons-element" class="display-group">'
          + '   <fieldset id="fieldset-buttons" class="buttons">'
          + '    <dl>'
          + '     <dd id="save-element">'
          + '      <input class="preferred" name="create" value="Create" '
          + '       type="submit" label="Create" disabled="disabled"'
          + '       dojoType="dijit.form.Button">'
          + '     </dd>'
          + '     <dd id="cancel-element">'
          + '      <input name="cancel" value="Cancel" '
          + '       type="button" label="Cancel" '
          + '       dojoType="dijit.form.Button">'
          + '     </dd>'
          + '    </dl>'
          + '   </fieldset>'
          + '  </dd>'
          + ' </dl>'
          + '</form>'
        );

        // set initial path if one was given.
        if (path) {
            dojo.query('input[name=path]', dialog.domNode)
                .attr('value', path);
        }

        // disable create button if there is no name.
        dojo.query('input[name=name]', dialog.domNode)
            .connect('keyup', function(){
                dijit.byNode(dojo.query('.dijitButton.preferred', dialog.domNode)[0])
                     .set('disabled', !this.value.length);
            });

        // close dialog on cancel.
        dojo.query('input[name=cancel]', dialog.domNode)
            .connect('onclick', dialog, 'hide');

        // attempt to create folder on submit.
        dojo.connect(dialog, 'onExecute', dojo.hitch(this, function(){
            var form   = dojo.query('form', dialog.domNode)[0];
            var values = dojo.formToObject(form);
            var path   = this._trimSlashes(values.path);
            var name   = this._trimSlashes(values.name);
            var id     = this._trimSlashes(path + '/' + name);

            dojo.xhrPost({
                handleAs:   'json',
                url:        p4cms.url({
                    module: 'ide',
                    action: 'paths',
                    format: 'json'
                }),
                content:    {path: id},
                load:       dojo.hitch(this, function(data){
                    var notice;
                    if (data !== false) {
                        notice = new p4cms.ui.Notice({
                            message:    "Created '" + name + "' folder.",
                            severity:   'success'
                        });

                        // reload the parent node.
                        this._reloadTreeNode(path);
                    } else {
                        notice = new p4cms.ui.Notice({
                            message:    "Failed to create '" + name + "' folder." +
                                        "<br>Check file permissions.",
                            severity:   'error'
                        });
                    }
                }),
                error:      function(response){
                    var data, notice;
                    try {
                        data   = dojo.fromJson(response.responseText);
                        notice = new p4cms.ui.Notice({
                            message:    data.message,
                            severity:   'error'
                        });
                    } catch (error) {
                        notice = new p4cms.ui.Notice({
                            message:    "Unexpected error trying to create folder.",
                            severity:   'error'
                        });
                    }
                }
            });
        }));

        return dialog;
    },

    _getCopyDialog: function(item){
        var dialog = new p4cms.ui.Dialog({
            title:   'Copy',
            'class': 'form-dialog'
        });

        dialog.set('content',
            '<form class="p4cms-ui">'
          + ' <dl>'
          + '  <dt>'
          + '   <label for="name" class="required readonly">From</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <input type="text" name="source" id="source" size="40" readonly="readonly">'
          + '  </dd>'
          + '  <dt>'
          + '   <label for="path" class="required">To</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <input type="text" name="target" id="target" size="40">'
          + '  </dd>'
          + '  <dd id="buttons-element" class="display-group">'
          + '   <fieldset id="fieldset-buttons" class="buttons">'
          + '    <dl>'
          + '     <dd id="copy-element">'
          + '      <input class="preferred" name="copy" value="Copy" '
          + '       type="submit" label="Copy" disabled="disabled" '
          + '       dojoType="dijit.form.Button">'
          + '     </dd>'
          + '     <dd id="cancel-element">'
          + '      <input name="cancel" value="Cancel" '
          + '       type="button" label="Cancel" '
          + '       dojoType="dijit.form.Button">'
          + '     </dd>'
          + '    </dl>'
          + '   </fieldset>'
          + '  </dd>'
          + ' </dl>'
          + '</form>'
        );

        // disable copy button if there is 'to' value
        dojo.query('input[name=target]', dialog.domNode)
            .connect('keyup', function(){
                dijit.byNode(dojo.query('.dijitButton.preferred', dialog.domNode)[0])
                     .set('disabled', !this.value.length || this.value === (item.id || item.$ref));
            });

        // to value defaults to from path.
        dojo.query('input[type=text]', dialog.domNode)
            .attr('value', item.id || item.$ref);

        // close dialog on cancel.
        dojo.query('input[name=cancel]', dialog.domNode)
            .connect('onclick', dialog, 'hide');

        // attempt to copy path server-side.
        dojo.connect(dialog, 'onExecute', dojo.hitch(this, function(){
            var form   = dojo.query('form', dialog.domNode)[0],
                values = dojo.formToObject(form),
                source = this._trimSlashes(values.source),
                target = this._trimSlashes(values.target);

            dojo.xhrPost({
                handleAs:   'json',
                url:        p4cms.url({
                    module: 'ide',
                    action: 'copy',
                    format: 'json'
                }),
                content:    {source: source, target: target},
                load:       dojo.hitch(this, function(data){
                    var notice;
                    if (data !== false) {
                        notice = new p4cms.ui.Notice({
                            message:    "Copied '" + source + "' to '" + target + "'.",
                            severity:   'success'
                        });

                        this._reloadTreeNode(this._dirname(target));
                    } else {
                        notice = new p4cms.ui.Notice({
                            message:    "Failed to copy '" + source + "' to '" + target + "'.",
                            severity:   'error'
                        });
                    }
                }),
                error:      function(response){
                    var data, notice;
                    try {
                        data   = dojo.fromJson(response.responseText);
                        notice = new p4cms.ui.Notice({
                            message:    data.message,
                            severity:   'error'
                        });
                    } catch (error) {
                        notice = new p4cms.ui.Notice({
                            message:    "Unexpected error trying to copy.",
                            severity:   'error'
                        });
                    }
                }
            });
        }));

        return dialog;
    },

    _getNewPackageDialog: function(type){
        var dialog = new p4cms.ui.Dialog({
            title:   'New Package',
            'class': 'form-dialog'
        });

        dialog.set('content',
            '<form class="p4cms-ui">'
          + ' <dl>'
          + '  <dt>'
          + '   <label for="type">Type</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <select name="type" id="type">'
          + '    <option value="module">Module</option>'
          + '    <option value="theme">Theme</option>'
          + '   </select>'
          + '  </dd>'
          + '  <dt>'
          + '   <label for="name" class="required">Name</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <input type="text" name="name" id="name" size="40">'
          + '  </dd>'
          + '  <dt>'
          + '   <label for="description">Description</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <textarea name="description" id="description" rows=3 cols=40></textarea>'
          + '  </dd>'
          + '  <dt>'
          + '   <label for="tags">Tags</label>'
          + '  </dt>'
          + '  <dd>'
          + '   <input type="text" name="tags" id="tags" size="40">'
          + '  </dd>'
          + '  <dd id="buttons-element" class="display-group">'
          + '   <fieldset id="fieldset-buttons" class="buttons">'
          + '    <dl>'
          + '     <dd id="create-element">'
          + '      <input class="preferred" name="create" value="Create" '
          + '       type="submit" label="Create" disabled="disabled" '
          + '       dojoType="dijit.form.Button">'
          + '     </dd>'
          + '     <dd id="cancel-element">'
          + '      <input name="cancel" value="Cancel" '
          + '       type="button" label="Cancel" '
          + '       dojoType="dijit.form.Button">'
          + '     </dd>'
          + '    </dl>'
          + '   </fieldset>'
          + '  </dd>'
          + ' </dl>'
          + '</form>'
        );

        // disable create button if there is 'name' value
        dojo.query('input[name=name]', dialog.domNode)
            .connect('keyup', function(){
                dijit.byNode(dojo.query('.dijitButton.preferred', dialog.domNode)[0])
                     .set('disabled', !this.value.length);
            });

        // close dialog on cancel.
        dojo.query('input[name=cancel]', dialog.domNode)
            .connect('onclick', dialog, 'hide');

        // attempt to create package server-side.
        dojo.connect(dialog, 'onExecute', dojo.hitch(this, function(){
            var form   = dojo.query('form', dialog.domNode)[0];
            var values = dojo.formToObject(form);

            dojo.xhrPost({
                handleAs:   'json',
                url:        p4cms.url({
                    module: 'ide',
                    action: 'package',
                    format: 'json'
                }),
                content:    {
                    name:        values.name,
                    type:        values.type,
                    description: values.description,
                    tags:        values.tags
                },
                load:       dojo.hitch(this, function(data){
                    var notice;
                    if (data !== false) {
                        notice = new p4cms.ui.Notice({
                            message:    "Created '" + values.name + "' " + values.type + ".",
                            severity:   'success'
                        });

                        this._reloadTreeNode('all/' + values.type + 's');
                    } else {
                        notice = new p4cms.ui.Notice({
                            message:    "Failed to create '" + values.name + "' package.",
                            severity:   'error'
                        });
                    }
                }),
                error:      function(response){
                    var data, notice;
                    try {
                        data   = dojo.fromJson(response.responseText);
                        notice = new p4cms.ui.Notice({
                            message:    data.message,
                            severity:   'error'
                        });
                    } catch (error) {
                        notice = new p4cms.ui.Notice({
                            message:    "Unexpected error trying to create package.",
                            severity:   'error'
                        });
                    }
                }
            });
        }));

        return dialog;
    },

    _trimSlashes: function(path){
        return path.replace(/^[\/\\]*/, '').replace(/[\/\\]*$/, '');
    },

    _dirname: function(path){
        path = this._trimSlashes(path);
        path = path.replace(/\/?[^\/]+$/, '');
        return path.length ? path : '/';
    },

    _extension: function(file){
        return file.substr(file.lastIndexOf('.') + 1);
    },

    _getMode: function(file){
        var extension = this._extension(file);
        var modes     = {
            css:    "ace/mode/css",
            html:   "ace/mode/html",
            htm:    "ace/mode/html",
            js:     "ace/mode/javascript",
            json:   "ace/mode/json",
            php:    "ace/mode/php",
            phtml:  "ace/mode/php",
            xml:    "ace/mode/xml"
        };

        // early exit if no special mode for this file type.
        if (!modes[extension]) {
            return;
        }

        /*global require: false */
        var Mode = require(modes[extension]).Mode;
        return new Mode();
    }
});