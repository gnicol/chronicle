// summary:
//      An extended dojox.grid.EnhancedGrid
//      Adds option to disable focusing when clicking cells in the grid.
//      Adds option to disable sorting feature.
//      Provides easy access to item values
//      Sets a 'rowIndex' property on the row context menu when clicked.
//

dojo.provide("p4cms.ui.grid.DataGrid");
dojo.require("p4cms.ui.grid.View");
dojo.require("p4cms.ui.grid.Footer");
dojo.require("p4cms.ui.Tooltip");
dojo.require("dojox.grid.EnhancedGrid");
dojo.require("dojo.NodeList-traverse");
dojo.require("p4cms.ui.grid.EnhancedSelectorPatch");
dojo.require("p4cms.ui.grid.DataSelection");

dojo.declare("p4cms.ui.grid.DataGrid", dojox.grid.EnhancedGrid, {

    // @todo make this more fine-grained - disable for specific cells.
    disableFocus:   false,

    // list with field names that will not be sortable OR
    // string with field names (separated by comma) that will not be sortable OR
    // false to enable sorting on all columns OR
    // true  to disable sorting on all columns
    disableSort:    null,

    // grid row context menu
    _gridRowMenu:   null,

    // tooltip dialogs attached to the grid fields
    fieldTooltips:  null,

    // option to size the grid to use up available vertical
    // space in the window - unlike autoHeight which shrinks
    // the grid to fit its contents, this grows the grid to
    // fill the browser.
    dynamicHeight:  false,

    // option to specify the minimum grid height
    // can be expressed as a float, or percentage '50%' or '50', compared to viewport height
    // or can be expressed as pixels '50px'
    minGridHeight:  '0.5',
    resizeDelay:    50,
    resizeTimeout:  null,
    jsId:           '',

    startup: function() {
        this.inherited(arguments);

        // hook-up dynamic height if enabled.
        // we try this three times:
        //     - really early,
        //     - when the datagrid is loaded,
        //     - when the page is fully loaded.
        // if the datagrid is in a dialog, we caculate the dynamic
        // height only once.
        if (this.dynamicHeight) {
            var dialog = this._isInDialog();
            this._dynamicHeight();
            if (!dialog) {
                // make an early attempt to size the grid. Due to missing
                // images/styles this might not work out perfectly
                dojo.addOnLoad(this, '_dynamicHeight');

                // fire again when the page is fully loaded
                this.connect(window, 'onload', '_dynamicHeight');
            }
            this.connect(window, 'onresize', '_delayedDynamicHeight');
        }

        try {
            this._gridRowMenu = dijit.byId(this.plugins.menus.rowMenu);
        } catch(e) {
            // no row menu defined.
        }

        // unbind row context menu from grid node to avoid opening menu on blank rows
        if (this._gridRowMenu) {
            this._gridRowMenu.unBindDomNode(this.domNode);
            this.connect(this._gridRowMenu, 'onClose', function() {
                this._gridRowMenu.unBindDomNode(this.domNode);
            });
        }

        // add a 'sortable' class header cells of sortable columns.
        dojo.forEach(dojo.query('table th', this.viewsHeaderNode),
            dojo.hitch(this, function(column) {
                var index = parseInt(dojo.attr(column, 'idx'), 10);
                if (this.canSort(index + 1)) {
                    this.layout.cells[index].headerClasses += ' sortable';
                }
            })
        );

        // connect field tooltips
        if (this.fieldTooltips) {
            dojo.forEach(this.fieldTooltips, dojo.hitch(this, 'connectFieldTooltip'));
        }

        // add a 'selectable' class if selections are enabled.
        if (this.selectionMode !== 'none') {
            dojo.addClass(this.domNode, 'selectable');
        }

        // inject the id as a class on each row for styling and testing purposes.
        dojo.connect(this, 'onStyleRow', dojo.hitch(this, function(row){
            if (row.index !== undefined && this.store && this.getItem(row.index)) {
                var id = this.store.getIdentity(this.getItem(row.index));
                if (id !== undefined && id !== null) {
                    id = String(id).replace(/[^a-z0-9\.]+/ig, '-')
                                   .replace(/^[\-\.]+|[\-\.]+$/g, "")
                                   .toLowerCase();

                    row.customClasses += " row-id-" + id;
                }
            }
        }));
    },

    // assign our own data selection dijit that fixes issues with counting
    // selected rows that became inaccessible (e.g. due to filtering the grid)
    createSelection: function() {
        this.selection = new p4cms.ui.grid.DataSelection(this);
    },

    connectFieldTooltip: function(tooltipData) {
        this.connect(this, 'onCellMouseOver', function(e) {
            this.fieldTooltip(tooltipData, e);
        });
    },

    // attach tooltip dialog to the specified field
    // (fires on cell mouse over event)
    fieldTooltip: function(tooltipData, e) {
        // content can be pulled from a source field, via a formatter callback
        // or remotely via a href callback. if by href or formatter callback,
        // the callback is passed all of the current item values for context
        var content = '', href = '', callback;
        if (tooltipData.sourceField) {
            content = this.getItemValue(e.rowIndex, tooltipData.sourceField);
        } else if (tooltipData.formatterCallback) {
            callback = dojo.getObject(tooltipData.formatterCallback || '', false);
            content  = dojo.isFunction(callback) && this.getItem(e.rowIndex)
                ? callback(this.getItemValues(e.rowIndex))
                : null;
        } else if (tooltipData.hrefCallback) {
            callback = dojo.getObject(tooltipData.hrefCallback || '', false);
            href     = dojo.isFunction(callback) && this.getItem(e.rowIndex)
                ? callback(this.getItemValues(e.rowIndex))
                : null;
        }

        // if no content/href or different attach field, do nothing
        if ((!content && !href) || e.cell.field !== tooltipData.attachField) {
            return;
        }

        // get node to attach tooltip to
        var cell = this.getCellNode(e.rowIndex, e.cellIndex);

        // nothing to do if we've already attached.
        if (dojo.attr(cell, 'tooltip')) {
            return;
        }

        // tooltip definition has the option of specifying an 'aroundNode'
        // this is the node that we will position the tooltip around
        // it is specified as a css selector relative to the cell.
        var aroundNode = cell;
        if (tooltipData.aroundNode) {
            aroundNode = dojo.query(tooltipData.aroundNode, cell).length
                ? dojo.query(tooltipData.aroundNode, cell)[0]
                : cell;
        }

        // tooltip definition has the option of specifying tooltip classes
        // so that we can target different types of tooltips for styling
        var classes = 'grid-tooltip ' + (tooltipData.tooltipClass || '');

        // make the tooltip.
        var tooltip = new p4cms.ui.Tooltip({
            connectId:  cell,
            label:      content,
            href:       href,
            'class':    classes,
            aroundNode: aroundNode,
            position:   ["below"],
            showDelay:  href ? 750 : 1000
        });

        // add it as a cell attribute so that we only make it once.
        dojo.attr(cell, 'tooltip', tooltip);

        // must manually open it the first time.
        tooltip._onHover(e);
    },

    // extend postCreate to:
    //  - disable cell focus when disableFocus = true
    postCreate: function() {
        this.inherited(arguments);

        if (this.disableFocus) {
            this.focus.focusArea            = function() {};
            this.focus._delayedHeaderFocus  = function() {};
            this.focus._focusifyCellNode    = function() {};
            this.focus.focusGridView        = function() {};
            this.focus.focusClass           = "";
        }
    },

    // determine if grid can be sorted by column with 'index' order (start by 1 from left to right)
    // according to disableSort property (allows flexible formats, see description in declaration)
    canSort: function(index) {
        // convert index into field name
        var fieldName = this.layout.cells[Math.abs(index)-1].field;

        if (typeof(this.disableSort) === 'boolean') {
            return !this.disableSort;
        } else if (typeof(this.disableSort) === 'string') {
            return dojo.indexOf(this.disableSort.split(','), fieldName) === -1;
        } else if (typeof(this.disableSort) === 'object') {
            var sortable = true;
            dojo.forEach(this.disableSort, function(noSortFieldname) {
                if (noSortFieldname === fieldName) {
                    sortable = false;
                }
            });
            return sortable;
        }
        // enable by default
        return true;
    },

    // easy access to item values.
    getItemValue: function(index, attribute) {
        var item = this.getItem(index);
        return item ? this.store.getValue(item, attribute) : null;
    },

    getItemValues: function(index) {
        var values      = {},
            item        = this.getItem(index),
            attribute;

        if (!item) {
            return null;
        }

        var attributes  = this.store.getAttributes(item);
        for (attribute in attributes) {
            if (attributes.hasOwnProperty(attribute)) {
                attribute = attributes[attribute];
                values[attribute] = this.store.getValue(item, attribute);
            }
        }

        return values;
    },

    refresh: function() {
        this._lastScrollTop = this.scrollTop;
        this._clearData();
        this._fetch();
    },

    getCellNode: function(rowIndex, cellIndex) {
        return this.views.views[0].getCellNode(rowIndex, cellIndex);
    },

    getCells: function() {
        var i, cells = [];
        for (i = 0; i < this.layout.cells.length; i++) {
            cells.push(this.layout.cells[i].field);
        }
        return cells;
    },

    // Returns the parent node created by the p4cms
    // Data Grid view helper, with a fallback to the
    // grid's own domNode
    getGridContainer: function() {
        if (!this.domNode) {
            return null;
        }

        var nodelist    = new dojo.NodeList(this.domNode),
            container   = nodelist.closest('.data-grid');

        return container.length > 0 ? container[0] : this.domNode;
    },

    docontextmenu: function(e) {
        if (this._gridRowMenu) {
            // don't show context menu on selected rows (if there are at least 2 rows selected)
            if (this.selection.getSelectedCount() > 1 && this.selection.selected[e.rowIndex]) {
                return;
            }

            this._gridRowMenu.rowIndex = e.rowIndex;
            this._gridRowMenu.bindDomNode(this.domNode);
        }
        this.inherited(arguments);
    },

    _dynamicHeight: function(e) {
        var dialog = this._isInDialog();
        if (dialog) {
            this._sizeForDialog(dialog);
        } else if (this._isAbsolutelyPositioned()) {
            this._sizeForAbsolute();
        } else {
            this._sizeForBody();
        }
        window.clearTimeout(this.resizeTimeout);
    },

    _delayedDynamicHeight: function() {
        window.clearTimeout(this.resizeTimeout);
        this.resizeTimeout = window.setTimeout(
            dojo.hitch(this, '_dynamicHeight'),
            this.resizeDelay
        );
    },

    // compute minimum grid height in pixels
    _getMinGridHeight: function() {
        var minHeight = this.minGridHeight || 0.5;

        // handle string values
        if (isNaN(minHeight)) {
            // if minimum height expressed in pixels, just return that
            if (minHeight.indexOf('px') > 0) {
                return parseInt(minHeight, 10);
            }

            // if minimum height expressed as percentage, turn into a float
            if (minHeight.indexOf('%') >= 0 || parseInt(minHeight, 10) >= 1) {
                minHeight = parseInt(minHeight, 10) / 100;
            }
        }

        return Math.floor(dijit.getViewport().h * parseFloat(minHeight));
    },

    // if inside a dialog, consume minimum grid height of viewport.
    _sizeForDialog: function(dialog) {
        this.resize({h: this._getMinGridHeight()});
        dialog._position();
    },

    // use minimum grid height when absolutely positioned.
    _sizeForAbsolute: function() {
        this.resize({h: this._getMinGridHeight()});
    },

    // use available space in the body up-to point of scrolling.
    _sizeForBody: function() {
        // minimum height is half the viewport.
        var viewport  = dijit.getViewport();
        var minHeight = this._getMinGridHeight();

        // temporarily make the grid the tallest element
        // on the page to determine the height of elements
        // that flow below it.
        var height = dojo.coords(dojo.body()).h;
        this.resize({h: height});

        // get height of elements above and below the grid.
        var above = dojo.coords(this.domNode).t;
        var below = dojo.coords(dojo.body()).h - above - height;

        // the maximum height of the grid (w.out scrolling
        // the page) is the height of the viewport, minus
        // the space above and below the grid.
        height = viewport.h - above - below;

        // use the greater of height and minHeight.
        this.resize({h: Math.max(height, minHeight)});
    },

    _isAbsolutelyPositioned: function(node) {
        if (!node) {
            node = this.domNode;
        }
        if (!node) {
            return false;
        }
        if (node.style && dojo.style(node, 'position') === 'absolute') {
            return true;
        }
        if (!node.parentNode) {
            return false;
        }
        return this._isAbsolutelyPositioned(node.parentNode);
    },

    _isInDialog: function() {
        var result = dojo.query('#' + this.id).closest('.dijitDialog');
        if (result.length) {
            return dijit.byNode(result[0]);
        } else {
            return false;
        }
    },

    escapeHtml: function(value) {
        return (value && value.replace)
            ? value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            : value;
    },

    adaptWidth: function() {
        // private: sets width and position for views and update grid width if necessary
        var doAutoWidth = (!this.initialWidth && this.autoWidth);
        var w = doAutoWidth ? 0 : this.domNode.clientWidth || (this.domNode.offsetWidth - this._getPadBorder().w),
            vw = this.views.arrange(0, w);
        this.views.onEach("adaptWidth");
        if (doAutoWidth) {
            this.domNode.style.width = vw + "px";
        }
    },

    // extend fetch to allow concurrent requests
    // fixes an issue where filter calls are ignored
    // if a previous call is still in-flight.
    _fetch: function(start, isRender) {
        // always include column list.
        if (!this.query) {
            this.query = {};
        }
        this.query['columns[]'] = this.getCells();

        // if there is a request in flight, clear it.
        this._pending_requests[(start || 0)] = false;

        this.inherited(arguments);

        // while loading, add css 'loading' class.
        dojo.addClass(this.domNode, 'loading');
    },

    // extend fetch complete handler to ignore stale responses.
    _onFetchComplete: function(items, req) {
        var ignoredProps = {start: null, count: null};
        var requestQuery = dojo.mixin(dojo.clone(req.query), ignoredProps);
        var currentQuery = dojo.mixin(dojo.clone(this.query), ignoredProps);

        // request query must match current instance query, or its stale.
        if (dojo.toJson(requestQuery) !== dojo.toJson(currentQuery)) {
            return;
        }

        this.inherited(arguments);

        // done loading, strip css 'loading' class.
        dojo.removeClass(this.domNode, 'loading');
    },

    // extend create view to patch/hack the view's get scrollbar
    // method such that we always leave space for a scrollbar.
    // this fixes an issue where the grid may scroll sideways
    // needlessly (if the scrollbar is introduced after the content
    // width has been determined).
    createView: function() {
        var view = this.inherited(arguments);
        view.getScrollbarWidth = function() {
            return dojox.html.metrics.getScrollbar().w;
        };
        return view;
    },

    // called before the rearranger plugin's onMoveRows method
    onMoveRows: function(rowsToMove, targetPos) {},

    // called before the selector plugin's valid method if the DnD plugin is present
    selectionValidating: function(type, item, allowNotSelectable) {},

    destroy: function() {
        window.clearTimeout(this.resizeTimeout);
        this.inherited(arguments);

        // unset global reference (set via jsId) to this instance
        // dojo doesn't do it automatically - see http://bugs.dojotoolkit.org/ticket/10799
        if (this.jsId) {
            /*jslint evil: true*/
            eval(this.jsId + ' = null');
            /*jslint evil: false*/
        }
    }
});

// needed to extend the data grid see:
// http://bugs.dojotoolkit.org/ticket/9150
p4cms.ui.grid.DataGrid.markupFactory = function(props, node, ctor, cellFunc) {
    return dojox.grid._Grid.markupFactory(props, node, ctor,
        dojo.partial(dojox.grid.DataGrid.cell_markupFactory, cellFunc));
};