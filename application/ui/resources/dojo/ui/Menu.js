dojo.require('dijit.Menu');
dojo.provide('p4cms.ui.Menu');

dojo.declare("p4cms.ui.Menu", dijit.Menu, {
    aroundTarget: false,
    wrapperClass: '',

    // extend parent to:
    //  - fire onShow of any children that define it.
    //  - add support for a wrapper class
    //  - automatically scroll menus if they are too tall.
    onOpen: function() {
        var wrapper = dijit.popup._createWrapper(this);
        dojo.addClass(wrapper, 'p4cms-ui');
        dojo.addClass(wrapper, this.wrapperClass);

        // remove/reset any scrolling style rules.
        // this allows a menu to expand if the user makes viewport larger.
        dojo.removeClass(wrapper, 'scrolling-menu');
        dojo.style(wrapper, {
            height:     'auto',
            overflowX:  'visible',
            overflowY:  'visible'
        });

        this.inherited(arguments);

        dojo.forEach(this.getChildren(),
            function(child)
            {
                if (dojo.isFunction(child.onShow)) {
                    dojo.hitch(child, 'onShow')();
                }
            }
        );

        // shrink the menu if it is too tall for the viewport.
        var coords = dojo.position(wrapper);
        var view   = dojo.window.getBox();
        var margin = 25;
        if (view.h <= (coords.h + coords.y + margin)) {
            dojo.addClass(wrapper, 'scrolling-menu');
            dojo.style(wrapper, {
                height:     (view.h - coords.y - margin) + 'px',
                overflowX:  'hidden',
                overflowY:  'scroll'
            });
        }
    },

    // extend openPopup to offset opened popups by their own padding
    _openPopup: function() {
        this._stopPopupTimer();
        var from_item = this.focusedChild;
        if (!from_item || from_item.popup.isShowingNow) {
            return;
        }

        this.inherited(arguments);

        var wrapper         = dijit.popup._createWrapper(this.currentPopup),
            topPadding      = dojo._getPadExtents(wrapper).t,
            isFixed         = dojo.style(wrapper, 'position') === 'fixed',
            wrapperPos      = dojo.position(wrapper, !isFixed);

        // offset the wrapper by it's padding so it's content lines up with the target
        dojo.style(wrapper, 'top', (wrapperPos.y - topPadding) + 'px');
    },

    // extend parent to support positioning the menu around
    // the target element instead of the mouse click location.
    _openMyself: function(args){
        if (!this.aroundTarget) {
            return this.inherited(arguments);
        }

        // if target is a child of one of our bound elements, use the bound element instead.
        var target = args.target;
        dojo.some(this._bindings, function(binding) {
            if (dojo.isDescendant(target, binding.node)) {
                target = binding.node;
                return false;
            }
        });

        var isFixed = p4cms.ui.withinPosition(target, 'fixed'),
            coords  = dojo.position(target, !isFixed);
        args.coords = {x: coords.x, y: (coords.y + coords.h)};

        return this.inherited(arguments, [args]);
    }
});

// @todo remove when dojo adds position fixed support
// keep out of the global namespace
(function() {
    // extends Menu to add support for fixed positioning
    var oldMenuOpen = dijit.Menu.prototype._openMyself;
    dojo.extend(dijit.Menu, {
        _openMyself: function(args) {
            var target  = args.target,
                isFixed = p4cms.ui.withinPosition(target, 'fixed'),
                coords  = args.coords;

            if (isFixed && !coords) {
                coords      = dojo.position(target);
                coords.x    += 10;
                coords.y    += 10;
                args.coords = coords;
            }

            if (isFixed) {
                dojo.style(dijit.popup._createWrapper(this), 'position', 'fixed');
            }

            oldMenuOpen.apply(this, [args]);
        }
    });
}());
