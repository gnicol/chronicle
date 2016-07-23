dojo.provide('p4cms.ui.router.route.Module');

dojo.declare('p4cms.ui.router.route.Module', null, {
    _defaults:   {},

    constructor: function(params) {
        if (typeof params === 'undefined') {
            params = {};
        }
        this._defaults = params.defaults || {};
    },

    assemble: function(data) {
        if (typeof data === 'undefined' || data === null) {
            data = {};
        }

        data = dojo.mixin(dojo.clone(this._defaults), data);

        var url = '';

        var module = data.module || 'index';
        delete data.module;

        var controller = data.controller || 'index';
        delete data.controller;

        var action = data.action || 'index';
        delete data.action;

        url = this._encodeParams(data);

        url = '/' + encodeURIComponent(action) + url;
        url = '/' + encodeURIComponent(controller) + url;
        url = '/' + encodeURIComponent(module) + url;

        // if the module/controller/action are index and no params are set,
        // go with a single '/' to make it pretty.
        url = url.replace(/^\/*index\/index\/index\/*$/,"");

        return url;
    },

    _encodeParams: function(params) {
        var key, url = '', queryParams = {};

        // use flatten params to translate arrays into
        // php notation (using trailing square brackets)
        params = p4cms.ui.flattenObject(params);

        // loop over all properties and either encode them
        // into the url or, if they contain / or [, place
        // them into the query params
        for (key in params) {
            if (params.hasOwnProperty(key)) {
                var value = params[key];

                // ensure key/value are strings for later checks
                key   = key.toString();
                value = value.toString();

                // if the param contains a slash '/' in key or value move it to the query string.
                // this is a work-around for apache whereby it chokes on %2f in path info.
                // also, if the key contains a '[' it is likely an array and should be moved.
                if (key.indexOf('/') !== -1 || value.indexOf('/') !== -1 || key.indexOf('[') !== -1) {
                    queryParams[key] = value;
                    continue;
                }

                key   = encodeURIComponent(key);
                value = encodeURIComponent(value);
                url  += '/' + key;
                url  += '/' + value;
            }
        }

        // tack on any query params we have collected above
        var queryString = dojo.objectToQuery(queryParams);
        if (queryString) {
            url += '?' + queryString;
        }

        return url;
    }
});