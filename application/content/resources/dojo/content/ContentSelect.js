// summary:
//      Provides a content selection form element that supports
//      selecting multiple content entries if multiple is true.
//
//      Special support for use with content editing and content
//      elements. When content is selected or cleared, the content
//      element is notified.
//
//      Special support for drag and drop upload and select when
//      used with content editing.

dojo.provide("p4cms.content.ContentSelect");
dojo.require("dijit._Widget");
dojo.require("dijit.form.Button");
dojo.require("p4cms.content.SelectDialog");
dojo.require("p4cms.content.dnd.DropZone");

dojo.declare("p4cms.content.ContentSelect", dijit._Widget, {
    name:           '',
    browseOptions:  null,
    form:           null,
    multiple:       null,
    selected:       null,
    entryData:      null,
    validTypes:     null,
    extraFields:    null,
    rowCount:       0,

    startup: function() {
        this.inherited(arguments);

        // normalize object properties (we expect arrays later)
        this.selected       = this.selected      || [];
        this.entryData      = this.entryData     || [];
        this.validTypes     = this.validTypes    || [];
        this.extraFields    = this.extraFields   || [];

        // add classes so dijit can be targetted via css.
        var classes = 'p4cms-ui content-select' + (this.multiple ? ' multiple-select' : '');
        dojo.addClass(this.domNode, classes);

        // find our containing form element.
        this.form = new dojo.NodeList(this.domNode).closest('form')[0];

        // if multiple select is disabled, remove excess selections.
        if (!this.multiple && this.selected.length) {
            this.selected = this.selected.slice(0, 1);
        }

        // add a select row for each value.
        dojo.forEach(this.selected, dojo.hitch(this, function(item){
            this.addSelectRow(item);
        }));

        // ensure we have an empty row for the user's next selection
        // needed if no content selected, or multiple select is enabled.
        if (this.multiple || !this.selected.length) {
            this.addSelectRow();
        }

        // if files are dropped/uploaded directly on this element, select them.
        this._setupDropZone();

        // let external users know we've loaded.
        this.onLoad();
    },

    addSelectRow: function(value){
        // increment row count so we can group fields under
        // a common index when extra fields are present.
        this.rowCount++;

        // normalize selection value and extract id.
        value  = value    || {};
        var id = value.id || '';

        var row = dojo.create('div', {
            'class':    'content-select-row',
            style:      {display: 'none'}
        }, this.domNode);

        var input = dojo.create('input', {
            type:       'hidden',
            name:       this.getFieldName('id'),
            value:      id
        }, row);

        // fire this dijit's onChange hook when the id changes.
        dojo.connect(input, 'onchange', this, 'onChange');

        // if we have an associated content element,
        // notify it whenever the input value changes.
        var element = this._getContentElement();
        if (element) {
            dojo.connect(input, 'onchange', element, 'onFormChange');
        }

        // prepare title if we have it - if we don't have a title
        // that is an indication that the selection is invalid
        var title = '';
        var valid = true;
        if (this.entryData
            && this.entryData[id]
            && this.entryData[id].title
        ) {
            title = this.entryData[id].title;
        } else if (id) {
            title = id;
            valid = false;
        }

        // validate content type against valid-types if given.
        if (this.entryData
            && this.validTypes
            && this.validTypes.length
            && this.entryData[id]
            && this.entryData[id].type
            && dojo.indexOf(this.validTypes, this.entryData[id].type) === -1
        ) {
            valid = false;
        }

        // if selection is invalid, flag row as such.
        if (!valid) {
            title = "Invalid (" + title + ")";
            dojo.addClass(row, 'invalid');
        }
        dojo.create('input', {
            type:       'text',
            value:      title,
            readonly:   'readonly'
        }, row);

        var browse = new dijit.form.Button({
            label:      'Browse',
            'class':    'button-small',
            onClick:    dojo.hitch(this, 'browse', row)
        }).placeAt(row);

        var clear = new dijit.form.Button({
            label:      'Clear',
            'class':    'button-small',
            onClick:    dojo.hitch(this, 'clear', row)
        }).placeAt(row);

        // add extra fields (e.g. caption, link)
        this.addExtraFields(value, row);

        dojo.style(row, 'display', 'block');

        // if inside a content element's tooltip dialog, reposition it
        // now that we have added another row.
        if (element) {
            element.repositionEditDialog();
        }

        // scroll the new row into view - set timeout is apparently needed here.
        window.setTimeout(dojo.hitch(this, function(){
            this.domNode.scrollTop = this.domNode.scrollHeight;
        }), 500);

        return row;
    },

    addExtraFields: function(value, row){
        if (!this.extraFields.length) {
            return;
        }

        var fieldset = dojo.create('fieldset', {
            'class': 'extra-fields'
        }, row);

        dojo.forEach(this.extraFields, dojo.hitch(this, function(field){
            var wrapper = dojo.create('div', {'class': 'field-wrapper'}, fieldset);
            dojo.create('label', {
                innerHTML:  field.charAt(0).toUpperCase() + field.slice(1)
            }, wrapper);
            dojo.create('input', {
                type:       'text',
                name:       this.getFieldName(field),
                value:      value[field] || ''
            }, wrapper);
        }));
    },

    getFieldName: function(field){
        var name = this.name;

        if (this.multiple && this.extraFields.length) {
            name += '[' + this.rowCount + '][' + field + ']';
        } else if (this.extraFields.length) {
            name += '[' + field + ']';
        } else if (this.multiple) {
            name += '[]';
        }

        return name;
    },

    // get the next free select row or add one if needed.
    // this will always be the last row because we auto-remove rows on clear.
    getFreeRow: function(){
        var last  = dojo.query('.content-select-row:last-child', this.domNode)[0];
        var input = dojo.query('input[type=hidden]', last)[0];

        // if the row has no value or in single select mode, it is free.
        if (!this.multiple || !dojo.attr(input, 'value')) {
            return last;
        }

        return this.addSelectRow();
    },

    browse: function(row){
        // if inside a tooltip dialog, don't close tooltip when browse dialog opens.
        var tooltip = this._getContainingTooltip();

        // use the extended selection mode for multiple select.
        var options = dojo.mixin({selectionMode: this.multiple ? 'extended' : 'single'}, this.browseOptions);
        var dialog  = new p4cms.content.SelectDialog({browseOptions: options});

        // this will ensure that focusing the dialog won't blur this dijit
        dojo.attr(dialog.domNode, 'dijitPopupParent', this.get('id'));

        dialog.getSelection().addCallback(dojo.hitch(this, function(selection) {
            // if doing multiple select, expect a list of selected items.
            if (this.multiple) {
                dojo.forEach(selection, dojo.hitch(this, function(item) {
                    this.select(item.i, row);

                    // it's important we update the row reference -- the first selection
                    // goes into the row the user clicked browse on, subsequent selections
                    // go into new rows (added here) and we end with one extra row.
                    row = this.getFreeRow();
                }));
            } else {
                this.select(selection, row);
            }
            this._triggerChange();
        }));

        dialog.show();
    },

    clear: function(row){
        var hidden = dojo.query('input[type=hidden]', row)[0];
        var title  = dojo.query('input[type=text]',   row)[0];
        dojo.attr(hidden, 'value', '');
        dojo.attr(title,  'value', '');

        // if this is the last row, no need to remove row, just fire on change.
        var last = dojo.query('.content-select-row:last-child', this.domNode)[0];
        if (row === last) {
            this._triggerChange();
            return;
        }

        // clear input name so it doesn't contribute an empty element
        // to selected content array - this is particularly important
        // when used with content elements and validateField() because
        // validate fires before the input is removed and if the value
        // changes while validate is running, the results are ignored.
        dojo.attr(hidden, 'name', '');

        // fade out then destroy the row.
        var tooltip = this._getContainingTooltip();
        var element = this._getContentElement();
        p4cms.ui.hide(row, {onEnd: dojo.hitch(this, function(){
            dojo.destroy(row);

            // fire on change event so third parties know something happened
            // hope they don't need the name, but we must clear it first!
            this._triggerChange();

            // if inside a content element's tooltip dialog, reposition it.
            if (element) {
                element.repositionEditDialog();
            }
        })});
    },

    select: function(entry, row){
        var input = dojo.query('input[type=hidden]', row)[0];
        var label = dojo.query('input[type=text]',   row)[0];
        var id    = entry && entry.id    ? entry.id    : '';
        var title = entry && entry.title ? entry.title : '';
        dojo.attr(input, 'value', id);
        dojo.attr(label, 'value', title);

        // clear existing invalid class if present.
        dojo.removeClass(row, 'invalid');

        // validate content type against valid-types.
        var type = entry && entry.type ? entry.type.id || entry.type : null;
        if (type
            && this.validTypes
            && this.validTypes.length
            && dojo.indexOf(this.validTypes, type) === -1
        ) {
            dojo.addClass(row, 'invalid');
            dojo.attr(label, 'value', "Invalid (" + dojo.attr(label, 'value') + ")");
        }
    },

    // allow external users to hook into load and change events.
    onLoad:   function(){},
    onChange: function(){},

    // detect if we are inside of a tooltip dialog and return it.
    _getContainingTooltip: function(){
        var tooltip = new dojo.NodeList(this.domNode).closest('.dijitTooltipDialog')[0];
        return tooltip
            ? dijit.byNode(tooltip)
            : false;
    },

    // if we are associated with a content element, find it.
    _getContentElement: function(){
        var entry = new dojo.NodeList(this.form).closest('[dojoType=p4cms.content.Entry]')[0];
        return entry
            ? dijit.byNode(entry).getElement(this.name)
            : null;
    },

    _setupDropZone: function(dropNode){
        var contentSelect  = this;
        var contentElement = this._getContentElement();

        // if this control is not for a content element, don't setup
        // a drop-zone. it would be possible to make the control itself
        // a drop-zone, but that is less important and more work.
        if (!contentElement) {
            return;
        }

        // customize the drop-zone for use with this content element.
        var dropZone = new p4cms.content.dnd.DropZone({node: dropNode || contentElement.domNode});
        dojo.safeMixin(dropZone, {
            onDrop: function(e){
                if (!contentElement.inEditMode) {
                    return;
                }

                var uploader = this.inherited(arguments);
                dojo.safeMixin(uploader, {
                    onFileUploaded: function(file, e, data){
                        try {
                            var entry = dojo.fromJson(e.target.response);
                            contentSelect.select(
                                {
                                    id:     entry.contentId,
                                    title:  entry.contentTitle,
                                    type:   entry.contentType
                                },
                                contentSelect.getFreeRow()
                            );
                        } catch (error) {
                            // unable to extract content id.
                        }
                    },
                    onComplete: function(files, e){
                        contentSelect.getFreeRow();
                        contentSelect._triggerChange();
                    }
                });
            }
        });

        // if dnd uploads are supported, add note to placeholder text.
        if (dropZone.isSupported()) {
            var multiple = this.multiple;
            dojo.safeMixin(contentElement, {
                getPlaceholderText: function(){
                    return this.inherited(arguments) + ' (drop file' + (multiple ? 's' : '') + ' here)';
                }
            });
        }
    },

    _triggerChange: function() {
        var firstInput = dojo.query('input[type=hidden]', this.domNode)[0];
        if (firstInput) {
            p4cms.ui.trigger(firstInput, 'change');
        }

        // belt & suspenders, if we have an associated content element
        // we really want to ensure it knows that our value has changed
        // firing the change event on the input might not be sufficient.
        if (this._getContentElement()) {
            this._getContentElement.onFormChange();
        }
    }
});
