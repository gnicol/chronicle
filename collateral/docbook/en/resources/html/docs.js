// handle imagemap overlay with callout<->area highlighting
$(document).ready(function() {
    $('table[summary="Pub/Sub Topics"] tr').each(function() {
        $("table", this).hide();
    });
    $('table[summary="Pub/Sub Topics"] tr').click(function() {
        $("table", this).toggle();
    });
    $('table[summary="Pub/Sub Topics"] tr').css('cursor', 'pointer');
    $('a.toggle_pubsub_args').click(function() {
        $('table[summary="Pub/Sub Topics"] tr').each(function() {
            $("table", this).toggle();
        });
    });

    $("div.mediaobjectco").map(function() {
        // the technique involves providing the wrapper that contains the images, a highlight
        // layer above it, and a transparent layer above that. The usemap attribute is transferred
        // from the image to the transparent overlay so that the highlight does not interfere with
        // hover events.
        var wrapper = $('<div class="imagemap-wrapper"><div class="highlight"></div></div>');
        $(wrapper).prependTo(this);

        // ensure the embedded image comes first
        var img = $('img', this).first();
        img.appendTo(wrapper);

        // add a transparent img overlay
        var overlay = $('<img src="images/p.gif" border="0"/>');
        var usemap = $(img).attr("usemap");
        overlay.attr("usemap", $(img).attr("usemap"));
        $(img).removeAttr("usemap");
        $(overlay).prependTo(wrapper);
        overlay.css({"z-index": 100})

        // capture the image's size with an in-memory copy
        $('<img/>')
            .load(function() {
                $(overlay).attr({
                    "width":    this.width,
                    "height":   this.height
                });
                $(wrapper).css({
                    width:  this.width,
                    height: this.height
                });
            })
            .attr("src", $(img).attr("src"));
    });

    // setup the hover events on the image
    $(".mediaobjectco area").hover(
        function () {
            var parent = $(this).parents("div.mediaobjectco")[0];
            var id = $(this).attr("id");
            $("td." + id, parent).addClass("active");
            imagemapOverlayHighlightArea(parent, id);
        },
        function () {
            var parent = $(this).parents("div.mediaobjectco")[0];
            var id = $(this).attr("id");
            $("td." + id, parent).removeClass("active");
            imagemapOverlayDeHighlightArea(parent, id);
        }
    );

    // setup hover events on the callout list
    $(".mediaobjectco td, .mediaobjectco dl dt, .mediaobjectco dl dd").hover(
        function () {
            var parent = $(this).parents("div.mediaobjectco")[0];
            var coclass = $(this).attr("class");
            imagemapOverlayHighlightArea(parent, coclass);
        },
        function () {
            var parent = $(this).parents("div.mediaobjectco")[0];
            imagemapOverlayDeHighlightArea(parent, $(this).attr("class"));
        }
    );

    // add arrow-key navigation between pages
    $(document).keydown(function(event) {
        if (event.which !== 37 && event.which !== 39) {
            return;
        }

        var dir = event.which === 39 ? "next" : "prev";
        anchor = $("span.prev-next-nav a." + dir)[0];
        if (anchor && anchor.href) {
            location.href = anchor.href;
        }
    });
});

// perform the highlighting
function imagemapOverlayHighlightArea(parent, coclass) {
    var wrapper = $('div.imagemap-wrapper', parent)[0];
    $(wrapper).addClass(coclass);
    $(wrapper).addClass('active');
    var area = $('area#'+coclass, parent);
    var coords = $(area).attr("coords").split(',');
    var highlight = $('.highlight', wrapper);
    $(highlight).css({
        "left":   coords[0] + "px",
        "top":    coords[1] + "px",
        "width":  Math.abs(coords[2] - coords[0]) + "px",
        "height": Math.abs(coords[3] - coords[1]) + "px"
    });
}

function imagemapOverlayDeHighlightArea(parent, coclass) {
    var wrapper = $('div.imagemap-wrapper', parent)[0];
    $(wrapper).removeClass('active');
    $(wrapper).removeClass(coclass);
}

// provide a print method for the parent to invoke so that only iframe content gets printed
function printPage() {
    print();
}

// record the current help page, for six hours, so we can revisit later
var date = new Date();
date.setTime(date.getTime() + (6 * 60 * 60 * 1000));
document.cookie='help-page=' + window.location.href + "; expires=" + date.toGMTString() + "; path=/";