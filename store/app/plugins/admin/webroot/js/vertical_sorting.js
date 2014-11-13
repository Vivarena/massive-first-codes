
    
    var sort_container;

    function init_sort_object(container){
        sort_container = container;
    }


        function reviewArrows(){

            var rowCount = sort_container.find('tr').length;

            if (rowCount > 2) {
                sort_container.find('tbody tr:last-child .dw_btn').hide();
                sort_container.find('tbody tr:last-child .up_btn').show();
                sort_container.find('tbody tr:first-child').next().find('.up_btn').hide();
                sort_container.find('tbody tr:first-child').next().find('.dw_btn').show();
                if (rowCount > 3) {
                    sort_container.find('tbody tr:first-child').next().next().find('.up_btn').show();
                    sort_container.find('tbody tr:first-child').next().next().find('.dw_btn').show();
                    sort_container.find('tbody tr:last-child').prev().find('.dw_btn').show();
                    sort_container.find('tbody tr:last-child').prev().find('.up_btn').show();
                }
            } else {
                sort_container.find('tbody tr .dw_btn').hide();
                sort_container.find('tbody tr .up_btn').hide();
            }

        }

        $(".move_down").live("click", function() {
            var this_href = $(this);
            var sort_obj = $(this).closest('tr');
            var img_down = $(this).find("img");
            var next_obj = sort_obj.next();
            img_down.animate({top:"20px"}, 200, function(){
                img_down.animate({top:"0px"}, 200, function(){
                    sort_obj.insertAfter(next_obj);
                    reviewArrows();
                    save_sort(this_href);

                });
            });

            return false;
        });

        $(".move_up").live("click", function() {
            var this_href = $(this);
            var sort_obj = $(this).closest('tr');
            var img_down = $(this).find("img");
            var prev_obj = sort_obj.prev();
            img_down.animate({top:"-20px"}, 200, function(){
                img_down.animate({top:"0px"}, 200, function(){
                    sort_obj.insertBefore(prev_obj);
                    reviewArrows();
                    save_sort(this_href);

                });
            });

            return false;
        });

        var save_sort = function(url){
            if (url.closest('tr').find('.first img').length > 0){
                var top_loader = url.closest('tr').find('.first img').offset().top;
                var left_loader = url.closest('tr').find('.first img').offset().left;
                var w = url.closest('tr').find('.first img').width();
                var h = url.closest('tr').find('.first img').height();
                top_loader = top_loader + ((h/2)-8);
                left_loader = left_loader + ((w-90)/2);
                url.closest('tr').find('.first img').after('<span class="loader_absolute" style="top: ' + top_loader + 'px; left: ' + left_loader + 'px; ">Saving sort...</span>');
            } else {
                sort_container.before('<span class="loader_after">Saving sort...</span>');
            }
            $.post(url.attr('href'),
                function(data) {
                    if(data.error) {
                        window.alert("Error in sort function!");
                    }
                    if (url.closest('tr').find('.first img').length > 0){
                        url.closest('tr').find('.first img').next('.loader_absolute').remove();
                    } else {
                        sort_container.prev('.loader_after').remove();
                    }
                },
                "json"
                );
            return true;

        };
