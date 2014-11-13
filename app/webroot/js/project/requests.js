$(function () {
    var requestUsers = {
        optJS : {
            content: $('.box-list'),
            btnAdd: '.addToNet',
            btnDel: '.delete',
            btnConfirm: '.confirm'
        },
        init: function(options) {
            this.optJS = $.extend(this.optJS, options);
            this.optJS.content.on('click', this.optJS.btnAdd, this.addToNetwork);
            this.optJS.content.on('click', this.optJS.btnDel, this.unFriend);
            this.optJS.content.on('click', this.optJS.btnConfirm, this.confirmFriend);
        },

        addToNetwork: function(e){
            var $this = $(this);
            $.post(
                $this.attr('href'),
                { 'data[id]': $this.data('id') },
                function( data ) {
                    $('.infoMsg').remove();
                    if ( data.status ) {
                        if ($this.attr('href')=='/community/cancelRequest') {
                            $this.text('Add Friend');
                            $this.attr('href','/community/addToNetwork');
                        } else {
                            $this.after('<span class="infoMsg"> - '+ data.message +'</span>');
                            $this.text('Cancel');
                            $this.attr('href','/community/cancelRequest');
                        }
                    } else {
                        $this.replaceWith('<span class="infoMsg">' + data.message + '</span>');
                    }
                }, 'json'
            );
            e.preventDefault();
        },

        unFriend: function(event){
            event.preventDefault();
            modalPopup.confirmPopup('Delete', 'Do you really want to remove from your friends list?', function(){
                $.post('/community/unFriend', $(event.target).data(), function (result) {
                    link = $(event.target);
                    //link.css('background', 'none');
                    //link.css('color', 'red');
                    if (result.status) {
                        link.replaceWith('<span>'+result.text+'</span>');
                    } else {
                        link.replaceWith('<span>Error</span>');
                    }
                }, 'json');
            });

        },

        confirmFriend: function(event){
            event.preventDefault();
            $.post('/community/confirmFriend', $(event.target).data(), function (result) {
                link = $(event.target);
                link.css('background', 'none');
                if (result.status) {
                    link.replaceWith('<span> - '+result.message+'</span>');
                    link.css('color', 'green');
                } else {
                    link.replaceWith('<span>Error</span>');
                    link.css('color', 'red');
                }
            }, 'json');
        }
    };
    requestUsers.init();
    window.reqUsers = requestUsers;
});
