var facebookApi = {

    // Deferred object for async actions
    def: null,

    // Storage of Facebook response
    storage: null,

    settings: {
        el: 'body',
        loginBtn: '.loginFB',
        inviteBtn: '.inviteBtn',
        //scope: 'email,user_birthday,user_location,user_hometown,publish_actions,publish_stream,user_events',
        scope: 'user_status, friends_status,email,read_stream,publish_stream,user_birthday,user_location,user_hometown,user_photos,friends_photos, user_checkins, publish_checkins, friends_checkins, friends_location'
    },

    init: function(opt){
        console.log('FB init');
        this.def = new $.Deferred();
        this.settings = $.extend(this.settings, opt);
        FB.init({
            //appId: '559801934073207',
            appId: '691338290876178',
            channelUrl: '//www.vivarena.com/channel',
            status: true, // check login status
            cookie: true, // enable cookies to allow the server to access the session
            xfbml: true  // parse XFBML
        });

        this.bindEvents();
    },

    bindEvents: function(){
        var that = this;
        $(this.settings.el).on('click', that.settings.loginBtn, function(e){
            e.preventDefault();
            that.getLoginStatus(this, that);
        });

        $(this.settings.el).on('click', that.settings.inviteBtn, function(e){
            e.preventDefault();
            FB.ui({
                method: 'apprequests',
                message: 'Go to this awesome site =)'
            }, function(response) {
                console.log(response);
                if(response && response.to.length) {
                    FB.ui({
                        method: 'apprequests',
                        message: 'Go to this awesome site =)',
                        to: response.to
                    }, function(resp) {
                        console.log(resp);
                    });
                }
            });
        });
    },

    getLoginStatus: function(thisBtn, that){
        that.getStatus(function(uid, accessToken){ that.sendData(uid, accessToken, thisBtn) });
    },

    getStatus: function(callback){
        var that = this;
        FB.getLoginStatus(function (response) {
            if (response.status === 'connected') {
                var uid = response.authResponse.userID;
                var accessToken = response.authResponse.accessToken;
                callback(uid, accessToken);
            } else {
                FB.login(function (subResponse) {
                    if (subResponse.authResponse != null) {
                        var uid = subResponse.authResponse.userID;
                        var accessToken = subResponse.authResponse.accessToken;
                        callback(uid, accessToken);
                    }
                }, { scope: that.settings.scope });
            }
        });
    },

    sendData: function(uid, accToken, thisBtn){
        var that = this, btnOuter = $(thisBtn).closest('li'),
            typeUser = $(that.settings.el).find('.userType:checked'),
            userType = (typeUser.length > 0) ? typeUser.val() : null;
        $.post("/users/fbProcess", {'data[FB][uid]' : uid, 'data[FB][accessToken]' : accToken, 'data[User][type]' : userType},
            function(data) {
                if(data.User != undefined) {
                    $.post('/users/ajax_register', {data: data}, function(resp) {
                        window.location = '/community';
                    });
                }else if(data.status = true) {
                    window.location = '/community';
                }
        }, "json");
    }
};