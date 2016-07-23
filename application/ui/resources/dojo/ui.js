// summary:
//      Support for ui module.

dojo.provide('p4cms.ui');
dojo.require('p4cms.ui.base');
dojo.require('p4cms.ui.body');
dojo.require('p4cms.ui.toolbar.Toolbar');
dojo.require('p4cms.ui.EditableElement');
dojo.require('p4cms.ui.Tooltip');
dojo.require('p4cms.ui.TooltipDialog');
dojo.require('p4cms.ui.ConfirmTooltip');
dojo.require('p4cms.ui.Dialog');
dojo.require('p4cms.ui.ConfirmDialog');
dojo.require("p4cms.ui.DateTextBox");
dojo.require('p4cms.ui.ScrollingTabController');
dojo.require("dojo.date.locale");

// edit mode contains an array keyed on 'group' with boolean on/off values
// prime as empty array.
p4cms.ui.inEditMode = [];

p4cms.ui._initializeEditGroup = function(group) {
    // bail if no group given or group is already known
    if (!group || p4cms.ui.inEditMode[group] !== undefined) {
        return;
    }

    // initialize edit group state
    p4cms.ui.inEditMode[group] = false;

    // hook up edit mode toggles if this if our first encounter
    dojo.subscribe(
        "p4cms." + group + ".enableEditMode",
        function () {p4cms.ui.inEditMode[group] = true;}
    );
    dojo.subscribe(
        "p4cms." + group + ".disableEditMode",
        function () {p4cms.ui.inEditMode[group] = false;}
    );
};

p4cms.ui.enableEditGroup = function(group) {
    // bail if no group given
    if (!group) {
        return;
    }

    p4cms.ui._initializeEditGroup(group);
    p4cms.ui.inEditMode[group] = true;

    dojo.publish("p4cms." + group + ".enableEditMode");
};

p4cms.ui.disableEditGroup = function(group) {
    // bail if no group given
    if (!group) {
        return;
    }

    p4cms.ui._initializeEditGroup(group);
    p4cms.ui.inEditMode[group] = false;

    dojo.publish("p4cms." + group + ".disableEditMode");
};


// function to toggle edit mode for a passed group.
p4cms.ui.toggleEditGroup = function(group) {
    // bail if no group given
    if (!group) {
        return;
    }

    // look at current mode and publish apposing message
    if (!p4cms.ui.inEditMode[group]){
        p4cms.ui.enableEditGroup(group);
    } else {
        p4cms.ui.disableEditGroup(group);
    }
};

p4cms.ui.isInEditMode = function(group) {
    p4cms.ui._initializeEditGroup(group);
    return p4cms.ui.inEditMode[group];
};

p4cms.ui.resizeHandler = dojo.connect(window, "onresize",
    function()
    {
        dojo.publish("p4cms.ui.refreshEditMode");
    }
);

p4cms.ui.durationAgo = function(seconds) {
    var time, i;

    // entry 1 is max age in seconds that rule applies to
    // entry 2 is divisor to apply to value
    // entry 3 is text to append to divided value; text
    //         alone is used when entry 2 is 0
    var times = [
        [60,        0,          'just now'],
        [120,       0,          '1 minute ago'],
        [3600,      60,         'minutes ago'],
        [7200,      0,          '1 hour ago'],
        [86400,     3600,       'hours ago'],
        [172800,    0,          'yesterday'],
        [604800,    86400,      'days ago'],
        [1209600,   0,          'last week'],
        [2419200,   604800,     'weeks ago'],
        [4838400,   0,          'last month'],
        [29030400,  2419200,    'months ago'],
        [31535999,  0,          '12 months ago'],
        [34689600,  0,          'year ago']
    ];

    for (i = 0; i < times.length; i++) {
        time = times[i];
        if (seconds < time[0]) {
            if (time[1]) {
                return Math.floor(seconds / time[1]) + " " + time[2];
            }
            return time[2];
        }
    }

    return (seconds/365.0/24.0/60.0/60.0).toFixed(1) + " years ago";
};

// accepts a javascript Date object or 'unix time' in seconds
p4cms.ui.timeAgo = function(date, fallbackFormat) {
    // provide a default for fallbackFormat; for format rules see
    // http://dojotoolkit.org/reference-guide/dojo/date/locale/format.html
    fallbackFormat = fallbackFormat || 'MMMM d, yyyy h:mm a';

    // normalize date input to Date object
    if (!dojo.isObject(date)) {
        // if we have an int value its a unix time; convert to milliseconds
        if (date.toString().match(/^[0-9]+$/)) {
            date = parseInt(date, 10);
            date = date * 1000;
        }

        date = new Date(date);
    }

    var seconds = Math.round((new Date().getTime() - date.getTime()) / 1000);
    seconds     = seconds - p4cms.ui.serverTimeOffset;

    // if entry is under 336 days old show in 'duration' format
    if (seconds < 29030400) {
        return p4cms.ui.durationAgo(seconds);
    }

    // if we made it here; return a formatted
    // date as entry is older than 336 days
    return dojo.date.locale.format(
        date,
        {
            selector: "date",
            datePattern: fallbackFormat
        }
    );
};

