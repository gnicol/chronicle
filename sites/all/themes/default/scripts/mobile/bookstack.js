// summary:
//      stack that tracks the current book within a history of books

dojo.provide('p4cms.mobile.bookstack');

p4cms.mobile.bookstack = (function(){
    // bookstack public object
    var bookStack = [];
    dojo.mixin(bookStack, {
        current:    -1,
        pushBook: function(book) {
            if (bookStack.getBook() === book) {
                return;
            }

            // if we already have the book, just set the current pointer
            if (dojo.indexOf(bookStack, book) >= 0) {
                bookStack._setCurrentBook(book);
                return;
            }

            // if we already have a current and our array is larger than it, drop everything after it
            // because we are starting some fresh history
            if (bookStack.current >= 0 && bookStack.current < bookStack.length-1) {
                bookStack.splice(bookStack.current+1, (bookStack.length-bookStack.current-1));
            }


            // add the new book
            bookStack.push(book);
            bookStack._setCurrentBook(book);
        },

        // add given book on the top of the stack and advance the 'current'
        // pointer so it will reference the same item as before
        unshiftBook: function (book) {
            if (bookStack.getBook() === book) {
                return;
            }

            // if we already have the book, just set the current pointer
            if (dojo.indexOf(bookStack, book) >= 0) {
                bookStack._setCurrentBook(book);
                return;
            }

            // add the new book to the top
            bookStack.unshift(book);

            if (bookStack.current >= 0) {
                bookStack.current++;
            }

            bookStack._setCurrentBook(book);
        },

        _setCurrentBook: function(book) {
            // remove current class from the current book if it exists
            if (bookStack.getBook()) {
                dojo.removeClass(bookStack.getBook().domNode, 'p4cms-current-book');
            }

            bookStack.current = dojo.indexOf(bookStack, book);
            dojo.addClass(book.domNode, 'p4cms-current-book');
        },

        // get book at index
        getBook: function(index) {
            if (index === undefined) {
                index = bookStack.current;
            }

            if (index < 0 || index >= bookStack.length) {
                return null;
            }

            return bookStack[index];
        },

        getNext: function(book) {
            var index = dojo.indexOf(bookStack, book);
            return bookStack.getBook(index >= 0 ? index+1: -1);
        },

        getPrevious: function(book) {
            var index = dojo.indexOf(bookStack, book);
            return bookStack.getBook(index > 0 ? index-1 : -1);
        },

        clear: function () {
            if (bookStack.getBook()) {
                dojo.removeClass(bookStack.getBook().domNode, 'p4cms-current-book');
            }
            bookStack.splice(0, bookStack.length);
            bookStack.current = -1;
        }
    });

    return bookStack;
}());