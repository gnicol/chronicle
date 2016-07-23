// summary
//      diff mode plugin for overlay comparison of html or images
//      this plugin behaves slightly differently that other overlay diffs, such as
//      p4merge.
//
//      Overlay control is handled by a slider that has a value range from 0 to 100.
//      At 0, the old content is fully shown, and not the new content, at 100, the
//      new content is shown, and not the old. At 50, the old content is fully shown,
//      with the new content shown at 50% transparency.
//
//      The new content always matches it's transparency percent to the slider. The old content
//      stays at 100% transparency until after the slider position is greater than 50, after that the
//      content decreases in transparency until it is at 0% when the slider is at the 100 position.
//
//      Side-effect: If there has been no change in the content, the overlay effect will likely
//      still fade a bit when slider position is greater than 50, but appear to restore after 75.

dojo.provide("p4cms.diff.plugins.OverlayDiff");

dojo.require("p4cms.diff.plugins.DiffPlugin");
dojo.require("dijit._Templated");
dojo.require("dijit.form.HorizontalSlider");

dojo.declare("p4cms.diff.plugins.OverlayDiff", [p4cms.diff.plugins.DiffPlugin, dijit._Templated],
{
    containers: null,
    handlers:   null,
    isPlaying:  false,

    // positioning
    //      mm (middle-middle)
    //      tl (top-left)
    //
    // if positioning is unrecognized, mm is used
    positionAt: 'mm',

    // define the dom template
    templateString: '<div class="overlay diff-mode"><table class="diff-table"><tbody class="diff"><tr>'
                  + '<td class="left"><div class="overlay-container" dojoAttachPoint="containerNode"></div></td>'
                  + '</tr></tbody></table><div dojoAttachPoint="toolBarNode" class="overlay-tools" style="overflow:hidden;">'
                  + '</div></div>',

    // lifecycle
    // define complex objects
    postMixInProperties: function() {
        this.inherited(arguments);
        this.handlers = [];
    },

    // lifecycle
    // grab contents from sidebyside mode, and use them in our mode
    buildRendering: function() {
        this.inherited(arguments);

        // grab the contents from the sidebysideview
        var refContainers = dojo.query('.side-by-side .side-by-side-container .wrapper',
            this.getDiffElement().domNode);

        // clone the containers so that we can use them for overlay diff
        this.containers = {
            bottom: dojo.clone(refContainers[0]),
            top:    dojo.clone(refContainers[1])
        };
        dojo.place(this.containers.bottom, this.containerNode);
        dojo.place(this.containers.top, this.containerNode);

        this.createSlider();

        dojo.place(this.domNode, this.getDiffElement().domNode);
    },

    // creates a horizontal slider with some extra control buttons and
    // connects up the events
    createSlider: function() {
        // create table
        dojo.place('<table class="overlay-tool-container"><tr>'
            + '<td><div class="left-toolbar"></div></td><td class="slide-position-wrap">'
            + '<table class="slide-position"><tbody><tr><td class="left"></div></td>'
            + '<td class="middle"></td><td class="right"></div></td></tr></tbody></table>'
            + '</td><td><div class="right-toolbar"></div></td></tr></table>', this.toolBarNode);

        // create slider control
        this.slider = new dijit.form.HorizontalSlider({
            showButtons:            false,
            intermediateChanges:    true
        });

        // adjust overlay opacity when slider changes
        var handler = dojo.connect(this.slider, 'onChange', this, 'adjustOpacity');
        this.handlers.push(handler);
        dojo.place(this.slider.domNode, dojo.query('.middle', this.toolBarNode)[0]);

        // add play button
        this.playButton = new dijit.form.Button({
            'class':    'button-small overlay-play-button',
            iconClass:  'button-next',
            title:      'Play Transition',
            showLabel:  false,
            onClick:    dojo.hitch(this, 'playOverlay')
        });
        dojo.place(this.playButton.domNode, dojo.query('.left-toolbar', this.toolBarNode)[0]);

        // add a toggle borders button
        this.toggleBordersButton = new dijit.form.Button({
            'class':    'button-small overlay-toggle-button',
            iconClass:  'button-toggle',
            title:      'Toggle Borders',
            showLabel:  false,
            onClick:    dojo.hitch(this, 'toggleBorders')
        });
        dojo.place(this.toggleBordersButton.domNode, dojo.query('.right-toolbar', this.toolBarNode)[0]);

        // create our quick control icons
        var upperIcon   = dojo.create('div', {'class': 'overlay-slider-icon overlay-default', title: '50% New Image'},
                this.slider.progressBar, 'first'),
            leftIcon    = dojo.create('div', {'class': 'overlay-slider-icon', title: '100% Old Image'},
                dojo.query('.left', this.toolBarNode)[0]),
            rightIcon   = dojo.create('div', {'class': 'overlay-slider-icon', title: '100% New Image'},
                dojo.query('.right', this.toolBarNode)[0]);

        // attach mouseover events for our slider control icons
        var iconNodes = new dojo.NodeList(leftIcon, rightIcon, upperIcon);
        iconNodes.connect('onmouseenter', null, function(node) { dojo.addClass(this, 'mouseover'); });
        iconNodes.connect('onmousedown', null, function(node) { dojo.addClass(this, 'mousedown'); });
        iconNodes.connect('onmouseup', null, function(node) { dojo.removeClass(this, 'mousedown'); });

        iconNodes.connect('onmouseleave', null, function(node) {
            dojo.removeClass(this, 'mouseover');
            // also remove mousedown because the mouseup event isn't going to fire
            dojo.removeClass(this, 'mousedown');
        });

        // connect control events
        dojo.connect(leftIcon, 'onclick', this, function() {
            this.slider.set('value', 0);
        });
        dojo.connect(rightIcon, 'onclick', this, function() {
            this.slider.set('value', 100);
        });
        dojo.connect(upperIcon, 'onmousedown', this, function(event) {
            this.slider.set('value', 50);
            // don't trigger slider events
            dojo.stopEvent(event);
        });
    },

    // lifecycle
    // add our mode to the diffElement
    postCreate: function() {
        this.inherited(arguments);

        if (dojo.isFunction(this.getDiffElement().addMode)) {
            this.getDiffElement().addMode(this, 'overlay', this.domNode, 'Overlay');
        }
    },

    // lifecycle
    // do any calls and queries that are dependant on the diff or viewer to be
    // started
    startup: function() {
        this.inherited(arguments);

        // slider value to set opacity at desired default
        this.slider.set('value', 50);

        if (!this.parentSizePane) {
            this.parentSizePane = dojo.query('#'+this.getDiffElement().id).closest('.diff-comparison-pane')[0];
        }

        this.resize();
        this.handlers.push(
            dojo.connect(this.getDiffElement().getViewer(), '_sizeForDialog', this, 'resize')
        );
    },

    // takes slider position, 1-100, and determines what opacity to set the
    // two content fields
    adjustOpacity: function(position) {
        // if we are playing diff, do not adjust opacity
        if (this.isPlaying) {
            return;
        }

        var percent     = Math.floor(position),
            underlay    = 100 - percent;

        // bottom content should stay at full opacity until position is past 50
        underlay = underlay >= 50 ? 50 : underlay;

        // overlay animates through full slider
        // underlay animates through half the slider
        var anim = dojo.fx.combine([
            dojo._fade({
                node:       this.containers.bottom,
                end:        underlay/50,
                duration:   10
            }),
            dojo._fade({
                node:       this.containers.top,
                end:        percent/100,
                duration:    10
            })
        ]);
        anim.play();
    },

    // plays the full slider adjustment, from 0 - 100, as if it were a video
    // updating the slider position every full integer increment
    playOverlay: function() {
        // don't play overlay if we are already playing
        if(this.isPlaying) {
            return;
        }

        // play stage active
        this.isPlaying = true;
        this.playButton.set('disabled', true);

        // set slider to default position
        this.slider.set('value', 0);

        // animate the bottom container back to full opacity
        // and start the overlay animation
        var anim = dojo.fx.combine([
            dojo._fade({
                node:       this.containers.bottom,
                start:      1,
                end:        0,
                duration:   3000,
                easing:     function(n) {
                    // easing function so bottom content doesn't start animating
                    // until progress is past 0.5, then start animating at double speed
                    // to achieve full transparency by animation end
                    return (n <= 0.5 ? 0 : (n - 0.5) * 2);
                }
            }),
            dojo._fade({
                node:       this.containers.top,
                end:        1,
                start:      0,
                duration:   3000,
                onEnd:      dojo.hitch(this, function() {
                    // play overlay is finished
                    this.isPlaying = false;
                    this.playButton.set('disabled', false);
                }),
                onAnimate:  dojo.hitch(this, function(change) {
                    var position = Math.floor(change.opacity * 100);

                    // only update the slider if position has changed more than
                    // one integer value
                    if(position - this.slider.get('value') >= 1) {
                        this.slider.set('value', position);
                    }
                })
            })
        ]);
        anim.play(100, true);
    },

    // toggle the diff borders to show extents
    toggleBorders: function() {
        dojo.toggleClass(this.toggleBordersButton.domNode, 'active');
        dojo.toggleClass(this.containers.top, 'border-activate');
        dojo.toggleClass(this.containers.bottom, 'border-activate');
    },

    // calculate the max-height before showing scrollbars
    // and calculate size for use in positioning content
    resize: function() {
        var parentBox = dojo._getContentBox(this.parentSizePane),
            newHeight = Math.floor(parentBox.h * 0.8);

        // reset width and height of containers so we can determine proper sizing
        dojo.style(this.containers.bottom, {height:'auto', width:'auto'});
        dojo.style(this.containers.top, {height:'auto', width:'auto'});

        // explicitly set the containers sizing for use in positioning
        dojo.contentBox(this.containers.bottom, dojo._getContentBox(this.containers.bottom));
        dojo.contentBox(this.containers.top, dojo._getContentBox(this.containers.top));

        // update the overlay mode's height to contain the positioned containers
        var box = dojo._getMarginBox(this.containers.bottom).h;
        box = Math.max(box, dojo._getMarginBox(this.containers.top).h);
        dojo.contentBox(this.containerNode, {h:box});
        dojo.style(this.containerNode, 'maxHeight', newHeight + 'px');

        this.position();
    },

    // position content so that overlay is most effective
    position: function() {
        var containerBox    = dojo._getContentBox(this.containerNode),
            bottomBox       = dojo._getMarginBox(this.containers.bottom),
            topBox          = dojo._getMarginBox(this.containers.top),
            halfWidth       = Math.max(bottomBox.w, topBox.w),
            halfHeight      = Math.max(bottomBox.h, topBox.h) / 2,
            bottomBoxLeftPosition, bottomBoxTopPosition, topBoxLeftPosition, topBoxTopPosition;

        // accommodate the width of the container if content width is smaller than container width
        halfWidth = (halfWidth > containerBox.w ? halfWidth : containerBox.w) / 2;
        bottomBoxLeftPosition = bottomBoxTopPosition = topBoxLeftPosition = topBoxTopPosition = 0;

        // do positioning, default to mm (middle-middle) if unrecognized
        switch (this.positionAt) {
            case 'tl':
                var widest = (topBox.w > bottomBox.w ? topBox : bottomBox);
                bottomBoxLeftPosition = topBoxLeftPosition = Math.floor(halfWidth - (widest.w /2));
                break;
            default:
                bottomBoxLeftPosition   = Math.floor(halfWidth - (bottomBox.w /2));
                topBoxLeftPosition      = Math.floor(halfWidth - (topBox.w /2));
                bottomBoxTopPosition    = Math.floor(halfHeight - (bottomBox.h / 2));
                topBoxTopPosition       = Math.floor(halfHeight - (topBox.h / 2));
        }

        // compute the positions of the overlayed content
        // ensure that top position is positive, otherwise scrolling breaks
        var bottomPosition = {
            t: (bottomBoxTopPosition > 0 ? bottomBoxTopPosition : 0),
            l: bottomBoxLeftPosition
        };
        var topPosition = {
            t: (topBoxTopPosition > 0 ? topBoxTopPosition : 0),
            l: topBoxLeftPosition
        };

        dojo.style(this.containers.bottom, {top:bottomPosition.t + 'px', left:bottomPosition.l + 'px'});
        dojo.style(this.containers.top, {top:topPosition.t + 'px', left:topPosition.l + 'px'});
    },

    // override
    // on activate, resize and reposition
    activateMode: function(modeId, changeObj) {
        this.inherited(arguments);

        // ensure sizing is correct, and position the images
        this.resize();
    },

    // lifecycle
    // disconnect our handlers
    destroy: function() {
        this.inherited(arguments);
        var i;

        for (i = 0; i < this.handlers.length; i++) {
            dojo.disconnect(this.handlers[i]);
        }
    }
});