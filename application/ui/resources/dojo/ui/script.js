// summary:
//      extends the dojo.io.script functions to provide error handling when a script to load

dojo.provide("p4cms.ui.script");
dojo.require("dojo.io.script");

p4cms.ui.script = dojo.delegate(dojo.io.script, {
    get: function(args){
        var deferred    = dojo.io.script.get(args),
            node        = dojo.byId(deferred.ioArgs.id);

        // fire the deferred error if script errors out
        if (dojo.isIE < 9) {
            dojo.connect(node,'onreadystatechange', deferred, function(){
                if (/complete|loaded/.test(node.readyState)) {
                    // use a timeout of 60 so that we know we are going to
                    // happen after the ioWatch check
                    // (which is on a 50 millisecond interval)
                    setTimeout(dojo.hitch(this, function() {
                        if (!this.results) {
                            this.errback();
                        }
                    }), 60);
                }
            });
        } else {
            dojo.connect(
                node,
                'onerror',
                deferred, 'errback'
            );
        }

        return deferred;
    }
});