// Utility method to toggle checked state of child checkboxes
// assuming parent-child are defined using li-ul structure
// Used for Content_Form_Element_TypeGroup.
p4cms.ui.toggleChildCheckboxes = function(checkbox) {
    var li  = new dojo.NodeList(checkbox).closest('li'),
        // Some browsers move the ul within the li, account for differences
        ul  = li.next('ul')[0] || dojo.query('ul', li[0])[0];

    if (!ul) {
        return;
    }

    dojo.query('input', ul).attr('checked', dojo.attr(checkbox, 'checked'));
};

// Utility method to toggle checked state of parent checkbox
// assuming parent-child are defined using li-ul structure
p4cms.ui.toggleParentCheckbox = function(checkbox) {
    var ul      = new dojo.NodeList(checkbox).closest('ul'),
        inputs  = dojo.query('input', ul[0]),
        // Some browsers move the ul within the li, account for differences
        li      = ul.prev('li')[0] || ul.closest('li')[0],
        group   = dojo.query('input', li)[0];

    dojo.attr(group, 'checked',
        !inputs.some('return !dojo.attr(item, \'checked\')'));
};

dojo.subscribe('p4cms.ui.toolbar.ignoreFilters.populate', function(menu, event, filters) {
    filters.push('#p4cms-ui-notices');
});

// takes a root element, and optionally a parent scope,
// and finds the root's immediate tabable siblings
//
// this method is very similar to dijit's getTabNavigable method,
// but dijit._getTabNavigable will only give you the first and the last
// tabable elements under a node, we sometimes need the siblings to determine
// where to go next
p4cms.ui.getSiblingTabNavigable = function(root, scope) {
    var next, previous, closestLowIndex, closestHighIndex, afterRoot,
        rootIndex   = dojo.attr(root, "tabIndex"),
        shown       = dijit._isElementShown;

    var walkTree = function(parent) {
        dojo.query("> *", parent).forEach(function(child) {
            // if the child is not part of the HTML doc, or is not shown, break out
            if ((dojo.isIE && child.scopeName!=="HTML") || !shown(child)) {
                return;
            }

            // if the child is the root, we are now done with the previous tabbable
            // element and can move on to the next tabbable element
            if (child === root) {
                afterRoot = true;
            } else {
                if (dijit.isTabNavigable(child)) {
                    var tabindex = dojo.attr(child, "tabIndex");

                    // use dom order if the child has no tabIndex, has a tabIndex of 0,
                    // or shares the same tabIndex as our root
                    // otherwise, if the child's tabIndex is greater than 0, we will use
                    // its tabindex to determine its order
                    if (!dojo.hasAttr(child, "tabIndex") || tabindex === 0 || tabindex === rootIndex) {
                        if (!afterRoot) {
                            previous = child;
                        } else if (!next) {
                            next = child;
                        }
                    } else if (rootIndex && tabindex > 0) {
                        if (tabindex < rootIndex && tabindex > closestLowIndex) {
                            closestLowIndex = tabindex;
                            previous = child;
                        } else if (tabindex > rootIndex && tabindex < closestHighIndex) {
                            closestHighIndex = tabindex;
                            next = child;
                        }
                    }
                }
            }

            // walk down the tree unless we hit a select, in which case
            // we don't want to focus any of its children
            if (child.nodeName.toUpperCase() !== 'SELECT') {
                walkTree(child);
            }
        });
    };

    // if parent is shown and exists, walk the tree down from the parent
    if (shown(scope || root.parentNode)) {
        walkTree(scope || root.parentNode);
    }

    return {previous: previous, next: next};
};

// pull the dojox.lang.functional.keys() code to reduce the js foot print
// modified to satisfy JSLine, also check if Object.keys exists and use it if so.
p4cms.ui.keys = function(/*Object*/ obj){
    // summary: returns an array of all keys in the object
    // if Object.keys is supported, use it
    if (Object.keys) {
        return Object.keys(obj);
    }

    // if Object.keys is not supported
    var t = [], i;
    for(i in obj){
        if(obj.hasOwnProperty(i)){
            t.push(i);
        }
    }
    return	t; // Array
};