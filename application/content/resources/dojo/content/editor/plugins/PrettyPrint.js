// summary:
//      Extend prettyprint to workaround an IE7 bug where setting innerHTML would cause url
//      in images and links to become absolute. This caused issues with our branches, and would
//      cause problems if you ever changed the address to your site.

dojo.provide('p4cms.content.editor.plugins.PrettyPrint');

// Register this plugin before the prettyprint plugin is registered
// so that this one takes over the default
dojo.subscribe(dijit._scopeName + ".Editor.getPlugin", null, function(o) {
    var name = o.args.name.toLowerCase();
    if (name === "prettyprint") {
        o.plugin = new p4cms.content.editor.plugins.PrettyPrint({
            indentBy:   o.args.hasOwnProperty("indentBy") ? o.args.indentBy : -1,
            lineLength: o.args.hasOwnProperty("lineLength") ? o.args.lineLength : -1,
            entityMap:  o.args.hasOwnProperty("entityMap") ? o.args.entityMap : dojox.html.entities.html.concat([
                ["\u00A2", "cent"], ["\u00A3", "pound"], ["\u20AC", "euro"],
                ["\u00A5", "yen"], ["\u00A9", "copy"], ["\u00A7", "sect"],
                ["\u2026", "hellip"], ["\u00AE", "reg"]
            ]),
            xhtml:      o.args.hasOwnProperty("xhtml") ? o.args.xhtml : false
        });
    }
});

dojo.require('dojox.editor.plugins.PrettyPrint');

dojo.declare("p4cms.content.editor.plugins.PrettyPrint", dojox.editor.plugins.PrettyPrint, {
    setEditor: function(editor){
        this.inherited(arguments);

        // patch editor.getValue to filter out absolute paths
        this.editor.onLoadDeferred.addCallback(dojo.hitch(this, function() {
            var oldGetValue = this.editor.getValue;
            this.editor.getValue = dojo.hitch(this, function() {
                var value = oldGetValue(arguments);
                return this.stripAbsoluteUrls(value);
            });
        }));
    },

    stripAbsoluteUrls: function(value) {
        var tags       = 'img|a',
            attributes = 'src|href';

        // only strip absolute urls that point to the current origin
        var baseUrl = window.location.protocol + '//' + window.location.host;

        return value.replace(
            new RegExp('<(' + tags + ')(\\s+[^>]*)(' + attributes + ')=([\'"]?)([^>]+)>', 'i'),
            function(match, tag, middle, attribute, quote, link)
            {
                if (link.indexOf(baseUrl) !== 0) {
                    return match;
                }

                return '<' + tag + middle + attribute + '=' + quote + link.substr(baseUrl.length) + '>';
            },
            value
        );
    }
});