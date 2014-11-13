$(function() {


    var formContainer = $('#formSignup'),
        ajaxLoader = '<span class="btn loaderAjax"><img src="/img/loader2.gif"/></span>';


    formContainer.on('click', '.btn-sign', function(e){

        var formData = formContainer.serialize(),
            thisBtn = $(this);
        thisBtn.hide();
        thisBtn.after(ajaxLoader);
        $.post("/users/ajax_register", formData,
            function(data) {
                $('.loaderAjax').remove();
                thisBtn.show();
                if(data.status) {
                    window.location = '/community'
                } else {
                    $('.error-span').remove();
                    for(err in data.errors){
                        $('#input_' + err).after('<span class="error-span">' + data.errors[err] + '</span>');
                    }
                }

            },
            "json"
        );

        e.preventDefault();
    });

    formContainer.on('keypress', '.inputSignup', function(e){
        $(this).next('span').remove();
    });


});

