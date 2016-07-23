dojo.provide('p4cms.workflow.content.grid.Actions');

p4cms.workflow.content.grid.Actions = {

    onClickChangeStatus: function() {
        var rowIndex  = dijit.byId('dijitmenu-content-grid').rowIndex;
        p4cms.workflow.content.grid.Utility.openWorkflowDialog(rowIndex);
    },

    onShowChangeStatus: function(menuItem) {
        var rowIndex = dijit.byId('dijitmenu-content-grid').rowIndex;
        
        // hide menu item if entry has no workflow
        var workflowId = p4cms.content.grid.instance.getItemValue(rowIndex, 'workflowId');
        menuItem.set('disabled', !workflowId);
    }
};