dojo.provide('p4cms.user.base');

dojo.require("p4cms.ui.TooltipDialog");

// reuse the login dialog.
p4cms.user.loginDialog = null;

// function to present login tooltip dialog.
p4cms.user.login = function(link) {
    // create the login dialog if necessary.
    if (!p4cms.user.loginDialog) {
        var dialog = new p4cms.ui.TooltipDialog({
            href:           p4cms.url({
                module: 'user',
                action: 'login',
                format: 'partial'
            }),
            executeScripts: true
        });

        dojo.addClass(dialog.domNode, 'p4cms-user-login');

        // wire-up login to xhr so we process bad logins without refreshing the page
        dojo.connect(dialog, 'onLoad', null, function() {
            var form = dojo.query('form', dialog.domNode)[0];

            // connect our custom action to the login form submit:
            // redirect if login was ok and show errors in the tooltip if login failed
            dojo.connect(form, 'onsubmit', function(event) {
                // disable default form action
                dojo.stopEvent(event);

                dojo.xhrPost({
                    url:        p4cms.url({
                        module: 'user',
                        action: 'login',
                        format: 'json'
                    }),
                    form:       form,
                    handleAs:   'json',
                    load:       function(response) {
                        // looks like it worked, reload
                        window.location.reload();
                    },
                    error:      function(response, ioArgs) {
                        var text = dojo.fromJson(ioArgs.xhr.responseText);

                        // blur the focused element if it is a child of the dialog
                        if (document.activeElement
                                && dojo.isDescendant(document.activeElement, dialog.domNode)) {
                            dijit.focus(p4cms.user.loginDialog.domNode);
                        }

                        // update tooltip content to display form errors
                        p4cms.user.loginDialog.set('content', text.form);
                    }
                });
            });
        });

        p4cms.user.loginDialog = dialog;
        dialog.startup();
    }

    // wrap link text in span to use for positioning the dialog
    // we do this to be more resilient to theme styling on the link tag
    var span = dojo.query("span", link)[0]
            || dojo.create('span', {innerHTML: link.innerHTML}, link, 'only');

    // attach dialog to this link and display.
    p4cms.user.loginDialog.attachToElement(link, {around: span}, true);
    p4cms.user.loginDialog.openAroundElement(link, {around: span});

    // remove onclick so this code only fires once per link.
    dojo.removeAttr(link, 'onClick');
};
