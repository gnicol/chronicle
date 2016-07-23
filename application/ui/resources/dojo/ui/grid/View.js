// summary
//      Upstream version of dojox.grid.View that includes Firefox Smooth Scroll fixes outlined in
//      http://bugs.dojotoolkit.org/ticket/15487
//      @todo remove when dojo is updated to dojo 1.6.2 or higher

dojo.provide('p4cms.ui.grid.View');
dojo.require('dojox.grid._View');

dojo.extend(dojox.grid._View, {
    _nativeScroll: false,

    doscroll: function(inEvent) {
        if (dojo.isFF >= 13) {
            this._nativeScroll = true;
        }
        //var s = dojo.marginBox(this.headerContentNode.firstChild);
        var isLtr = dojo._isBodyLtr();
        if(this.firstScroll < 2){
            if((!isLtr && this.firstScroll === 1) || (isLtr && this.firstScroll === 0)){
                var s = dojo.marginBox(this.headerNodeContainer);
                if(dojo.isIE){
                    this.headerNodeContainer.style.width = s.w + this.getScrollbarWidth() + 'px';
                }else if(dojo.isMoz){
                    //TODO currently only for FF, not sure for safari and opera
                    this.headerNodeContainer.style.width = s.w - this.getScrollbarWidth() + 'px';
                    //this.headerNodeContainer.style.width = s.w + 'px';
                    //set scroll to right in FF
                    this.scrollboxNode.scrollLeft = isLtr ?
                        this.scrollboxNode.clientWidth - this.scrollboxNode.scrollWidth :
                        this.scrollboxNode.scrollWidth - this.scrollboxNode.clientWidth;
                }
            }
            this.firstScroll++;
        }
        this.headerNode.scrollLeft = this.scrollboxNode.scrollLeft;
        // 'lastTop' is a semaphore to prevent feedback-loop with setScrollTop below
        var top = this.scrollboxNode.scrollTop;
        if(top !== this.lastTop){
            this.grid.scrollTo(top);
        }
        this._nativeScroll = false;
    },

    setScrollTop: function(inTop) {
        // 'lastTop' is a semaphore to prevent feedback-loop with doScroll above
        this.lastTop = inTop;
        if (!this._nativeScroll) {
            //fix #15487
            this.scrollboxNode.scrollTop = inTop;
        }
        return this.scrollboxNode.scrollTop;
    }
});