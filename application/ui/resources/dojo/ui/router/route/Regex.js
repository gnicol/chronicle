dojo.provide('p4cms.ui.router.route.Regex');

dojo.require("dojox.string.sprintf");

dojo.declare('p4cms.ui.router.route.Regex', null, {
    _defaults:   {},
    _map:        {},
    _reverse:    '',

    constructor: function(params)
    {
        this._defaults  = params.defaults || {};
        this._map       = params.map      || {};
        this._reverse   = params.reverse  || '';
    },

    /**
     * Assemble follows the guide of the Zend_Controller_Router_Route_Regex.
     *
     * We expect _map to contain a hash of
     *  fieldName  => '1',
     *  fieldName2 => '2',
     *  etc...
     *
     * We will loop over map and assemble a 'values' array.
     * For a given field, if the passed data array contains an entry we will
     * use it. We fall back to the defaults array and lastly an empty string.
     *
     * The dojo sprintf class is then handled the '_reverse' string setup on
     * this route and the assembled values array to provide a url.
     *
     * @return  string  The resultant url
     */
    assemble: function(data) {
        var field, mapFields = [],
            values = [],
            value  = '';
        for (field in this._map) {
            if (this._map.hasOwnProperty(field)) {
                // create an array copy of the fields for later use with query params
                mapFields.push(field);

                // we do a fairly explicit undefined check to allow
                // null or empty data values to over-ride set defaults.
                if (data[field] !== undefined) {
                    value = data[field];
                } else if (this._defaults[field] !== undefined) {
                    value = this._defaults[field];
                } else {
                    value = '';
                }

                values[this._map[field] - 1] = value;
            }
        }

        var formatter = new dojox.string.sprintf.Formatter(this._reverse);

        // the format function expects seperate arguments for values
        // by callying 'apply' we can instead pass an arguments array
        var url = formatter.format.apply(formatter, values);

        // move any values not covered by the map into the query string
        var queryParams = {};
        for (field in data) {
            if (data.hasOwnProperty(field)) {
                // skip any field that is listed in map; we have already handled it
                if (dojo.indexOf(mapFields, field) !== -1) {
                    continue;
                }

                queryParams[field] = data[field];
            }
        }

        // use flatten query params to translate arrays into
        // php notation (using trailing square brackets)
        queryParams = p4cms.ui.flattenObject(queryParams);

        var queryString = dojo.objectToQuery(queryParams);
        if (queryString) {
            url += '?' + queryString;
        }

        return url;
    }
});
