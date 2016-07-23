// summary:
//      The columnizer contains functions for creating and filling columns from uncolumnized nodes.
//      The main external function is columnizer.columnize()
dojo.provide('p4cms.mobile.columnizer');

(function() {
    var columnizer = {
        // main external function for columnizing nodes
        columnize: function(sourceNode, targetNode, numberOfColumns, maxHeight, callback) {
            // grab a copy of the contents so sourceNode and targetNode can be the same
            // can't use nodeList.children here because it skips textNodes
            var workingNode = dojo.create('div');
            while (sourceNode.childNodes.length > 0) {
                dojo.place(sourceNode.childNodes[0], workingNode);
            }

            // empty the target (cleanup any dijits first)
            dojo.query("[widgetid]", targetNode).forEach(function(node) {
                if (dijit.byNode(node)) {
                    dijit.byNode(node).destroy();
                }
            });
            dojo.empty(targetNode);

            // create and fill the columns
            var i;
            for (i = 0; i < numberOfColumns; i++) {
                var col = dojo.create('div', {
                        'class': 'p4cms-column p4cms-' + numberOfColumns + '-column-layout '
                            + 'p4cms-column-' + (i + 1) + (i === 0 ? ' p4cms-column-first' : "")
                            + (i === (numberOfColumns - 1) ? ' p4cms-column-last' : "")
                    }, targetNode);

                // fill the columns with content
                columnizer._fill(workingNode, col, col, maxHeight);
            }

            dojo.create('br', {'class': 'p4cms-column-clear', style: {clear: 'both'}}, targetNode);

            // callback with any extra overflow content
            if (callback) {
                var extra = workingNode.childNodes.length && workingNode;
                callback(extra);
            }
        },

        // fills the specified targetNode with nodes from the sourceNode, until the columnNode is full
        // it determines whether the columnNode is full by comparing the height against maxHeight
        _fill: function(sourceNode, targetNode, columnNode, maxHeight) {
            //while the height of the column is less than the maxHeight, add nodes
            while (columnizer._getMarginScrollHeight(columnNode) <= maxHeight && sourceNode.childNodes.length > 0) {
                var node = dojo.place(sourceNode.childNodes[0], targetNode);
                columnizer._sizeImages(node, maxHeight);
            }

            // if none were added, or the nodes were completely added, we're done filling this column
            if (targetNode.childNodes.length === 0 || columnizer._getMarginScrollHeight(columnNode) <= maxHeight) {
                return;
            }

            // remove the node that made the column too tall
            var violatingNode = targetNode.childNodes[targetNode.childNodes.length-1];
            targetNode.removeChild(violatingNode);

            // recurse through nested nodes
            //  - nodeType of 1 is an element node (img, table, div, etc)
            //  - nodeType of 3 is a text node
            if (violatingNode.nodeType === 1 && violatingNode.nodeName.toLowerCase() !== 'img') {
                // duplicate the violating node, so it will appear
                // in multiple columns with it's split content
                var duplicateNode = dojo.clone(violatingNode);
                dojo.empty(duplicateNode);
                dojo.place(duplicateNode, targetNode);

                // try to fill the column using parts of the violating node
                columnizer._fill(violatingNode, duplicateNode, columnNode, maxHeight);

                // nothing was added, remove the duplicateNode
                if (duplicateNode.childNodes.length <= 0) {
                    dojo.destroy(duplicateNode);
                }

                // if everything was added, violatingNode is not needed
                if (violatingNode.childNodes.length <= 0) {
                    violatingNode = null;
                }
            } else if (violatingNode.nodeType === 3) {
                var sourceText  = violatingNode.nodeValue,
                    clearfix    = dojo.create('br', {style:{clear:'both'}}, targetNode),
                    violatingText;

                // add text to column, breaking on spaces
                while (columnizer._getMarginScrollHeight(columnNode) <= maxHeight && sourceText.length) {
                    var spaceIndex = sourceText.search(/\s/);
                    spaceIndex     = spaceIndex === 0 ? 1 : spaceIndex;
                    var firstWord  = (spaceIndex !== -1 ? sourceText.substring(0, spaceIndex) : sourceText);
                    violatingText  = document.createTextNode(firstWord);
                    sourceText     = (spaceIndex !== -1 ? sourceText.substring(spaceIndex) : "");
                    dojo.place(violatingText, clearfix, 'before');
                }

                dojo.destroy(clearfix);
                var remainingText = sourceText;

                // back out last text node if too tall
                if (columnizer._getMarginScrollHeight(columnNode) > maxHeight && violatingText !== undefined) {
                    targetNode.removeChild(violatingText);
                    remainingText = violatingText.nodeValue + sourceText;
                    violatingNode.nodeValue = remainingText;
                }

                // if everything was added, violatingNode is not needed
                if (remainingText.length === 0) {
                    violatingNode = null;
                }
            }

            // add the old node back
            if (violatingNode) {
                dojo.place(violatingNode, sourceNode, 'first');
            }
        },

        // returns the margin size, including scroll overflow
        _getMarginScrollHeight: function(node) {
            var marginHeight    = node.offsetHeight + dojo._getMarginExtents(node).h,
                scrollOverflow  = node.scrollHeight - node.clientHeight;

            return Math.round(marginHeight+scrollOverflow);
        },

        // resize images, keeping their original ratio and calculating
        // a new height that better matches their width within the column.
        _sizeImages: function(node, maxHeight) {
            var imgList = new dojo.NodeList();
            if (node.tagName && node.tagName.toLowerCase() === 'img') {
                imgList.push(node);
            } else if (node.nodeType === 1) {
                imgList = dojo.query('img', node);
            }
            imgList.forEach(function(image) {
                // compute height from width.
                var ratio = parseInt(dojo.attr(image, 'height'), 10) / parseInt(dojo.attr(image, 'width'), 10);
                dojo.style(image, {
                    'height':       (dojo.contentBox(image).w * ratio) + 'px',
                    'maxHeight':    (maxHeight * 0.85) + 'px'
                });

                // floated images have to be cleared in order to properly give us size
                var align           = dojo.attr(image, 'align') || dojo.style(image, 'float'),
                    parentScroll    = dojo.style(image.parentNode, 'overflowY');
                if (align && parentScroll.toLowerCase() !== 'auto' && parentScroll.toLowerCase() !== 'scroll') {
                    dojo.style(image.parentNode, 'overflow', 'hidden');
                }
            });
        }
    };

    p4cms.mobile.columnizer = columnizer;
}());