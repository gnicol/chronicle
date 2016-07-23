dojo.provide('p4cms.ui.grid.formatters.TimeAgo');

// format a unix timestamp in terms of 'time ago'
p4cms.ui.grid.formatters.TimeAgo = function(item, row) {
    var time = parseInt(item, 10);
    if (!time) {
        return;
    }
    
    // assumes unixtime
    return p4cms.ui.timeAgo(time);
};