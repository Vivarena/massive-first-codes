$(document).ready(function(){

    var form = $('#cont_form');
    var fields = form.find('input[type="text"]');

    form.submit(function(e){
        fields.each(function(){
            if($(this).val() == '') {
                e.preventDefault();
//                $(this).css('border', '1px solid red').delay(2300).queue(function () {$(this).css({'border': '1px solid #C1CEE5'});$(this).dequeue();});
                $(this).val('Field required').css('color', 'red').delay(2300).queue(function () {$(this).val('').css({'color': '#707070'});$(this).dequeue();});
            }
        });
    });

    $('#foot_subscribe').click(function(e){
        e.preventDefault();
        var input = $(this).parent().children('input');
        if(input.val() == '') {
            input.val('Field required').delay(2300).queue(function () {
                input.val('');
                input.dequeue();
            });
        }else {
            $.post('/subscribe', {'data[Subscriber][email]': input.val()}, function(data){
                if(data.error == true) {
                    input.val(data.errors.email).delay(2300).queue(function () {
                        input.val('');
                        input.dequeue();
                    });
                }else {
                    input.val('Thank you').css('font-style', 'italic');
                }
            }, 'json');
        }
    });

    $('.photos_thumbnails .thumbnail a').click(function(e) {
        e.preventDefault();
        var photo_block = $('.photo-block');
        var overlay = photo_block.children('.photo-overlay');
        var id = $(this).attr('data-id');
        overlay.css('display', 'block');
        $.post('/photo-gallery', {'data[id]': id, 'data[ajax]': true}, function(response){
            if(response.error == false){
                photo_block.children('img').attr('src', '/thumbs/663x343'+response.photo[0].image);
                photo_block.find('h3').text(response.photo[0].name);
                photo_block.find('p').text($('<p>'+response.photo[0].description+'</p>').text());
                photo_block.children('img').load(function() {
                    overlay.css('display', 'none');
                });
            }
        }, 'json');
    });

});