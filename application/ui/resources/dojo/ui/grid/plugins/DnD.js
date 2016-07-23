// summary
//      Provides our own implementation of the enhanced Grid's drag and drop that adds
//      the ability to drop an item under another item, and allows you to drag items
//      that were not previously selected
//      Also defaults the configuration to a row single selection mode
dojo.provide('p4cms.ui.grid.plugins.DnD');

dojo.require('dojox.grid.enhanced.plugins.DnD');

dojo.declare("p4cms.ui.grid.plugins.DnD", dojox.grid.enhanced.plugins.DnD, {
    name: "p4cms-dnd",

    _config: {
        "row":{
            "within":true,
            "in":false,
            "out":false
        },
        "col":{
            "within":false,
            "in":false,
            "out":false
        },
        "cell":{
            "within":false,
            "in":false,
            "out":false
        }
    },

    enabled: true,

    // config for what kinds of drop actions are
    // available on a row
    _dropConfig : {
        'within':   true,
        'above':    true,
        'below':    true
    },

    constructor: function(grid, args) {
        var selector    = grid.pluginMgr.getPlugin("selector"),
            rearrange   = grid.pluginMgr.getPlugin('rearrange');

        // hook up rearranger events
        var oldMoveRows = rearrange.moveRows;
        rearrange.moveRows = function(rowsToMove, targetPos) {
            if (grid.onMoveRows(rowsToMove, targetPos) !== false) {
                oldMoveRows.apply(rearrange, arguments);
            }
        };

        // configure the selector so the grid doesn't have to
        selector.setupConfig({row: grid.selectionMode, col:false, cell: false});

        // hook up selector events
        var oldIsValid = selector._isValid;
        selector._isValid = function(type, item, allowNotSelectable) {
            var result = grid.selectionValidating(type, item, allowNotSelectable);
            if (result !== false) {
                result = oldIsValid.apply(selector, arguments);
            }
            return result;
        };

        // mixin dropconfig
        this._dropConfig = dojo.mixin({}, this._dropConfig, args.dropConfig);
    },

    // extends initEvents to do a complete selection onCellMouseDown so that we don't have to wait
    // for a mouse up before enabling dnd
    _initEvents: function() {
        var s = this.selector;
        this.connect(this.grid, "onCellMouseDown", function(evt) {
            // if we are selecting, and not doing a multiselect, do the full selection now
            // don't wait for mouseup
            if (!evt.ctrlKey && s.isSelecting() && s._startPoint.row.row === evt.rowIndex) {
                s.select('row', evt.rowIndex);
                this._dndReady = s.isSelected("cell", evt.rowIndex, evt.cell.index);
                s.selectEnabled(!this._dndReady);
            }

            // this stops chrome from doing a text selection
            document.onselectstart = function(){ return false; };
        });

        this.inherited(arguments);
    },

    // overridden to restore browser selection
    _onMouseUp: function() {
        this.inherited(arguments);

        // restore selection
        document.onselectstart = function(){ return true; };
    },

    // overridden to stop grid tooltips
    _startDnd: function(evt) {
        if(!this.enabled) {
            return;
        }

        // hide any existing tooltips, and make sure they cannot be shown
        if (dijit._masterTT) {
            this.oldTooltipShow     = dijit._masterTT.show;
            dijit._masterTT.show    = function() {};
            dijit._masterTT.hide(dijit._masterTT.aroundNode);
        }

        this.inherited(arguments);
    },

    // overridden to restore grid tooltips
    _endDnd: function(destroySource) {
        this.inherited(arguments);

        // bring back tooltip show
        if (dijit._masterTT && this.oldTooltipShow) {
           dijit._masterTT.show = this.oldTooltipShow;
        }
    },

    // overrides the original to use our own avatar
    // and configure the dnd manager
    _createSource: function(evt) {
        this._elem.createDnDNodes(this._dndRegion);
        var m               = dojo.dnd.manager(),
            oldMakeAvatar   = m.makeAvatar;
        m._dndPlugin        = this;
        m._oldOffsetX       = m.OFFSET_X;
        m._oldOffsetY       = m.OFFSET_Y;
        m.OFFSET_Y          = -5;
        m.OFFSET_X          = -5;
        m.makeAvatar        = function() {
            var avatar = new p4cms.ui.grid.plugins.GridDnDAvatar(m);
            delete m._dndPlugin;
            return avatar;
        };
        m.startDrag(this._source, this._elem.getDnDNodes(), evt.ctrlKey);
        m.makeAvatar = oldMakeAvatar;
        m.onMouseMove(evt);
    },

    // overrides the original to reset dnd manager
    _destroySource: function() {
        this.inherited(arguments);

        var m = dojo.dnd.manager();
        m.OFFSET_Y = m._oldOffsetY;
        m.OFFSET_X = m._oldOffsetX;
    },

    // overrides the original to add 'dropIn' detection
    _calcRowTargetAnchorPos: function(evt, containerPos) {
        var g       = this.grid, top, i = 0,
            cells   = g.layout.cells;

        while (cells[i].hidden) { ++i; }

        var cell        = g.layout.cells[i],
            rowIndex    = g.scroller.firstVisibleRow,
            nodePos     = dojo.position(cell.getNode(rowIndex));

        while (nodePos.y + nodePos.h < evt.clientY) {
            if (++rowIndex >= g.rowCount) {
                break;
            }
            nodePos = dojo.position(cell.getNode(rowIndex));
        }

        var inRow = false;
        if (rowIndex < g.rowCount) {
            if (this.selector.isSelected("row", rowIndex) && this.selector.isSelected("row", rowIndex - 1)) {
                var ranges = this._dndRegion.selected;
                for (i = 0; i < ranges.length; ++i) {
                    if (dojo.indexOf(ranges[i], rowIndex) >= 0) {
                        rowIndex        = ranges[i][0];
                        nodePos     = dojo.position(cell.getNode(rowIndex));
                        break;
                    }
                }
            }
            top = nodePos.y;

            var topRegion       = nodePos.y + (nodePos.h * 0.25),
                bottomRegion    = nodePos.y + (nodePos.h * 0.75),
                onlyWithin      = (!this._dropConfig.above && this._dropConfig.within);

            // determine if drop should be in the row or not
            // otherwise check if we should be moving to the next row
            if (this._dropConfig.within && evt.clientY > topRegion && evt.clientY < bottomRegion) {
                inRow = true;
            } else if (this._dropConfig.below && evt.clientY >= bottomRegion) {
                rowIndex++;
                top = nodePos.y + nodePos.h;
            } else if (onlyWithin && (rowIndex !== 0 || evt.clientY > topRegion)) {
                if (rowIndex === g.rowCount && evt.clientY >= bottomRegion) {
                    rowIndex++;
                    top = nodePos.y + nodePos.h;
                } else {
                    inRow = true;
                }
            }
        }

        // if we are at the end of the rows, make sure the anchor appears in the viewing area
        if (rowIndex >= g.rowCount) {
            top = nodePos.y + nodePos.h - this._targetAnchorBorderWidth;
        }

        this._target = {pos: rowIndex, dropIn: inRow, nodeHeight: nodePos.h};
        return top - containerPos.y;
    },

    // extends the original to add additional styling when doing a 'dropIn'
    _markTargetAnchor: function(evt) {
        if(this._alreadyOut){
            return;
        }

        // remove any previous row's styling
        this.removeRowDropClass();
        this.inherited(arguments);

        // add any new styling to rows that require it
        if (this._target && this._target.dropIn) {
            this.addRowDropClass();
            dojo.addClass(this._targetAnchor.row, 'p4cms-drop-anchor-in');
        } else if (this._targetAnchor.row) {
            dojo.removeClass(this._targetAnchor.row, 'p4cms-drop-anchor-in');
        }
    },

    _unmarkTargetAnchor: function() {
        this.removeRowDropClass();
        this.inherited(arguments);
    },

    // addes the drop class to the target row
    addRowDropClass: function() {
        if (this._target) {
            this.grid.views.forEach(dojo.hitch(this, function(view) {
                var rowView = view.rowNodes[this._target.pos];
                rowView     = rowView && dojo.addClass(rowView, 'p4cms-drop-row-in');
            }));
        }
    },

    removeRowDropClass: function() {
        if (this._target) {
            this.grid.views.forEach(dojo.hitch(this, function(view) {
                var rowView = view.rowNodes[this._target.pos];
                rowView     = rowView && dojo.removeClass(rowView, 'p4cms-drop-row-in');
            }));
        }
    }
});

