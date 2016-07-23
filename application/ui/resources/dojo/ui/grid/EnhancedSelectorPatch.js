dojo.provide("p4cms.ui.grid.EnhancedSelectorPatch");

// Updated the enhanced grid Selector _startSelect to include
// upstream (1.7) fix for enforcing single selection
// @todo remove when dojo is updated to 1.7
dojo.require("dojox.grid.enhanced.plugins.Selector");
(function() {
    var DISABLED = 0, SINGLE = 1, MULTI = 2;
    dojo.extend(dojox.grid.enhanced.plugins.Selector, {
        _startSelect: function(type, start, extending, isRange, mandatarySelect, toSelect) {
            if (!this._isValid(type, start)) {
                return;
            }
            var lastIsSelected  = this._isSelected(type, this._lastEndPoint[type]),
                isSelected      = this._isSelected(type, start);
            
            this._toSelect = mandatarySelect ? isSelected : !isSelected;
            
// #P4CMS BEGIN PATCH
            if (!extending || (!isSelected && this._config[type] === SINGLE)) {
                this._clearSelection("col", start);
                this._clearSelection("cell", start);
                if (type === 'row' && this._config[type] === SINGLE) {
                    this._clearSelection('row', start);
                }
                this._toSelect = toSelect === undefined ? true : toSelect;
            }
            
            this._selecting[type]       = true;
            this._currentPoint[type]    = null;
            
            if (isRange && this._lastType === type && lastIsSelected === this._toSelect && this._config[type] === MULTI) {
// #P4CMS END PATCH
                if (type === "row") {
                    this._isUsingRowSelector = true;
                }
                this._startPoint[type] = this._lastEndPoint[type];
                this._highlight(type, this._startPoint[type]);
                this._isUsingRowSelector = false;
            } else {
                this._startPoint[type] = start;
            }
            this._curType = type;
            this._fireEvent("start", type);
            this._isStartFocus = true;
            this._isUsingRowSelector = true;
            this._highlight(type, start, this._toSelect);
            this._isStartFocus = false;
        }  
    });
}());