// summary:
//      Content type grid field formatting functions.

dojo.provide("p4cms.content.type.grid.Formatters");

p4cms.content.type.grid.Formatters = {
    icon: function(item, row) {
        var iconUrl     = this.grid.getItemValue(row, 'icon');
        // @todo: use the proper image size instead of forcing resize
        return '<span class="content-type-icon"><img height="28px" width="28px" src="' + iconUrl + '" /></span>';
    }
};