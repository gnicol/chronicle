// summary:
//      A widget that contains Pages

dojo.provide('p4cms.mobile.Book');

dojo.require('dijit._Widget');
dojo.require('p4cms.mobile.bookstack');
dojo.require('p4cms.mobile.Page');
dojo.require('p4cms.mobile.PagedLayout');

dojo.declare('p4cms.mobile.Book', dijit._Widget, {
    paginate:    null,
    baseClass:   'mblBook',
    currentPage: null,
    isCover:     false,

    startup: function() {
        this.inherited(arguments);

        if (this.paginate) {
            this.layout = new p4cms.mobile.PagedLayout(
                dojo.mixin({targetNode: this.domNode, book: this}, this.paginate)
            );
        }

        // if this is the cover book, add to the top of the book stack
        // book will otherwise be added to the end later
        if (this.isCover) {
            p4cms.mobile.bookstack.unshiftBook(this);
        }

        // build navigation.
        this.buildNavigation();
        dojo.publish('p4cms.mobile.Book.startup', [this]);
    },

    buildNavigation: function(){
        this.buildHeader();
        this.buildFooter();

        // update page navigation anytime page changes.
        this.getPages().forEach(function(page){
           this.addPageListeners(dijit.byNode(page));
        }, this);

        // update navigation footer anytime pages are added.
        dojo.subscribe('p4cms.mobile.Page.startup', this, function(page) {
            if (!dojo.isDescendant(page.domNode, this.domNode)) {
                return;
            }

            // update navigation on startup, and hookup navigation updates
            this.updateNavigation();
            this.addPageListeners(page);
        });

        this.updateNavigation();
    },

    buildHeader: function() {
        this.header     = dojo.create('div', {'class': 'page-header'});
        this.homeButton = dojo.create('div', {
            'class':    'page-header-button',
            innerHTML:  'Home',
            title:      'Home'
        }, this.header);
        this.backButton = dojo.create('div', {
            'class':    'page-header-button',
            innerHTML:  'Back',
            title:      'Back'
        }, this.header);

        // wire up the back/home buttons
        this.connect(this.homeButton, 'onclick', function() {
            p4cms.mobile.loadUrl(p4cms.url());
        });
        this.connect(this.backButton, 'onclick', 'previousBook');

        // if use has access to the toolbar, create the toolbar button
        if (p4cms.ui.getClass('p4cms.ui.toolbar.Toolbar')) {
            var dock            = p4cms.ui.toolbar.getPrimaryToolbar().dockRegion,
                toolbarButton   = dojo.create('div', {
                    'class':    'page-header-button toggle-site-toolbar',
                    innerHTML:  '&#8801;',
                    title:      'Manage'
                }, this.header);

            // wire up toggle toolbar button
            this.connect(toolbarButton, 'onclick', function() {
                dock.toggle();
            });

            var config = {node:this.header, duration: dock.hideDelay};
            this.connect(dock, 'expand', function() {
                dojo.fadeOut(config).play();
            });
            this.connect(dock, 'collapse', function() {
                dojo.fadeIn(config).play();
            });

            if (!dock.isCollapsed) {
                dojo.style(this.header, 'opacity', 0);
            }
        }

        dojo.place(this.header, this.domNode, 'first');
    },

    buildFooter: function() {
        this.footer       = dojo.create('div',  {'class': 'page-footer'});
        var pageNumbers   = dojo.create('div',  {'class': 'page-numbers',   innerHTML: 'Page '}, this.footer);
        this.pageNumber   = dojo.create('span', {'class': 'page-number'},   pageNumbers);
        var pageSeparator = dojo.create('span', {'class': 'page-separator', innerHTML: ' of '},  pageNumbers);
        this.pageCount    = dojo.create('span', {'class': 'page-count'},    pageNumbers);
        this.pageLinks    = dojo.create('div',  {'class': 'page-links'},    this.footer);

        this.pagePrev     = dojo.create('span', {'class': 'page-prev',      innerHTML: '&#9664; Prev'},  this.pageLinks);
        this.pageNext     = dojo.create('span', {'class': 'page-next',      innerHTML: 'Next &#9654;'},  this.pageLinks);

        dojo.place(this.footer, this.domNode);

        // wire up prev/next buttons
        this.connect(this.pagePrev, 'onclick', 'prevPage');
        this.connect(this.pageNext, 'onclick', 'nextPage');
    },

    // Updates the currentPage reference, and footer navigation to accurately
    // show the current page numbering
    updateNavigation: function() {
        // open the book in the bookstack
        p4cms.mobile.bookstack.pushBook(this);
        this.updatePageCount();

        // set the current page pointer
        var currentNumber   = this.getDisplayingPageNumber() || 0,
            pageNode        = dojo.query('.p4cms-swap-view', this.domNode)[currentNumber];

        if (!pageNode) {
            return;
        }

        this.currentPage    = dijit.byNode(pageNode);

        // add a page-n class to the book (e.g. so we
        // can hide page navigation on the first page)
        var classes = dojo.attr(this.domNode, 'class').replace(/\b(page-[0-9]+)\b/g, '');
        dojo.attr(this.domNode, 'class', classes.trim() + ' page-' + (currentNumber + 1));

        // make prev/next buttons inactive when on first/last page
        dojo.toggleClass(this.pagePrev, 'inactive', currentNumber === 0);
        dojo.toggleClass(this.pageNext, 'inactive', currentNumber === (this.getPages().length - 1));

        // hide back button when there is no history
        dojo.style(this.backButton, 'display', (p4cms.mobile.bookstack.getPrevious(this) ? '' : 'none'));

        // hide home button when we the home book.
        if (this.isCover) {
            dojo.style(this.homeButton, 'display', 'none');
        }
    },

    updatePageCount: function() {
        var pages                   = dojo.query('.p4cms-swap-view:not(.p4cms-dontcount-view)', this.domNode),
            currentNumber           = this.getDisplayingPageNumber(pages);
        this.pageNumber.innerHTML   = currentNumber + 1;
        this.pageCount.innerHTML    = pages.length;
    },

    // hooks to some of the passed page's methods so the book knows when the pages change
    addPageListeners: function(page) {
        if (page._bookListener) {
            return;
        }

        var transitionMove = function(moveTo, dir, transition) {
            if (transition === 'slidev') {
                this.moveNavigationToPage(page);
            }
        };

        this.connect(page, 'destroy',               'updateNavigation');
        this.connect(page, 'onShow',                'updateNavigation');
        this.connect(page, '_enableVerticalMode',   dojo.partial(this.moveNavigationToPage, page));
        this.connect(page, 'onBeforeTransitionOut', transitionMove);
        this.connect(page, 'onBeforeTransitionIn',  transitionMove);

        page._bookListener = this;
    },

    // moves the book header and footer into the passed page and add the appropriate
    // listeners for reversing the move
    moveNavigationToPage: function(page) {
        if (!dojo.isDescendant(page.domNode, this.domNode) || page._bookNavigation) {
            return;
        }

        dojo.place(this.footer, page.domNode);
        dojo.place(this.header, page.domNode);
        dojo.style(this.footer, 'display', 'table');
        dojo.style(this.header, 'display', 'block');

        page._bookNavigation            = this.footer;
        page._abortBookHandler          = page.connect(page, 'abort',
                                            dojo.hitch(this, 'moveNavigationFromPage', page));
        page._animationEndBookHandler   = page.connect(page, 'onFlickAnimationEnd',
                                            dojo.hitch(this, 'moveNavigationFromPage', page));
    },

    // moves the book footer form the passed page back into the book and disconnects
    // the default listeners that trigger the same task
    moveNavigationFromPage: function(page) {
        if (!dojo.isDescendant(page.domNode, this.domNode) || !page._bookNavigation) {
            return;
        }

        dojo.style(this.footer, 'display', '');
        dojo.style(this.header, 'display', '');
        dojo.place(this.footer, this.domNode);
        dojo.place(this.header, this.domNode);
        page._bookNavigation = null;

        page.disconnect(page._abortBookHandler);
        page.disconnect(page._animationEndBookHandler);
    },

    getPages: function(){
        return dojo.query('.p4cms-swap-view', this.domNode);
    },

    getDisplayingPageNumber: function(nodes){
        var i;
        nodes = nodes || this.getPages();
        for(i = 0; i < nodes.length; i++){
            if (dijit.byNode(nodes[i]) === dojox.mobile.currentView
                || (!dojox.mobile.currentView && dojo.style(nodes[i], "display") !== "none")
            ) {
                return i;
            }
        }

        return null;
    },

    getCurrentPage: function(){
        if (this.currentPage) {
            return this.currentPage;
        }

        var index = this.getDisplayingPageNumber();
        return index !== null && dijit.byNode(dojo.query('.p4cms-swap-view', this.domNode)[index]);
    },

    nextPage: function() {
        var current = this.getCurrentPage();
        var next    = current && current._nextView(current.domNode);
        if (next) {
            current.performTransition(next.id, 1, 'slide');
        }
    },

    prevPage: function() {
        var current = this.getCurrentPage();
        var prev    = current && current._previousView(current.domNode);
        if (prev) {
            current.performTransition(prev.id, -1, 'slide');
        }
    },

    previousBook: function() {
        var currentPage     = this.getCurrentPage(),
            previous        = p4cms.mobile.bookstack.getPrevious(this);

        if (previous && previous.getCurrentPage()) {
            currentPage.performTransition(previous.getCurrentPage().id, -1, 'slidev');
        }
    }
});

dojo.subscribe('p4cms.ui.toolbar.ignoreFilters.populate', function(menu, event, filters) {
    filters.push('.toggle-site-toolbar');
    filters.push('.page-links');
});