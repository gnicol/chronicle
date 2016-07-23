// summary:
//      Support for url module.

dojo.provide("p4cms.url");

dojo.subscribe('p4cms.content.form.ready', function(form, entry){
    var title  = dojo.query('input[name=title]',                     form)[0];
    var label  = dojo.query('dt[id*="url-path-label"] label',        form)[0];
    var path   = dojo.query('input[name*="url[path]"]',              form)[0];
    var auto   = dojo.query('input[name*="url[auto]"][value=true]',  form)[0];

    if (!title || !path || !auto) {
        return;
    }

    // replace the path label with the current host and base url
    var host = window.location.href.match(/^[a-z]+:\/\/[^\/]+\//i);
    dojo.style(label, 'display', 'none');
    dojo.create('span', {innerHTML: host + p4cms.baseUrl}, label, 'after');

    // update path when title changes.
    dojo.connect(title, 'onkeyup', function(){
        if (!dojo.attr(auto, 'checked')) {
            return;
        }

        path.value = p4cms.url.autoGenerate(entry);
    });
});

p4cms.url.autoGenerate = function(entry) {
    var title = !dojo.isString(entry)
        ? entry.getElement('title').getFormValue()
        : entry;

    if (!title) {
        return '';
    }

    return title.replace(/[^a-z0-9\.]+/ig, '-')
                .replace(/^[\-\.]+|[\-\.]+$/g, "")
                .toLowerCase();
};

// when a file upload occurs (via dnd) assign a url based on the title.
dojo.subscribe("p4cms.content.dnd.upload.data", function(file, data) {
    data['url[auto]'] = 'true';
    data['url[path]'] = p4cms.url.autoGenerate(data.title);
});