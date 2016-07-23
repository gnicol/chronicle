// summary:
//      Content grid field formatting functions.

dojo.provide("p4cms.content.grid.Formatters");

p4cms.content.grid.Formatters = {
    title: function(item, row, column, noLink) {
        if (!item) {
            return;
        }

        var grid   = p4cms.content.grid.instance;
        var values = grid.getItemValues(row);
        var url    = values.uri;

        if (values.deleted) {
            url = p4cms.url({
                module:  'content',
                action:  'view',
                id:      values.id,
                version: values.version
            });
        }

        if (!noLink) {
            item = '<a oncontextmenu="dojo.fixEvent(event).stopPropagation();"'
                 + ' href="' + url + '">' + item + '</a>';
        }

        if (values.deleted) {
            item = '<span class="delete-banner">Deleted: </span><span class="deleted">' + item + '</span>';
        }
        return item;
    },

    titleNoLink: function(item, row, column) {
        return p4cms.content.grid.Formatters.title(item, row, column, true);
    },

    icon: function(item, row) {
        var iconUrl     = this.grid.getItemValue(row, 'icon');
        // @todo: use the proper image size instead of forcing resize
        return '<span class="content-type-icon"><img height="28px" width="28px" src="' + iconUrl + '" /></span>';
    },

    typeTooltip: function (itemValues) {
        if (!itemValues.type) {
            return "";
        }
        var name        = itemValues.type.label
                        ? p4cms.ui.escapeHtml(itemValues.type.label)
                        : '<i>No Name</i>';
        var description = itemValues.type.description
                        ? p4cms.ui.escapeHtml(itemValues.type.description).replace(/(\r\n|\n\r|\r|\n)/g, '<br/>')
                        : '<i>No Description</i>';
        var fields      = itemValues.type.fields && itemValues.type.fields.length
                        ? p4cms.ui.escapeHtml(itemValues.type.fields.join(', '))
                        : '<i>No Fields</i>';
        return "<dl>"
             + "<dt class=\"content-type-name\">Name</dt>"
             + "<dd class=\"content-type-name\">" + name + "</dd>"
             + "<dt class=\"content-type-description\">Description</dt>"
             + "<dd class=\"content-type-description\">" + description + "</dd>"
             + "<dt class=\"content-type-fields\">Fields</dt>"
             + "<dd class=\"content-type-fields\">" + fields + "</dd>"
             + "</dl>";
    }
};