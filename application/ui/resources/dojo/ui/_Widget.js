// summary:
//      Patched dijit._Widget to include upstream touch event handling which fixes
//      a problem with closing dialogs on touch devices.
//      Dojo bug can be found at: http://bugs.dojotoolkit.org/ticket/13488
//      @todo remove this file when dojo is upgraded to/past dojo 1.8

dojo.provide('p4cms.ui._Widget');
dojo.require('dijit._Widget');

dijit._Widget.extend({
    connect: function(obj, event, method) {
        var d = dojo,
            dc = d._connect,
            handles = this.inherited(arguments, [obj, event === "ondijitclick" ? "onclick" : event, method]);

        if (event === "ondijitclick") {
            if (d.indexOf(this.nodesWithKeyClick, obj.nodeName.toLowerCase()) === -1) { // is NOT input or button
                var m = d.hitch(this, method);
                handles.push(
                    dc(obj, "onkeydown", this, function(e) {
                        if ((e.keyCode === d.keys.ENTER || e.keyCode === d.keys.SPACE) &&
                            !e.ctrlKey && !e.shiftKey && !e.altKey && !e.metaKey) {
                            dijit._lastKeyDownNode = e.target;

                            if (!(this.hasOwnProperty("openDropDown") && obj === this._buttonNode)) {
                                e.preventDefault();
                            }
                        }
                    }),
                    dc(obj, "onkeyup", this, function(e) {
                        if ( (e.keyCode === d.keys.ENTER || e.keyCode === d.keys.SPACE) &&
                            e.target === dijit._lastKeyDownNode &&
                            !e.ctrlKey && !e.shiftKey && !e.altKey && !e.metaKey) {
                                dijit._lastKeyDownNode = null;
                                return m(e);
                        }
                    })
                );

                if (dojox.mobile && dojox.mobile.hasTouch) {
                    // touchstart-->touchend will automatically generate a click event, but there are problems
                    // on iOS after focus has been programatically shifted (#14604, #14918), so setup a failsafe
                    // if click doesn't fire naturally.
                    var clickTimer;
                    handles.push(
                        dc(obj, "touchend", function(e) {
                            var target = e.target;
                            clickTimer = setTimeout(function() {
                                clickTimer = null;
                                m(e);
                            }, 600);
                        }),
                        dc(obj, "click", function(e) {
                            // If browser generates a click naturally, clear the timer to fire a synthetic click event
                            if (clickTimer) {
                                clearTimeout(clickTimer);
                            }
                        })
                    );
                }
            }
        }

        return handles;
    }
});