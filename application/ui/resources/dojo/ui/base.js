dojo.provide('p4cms.ui.base');

dojo.require('p4cms.ui.parser');
dojo.require('p4cms.ui._Widget');
dojo.require('p4cms.ui.Router');
dojo.require('p4cms.ui.Notifications');
dojo.require("p4cms.ui.Form");
dojo.require("p4cms.ui.place");
dojo.require('p4cms.ui.popup');
dojo.require('p4cms.ui.LightBox');
dojo.require("p4cms.ui.SingleClickButton");

// initialize resize handler (will hold event handler
// to connect window resize to editable element refresh).
p4cms.ui.resizeHandler = null;

// instantiate notifications.
dojo.addOnLoad(function() {
    var notify = new p4cms.ui.Notifications();
});

p4cms.ui.show = function(node, params) {
    // if node is already visible, do nothing
    // using String() function since dojo.style(node, 'opacity')
    // returns number instead of string on IE7,8
    if (dojo.style(node, 'display') === 'block' && String(dojo.style(node, 'opacity')) !== '0') {
        return null;
    }

    // combine defaults with passed params.
    var defaults = {node: node};
    params = dojo.mixin(defaults, (params || {}));

    dojo.style(node, 'opacity', '0');
    dojo.style(node, 'display', 'block');
    var anim = dojo.fadeIn(params);
    anim.play();

    return anim;
};

p4cms.ui.hide = function(node, params) {
    // combine defaults with passed params.
    var defaults = {node: node};
    params = dojo.mixin(defaults, (params || {}));

    var anim = dojo.fadeOut(params);
    dojo.connect(anim, 'onEnd', function(){
        dojo.style(node, 'display', 'none');
    });
    anim.play();

    return anim;
};

// simulate a dom event
// @todo remove this in favor of forthcoming dojo.on() in dojo 1.7
p4cms.ui.trigger = function(node, event) {
    var e;
    if (document.createEvent) {
        e = document.createEvent('MouseEvents');
        e.initEvent(event, true, false);
        node.dispatchEvent(e);
    } else if (document.createEventObject) {
        e = document.createEventObject();
        node.fireEvent('on' + event, e);
    }
};

// parse error response message from xhr request when it fails;
// dojo doesn't json decode the response for us on error
p4cms.ui.getXhrErrorMessage = function (args) {
    try {
        return dojo.fromJson(args[1].xhr.responseText).message;
    } catch (err) {
        return null;
    }
};

// helper function for escaping string inserted into HTML element content
p4cms.ui.escapeHtml = function (str) {
    return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
        .replace(/'/g, "&#x27;").replace(/"/g, "&quot;").replace(/\//g, "&#x2F;");
};

// return input string with capitalized first char of all words
p4cms.ui.capitalize = function(str) {
    return String(str).replace(
        /(^|\s)([a-z])/g,
        function(match, p1, p2) {
            return p1 + p2.toUpperCase();
        }
    );
};

// flattens the passed object; by default php array notation is
// used for the resulting keys. If specified the flattenCallback
// will be passed the args: value (scalar), prefix (array),
// output (object) and is responsible for formatting output.
p4cms.ui.flattenObject = function(object, callback) {
    var output = {};
    var key;
    for (key in object) {
        if (object.hasOwnProperty(key)) {
            p4cms.ui._flatten(object[key], key, output, callback);
        }
    }

    return output;
};

// helper function for flattenObject method; don't call directly
p4cms.ui._flatten = function(input, prefix, output, callback) {
    // if flattenObject called us with a non array/hash input
    // just plunk it under prefix (which will be a string)
    if (!dojo.isObject(input) || !input) {
        output[prefix] = input;
        return;
    }

    // normalize prefix to an array
    if (!dojo.isArray(prefix)) {
        prefix = [prefix];
    }

    var key, value;
    for (key in input) {
        if (input.hasOwnProperty(key)) {
            value = input[key];
            if (dojo.isObject(value) && value) {
                p4cms.ui._flatten(value, prefix.concat(key), output, callback);
            } else {
                // use callback to flatten if one was passed otherwise
                // fallback using our default php array notation
                if (callback) {
                    callback(prefix, value, output);
                } else {
                    key         = prefix[0]
                                + (prefix.length > 1 ? '[' + prefix.slice(1).join('][') + ']' : '')
                                + '[' + key + ']';
                    output[key] = value;
                }
            }
        }
    }
};

// Returns whether the passed node exists within any node of the specified position
p4cms.ui.withinPosition = function (node, position) {
    var obj = node;
    do {
        if (dojo.style(obj, 'position') === position) {
            return true;
        }
        obj = obj.offsetParent;
    } while (obj);

    return false;
};

// checks to see if the class string exists, resolves it, makes sure it is callable,
// then returns the resolved class
p4cms.ui.getClass = function(classString) {
    var cls = dojo.getObject(classString);
    return (dojo.isFunction(cls) && cls);
};
