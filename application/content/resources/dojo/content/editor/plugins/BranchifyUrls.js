// summary
//       When setting the value of the editor, this plugin injects
//       the current branch base url into images to ensure that they
//       reference the correct branch. When getting the value out of
//       the editor, this plugin strips branch base urls from links
//       and images.

dojo.provide("p4cms.content.editor.plugins.BranchifyUrls");
dojo.require("dijit._editor._Plugin");

dojo.declare("p4cms.content.editor.plugins.BranchifyUrls", dijit._editor._Plugin, {
	setEditor: function(editor) {
        // qualify img tags with the branch base url so that they
        // work in the editor (we'll remove the branch on the way out)
        editor.contentPreFilters.push(dojo.hitch(this, function(html) {
            return this._filter(html, ['img'], ['src']);
        }));

        // strip branch base urls from img and a tags.
		editor.contentPostFilters.push(dojo.hitch(this, function(html) {
            return this._filter(html, null, null, true);
        }));
	},

    // Modifies urls in certain html tags to insert the current branch
    // base url. This is needed in some cases to ensure that resources
    // come from the correct branch.
    //
    // This is effectively a port of the PHP file:
    // /library/P4Cms/Filter/BranchifyUrls.php
    _filter: function(value, tags, attributes, strip) {
        tags       = tags       || ['img', 'a'];
        attributes = attributes || ['src', 'href'];
        strip      = strip      || false;

        // if tags or attributes are empty, nothing to do.
        if (!tags.length || !attributes.length) {
            return value;
        }

        tags           = tags.join('|');
        attributes     = attributes.join('|');
        var baseUrl    = p4cms.baseUrl || '';
        var branchBase = p4cms.branchBaseUrl || '';

        return value.replace(
            new RegExp('<(' + tags + ')(\\s+[^>]*)(' + attributes + ')=([\'"]?)([^>]+)>', 'i'),
            function(match, tag, middle, attribute, quote, link)
            {
                // we only munge urls that are absolute with respect
                // to the current domain and start with our base-url.
                if (link.charAt(0) !== '/' || (baseUrl && link.indexOf(baseUrl) !== 0)) {
                    return match;
                }

                // strip off the base url and any leading branch specifier
                link = link.substr(baseUrl.length);
                link = link.charAt(1) === '-'
                    ? link.replace(/^\/-[^\/]+-/, '')
                    : link;

                // if not stripping, prepend the link with the active branch base url.
                link = strip
                    ? baseUrl    + link
                    : branchBase + link;

                return '<' + tag + middle + attribute + '=' + quote + link + '>';
            },
            value
        );
    }
});

// Register this plugin.
dojo.subscribe(dijit._scopeName + ".Editor.getPlugin", null, function(o) {
    if (o.plugin) {
        return;
    }
    var name = o.args.name.toLowerCase();
    if (name === "branchifyurls") {
        o.plugin = new p4cms.content.editor.plugins.BranchifyUrls();
    }
});