// extend the DnD grid avatar to meet our needs
// note that our current revision only handles rows.
dojo.declare("p4cms.ui.grid.plugins.GridDnDAvatar", dojox.grid.enhanced.plugins.GridDnDAvatar, {
    construct: function() {
        // create the avatar dom
        var type        = this.manager._dndPlugin._dndRegion.type,
            dndPlugin   = this.manager.source.dnd,
            grid        = dndPlugin.grid,
            avatar      = dojo.create("table", {
                "border":       "0",
                "cellspacing":  "0",
                "class":        "dojoxGridDndAvatar "
                                + dojo.attr(grid.getGridContainer(), 'class'),
                "style":        {
                    position:   "absolute",
                    zIndex:     "1999",
                    margin:     "0px"
                }
            }),
            body        = dojo.create("tbody", null, avatar),
            rowItems    = dndPlugin.selector._selected[type];

        // make a copy of each selected row to show in our avatar
        dojo.forEach(rowItems, function(rowItem) {
            var tr          = dojo.create("tr", {'class':'dojoxGridRow'}, body),
                viewRows    = new dojo.NodeList();

            // nodes that make up a row may be spread accross multiple views
            // go through each view and find all the row's nodes
            grid.views.forEach(function(view) {
                viewRows.push(view.rowNodes[rowItem.row]);
            });

            // clone first cell into our row
            var td = dojo.place(dojo.clone(viewRows.query('.dojoxGridCell')[0]), tr);
            dojo.style(td, 'width', 'auto');
        });

        this.node = avatar;
    }
});

dojox.grid.EnhancedGrid.registerPlugin(p4cms.ui.grid.plugins.DnD, {
    "dependency": ["selector", "rearrange"]
});