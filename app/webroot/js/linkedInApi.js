var linkedInApi = {

    linkedInLoaded: false,

    selectedConnections: [],

    settings: {
        el: 'body',
        apiKey: 'umuen1vdhk17',
        scope: "r_basicprofile r_emailaddress r_fullprofile r_contactinfo r_network w_messages",
        fields: ["id", "firstName", "lastName", "formattedName", "pictureUrl", "emailAddress", "summary", "educations", "dateOfBirth", "positions:(company)", "publicProfileUrl"],
        loginBtn: '.loginLinkedIn',
        inviteBtn: '.inviteLinkedinBtn'
    },

    init: function(opt){
        this.settings = $.extend(this.settings, opt);
        var that = this;

        $.getScript("http://platform.linkedin.com/in.js?async=true", function success() {
            that.linkedInLoaded = true;
            $(that.settings.el).find(that.settings.loginBtn + ' img').attr('src', '/img/in-login.png');

            IN.init({
                api_key: that.settings.apiKey,
                scope: that.settings.scope,
                authorize: true,
                credentials_cookie: true
            });

            that.bindEvents();
        });
    },

    bindEvents: function(){
        var that = this;
        $(this.settings.el).on('click', that.settings.loginBtn, function(e){
            e.preventDefault();
            if (that.linkedInLoaded) {
                if ($(this).closest('form').find('.agreeCheckbox').length > 0) {
                    if (GoodValidate.checkAgree($(this).closest('form').find('.agreeCheckbox'))) {
                        that.onLinkedInAuth();
                    }
                } else that.onLinkedInAuth();
            }
        });

        $(this.settings.el).on('click', that.settings.inviteBtn, function(e){
            e.preventDefault();
            that.__getConnections();
        });

        $('body').on('click', '#inviteConnections', function(e){
            e.preventDefault();
            that.selectedConnections = [];

            var BODY = {
                'recipients': {
                    'values': []
                },
                'subject': 'Vivarena',
                'body': 'Go and see this awesome site! http://vivarena.com'
            };

            $(".friends_container").find('input:checked').each(function(index, node) {
                that.selectedConnections.push($(node).val());
                BODY.recipients.values.push({
                    'person': {'_path': '/people/'+$(node).val()}
                });
            });

            IN.API.Raw('/people/~/mailbox').method('POST').body(JSON.stringify(BODY))
                .result(function() {
                    $(".friends_container").html('Invitation send success!');
                })
                .error(function error(e) {
                    $(".friends_container").html('Error while send invitation');
                });
        });

    },

    __getConnections: function() {
        var $this = this;
        IN.User.authorize(function() {
            IN.API.Connections("me").fields($this.settings.fields).result(function(result, metadata) {
                $this.__renderConnections(result.values);
            });
        });
    },

    __renderConnections: function(connections) {
        var connHTML = "<ul>";

        for (id in connections) {
            connHTML = connHTML + "<li><a href=\"#\">";

            /* picture url not always there, must be defensive */
            if (connections[id].hasOwnProperty('pictureUrl')) {
                connHTML = connHTML + "<img align=\"baseline\" src=\"" + connections[id].pictureUrl + "\"></a>";
            }  else {
                connHTML = connHTML + "<img align=\"baseline\" src=\"http://static02.linkedin.com/scds/common/u/img/icon/icon_no_photo_80x80.png\"></a>";
            }

            connHTML = connHTML + "&nbsp;<a href=\"" + connections[id].publicProfileUrl + "\">";
            connHTML = connHTML + connections[id].firstName + " " + connections[id].lastName + "</a>";
            connHTML = connHTML + "<input name=\"connections[]\" type=\"checkbox\" value=\"" + connections[id].id + "\">";
            connHTML = connHTML + "</li>";
        }

        connHTML = connHTML + "</ul>";
        connHTML = connHTML + "<div><a href='#' class='btn' id='inviteConnections'>Invite</a></div>";
        $(".friends_container").html(connHTML);
    },

    onLinkedInAuth: function() {
        var that = this;
        IN.User.authorize(function() {
            IN.API.Profile("me").fields(that.settings.fields).result(function(data){
                linkedInApi.parseProfile(data);
            });
        });
    },

    parseProfile: function(profiles) {
        /*var getBigPicture = IN.API.Raw("/people/~/picture-urls::(original)").result();
        if (getBigPicture.values != undefined) getBigPicture = getBigPicture.values[0];*/
        var member = profiles.values[0];

        this.sendToServer(member);

    },

    sendToServer: function(data){
        $.post("/users/linkedInProcess", { 'data[LinkedIn]' : data },
            function(data) {
                if(data.User != undefined) {
                    $.post('/users/ajax_register', {data: data}, function(resp) {
                        window.location = '/community';
                    });
                }else if(data.status = true) {
                    window.location = '/community';
                }
            },
            "json"
        );
    }
};