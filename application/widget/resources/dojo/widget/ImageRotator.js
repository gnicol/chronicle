// summary:
//      Support for the image rotator widget.

dojo.provide("p4cms.widget.ImageRotator");
dojo.require("dijit._Widget");
dojo.require("dojo.NodeList-traverse");

dojo.declare("p4cms.widget.ImageRotator", dijit._Widget,
{
    current:        0,
    images:         null,
    delay:          5,
    timeoutId:      null,
    slowDeviceFade: 15,

    startup: function(){
        this.images = dojo.query('.image', this.domNode);

        // if we have no images, nothing to animate.
        if (this.images.length < 1) {
            return;
        }

        // configure presentation of images - these styles
        // belong here because the javascript depends on them
        // for proper animation.
        this.images.style({
            position:                   'absolute',
            top:                        '-10%',
            left:                       '-10%',
            width:                      '120%',
            height:                     '120%',
            opacity:                    '0',
            backgroundSize:             'cover',
            backgroundRepeat:           'no-repeat',
            backgroundPosition:         'center center'
        });

        // all captions should be hidden initially.
        dojo.query('.image-caption', this.domNode).style('opacity', '0');

        // kick off the animation.
        this.next();

        // cancel animations while finger is on the screen
        // this enhances performances, and gives the user a change to admire
        // the current image
        var isAndroid   = parseFloat(navigator.userAgent.split("Android ")[1]) || undefined;
        var rotateDelay = (dojo.isWebKit && isAndroid) ? this.slowDeviceFade : this.delay;
        this.connect(dojo.doc, 'ontouchstart', function() {
            window.clearTimeout(this.timeoutId);
        });
        this.connect(dojo.doc, 'ontouchend', function() {
            // schedule the next animation.
            window.clearTimeout(this.timeoutId);
            this.timeoutId = setTimeout(dojo.hitch(this, 'next'), rotateDelay*1000);
        });
    },

    next: function() {
        var isAndroid   = parseFloat(navigator.userAgent.split("Android ")[1]) || undefined;
        var rotateDelay = (dojo.isWebKit && isAndroid) ? this.slowDeviceFade : this.delay;

        // skip this animation if we don't currently have layout
        if (dojo.contentBox(this.domNode).h <= 0) {
            // schedule the next animation.
            window.clearTimeout(this.timeoutId);
            this.timeoutId = setTimeout(dojo.hitch(this, 'next'), rotateDelay*1000);
            return;
        }

        var images  = this.images;
        var current = images[this.current];
        var li      = new dojo.NodeList(current).closest('li')[0];

        // instantly set the current image to 0 opacity (so it can be faded in)
        // and make it the last image so that it will be on top and therefore
        // visible and able to receive click events. we don't bother to shuffle
        // and hide if we only have one image because it is always on top.
        if (images.length > 1) {
            dojo.style(current, {
                opacity:                    '0',
                transitionProperty:         'none',
                WebkitTransitionProperty:   'none',
                MozTransitionProperty:      'none'
            });
            dojo.place(li, li.parentNode, 'last');
        }

        // display the current caption - hide all others.
        dojo.query('.image-caption', this.domNode).forEach(
            dojo.hitch(this, function(node){
                p4cms.ui[node.parentNode === li ? 'show' : 'hide'](node, {duration: Math.round(this.delay/2*1000)});
            })
        );

        // randomly select animation effect for the current image and apply it.
        // we need to do this in a timeout, so that our previous call to set
        // opacity to 0 can take effect and the new value of 1 can animate in.
        setTimeout(dojo.hitch(this, function(){
            var fadeTime  = Math.round(this.delay * 0.8);
            var moveTime  = fadeTime * 2;
            var translate = ['-6%','6%','0,6%','0,-6%','-6%,6%','6%,6%','-6%,-6%','6%,-6%'];
            var scale     = ['1','1.1','.95'];
             if (dojo.isIE > 9 || dojo.isFF || (dojo.isWebKit && !isAndroid)) {
                translate     = 'translate(' + translate[Math.floor(Math.random()*translate.length)] + ')';
                scale         = 'scale('     + scale[Math.floor(Math.random()*scale.length)] + ')';
                dojo.style(current, {
                    opacity:                    1,
                    visibility:                 'visible',
                    transform:                  scale + ' ' + translate,
                    transitionProperty:         'opacity, transform',
                    transitionDuration:         fadeTime + 's, ' + moveTime + 's',
                    // use a rotate to workaround the mozilla bug with smooth scaling
                    //   https://bugzilla.mozilla.org/show_bug.cgi?id=663776
                    MozTransform:               scale + ' ' + translate + ' rotate(0.01deg)',
                    MozTransitionProperty:      'opacity, -moz-transform',
                    MozTransitionDuration:      fadeTime + 's, ' + moveTime + 's',
                    WebkitTransform:            scale + ' ' + translate,
                    WebkitTransitionProperty:   'opacity, -webkit-transform',
                    WebkitTransitionDuration:   fadeTime + 's, ' + moveTime + 's'
                });
             } else {
                dojo.style(current, {visibility:'visible'});
                dojo.fadeIn({node: current, duration: 500}).play();
            }
        }), 0);

        // loop around.
        if (++this.current >= images.length) {
            this.current = 0;
        }

        // schedule the next animation.
        this.timeoutId = setTimeout(dojo.hitch(this, 'next'), (rotateDelay * 1000));
    },

    destroy: function(){
        window.clearTimeout(this.timeoutId);
        this.inherited(arguments);
    }
});