// summary
//      I've patched in the latest editor node html functions from trunk (dojo 1.8)
//      these are required to fix issue an issue with IE9 messing up image width and
//      height in the editor. See bug: http://bugs.dojotoolkit.org/ticket/15032
//      @todo Remove this entire file when dojo 1.8 lands

dojo.provide('p4cms.content.editor.html');

dijit._editor.getNodeHtml = function(node) {
    // summary:
    //              Return string representing HTML for node and it's children
    var output = [];
    dijit._editor.getNodeHtmlHelper(node, output);
    return output.join("");
};

dijit._editor.getNodeHtmlHelper = function(node, output){
    // summary:
    //              Pushes array of strings into output[] which represent HTML for node and it's children
    var form                    = document.createElement("form"),
        attributesExplicit      = form.attributes.length === 0,
        attributesSpecifiedFlag = form.attributes.length > 0 && form.attributes.length < 40;
    switch(node.nodeType){
        case 1: //element node
            var lName = node.nodeName.toLowerCase();
            if(!lName || lName.charAt(0) === "/"){
                // IE does some strange things with malformed HTML input, like
                // treating a close tag </span> without an open tag <span>, as
                // a new tag with tagName of /span.  Corrupts output HTML, remove
                // them.  Other browsers don't prefix tags that way, so will
                // never show up.
                return "";
            }
            output.push('<', lName);

            //store the list of attributes and sort it to have the
            //attributes appear in the dictionary order
            var attrarray = [], attrhash = {};
            var attr;
            if(attributesExplicit || attributesSpecifiedFlag) {
                // IE8+ and all other browsers.
                var i = 0;
                attr = node.attributes[i++];
                while (attr) {
                    // ignore all attributes starting with _dj which are
                    // internal temporary attributes used by the editor
                    var n = attr.name;
                    if(n.substr(0,3) !== '_dj' &&
                        (!attributesSpecifiedFlag || attr.specified) && !(attrhash.hasOwnProperty(n))){
                        // workaround repeated attributes bug in IE8 (LinkDialog test)
                        var v = attr.value;
                        if((n === 'src' || n === 'href') && node.getAttribute('_djrealurl')){
                            v = node.getAttribute('_djrealurl');
                        }
                        if(dojo.isIE === 8 && n === "style"){
                            v = v.replace("HEIGHT:", "height:").replace("WIDTH:", "width:");
                        }
                        attrarray.push([n,v]);
                        attrhash[n] = v;
                    }
                    attr = node.attributes[i++];
                }
            }else{
                // IE6-7 code path
                var clone = /^input$|^img$/i.test(node.nodeName) ? node : node.cloneNode(false);
                var s = clone.outerHTML;
                // Split up and manage the attrs via regexp
                // similar to prettyPrint attr logic.
                var rgxp_attrsMatch = /[\w\-]+=("[^"]*"|'[^']*'|\S*)/gi;
                var attrSplit = s.match(rgxp_attrsMatch);
                s = s.substr(0, s.indexOf('>'));
                dojo.forEach(attrSplit, function(attr){
                    if(attr){
                        var idx = attr.indexOf("=");
                        if(idx > 0){
                            var key = attr.substring(0,idx);
                            if(key.substr(0,3) !== '_dj'){
                                if((key === 'src' || key === 'href') && node.getAttribute('_djrealurl')){
                                    attrarray.push([key,node.getAttribute('_djrealurl')]);
                                    return;
                                }
                                var val, match;
                                switch(key){
                                    case 'style':
                                        val = node.style.cssText.toLowerCase();
                                        break;
                                    case 'class':
                                        val = node.className;
                                        break;
                                    case 'width':
                                        if(lName === "img"){
                                            // This somehow gets lost on IE for IMG tags and the like
                                            // and we have to find it in outerHTML, known IE oddity.
                                            match=/width=(\S+)/i.exec(s);
                                            if(match){
                                                val = match[1];
                                            }
                                        } else {
                                            val = node.getAttribute(key);
                                        }
                                        break;
                                    case 'height':
                                        if(lName === "img"){
                                            // This somehow gets lost on IE for IMG tags and the like
                                            // and we have to find it in outerHTML, known IE oddity.
                                            match=/height=(\S+)/i.exec(s);
                                            if(match){
                                                val = match[1];
                                            }
                                            break;
                                        } else {
                                            val = node.getAttribute(key);
                                        }
                                        break;
                                    default:
                                        val = node.getAttribute(key);
                                }
                                if(val !== null){
                                    attrarray.push([key, val.toString()]);
                                }
                            }
                        }
                    }
                }, this);
            }
            attrarray.sort(function(a,b){
                return a[0] < b[0] ? -1 : (a[0] === b[0] ? 0 : 1);
            });
            var j = 0;
            attr = attrarray[j++];
            while(attr){
                output.push(' ', attr[0], '="',
                    (typeof attr[1] === "string" ? dijit._editor.escapeXml(attr[1], true) : attr[1]), '"');
                attr = attrarray[j++];
            }
            switch(lName){
                case 'br':
                case 'hr':
                case 'img':
                case 'input':
                case 'base':
                case 'meta':
                case 'area':
                case 'basefont':
                    // These should all be singly closed
                    output.push(' />');
                    break;
                case 'script':
                    // Browsers handle script tags differently in how you get content,
                    // but innerHTML always seems to work, so insert its content that way
                    // Yes, it's bad to allow script tags in the editor code, but some people
                    // seem to want to do it, so we need to at least return them right.
                    // other plugins/filters can strip them.
                    output.push('>', node.innerHTML, '</', lName, '>');
                    break;
                default:
                    output.push('>');
                    if(node.hasChildNodes()){
                            dijit._editor.getChildrenHtmlHelper(node, output);
                    }
                    output.push('</', lName, '>');
            }
            break;
        case 4: // cdata
        case 3: // text
            // FIXME:
            output.push(dijit._editor.escapeXml(node.nodeValue, true));
            break;
        case 8: //comment
            // FIXME:
            output.push('<!--', dijit._editor.escapeXml(node.nodeValue, true), '-->');
            break;
        default:
            output.push("<!-- Element not recognized - Type: ", node.nodeType, " Name: ", node.nodeName, "-->");
    }
};

dijit._editor.getChildrenHtml = function(node) {
    // summary:
    //              Returns the html content of a DomNode's children
    var output = [];
    dijit._editor.getChildrenHtmlHelper(node, output);
    return output.join("");
};

dijit._editor.getChildrenHtmlHelper = function(dom, output) {
    // summary:
    //              Pushes the html content of a DomNode's children into out[]
    if(!dom){ return; }
    var out = "";
    if(!dom){ return out; }
    var nodes = dom.childNodes || dom;

    //IE issue.
    //If we have an actual node we can check parent relationships on for IE,
    //We should check, as IE sometimes builds invalid DOMS.  If no parent, we can't check
    //And should just process it and hope for the best.
    var checkParent = !dojo.isIE || nodes !== dom;

    var node, i = 0;
    node = nodes[i++];
    while(node) {
        //IE is broken.  DOMs are supposed to be a tree.  But in the case of malformed HTML, IE generates a graph
        //meaning one node ends up with multiple references (multiple parents).  This is totally wrong and invalid, but
        //such is what it is.  We have to keep track and check for this because otherwise the source output HTML will have dups.
        //No other browser generates a graph.  Leave it to IE to break a fundamental DOM rule.  So, we check the parent if we can
        //If we can't, nothing more we can do other than walk it.
        if(!checkParent || node.parentNode === dom){
            dijit._editor.getNodeHtmlHelper(node, output);
        }
        node = nodes[i++];
    }
};