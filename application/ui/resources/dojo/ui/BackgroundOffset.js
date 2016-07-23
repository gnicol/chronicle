// summary:
// Manages background offset require to present the toolbar, notifications, etc.

if (!dojo._hasResource['p4cms.ui.BackgroundOffset']) {
    dojo._hasResource['p4cms.ui.BackgroundOffset'] = true;
    dojo.provide('p4cms.ui.BackgroundOffset');

    // closure for singleton values
    p4cms.ui._bgo = {
        bgOffset:       0,
        leftPos:        0,
        topPos:         0,
        paddingTop:     0,
        toolbarHeight:  0,
        ptPos:          0
    };

    p4cms.ui.BackgroundOffset.increase = function(pixels) {
        p4cms.ui.BackgroundOffset._applyOffset(parseInt(pixels, 10));
    };

    p4cms.ui.BackgroundOffset.decrease = function(pixels) {
        p4cms.ui.BackgroundOffset._applyOffset(-parseInt(pixels, 10));
    };

    p4cms.ui.BackgroundOffset.get = function() {
        if (typeof p4cms.ui._bgo.originalBP === 'undefined') {
            p4cms.ui.BackgroundOffset._parseBackgroundPosition();
        }
        return p4cms.ui._bgo.topPos;
    };

    p4cms.ui.BackgroundOffset.getWindowDimensions = function() {
        var de = document.documentElement;
        var width = window.self.innerWidth
                  || (de && de.clientWidth)
                  || document.body.clientWidth;

        // webkit positions percentage backgrounds against window height
        var height = window.self.innerHeight
                  || (de && de.clientHeight)
                  || document.body.clientHeight;

        // mozilla positions percentage background against content height
        if (dojo.isMozilla) {
            var cs = document.defaultView.getComputedStyle(dojo.body(), false);
            height = Math.floor(parseFloat(cs.height)) - p4cms.ui._bgo.toolbarHeight;
        }

        return {
            'width':  width,
            'height': height
        };
    };

    p4cms.ui.BackgroundOffset.cleanupUrl = function(url) {
        var regExp = /^url\(["']?(.+?)["']?\)$/ig,
            matches = regExp.exec(url);
        if (!matches) {
            matches = regExp.exec(url);
        }

        return (!matches || typeof matches[1] === 'undefined')
            ? null
            : matches[1];
    };

    p4cms.ui.getBGimageDimensions = function() {
        var dim = { 'width':  0, 'height': 0 },
            bgImage = dojo.style(dojo.body(), 'backgroundImage');
        if (typeof bgImage === 'undefined') {
            return dim;
        }

        bgImage = p4cms.ui.BackgroundOffset.cleanupUrl(bgImage);
        var image = dojo.create('img', { 'src': bgImage });
        dim.width = image.width;
        dim.height = image.height;

        return dim;
    };

    p4cms.ui.BackgroundOffset.toPx = function(dimension, horizontal, useBgImg) {
        horizontal = horizontal ? true : false;
        useBgImg   = useBgImg   ? true : false;

        var dValue = parseFloat(dimension);
        if (dValue === 0.0) {
            return '0px';
        }

        var units = 'px';
        if (dimension.indexOf('%') >= 0) {
            units = '%';
        }

        var fontSize = 16.0;
        if (dimension.indexOf('em') >= 0) {
            units = 'em';
            fontSize = parseFloat(dojo.style(dojo.body(), 'fontSize'));
        }

        // if already in px, no-op
        if (units === 'px') {
            return dimension;
        }

        var area = p4cms.ui.BackgroundOffset.getWindowDimensions();
        var areaLength = horizontal ? area.width : area.height;

        var pixels = 0;
        if (units === '%' && areaLength > 0.0) {
            pixels = dValue * areaLength / 100;
        } else if (units === 'em' && areaLength > 0.0) {
            pixels = dValue * fontSize;
            if (dojo.isIE && (fontSize / areaLength) > 0.0) {
                pixels = 16 * pixels / areaLength;
            }
        }
        pixels = Math.ceil(pixels);

        if (useBgImg) {
            var bgImgArea = p4cms.ui.getBGimageDimensions();
            var bgImgLength = horizontal ? bgImgArea.width : bgImgArea.height;
            pixels -= dValue * bgImgLength / 100;
        }

        return Math.floor(pixels) + 'px';
    };

    p4cms.ui.BackgroundOffset.reset = function() {
        p4cms.ui._bgo.topPos = 0;
        p4cms.ui._bgo.ptPos = 0;
        if (typeof p4cms.ui._bgo.originalBP !== 'undefined') {
            dojo.style(dojo.body(), 'backgroundPosition', p4cms.ui._bgo.originalBP);
            dojo.style(dojo.body(), 'paddingTop', p4cms.ui._bgo.originalPT);
        }
        p4cms.ui._bgo.originalBP = undefined;
        p4cms.ui._bgo.originalPT = undefined;
    };

    p4cms.ui.BackgroundOffset._parseBackgroundPosition = function() {
        var bp = dojo.style(dojo.body(), 'backgroundPosition');
        p4cms.ui._bgo.originalBP = bp;

        if (bp && bp !== '') {
            var index = bp.indexOf(' ');
            p4cms.ui._bgo.leftPos = bp.substring(0, index);
            var topPos = p4cms.ui.BackgroundOffset.toPx(bp.substring(index), false, true);
            p4cms.ui._bgo.topPos = topPos;
        } else {
            p4cms.ui._bgo.topPos = '0px';
        }

        var pt = dojo.style(dojo.body(), 'paddingTop');
        p4cms.ui._bgo.originalPT = pt;
        if (pt === '') {
            pt = 0;
        }
        p4cms.ui._bgo.ptPos = p4cms.ui.BackgroundOffset.toPx(pt);
    };

    p4cms.ui.BackgroundOffset._applyOffset = function(offset) {
        if (!offset) {
            return;
        }

        if (typeof p4cms.ui._bgo.originalBP === 'undefined') {
            p4cms.ui.BackgroundOffset._parseBackgroundPosition();
        }

        // only adjust background positions specified in pixels.
        var topPos = p4cms.ui._bgo.topPos || "";
        var bgAdjust = p4cms.ui._bgo.originalBP;
        if (topPos.indexOf('px') >= 0) {
            topPos = parseFloat(topPos) + offset;
            p4cms.ui._bgo.topPos = topPos + 'px';

            var leftPos = p4cms.ui._bgo.leftPos;
            bgAdjust = leftPos + ' ' + topPos + 'px';
        }

        var ptPos = parseFloat(p4cms.ui._bgo.ptPos) + offset;
        p4cms.ui._bgo.ptPos = ptPos;
        dojo.style(dojo.body(), 'paddingTop', ptPos + 'px');
        dojo.style(dojo.body(), 'backgroundPosition', bgAdjust);
    };
}