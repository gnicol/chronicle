dojo.provide('p4cms.ui.Router');
dojo.require('p4cms.ui.base');
dojo.require('p4cms.ui.router.route.Module');
dojo.require('p4cms.ui.router.route.Regex');

/**
 * A global convienence function, this helper will utilize
 * p4cms.ui.Router to craft the specified url. Usage is
 * akin to the Zend URL helper data should likely contain:
 *  string  module      - optional defaults to 'index'
 *  string  controller  - optional defaults to 'index'
 *  string  action      - optional defaults to 'index'
 *
 * Addittionally, you can pass arbitrary key/string value
 * pairs which will be encoded as arguments.
 *
 * @param   data    hash    The data to pass to the route
 * @param   name    string  Optional - the route to use
 * @return  string          The url
 */
p4cms.url = function(data, name, baseUrl) {
    return p4cms.ui.Router.assemble(data, name, baseUrl);
};

/**
 * A global convienence function to construct and open a url.
 *
 * @param   data    string|object   A string url or data to pass to the route
 * @param   name    string          Optional - the route to use
 * @param   target  string          Target (e.g. '_blank' to open in a new window)
 */
p4cms.openUrl = function(data, name, target) {
    var url = dojo.isString(data)
        ? data
        : p4cms.url(data, name);

    // if we want to open in a new/specific window we utilize
    // window.open and the appropriate target
    if (target) {
        window.open(url, target);
    } else {
        // if opening in the current tab just set the location
        window.location.href = url;
    }
};

// The router class is setup to be 'static'. Call the methods
// directly without new'ing the class.
p4cms.ui.Router = {
    _routes: {},

    /**
     * Adds the requested array of routes. Any existing routes that
     * share the same name will be replaced/clobbered.
     *
     * The passed routes object is a hash in the format:
     * {
     *      routeName: {
     *          type:   'class.name.of.Type',
     *          key:    value,
     *          key2:   value2,
     *          etc...
     *      },
     *      routeName2...
     * }
     *
     * As illustrated an arbitrary set of key/value pairs can be included
     * and will be passed to the specified type's constructor.
     *
     * @param   routes  hash        The array of routes
     * @return  p4cms.ui.Router     To maintain a fluent interface
     */
    addRoutes: function(routes) {
        var name;
        for (name in routes) {
            if (routes.hasOwnProperty(name)) {
                p4cms.ui.Router.addRoute(name, routes[name]);
            }
        }

        return p4cms.ui.Router;
    },

    /**
     * Adds a single route. See addRoutes for more details.
     *
     * The basic format of route is:
     * {
     *     type:   'class.name.of.Type',
     *     key:    value,
     *     key2:   value2,
     *     etc...
     * }
     *
     * If name is already in use the existing entry will be replaced.
     * The name 'default' is special and will be used when no name or
     * an invalid name is specified to assemble.
     *
     * @param   name    string      The route name
     * @param   route   hash        The route details
     * @return  p4cms.ui.Router     To maintain a fluent interface
     */
    addRoute: function(name, route) {
        // require in the specified type
        // this also screens the value to some extent
        dojo.require(route.type);

        // new the requested type and pass in the
        // route params to its constructor
        var RouteType = dojo.getObject(route.type);
        p4cms.ui.Router._routes[name] = new RouteType(route);

        return p4cms.ui.Router;
    },

    /**
     * This function will return the URL that represents the passed data.
     *
     * If no route name is specified, or an invalid value is passed, the
     * route 'default' will be used. If no 'default' route exists a Module
     * route will be added with that name.
     *
     * @param   data    hash    The params to pass to the route
     * @param   name    string  Optional - the name of the route to utilize
     * @return  string          The url representing the requested data
     */
    assemble: function(data, name, baseUrl) {
        // if the requested route is not available, or specified, go default
        if (!name || !p4cms.ui.Router._routes[name]) {
            name = 'default';

            // create the default route if needed
            if (!p4cms.ui.Router._routes[name]) {
                p4cms.ui.Router._routes[name] = new p4cms.ui.router.route.Module();
            }
        }

        var url  = p4cms.ui.Router._routes[name].assemble(data);
        var base = baseUrl || p4cms.branchBaseUrl;

        // strip leading slashes on url and trailing slashes on
        // the base url to get into a known state
        url  = url.replace(/^\/+/, "");
        base = base.replace(/\/+$/, "");

        return base + '/' + url;
    }
};