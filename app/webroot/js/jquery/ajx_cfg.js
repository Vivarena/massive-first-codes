// reqiures jQuery and jQuery blockUI plugin

$(function () {

var ajaxGlobalLoader = {
    $body: $(document.body),
    loader: $('<div></div>')
            .append($('<img />', {
            'class':'image',
            'src':'/img/ajax-loader-dark.gif'
        }))
            .append($('<p></p>', {
            'class':'title',
            'text':'Please wait...'
        })),
    initLoader: function () {
        var that = this;
        this.$body.ajaxStart(function () {
            $.blockUI({
                message:that.loader.html(),
                fadeIn: 350,
                showOverlay: true,
                applyPlatformOpacityRules: false,
                baseZ: 9999,
                css:{
                    border:'none',
                    padding:'15px',
                    backgroundColor:'#000',
                    '-webkit-border-radius':'10px',
                    '-moz-border-radius':'10px',
                    '-o-border-radius':'10px',
                    'border-radius':'10px',
                    opacity:.7,
                    color:'#fff'
                }
            });
        });
        this.onCompleteAjaxLoader();
    },
    onCompleteAjaxLoader: function() {
        this.$body.ajaxStop  (function () {
            $.unblockUI({
                fadeOut: 300
            });
        });
    },
    stopGlobalLoader: function() {
        this.$body.unbind('ajaxStart');
    }
};

    ajaxGlobalLoader.initLoader();

    // Used to temporarily disable and reenable ajax loading animation
    window.ajaxGlobalLoader = ajaxGlobalLoader;
});
