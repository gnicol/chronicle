// summary:
//      Support for menu module.

dojo.provide('p4cms.menu');
dojo.require("p4cms.menu.grid.Formatters");
dojo.require("p4cms.menu.grid.Actions");
dojo.require("p4cms.menu.grid.Utility");

p4cms.menu.refreshSubForm = function(form) {
    var dialog     = new dojo.NodeList(form).closest('.dijitDialog')[0];
    var saveButton = dojo.query('dd#save-element .dijitButton', dialog)[0];
    
    if (saveButton) {
        saveButton = dijit.byNode(saveButton);
        saveButton.disable();
    }
    
    dojo.xhrGet({
        url:    p4cms.url({
            module:     'widget',
            controller: 'index',
            action:     'form'
        }),
        form:   form,
        load:   function(response) {
            var newForm = dojo.create('div');
            dojo.place(dojo.trim(response), newForm, 'only');
    
            // pull out the new sub-form and old sub-form elements
            var newSubForm = dojo.query('div[name=config]', newForm)[0];
            var oldSubForm = dojo.query('div[name=config]', form)[0];

            // re-enable the save button if we have one
            if (saveButton) {
                saveButton.enable();
            }

            // just be defensive; no update if we are missing either subform
            if (!newSubForm || !oldSubForm) {
                return;
            }

            // update the sub-form's content
            dijit.byNode(oldSubForm).set('content', newSubForm.childNodes);
        }
    });
};