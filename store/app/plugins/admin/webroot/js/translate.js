String.prototype.ucFirst = function () {
    return this.substr(0,1).toUpperCase() + this.substr(1,this.length);
};

(function($) {
    $(function() {
		$(".translate").click(function() {
            $("#tabs").addClass("translate-hover");
            var params = $.parseJSON($(this).attr('alt'));
            $.post(
                '/admin/admin_pages/translate',
                {
                    "data[Translate][text]" : $("#" + params.id + params.from.ucFirst()).val(),
                    "data[Translate][from]" : params.from,
                    "data[Translate][to]"   : params.to
                },
                function (result) {



                    result = $.parseJSON(result);
                    $("#" + params.id + params.to.ucFirst()).val(result);
                    $("#tabs").removeClass("translate-hover");

                }
            );
    /*
            google.language.translate(
                $("#" + params.id + params.from.ucFirst()).val(),
                lngMap[params.from],
                lngMap[params.to],
                function(result) {
                    console.log(result);
                    if (!result.error) {
                        //console.log(result);
                        $("#" + params.id + params.to.ucFirst()).val(result.translation)
                    } else {
                      //console.log(result);
                      alert("Translation error");
                    }
                }
            );
    */
        });
	});
})(jQuery);