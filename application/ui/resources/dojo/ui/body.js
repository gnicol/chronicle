// Created because the dojo native function does not check that d.body() exists before accessing d.body().dir,
// throwing an error in IE when using the ace editor.
//
// @todo update when dojo is updated; last updated dojo.1.6.1

dojo.provide('p4cms.ui.body');

dojo._isBodyLtr = function(){
    // returns boolean
    var d = dojo;
    return d.hasOwnProperty('_bodyLtr') ? d._bodyLtr :
        d._bodyLtr = ((d.body() && d.body().dir) || d.doc.documentElement.dir || "ltr").toLowerCase() === "ltr";
};
