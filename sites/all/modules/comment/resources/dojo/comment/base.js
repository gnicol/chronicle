dojo.provide('p4cms.comment.base');
dojo.require("dojox.layout.ContentPane");

p4cms.comment.voteUp = function (id) {
    p4cms.comment.vote(id, true);
};

p4cms.comment.voteDown = function (id) {
    p4cms.comment.vote(id, false);
};

p4cms.comment.vote = function (id, up) {
    dojo.xhrPost({
        url:        p4cms.url({
            id:         id,
            module:     'comment',
            controller: 'index',
            action:     up ? 'vote-up' : 'vote-down',
            format:     'json'
        }),
        handleAs:   'json',
        handle:     function(response){
            dojo.query('[commentId="' + id + '"] .comment-vote-count')
                .forEach(function(count){
                    dojo.fadeOut({
                        node: count,
                        onEnd: function(){
                            if (response.comment && response.comment.votes) {
                                count.innerHTML = response.comment.votes + " vote"
                                    + (response.comment.votes !== 1 ? "s" : "");
                            }
                            dojo.fadeIn({node: count}).play();
                        }
                    }).play();
                }
            );

            // if comment options only allow one vote per user,
            // disable the vote up/down links so user can't vote again.
            if (response.options && response.options.oneVotePerUser) {
                var disableVoteLink = function(node){
                    dojo.attr(node, 'onclick', '');
                    dojo.addClass(node, 'comment-vote-disabled');
                };

                dojo.query('[commentId="' + id + '"] .comment-vote-up a')
                    .forEach(disableVoteLink);
                dojo.query('[commentId="' + id + '"] .comment-vote-down a')
                    .forEach(disableVoteLink);
            }
        }
    });
};