// summary:
//      Support for workflow module.

dojo.provide("p4cms.workflow");
dojo.require("p4cms.workflow.grid.Actions");
dojo.require("p4cms.workflow.grid.Utility");
dojo.require("p4cms.workflow.ContentSubForm");
dojo.require("p4cms.workflow.content.grid.Actions");
dojo.require("p4cms.workflow.content.grid.Utility");
dojo.require("p4cms.ui.grid.Form");
dojo.require("p4cms.ui.grid.formatters.ActionsButton");
dojo.require("p4cms.ui.grid.formatters.CommaJoin");
dojo.require("p4cms.ui.TooltipDialog");
dojo.require('p4cms.ui.ConfirmDialog');
dojo.require('p4cms.ui.FormDialog');
dojo.require('dijit.form.Button');
dojo.require("dojo.date.locale");

p4cms.workflow.contentHistoryGridFormatters = {
    state: function(item, row) {
        if (!item) {
            return '';
        }

        var state = item.state || '';
        var attr  = item.exists ? '' : ' class="not-existing-state"';
        return '<span' + attr + '>' + state + '</span>';
    }
};

p4cms.workflow.contentGridFormatters = {
    state: function(item, row) {
        var values = p4cms.content.grid.instance.getItemValues(row);

        var state = values.workflowState || '';
        var attr  = !state ? ' class="unknown-state"' : '';
        return '<span' + attr + '>' + ( state || 'Unknown' ) + '</span>';
    }
};

// sets node's content to be "Server Time: <SERVER TIME>", where <SERVER TIME>
// represents formatted server time, for example: Jan 1, 7:05:22 AM
// time format can be optionally specified in the second parameter
// also sets a timer to update time node every second
// its safe to call this method more than once as it will clear the existing timer
// returns interval (timer) ID
p4cms.workflow.showServerTime = function(node, format) {
    // provide a default value for format
    format = format || 'MMM d, h:mm:ss a';

    // get the wrapper to put the server time details into
    var wrapper = dojo.query('.wrapper', node)[0] ||
        dojo.create('div', {'class' : 'wrapper'}, node, 'first');

    // create nodes with label and time and place them into the wrapper as the only childs
    dojo.create('span', {'class' : 'label', innerHTML: 'Server Time: '}, wrapper, 'only');
    var timeNode = dojo.create('span', {'class' : 'time'}, wrapper);

    // if the node already has timer running on it, clear it
    if (node.showServerTimeIntervalId) {
        clearInterval(node.showServerTimeIntervalId);
    }

    // update time node every second to show the current server time
    var intervalId = setInterval(
        function() {
            // update time node
            timeNode.innerHTML = dojo.date.locale.format(
                new Date(new Date() - p4cms.ui.serverTimeOffset * 1000),
                {
                    selector: "date",
                    datePattern: format
                }
            );
        },
        1000
    );

    node.showServerTimeIntervalId = intervalId;
    return intervalId;
};

// disable Workflow button in the content grid footer if no rows are selected
dojo.addOnLoad(function(){
    var gridFooter = dojo.query('.content-grid .grid-footer');
    if (!gridFooter.length) {
        return;
    }

    var workflowButton = dojo.query('.workflow-button', gridFooter[0]);
    var grid           = p4cms.content.grid.instance;
    if (workflowButton.length && grid) {
        var button = dijit.byNode(workflowButton[0]);
        // set initial button state
        button.set('disabled', grid.selection.getSelectedCount() === 0);

        // connect to enable Workflow button only if there are rows selected
        var footer = dijit.byNode(gridFooter[0]);
        dojo.connect(footer, 'updateSelected', function() {
            button.set('disabled', grid.selection.getSelectedCount() === 0);
        });
    }
});