// summary:
//      Extended p4cms.content.SubForm by adding extra functionality specific to workflow sub-form.

dojo.provide("p4cms.workflow.ContentSubForm");
dojo.require("p4cms.content.SubForm");

dojo.declare("p4cms.workflow.ContentSubForm", p4cms.content.SubForm,
{
    postCreate: function(){
        this.inherited(arguments);

        // display current time on server
        this._setupTimeNode();

        // connect to setup time node after update
        dojo.connect(this, 'onAfterUpdate', this, '_setupTimeNode');
    },

    // show current server time in the designated node
    _setupTimeNode: function () {
        var timeNode = dojo.query('.current-time', this.domNode);
        if (timeNode.length) {
            // timer may point to the old time node, so we clear it here
            // show server time can only clear timers present on the new node
            if (this._timer) {
                clearInterval(this._timer);
            }

            // create new timer to show current server time in the time node
            this._timer = p4cms.workflow.showServerTime(timeNode[0]);
        }
    }
});
