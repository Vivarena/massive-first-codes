;(function($, window, document, undefined) {
	"use strict";
    var timeout,
        defaults = {
    		background_color 	: '#FFFFFF',
    		color 				: '#000',
    		position		 	: 'top',
    		removebutton     	: true,
    		time			 	: 5000,
            wrapperEl           : '.wrapper',
            timeBefore          : 100
    	};

    function removebar() {
        if($('.jbar').length){
            clearTimeout(timeout);
            $('.jbar').slideUp('fast',function(){
                $(this).remove();
            });
        }
    }
    function init(options) {
        var opts = $.extend({}, defaults, options);
        return {
            show : function() {
                var $this = $('body'),
                    o = $.meta ? $.extend({}, opts, $this.data()) : opts;

                if(!$('.jbar').length){
                    timeout = setTimeout(removebar,o.time);
                    var _message_span = $(document.createElement('span')).addClass('jbar-content').html(o.message);
                    _message_span.css({"color" : o.color});
                    var _wrap_bar;
                    (o.position == 'bottom') ?
                    _wrap_bar	  = $(document.createElement('div')).addClass('jbar jbar-bottom'):
                    _wrap_bar	  = $(document.createElement('div')).addClass('jbar jbar-top') ;

                    _wrap_bar.css({"background-color" 	: o.background_color});
                    if(o.removebutton){
                        var _remove_cross = $(document.createElement('a')).addClass('jbar-cross');
                        _remove_cross.click(function(e){removebar();})
                    }
                    else{
                        _wrap_bar.css({"cursor"	: "pointer"});
                        _wrap_bar.click(function(e){removebar();})
                    }
                    setTimeout(function() {
                        _wrap_bar.append(_message_span).append(_remove_cross).hide().insertBefore($(o.wrapperEl)).slideDown('normal');
                    }, o.timeBefore);
                }
            }
        };
    }

    $.bar = function(options){
        var barObj = init(options);
        barObj.show();
    };

})(jQuery, window, document);