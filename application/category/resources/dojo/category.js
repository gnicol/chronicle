// summary:
//      Support for category module.

dojo.provide('p4cms.category');
dojo.require("p4cms.category.grid.Actions");
dojo.require("p4cms.category.grid.Formatters");
dojo.require("p4cms.category.grid.Utility");

// add (and save) new category when form is submitted from the tooltip dialog
p4cms.category.addCategoryFromTooltip = function(formDijit) {
    var form = formDijit.domNode;

    // get the Add Category button for this tooltip dialog form
    var tooltipDialog = new dojo.NodeList(form)
        .closest('.dijitTooltipDialogPopup.dijitPopup')[0];
    var addCategoryButton = dijit.byId(dojo.attr(tooltipDialog, 'dijitpopupparent'));

    // get the id of the current category sub-form
    var categorySubFormId = new dojo.NodeList(addCategoryButton.domNode)
        .closest('.content-sub-form')[0].id;

    var formCount = dojo.query('.p4cms-ui.category-form').length;

    dojo.xhrPost({
        url:        p4cms.url({
            'module'        : 'category',
            'controller'    : 'manage',
            'action'        : 'add',
            'format'        : 'json',
            'formIdPrefix'  : formCount,
            'short'         : 'true'
        }),
        form:       form,
        handleAs:   'json',
        load: function(response) {
            p4cms.category.updateSubForms(
                response.category.id,
                categorySubFormId
            );
        },
        error: function(response) {
            // attempt to decode error response.
            var data = null;
            try {
                data = dojo.fromJson(response.responseText);
            } catch (e) {}

            // if there are no errors, display notification
            if (!data.errors) {
                var notice = new p4cms.ui.Notice({
                    message:    'Unknown error occurred during adding a new category.',
                    severity:   'error',
                    name:       'content-category'
                });
            }

            // refresh the tooltip with updated form
            addCategoryButton.dropDown.set('content', data.form);
            addCategoryButton.openDropDown();
        }
    });
};

p4cms.category.updateSubForms = function(categoryId, currentSubFormId) {
    // update category sub-forms; may be multiple.
    dojo.query('.content-sub-form[formname=category]').forEach(
        function(subFormNode, index) {
            var subForm      = dijit.byNode(subFormNode);
            subForm.idPrefix = index;
            var deferred     = subForm.update();
            var subFormId    = subForm.id;

            // check new category when the current form is updated.
            if (subFormId === currentSubFormId) {
                deferred.addCallback(function() {
                    var subForm = dijit.byId(currentSubFormId);
                    var query   = 'input[type=checkbox][value=' + categoryId + ']';
                    dojo.query(query, subForm.domNode).attr('checked', true);

                    // notify user.
                    var notice = new p4cms.ui.Notice({
                        message:    'Added and selected category.',
                        name:       'content-category',
                        severity:   'success'
                    });
                });
            }
        }
    );
};