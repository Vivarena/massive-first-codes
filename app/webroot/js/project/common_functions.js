
var generalSettings = {

    ajaxLoaderURL: '<span class="btn loaderAjax"><img src="/img/loader2.gif"/></span>',
    removeLoader: function(el, showAfter){
        var elem = $('body'), showElem = null;
        if (typeof el !== "undefined") elem = el;
        if (typeof showAfter !== "undefined") showElem = showAfter;
        elem.find('.loaderAjax').remove();
        if (showElem !== null) showElem.fadeIn();
    },

    prettyDate: function(date, prefix) {
        var d = date.getDate(),
            m = date.getMonth() + 1,
            y = date.getFullYear();
        return (m<=9 ? '0' + m : m)+prefix+(d<=9 ? '0' + d : d)+prefix+y;
    }
};

    function showTopMsg(msg, type, closeBtn){
        var colorMsg = '#1E90FF', showClose = false;
        if (type != 'error' && type != 'success' && type != 'warning') type = 'success';
        if (closeBtn == true) showClose = true;
        var notify = humane.create({ timeout: 3000, baseCls: 'humane-libnotify', clickToClose: true });
        notify.log('<img src="/img/icons/' + type +'.png" class="icon"/> <span class="msgNotify">' + msg + '</span>');
    }

var modalPopup = {

    bindClicks: function(){
        var $this = this;
        $('body').on('click', '.closePopup', function(e){
            $this.closePopup();
            e.preventDefault();
        });
    },

    // NOTICE: Settings for pre-popup
    settings: function(typePopup, titlePopup, contentPopup, w, h){
        if (h === undefined) h = w;
        var templateDataPopup = {}, fromTemplatePopup, nanoId;
        templateDataPopup = {
            popupTitle: titlePopup,
            popupContent: contentPopup,
            width: w,
            height: h,
            halfWidth: Math.ceil(h/2)+(w*0.2),
            halfHeight: Math.ceil(w/2),
            showClose: '<span class="closePopup">X</span>'
        };
        switch (typePopup){
            case 'fixed':
                nanoId = '#popupTemplateFixed';
                break;
            case 'confirm':
                nanoId = '#popupTemplateConfirm';
                break;
            default:
                break;

        }
        return $.nano($(nanoId).html(), templateDataPopup);
    },

    // NOTICE: Show Popup window with specific content.
    showPopup: function(contentPopup, setting, callback){
        var popupSettings = {
            closeOnOverlayClick: false,
            closeOnEsc: false,
            onOpen: function(el, options){
                el.html(contentPopup);
                callback = (typeof callback == "function") ? callback : (typeof setting == 'function') ? setting : undefined;
                if (callback != undefined) callback(el);
            },
            onClose: function(el){ }
        };
        if (setting !== undefined && (typeof setting != 'function')) {
            popupSettings = $.extend(popupSettings, setting);
        }
        $.modal().open(popupSettings);
    },
    closePopup: function() {
        $.modal().close();
    },

    // NOTICE: Confirm popup
    confirmPopup: function (titlePopup, contentPopup, callback)
    {
        var $this = this, confirm = false,
            fromTemplatePopup = $this.settings(
            'confirm',
            titlePopup,
            contentPopup,
            300, 80
        );
        $this.showPopup(fromTemplatePopup, {
            closeOnEsc: false,
            onOpen: function(el, options){
                el.html(fromTemplatePopup);
                $('.yesConfirm').click(function(e){
                    e.preventDefault();
                    confirm = true;
                    $.modal().close();
                });
                $('.noConfirm').click(function(e){
                    e.preventDefault();
                    confirm = false;
                    $.modal().close();
                });
            },
            onClose: function(el){
                if (confirm) {
                    if (typeof callback == "function") callback();
                }
            }
        });
    }




};

$(function() {

    modalPopup.bindClicks();

});

/**
 * @author Mike S. <misilakov@gmail.com>
 *
 * Small Validator for FORMs
 *
 * @param opt
 * @returns {{initValidate: Function, setRules: Function, checkValidate: Function}}
 * @constructor
 */
function ValidateForm(opt) {

    var settingsValidate = {
        formValidate: '.form-to-valid',
        errorClass: 'error-valid'
    },
    rulesValidate = {
        '.email-valid': /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
        '.empty-valid': /\S/
    },

    setRules = function(options){
        rulesValidate = $.extend(rulesValidate, options);
    },

    initValidate = function(options){
        settingsValidate = $.extend(settingsValidate, options);
    },

    checkValidate = function(options){

        var $this = this,
            opt = settingsValidate,
            errors = [];

        opt = $.extend(opt, options);

        for (var className in rulesValidate) {
            var getElement = $(opt.formValidate).find(className);
            getElement.each(function(j){
                var getVal = $(this).val();
                if (!rulesValidate[className].test(getVal)) {
                    if (typeof $(this).attr('id') === 'undefined' || $(this).attr('id') === false) {
                        var rndNum = Math.floor(Math.random() * 9999) + 1;
                        $(this).attr('id', 'errorValid' + rndNum);
                    }
                    errors.push('#' +  $(this).attr('id'))
                }
            });
        }
        if (errors.length > 0) {
            for (var i in errors) {
                $(errors[i]).addClass(opt.errorClass);
                $(errors[i]).keyup(function() {
                    $(this).removeClass(opt.errorClass);
                })
            }
            return false;
        } else return true;

    };

    return {
        initValidate: initValidate,
        setRules: setRules,
        checkValidate: checkValidate
    };

}

$(document).ready(function() {
    $('#userActivityList').on('click', '.del_wall_item', function(e) {
        e.preventDefault();
        var id = $(this).attr('href'), $this = $(this);
        $.get('/community/deleteFeed/'+id, function(response) {
            $this.closest('.comment-list__one').slideUp();
            $this.closest('.comment-list').html(response);
        });
    });
});