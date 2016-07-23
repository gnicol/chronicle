//  summary:
//      Extends the existing disqus module in order to allow for multiple threads to be used in the
//      tablet theme. Loads the conversations into fullscreen div so that they can be scrolled on mobile browsers
dojo.provide('p4cms.mobile.disqus');

dojo.require('dojo.io.script');

(function() {
    var discussion = {
        threads: null,

        // overridden to support multiple threads
        loadThread: function(threadConfig) {
            // scope the thread to the entry
            var disqusWrappers = dojo.query("#content-entry-" + threadConfig.identifier + " .disqus-conversation-wrapper");
            if (disqusWrappers.length === 0) {
                return;
            }

            // hide the wrapper so it can't be seen on the page
            dojo.style(disqusWrappers[0], 'display', 'none');

            // load the first thread so that the js is ready
            if (!discussion.threads) {
                discussion.threads = {};
                dojo.attr(disqusWrappers[0], 'id', 'disqus_thread');

                window.disqus_shortname     = threadConfig.shortname;
                window.disqus_identifier    = threadConfig.identifier;
                window.disqus_url           = threadConfig.url;

                dojo.io.script.get({
                    url: 'http://' + window.disqus_shortname + '.disqus.com/embed.js',
                    checkString: 'DISQUS'
                });
            }

            // add the thread
            discussion.threads[threadConfig.identifier] = threadConfig;

            // add discussion button to book when the book is ready
            dojo.subscribe('p4cms.mobile.Book.startup', function(book) {
                var ourBook = discussion.getBook(dojo.query("#content-entry-" + threadConfig.identifier)[0]);
                if (!ourBook || book !== dijit.byNode(ourBook)) {
                    return;
                }

                var discussionButton  = dojo.create('span', {
                    'class':    'page-discussion',
                    innerHTML:  'Discussion'
                }, book.pageLinks, 'first');

                book.connect(discussionButton, 'onclick', function() {
                    discussion.showThread();
                });
            });
        },

        getBook: function(entryNode) {
            var books   = new dojo.NodeList();
            books       = books.concat(new dojo.NodeList(entryNode).closest('[dojoType=.mblBook'));
            books       = books.concat(dojo.query('.mblBook', entryNode));

            return books.length !== 1 ? null : books[0];
        },

        // returns the closest entry to the passed searchNode
        getEntry: function(searchNode) {
            var entries = new dojo.NodeList(),
                scope   = searchNode || dojo.body();

            entries     = entries.concat(new dojo.NodeList(scope).closest('[dojoType=p4cms.content.Entry]'));
            entries     = entries.concat(dojo.query('[dojoType=p4cms.content.Entry]', scope));

            return entries.length !== 1 ? null : entries[0];
        },

        // loads the proper discus thread, and opens full-screen
        showThread: function() {
            var entry = dojox.mobile.currentView && discussion.getEntry(dojox.mobile.currentView.domNode);
            if (!entry || !discussion.threads || !window.DISQUS) {
                return;
            }

            var identifier  = dojo.attr(entry, 'contentId'),///^content-entry-(\S*)/.exec(entry.id)[1],
                thread      = discussion.threads[identifier];

            if (!thread) {
                return;
            }

            // create the coverBox if one does not exists
            if (!discussion.coverBox) {
                discussion.createCoverBox();
            }

            // show the coverBox
            discussion.showCoverBox();

            // make sure the dialog is the only thread
            dojo.query('[id=disqus_thread]').forEach(function(node) {
                if (node !== discussion.coverBox.threadNode) {
                    dojo.removeAttr(node, 'id');
                }
            });

            dojo.style(discussion.coverBox._standby, 'opacity', 1);
            dojo.addClass(discussion.coverBox, 'disqus-loading');

            // call reset on the disqus thread
            window.DISQUS.reset({
                reload: true,
                config: function () {
                    // set the current thread
                    this.page.identifier    = thread.identifier;
                    this.page.url           = thread.url;

                    // add the callback if it doesn't already exist
                    if (!discussion.onReady) {
                        discussion.onReady = function() {
                            dojo.style(discussion.coverBox._standby, 'display', 'block');
                            dojo.removeClass(discussion.coverBox, 'disqus-loading');
                            p4cms.ui.hide(discussion.coverBox._standby);
                        };

                        this.callbacks.onReady.push(discussion.onReady);
                    }
                }
            });
        },

        // create a full-screen area for the disqus conversation to be loaded into
        createCoverBox: function() {
            discussion.coverBox = dojo.create('div', {
                'class': 'disqus-coverbox',
                'style': {
                    visibility: 'hidden'
                }
            }, dojo.body());

            discussion.coverBox.threadNode = dojo.create('div', {
                'class':    'disqus-conversation-wrapper',
                'id':       'disqus_thread'
            }, discussion.coverBox);

            discussion.coverBox.header = dojo.create('div', {'class': 'page-header'}, discussion.coverBox);

            discussion.coverBox.closeButton = dojo.create('div', {
                'class':    'page-header-button',
                innerHTML:  'Close',
                title:      'Close'
            }, discussion.coverBox.header);

            discussion.coverBox._standby = dojo.create('div', {
                'class': 'standby-overlay'
            }, discussion.coverBox);

            // wire up the close button
            dojo.connect(discussion.coverBox.closeButton, 'onclick', discussion.hideCoverBox);
        },

        // displays the full screen scrollable area
        showCoverBox: function() {
            // re-enable overflow if it has been disabled
            if (dojo.style(dojo.body(), 'overflowY') === 'hidden') {
                dojo.style(dojo.body().parentNode,  'overflowY', 'auto');
                dojo.style(dojo.body(),             'overflowY', 'auto');
            }

            // prepare the coverBox to be shown
            dojo.style(discussion.coverBox, {visibility: 'visible', display: 'none'});

            // show the coverBox and the standby spinner
            p4cms.ui.show(discussion.coverBox, {duration: 2000});
        },

        // hides the full screen scrollable area
        hideCoverBox: function() {
            p4cms.ui.hide(discussion.coverBox, {duration: 1000});

            // remove our overflow changes
            dojo.style(dojo.body().parentNode,  'overflowY', '');
            dojo.style(dojo.body(),             'overflowY', '');
        }
    };

    p4cms.mobile.disqus = discussion;
}());