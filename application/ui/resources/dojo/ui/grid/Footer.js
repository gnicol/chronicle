// summary:
//      A footer that is tied to a data grid and automatically
//      updates itself to reflect the state of the grid.

dojo.provide("p4cms.ui.grid.Footer");
dojo.require('dijit._Widget');
dojo.declare("p4cms.ui.grid.Footer", [dijit._Widget], {

    gridId:                 '',
    showRowCount:           true,
    updateHandler:          null,
    selectionUpdateHandler: null,
    storeDataHandler:       null,
    _numObligatoryRows:     0,

    startup: function() {
        // hitch to grid fetch to we can collect numrows
        this.updateHandler  = dojo.connect(
            this.getGrid(),
            '_onFetchComplete',
            this,
            'update'
        );

        // hitch to grid selection change to update selected rows count
        this.selectionUpdateHandler = dojo.connect(
            this.getGrid().selection,
            'onChanged',
            this,
            'updateSelected'
        );

        // as only certain data are saved by the grid data store,
        // connect to the designated data store function to extract
        // custom meta data and save them locally for later use
        this.storeDataHandler = dojo.connect(
            this.getGrid().store,
            '_filterResponse',
            this,
            'processStoreData'
        );
    },

    postCreate: function() {
        dojo.addClass(this.domNode, 'grid-footer');

        if (this.showRowCount) {
            var count    = dojo.create('span', {'class': 'row-count'});
            var number   = dojo.create('span', {'class': 'num-rows'}, count);
            var label    = dojo.create('span', {'class': 'count-label'}, count);
            var selected = dojo.create('span', {'class': 'selected-rows'}, count);
            dojo.place(count, this.domNode, 'first');
        }
    },

    processStoreData: function(data) {
        // update obligatory rows counter
        this._numObligatoryRows =
            data.numObligatoryRows ? parseInt(data.numObligatoryRows, 10) : 0;
    },

    update: function() {
        var number = dojo.query('.num-rows',    this.domNode)[0];
        var label  = dojo.query('.count-label', this.domNode)[0];
        if (number && label) {
            var grid = this.getGrid(),
                rows = grid.store._numRows - this._numObligatoryRows;
            number.innerHTML = rows || '0';
            label.innerHTML  = rows === 1 ? 'entry' : 'entries';
        }

        // update selected rows notification
        this.updateSelected();
    },

    updateSelected: function() {
        var selected = dojo.query('.selected-rows', this.domNode)[0];

        // if we have box with selected info, update it, otherwise do nothing
        if (selected) {
            // if there are selected rows, show their total count in the box,
            // otherwise leave the box empty
            var selectedCount  = this.getGrid().selection.getSelectedCount();
            selected.innerHTML = selectedCount ? '&nbsp;(' + selectedCount + ' selected)' : '';
        }
    },

    getGrid: function() {
        var grid = dijit.byId(this.gridId);
        if (!grid) {
            grid = dojo.getObject(this.gridId);
        }
        return grid;
    }
});
