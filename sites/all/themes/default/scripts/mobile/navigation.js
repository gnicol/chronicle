dojo.provide('p4cms.mobile.navigation');

dojo.require("dojox.encoding.digests.MD5");
dojo.require("dojox.NodeList.delegate");
dojo.require("dojo.NodeList-traverse");
dojo.require("p4cms.mobile.View");

// we try and hijack (XHR slide in) any internal links in a mblView that
// don't have onclick logic unless its included in the blacklist below
p4cms.mobile.navigation.blacklist = [
    new RegExp('^(/user)?(/index)?/logout'),
    new RegExp('^(/content)?(/index)?/image/'),
    new RegExp('^(/content)?(/index)?/download/'),
    new RegExp('[?&]action=(download|image)')
];

// make the site act as a single page app and track history manually
dojo.addOnLoad(function() {
    var initialUrl          = location.href,
        initialPath         = (new dojo._Url(initialUrl)).path,
        firstPop            = true;

    // returns an id created from the path
    p4cms.mobile.getIdForPath = function(path) {
        // trim trailing / characters from the path before calculating ids
        path = path.replace(/\/$/,'');
        return ('page-' + dojox.encoding.digests.MD5(path, dojox.encoding.digests.outputTypes.Hex));
    };

    // create a view to be used as a placeholder while loading other views
    var loadingView  = new dojox.mobile.View(
        {},
        dojo.create('div', {id: 'loading-view'}, dojo.body())
    );

    // mark the page we entered on so we know it has been loaded already
    dojo.query('.mblBook').forEach(function(bookNode) {
        if (dojo.attr(bookNode, 'page-id')) {
            return;
        }

        var pageId = p4cms.mobile.getIdForPath(initialPath);
        dojo.attr(bookNode, 'page-id', pageId);
    });

    // when back/forward operations occur use load page
    dojo.connect(window, 'onpopstate', function(e) {
        // some browsers fire a pop when the page first
        // loads which we want to ignore.
        if (location.href === initialUrl && firstPop) {
            firstPop = false;
            return;
        }

        firstPop = false;
        p4cms.mobile.loadUrl(e.state ? e.state.url : null, e);
    });

    // when vertical navigation occur, move in the history stack
    dojo.connect(p4cms.mobile.bookstack, '_setCurrentBook', function(book) {
        // if we can't pushState we have nothing to do here
        if (!window.history.pushState) {
            return;
        }

        var url     = initialPath,
            pane    = new dojo.NodeList(book.domNode).closest('.book-wrapper-pane');

        if (pane.length) {
            url = dijit.byNode(pane[0]).href.replace(/[?&]format=partial$/, '');
        }

        // if the current history url isn't already our page, push state
        var stateUrl = (window.history.state && window.history.state.url) || initialPath;
        if (stateUrl !== url) {
            window.history.pushState({url:url}, "" , url);
        }
    });

    p4cms.mobile.loadUrl = function(url, e) {
        url            = url || p4cms.url();
        var view       = dojox.mobile.currentView;
        var id         = p4cms.mobile.getIdForPath(url);
        var coverId    = p4cms.mobile.getIdForPath(p4cms.url());
        var dir        = id === coverId ? -1 : 1;
        var transition = 'slidev';

        // if the page is already loaded, jump right to it.
        var pageNode = dojo.query('[page-id=' + id + '] .mblView')[0];
        if (pageNode) {
            var page            = dijit.byNode(pageNode),
                bookIndex       = p4cms.mobile.bookstack.current >= 0 ? p4cms.mobile.bookstack.current : 0,
                relativeIndex   = dojo.indexOf(p4cms.mobile.bookstack, page.getBook());
            relativeIndex       = relativeIndex >= 0 ? relativeIndex - bookIndex : 1;

            // do nothing if we are already where we need to be
            if (relativeIndex === 0) {
                return;
            }

            // set transition direction based on the position in the bookstack
            dir = relativeIndex > 0 ? 1 : -1;

            // if we are dealing with a popstate and the page isn't in a bookstack, we
            // have to fade to the new page as we don't know which way to slide it in
            if ((e && e.type === 'popstate') && dojo.indexOf(p4cms.mobile.bookstack, page.getBook()) < 0) {
                p4cms.mobile.bookstack.clear();
                dir        = 1;
                transition = 'fade';
            }

            return view.performTransition(
                page.getBook().getCurrentPage().domNode,
                dir,
                transition
            );
        }

        // push the new page onto the history stack, it could also  be caught by
        // setCurrentBook after the book we are navigating to loads, but
        // doing it early allows the user's back button to take them to this page
        // in case loading the new page completely fails
        if (window.history.pushState && (!e || e.type !== 'popstate')) {
            window.history.pushState({url: url}, "", url);
        }

        // tack on the format=partial query param
        url += (url.indexOf('?') >= 0 ? '&' : '?') + 'format=partial';

        // if this is a history navigation (to anywhere other than a cover)
        // and we don't already have a page we have to fade to the new page
        // as we don't know which way to slide it in
        if (e && e.type === 'popstate' && id !== coverId) {
            p4cms.mobile.bookstack.clear();
            dir        = 1;
            transition = 'fade';
        }

        view.performTransition('loading-view', dir, transition, this, function() {
            // the loading view is not in a book, and is not a Page, so it's transitions don't
            // update the bookstack. We manually remove this class so that the books footer
            // is not displayed while the new book is being loaded
            dojo.removeClass(view.getBook().domNode, 'p4cms-current-book');

            var pane = new dojox.layout.ContentPane({
                'class':        'book-wrapper-pane',
                href:           url,
                preload:        true,
                cleanContent:   true,
                _onError:       function (type, error, consoleText) {
                    // setContent refreshes when it receives a null message, ensure a string is passed.
                    var message = error.responseText || error.message || 'Error - could not load content.';

                    // later code assumes the pane content will be wrapped
                    // in a dom node - ensure that is the case
                    // @todo - although this should not hurt, message might be wrapped
                    // in an element already, so in some cases this is not necessary
                    pane._setContent('<div>' + message + '</div>');

                    // add error-page class to take advantage of existing error page css; added to the pane
                    // so that the footer (page count & navigation) can be removed with CSS.
                    dojo.addClass(pane.domNode, 'error-page');
                }
            }).placeAt(dojo.body());
            dojo.attr(pane.domNode, 'page-id', id);
            pane.startup();

            // if the response isn't already in a book/page we will
            // automatically wrap the content in a new book/page.
            dojo.connect(pane, 'onLoad', function() {
                if (dojo.query('.mblBook .mblView', pane.domNode).length) {
                    return;
                }

                var page = new p4cms.mobile.Page({selected: true});
                dojo.query('> *', pane.domNode).place(page.domNode);

                var book = new p4cms.mobile.Book();
                dojo.addClass(book.domNode, 'auto-generated');
                dojo.place(page.domNode, book.domNode);
                dojo.place(book.domNode, pane.domNode);

                book.startup();
                page.startup();
            });
        });
    };

    // hijack internal links so they will utilize the 'load page'
    // on mouseup of a link we ensure we are last 'onclick' subscriber.
    // if your onclick handler fires we know the page would have been
    // navigated away from and this is a link worth hijacking.
    var upEvent     = dojox.mobile.hasTouch ? "ontouchend" : "onmouseup";
    var moveAction  = [];
    var handler     = null;
    new dojo.NodeList(dojo.body()).delegate("a", upEvent, function(evt) {
        var element = this,
            href    = dojo.attr(element, 'href'),
            inView  = new dojo.NodeList(element).closest('.mblView').length,
            i       = 0,
            moves   = moveAction;

        // reset the action counter
        moveAction  = [];

        // nothing to do for links outside the mbl view, empty links or anchor links
        if (!inView || !href || href.charAt(0) === '#') {
            return;
        }

        // only deal with internal links
        if (href.indexOf(p4cms.branchBaseUrl) !== 0) {
            return;
        }

        // don't hijack blacklisted links
        var noBase = href.substr(p4cms.branchBaseUrl.length);
        for (i=0; i < p4cms.mobile.navigation.blacklist.length; i++) {
            if (noBase.match(p4cms.mobile.navigation.blacklist[i])) {
                return;
            }
        }

        // we disconnect in the 'onclick' off of body but that may not run.
        // clear any dangling handlers here before adding a new one.
        dojo.disconnect(handler);

        // this was not a click event if it included more than one moveActions
        if (moves.length <= 1) {
            handler = dojo.connect(dojo.body(), 'onclick', function(evt) {
                dojo.disconnect(handler);

                if (evt.defaultPrevented) {
                    return;
                }

                p4cms.mobile.loadUrl(href);
                dojo.stopEvent(evt);
            });
        }
    });

    // track mousemoves for touch devices so we can determine whether the action was a click
    if (dojox.mobile.hasTouch) {
        new dojo.NodeList(dojo.body()).delegate("a", "ontouchmove", function(evt) {
            moveAction.push((new Date()).getTime());
        });
    }
});