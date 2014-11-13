
var GoogleCalendar = {

    el: '#calendarPage',
    authBtn: '#authorize-button',

    settings: {
        clientId: '411975218629-k5r33uhht71g4bbsgjr54vfk1i6om3i3.apps.googleusercontent.com',
        apiKey: 'AIzaSyBiCf-Sut5kOAtEyzl6mZ_Al46whuqAYTM',
        scopes: 'https://www.googleapis.com/auth/calendar'

    },

    init: function(opt) {
        this.settings = $.extend(this.settings, opt);
    },

    bindEvents: function() {
        var that = this;
        $(that.el).on('click', that.authBtn, function(e){
            e.preventDefault();
            that.handleAuthClick();
        });
    },
    checkAuth: function() {
        gapi.auth.authorize({client_id: this.settings.clientId, scope: this.settings.scopes, immediate: true}, GoogleCalendar.handleAuthResult);
    },

    handleAuthClick: function() {
        gapi.auth.authorize({client_id: this.settings.clientId, scope: this.settings.scopes, immediate: false}, GoogleCalendar.handleAuthResult);
        return false;
    },

    handleAuthResult: function(authResult) {
        var authorizeButton = document.getElementById('authorize-button');
        if (authResult) {
            //authorizeButton.style.visibility = 'hidden';
            GoogleCalendar.listCalendars()
        } else {
            authorizeButton.style.visibility = '';
            authorizeButton.onclick = GoogleCalendar.handleAuthClick;
        }
    },

    listCalendars: function() {
        var that = this;
        gapi.client.load('calendar', 'v3', function () {
            var requestListCal = gapi.client.calendar.calendarList.list();
            requestListCal.execute(function (resp) {
                //console.log(resp);
                var templateNote = $.nano($('#listCalendars').html(), {'desc' : ''}),
                    modalTemplate = modalPopup.settings('fixed', 'Google Calendars', templateNote, 450, 252);
                modalPopup.showPopup(modalTemplate, function(popup){
                    if (resp.items.length > 0) {
                        var itemsHtml = '';
                        for (var i = 0; i < resp.items.length; i++) {
                            itemsHtml += '<li><a href="#" class="gooCalItem" data-cal-id="'+resp.items[i].id+'">'+resp.items[i].summary+'</a></li>';
                        }
                        $('#listGooCal').html(itemsHtml);
                    }
                    popup.on('click', '.gooCalItem', function(e){
                        e.preventDefault();
                        var calId = $(this).data('calId');
                        that.getEvent(calId, popup);
                    });
                });
            });
        });
    },

    getEvent: function(calId, popup) {
        var that = this,
            requestEvents = gapi.client.calendar.events.list({
            'calendarId': calId
        });
        requestEvents.execute(function (resp) {
            if (resp.items.length > 0) {
                var itemsHtml = '', dateItem, jsDate;
                for (var i = 0; i < resp.items.length; i++) {
                    dateItem = moment((resp.items[i].start.dateTime) ? resp.items[i].start.dateTime : resp.items[i].start.date).format("MMM Do YYYY");
                    itemsHtml += '<li><span class="dateItem">('+dateItem+')</span><a href="#" class="gooCalItemEvent" data-event-id="'+resp.items[i].id+'">'+resp.items[i].summary+'</a></li>';
                }
                $('#listGooCal').html(itemsHtml);
                popup.find('.titleCal').text('Select event:');
                popup.on('click', '.gooCalItemEvent', function(e){
                    e.preventDefault();
                    var eventId = $(this).data('eventId');
                    modalPopup.closePopup();
                    that.getItemEvent(calId, eventId);
                });
            }
        });
    },

    getItemEvent: function(calId, idEvent) {
        var requestItemEvent = gapi.client.calendar.events.get({
            'eventId': idEvent,
            'calendarId': calId
        });
        requestItemEvent.execute(function (resp) {
            console.log(resp);

            var that = this,
                startDate = (resp.start.dateTime) ? resp.start.dateTime : resp.start.date,
                endDate = (resp.end.dateTime) ? resp.end.dateTime : resp.end.date,
                desc = (resp.description) ? resp.description : '',
                allDay = (resp.start.dateTime && resp.end.dateTime) ? 'false':'true',
                title = resp.summary,
                dataPost = {
                    'data[Calendar][start]': startDate,
                    'data[Calendar][end]': endDate,
                    'data[Calendar][all_day]': allDay,
                    'data[CalendarEvent][description]': title + ((desc) ? ': ' + desc : '')
                };
            $.post("/calendars/addEvent", dataPost,
                function(data) {
                    modalPopup.closePopup();
                    if(!data.error) {
                        Calendar.cal.fullCalendar('renderEvent', {
                                id: data.id,
                                title: data.shortDesc,
                                start: startDate,
                                end: endDate,
                                allDay: data.allDay
                            }, true
                        );
                        Calendar.cal.fullCalendar('rerenderEvents');
                    } else {
                        showTopMsg(data.errDesc, 'error');
                    }
            }, "json");
        });
    }




};