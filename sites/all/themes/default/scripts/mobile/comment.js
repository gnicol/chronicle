dojo.provide('p4cms.mobile.comment');

if (p4cms.ui.getClass('p4cms.comment.vote')) {
    dojo.require('p4cms.comment.base');

    (function(){
        var comments = {
            // returns the closest entry to the passed searchNode
            getEntry: function(searchNode) {
                var entries = new dojo.NodeList(),
                    scope   = searchNode || dojo.body();

                entries     = entries.concat(new dojo.NodeList(scope).closest('[dojoType=p4cms.content.Entry]'));
                entries     = entries.concat(dojo.query('[dojoType=p4cms.content.Entry]', scope));

                return entries.length !== 1 ? null : entries[0];
            },

            getBook: function(entryNode) {
                var books   = new dojo.NodeList();
                books       = books.concat(new dojo.NodeList(entryNode).closest('[dojoType=.mblBook'));
                books       = books.concat(dojo.query('.mblBook', entryNode));

                return books.length !== 1 ? null : books[0];
            },

            // opens comments in a lightbox
            showComments: function(node) {
                var entry = comments.getEntry(node);
                if (!entry) {
                    return;
                }

                var commentThread = dojo.query('.comments', entry);

                if (commentThread.length === 0) {
                    return;
                }

                commentThread = commentThread[0];

                var oldParent = commentThread.parentNode;

                // create the lightbox if one does not exists
                // else open the current one
                if (!comments.lightBox) {
                    comments.createLightBox();
                    comments.lightBox.connect(comments.lightBox, 'close', function() {
                        dojo.place(commentThread, oldParent);
                    });
                    dojo.place(commentThread, comments.lightBox.commentsNode, 'only');
                    comments.lightBox._sizeFrame();
                } else {
                    dojo.place(commentThread, comments.lightBox.commentsNode, 'only');
                    comments.lightBox.open();
                }
            },

            createLightBox: function() {
                comments.lightBox = new p4cms.ui.LightBox();

                // patch the lightbox to use include a comment wrapper
                dojo.safeMixin(comments.lightBox, {
                    // overridden to create our comments div
                    _makeFrame: function() {
                        this.frame = dojo.create('div', {
                            'class': 'lightbox-frame comments-lightbox',
                            'style': {
                                visibility: 'hidden',
                                position:   'fixed',
                                zIndex:     1001
                            }
                        }, dojo.body());

                        this.commentsNode = dojo.create('div', {
                            'class':    'comments-wrapper'
                        }, this.frame);
                        this.loaded = true;
                    },

                    // extended to reset the height attribute to auto after being positioned
                    // so the lightbox can grow with the contents
                    _sizeFrame: function() {
                        this.inherited(arguments);

                        var viewport  = dojo.window.getBox(),
                            maxHeight = (viewport.h * 0.9) - dojo._getPadExtents(this.commentsNode).h;

                        dojo.style(this.frame, {
                            height:     'auto'
                        });
                        dojo.style(this.commentsNode, {
                            height:                     'auto',
                            maxHeight:                  maxHeight + 'px',
                            overflowY:                  'auto'
                        });
                    },

                    // overridden to return the content size of the frame
                    _getContentSize: function() {
                        if (dojo.style(this.frame, 'display') === 'none') {
                            dojo.style(this.frame, {visibility: 'hidden', display: 'block'});
                        }

                        return dojo.contentBox(this.frame);
                    }
                });
                comments.lightBox.startup();

                // resize pages on window resize
                comments.lightBox.resizeListener = comments.lightBox.connect(window, 'onresize', function() {
                    window.clearTimeout(this.resizeHandler);
                    this.resizeHandler = window.setTimeout(dojo.hitch(this, '_sizeFrame', 500));
                });
            }
        };

        p4cms.mobile.comment = comments;

        dojo.subscribe('p4cms.mobile.Book.startup', function(book) {
            var entry = comments.getEntry(book.domNode);
            if (entry && dojo.query('.comments', entry).length !== 0) {
                var count           = dojo.query('.comments .comment-count', entry)[0].innerHTML;
                count               = (count === '(0)' ? '' : ' ' + count);
                var commentButton   = dojo.create('span', {
                    'class':    'page-comments',
                    innerHTML:  'Comments' + count
                }, book.pageLinks, 'first');

                book.connect(commentButton, 'onclick', function() {
                    comments.showComments(book.domNode);
                });
            }
        });
    }());
}