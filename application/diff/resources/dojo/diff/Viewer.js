// summary:
//      The brains of the diff interface.

dojo.provide("p4cms.diff.Viewer");
dojo.require("dijit.form.Button");
dojo.require("dojo.NodeList-traverse");

dojo.declare("p4cms.diff.Viewer", dijit._Widget,
{
    diffTypes:      ['change', 'insert', 'delete'],

    // option to size the viewer to use up available space in the window
    dynamicSize:    true,
    resizeDelay:    100,
    resizeTimeout:  null,

    startup: function() {
        this.inherited(arguments);
        dojo.addClass(this.domNode, 'diff-viewer');

        // last child selector doesn't work in IE7 or IE8, add class here
        dojo.query('.element table tbody:last-child', this.domNode).addClass('last-child');
        dojo.query('.element table tbody tr:last-child', this.domNode).addClass('last-child');

        // wire-up prev/next buttons.
        this.connect(this.getPrevButton(), 'onClick', 'prevDiff');
        this.connect(this.getNextButton(), 'onClick', 'nextDiff');

        // wire up hide unchanged
        this.connect(
            this.getHideUnchangedButton(),
            'onclick',
            'hideUnchanged'
        );

        // start out hiding unchanged
        dojo.attr(this.getHideUnchangedButton(), 'checked', 'checked');
        this.hideUnchanged();

        // wire up ignore whitespace
        this.connect(
            this.getIgnoreWhitespaceButton(),
            'onclick',
            'toggleWhitespace'
        );

        // focus first diff.
        window.setTimeout(dojo.hitch(this, function() {this.focusDiff(0);}), 0);

        // update the legend
        this.updateLegend();

        // size the version headings.
        this.sizeVersionHeadings();

        // make the comparison pane as big as we can.
        if (this.dynamicSize) {
            this._dynamicSize();
            this.connect(window, 'onresize', '_delayedDynamicSize');
        }

        // make sure the dialog height and width are set to auto
        var dialog = this._isInDialog();
        if (dialog) {
            this.connect(dialog, '_size', dojo.hitch(dialog, function() {
                var node = this.scrollNode || this.containerNode;

                var height = dojo.style(node, 'height'),
                    width  = dojo.style(node, 'width');
                if (width !== 'auto' || height !== 'auto') {
                    dojo.style(node, {width: 'auto', height: 'auto'});
                }
            }));
        }
    },

    sizeVersionHeadings: function(auto) {
        // tweak the width of the table inside the versions heading
        // to match width of tables within the scroll area
        var width = auto ? 'auto' : '100%';
        dojo.query('.versions-heading table', this.domNode)
            .style('width', width);
    },

    updateLegend: function() {
        var mode, modes = {'insert': 'addition', 'delete': 'deletion', 'change': 'change'};
        for (mode in modes) {
            if (modes.hasOwnProperty(mode)) {
                var count = this['get' + mode.charAt(0).toUpperCase() + mode.slice(1) + 'Count']();
                dojo.query('span.' + mode, this.domNode)[0].innerHTML =
                    '<b>' + count + '</b> ' + modes[mode] + (count !== 1 ? 's' : '');
            }
        }
    },

    updateDiffNavigation: function() {
        var currentDiff = this.getFocusedDiffIndex() + 1;
        var totalDiffs  = this.getAllDiffs().length;

        // update diff count.
        dojo.query('span.total-diff-chunks', this.domNode)[0].innerHTML = totalDiffs;

        // update current diff in diff count.
        dojo.query('span.current-diff-chunk', this.domNode)[0].innerHTML = currentDiff;

        // disable prev/next as appropriate.
        this.getPrevButton().set('disabled', (currentDiff <= 1 ? true : false));
        this.getNextButton().set('disabled', (currentDiff >= totalDiffs ? true : false));
    },

    getPrevButton: function() {
        return dijit.byNode(dojo.query('.prev-diff-button', this.domNode)[0]);
    },

    getNextButton: function() {
        return dijit.byNode(dojo.query('.next-diff-button', this.domNode)[0]);
    },

    getHideUnchangedButton: function() {
        return dojo.query('input[name="hideUnchanged"]', this.domNode)[0];
    },

    getIgnoreWhitespaceButton: function() {
        return dojo.query('input[name="ignoreWhitespace"]', this.domNode)[0];
    },

    getComparisonPane: function() {
        return dojo.query('.diff-comparison-pane', this.domNode)[0];
    },

    getToolbar: function() {
        return dojo.query('.toolbar', this.domNode)[0];
    },

    prevDiff: function() {
        var index = this.getFocusedDiffIndex();
        if (index > 0) {
            this.focusDiff(--index);
        }
    },

    nextDiff: function() {
        var index = this.getFocusedDiffIndex();
        if (index < (this.getAllDiffs().length - 1)) {
            this.focusDiff(++index);
        }
    },

    getAllDiffs: function() {
        return dojo.query('.element .diff-mode-active tbody.diff:not(.same)', this.domNode);
    },

    getFocusedDiff: function() {
        return dojo.query('tbody.focus', this.domNode)[0];
    },

    getFocusedDiffIndex: function() {
        var i, diffs    = this.getAllDiffs(),
            focused     = this.getFocusedDiff();
        for (i = 0; i < diffs.length; i++) {
            if (diffs[i] === focused) {
                return i;
            }
        }

        // negative one if no diff focused.
        return -1;
    },

    focusDiff: function(i) {
        var diffs = this.getAllDiffs();

        // verify index is good.
        if (!diffs.length) {
            return;
        }
        if (!diffs[i]) {
            return;
        }

        // highlight focused diff.
        dojo.query('tbody.focus', this.domNode).removeClass('focus');
        dojo.addClass(diffs[i], 'focus');

        // update prev/next navigation.
        this.updateDiffNavigation();

        // scroll chunk into view.
        dijit.scrollIntoView(diffs[i]);
    },

    getDiffOffsetTop: function(diffNode) {
        return dojo.marginBox(diffNode).t
             + dojo.marginBox(diffNode.parentNode).t;
    },

    getAllElements: function() {
        return dojo.query('.element', this.domNode);
    },

    getAllUnchanged: function() {
        var query = '.element.same';

        // if ignore whitespace checked, consider whitespace changes 'same'.
        if (this.isIgnoreWhitespaceChecked()) {
            query += ', .element.whitespace-change';
        }

        return dojo.query(query, this.domNode);
    },

    hideUnchanged: function() {
        // show all initially.
        this.getAllElements().style('display', '');

        // toggle unchanged.
        var display = this.isHideUnchangedChecked() ? 'none' : '';
        this.getAllUnchanged().style('display', display);
    },

    toggleWhitespace: function() {
        if (this.isIgnoreWhitespaceChecked()) {
            this.ignoreWhitespace();
        } else {
            this.recognizeWhitespace();
        }
    },

    ignoreWhitespace: function() {
        // append '-ignore' to the chunk type class of whitespace
        // only diffs so they aren't recognized as diffs.
        dojo.query('.element tbody.whitespace-change', this.domNode).forEach(
            dojo.hitch(this, function(node) {
                var type;
                for (type in this.diffTypes){
                    if (this.diffTypes.hasOwnProperty(type)) {
                        type = this.diffTypes[type];
                        if (dojo.hasClass(node, type)) {
                            dojo.replaceClass(node,
                                type + '-ignore same', type + ' focus');
                        }
                    }
                }
            })
        );

        // hide unchanged.
        this.hideUnchanged();

        // update prev/next navigation.
        this.updateDiffNavigation();

        // update legend counts.
        this.updateLegend();
    },

    recognizeWhitespace: function() {
        // remove '-ignore' from diff chunk type classes
        // so they are once again recognized as diffs
        dojo.query('.element tbody.whitespace-change', this.domNode).forEach(
            dojo.hitch(this, function(node) {
                var type;
                for (type in this.diffTypes){
                    if (this.diffTypes.hasOwnProperty(type)) {
                        type = this.diffTypes[type];
                        if (dojo.hasClass(node, type + '-ignore')) {
                            dojo.removeClass(node, type + '-ignore');
                            dojo.addClass(node, type);
                            dojo.removeClass(node, 'same');
                        }
                    }
                }
            })
        );

        // hide unchanged.
        this.hideUnchanged();

        // update prev/next navigation.
        this.updateDiffNavigation();

        // update legend counts.
        this.updateLegend();

        // if no currently focused diff, jump to first diff.
        if (!this.getFocusedDiff()) {
            this.focusDiff(0);
        }
    },

    getTableWidth: function() {
        var tables = dojo.query('.element .diff-mode-active table', this.domNode);
        if (!tables.length) {
            return 0;
        }
        return dojo.marginBox(tables[0]).w;
    },

    getInsertCount: function() {
        return dojo.query('.diff-mode-active tbody.insert', this.domNode).length;
    },

    getDeleteCount: function() {
        return dojo.query('.diff-mode-active tbody.delete', this.domNode).length;
    },

    getChangeCount: function() {
        return dojo.query('.diff-mode-active tbody.change', this.domNode).length;
    },

    isIgnoreWhitespaceChecked: function() {
        var button  = this.getIgnoreWhitespaceButton();
        return dojo.attr(button, 'checked');
    },

    isHideUnchangedChecked: function() {
        var button  = this.getHideUnchangedButton();
        return dojo.attr(button, 'checked');
    },

    _dynamicSize: function(e) {
        var dialog = this._isInDialog();
        if (dialog) {
            this._sizeForDialog(dialog);
            this.sizeVersionHeadings();
        }
    },

    _delayedDynamicSize: function() {
        window.clearTimeout(this.resizeTimeout);
        this.resizeTimeout = window.setTimeout(
            dojo.hitch(this, '_dynamicSize'),
            this.resizeDelay
        );
    },

    // if inside a dialog, consume 90% of viewport.
    _sizeForDialog: function(dialog) {
        // determine size of dialog without pane
        // (must also clear width on version headings)
        this.sizeVersionHeadings(true);
        var pane = this.getComparisonPane();
        dojo.style(pane, {display: 'none'});
        dojo.style(this.domNode, {width: 'auto'});

        var smallDialog = dojo.marginBox(dialog.domNode);
        var innerBox = dojo.marginBox(this.getToolbar());

        // restore view
        dojo.style(pane, {display: 'block'});

        // start with 90% of viewport.
        var width  = Math.floor(dijit.getViewport().w * 0.9);
        var height = Math.floor(dijit.getViewport().h * 0.9);

        // account for margins and other contents
        width -= (smallDialog.w - innerBox.w);
        height -= (height < smallDialog.h ? 0 : smallDialog.h);

        // apply height to diff pane for proper scrolling
        dojo.style(pane, {
            height: height + 'px'
        });

        // don't go narrower than width of small viewer.
        dojo.style(this.domNode, {
            width:  width < smallDialog.w ? 'auto' : width + 'px'
        });

        // force the the dialog to recenter
        dialog._relativePosition = null;
        dialog._position();

        // adjust version headings.
        this.sizeVersionHeadings();
    },

    destroy: function() {
        this.inherited(arguments);
        if (this.resizeTimeout) {
            clearTimeout(this.resizeTimeout);
        }
    },

    _isInDialog: function() {
        var result = dojo.query('#' + this.id).closest('.dijitDialog');
        if (result.length) {
            return dijit.byNode(result[0]);
        } else {
            return false;
        }
    }
});