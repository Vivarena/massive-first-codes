jQuery(function(){
  var content = $('#content');
    if ($('.chzn-select').length > 0) $('.chzn-select').chosen();

  function page(p_page) {
    var page = parseInt(p_page);

    $.ajax({
      url: '/messages/' + (isNaN(page) ? '' : 'page:' + page),
      success: function(data) {
        content.html(data);
      },
      error: function(data) {
               alert('error getting page');
             }
    });
  }

  $('#messages_link').click(function() {
    page();
  });

  content.on('click', '#prev_page', function() {
    page(parseInt($(this).data().page) - 1);
  });

  content.on('click', '#next_page', function() {
    page(parseInt($(this).data().page) + 1);
  });

  content.on('click', '#back', function() {
    page();
  });

  content.on('click', '.message_link', function() {
    var messageId = parseInt($(this).data().id);
    $.ajax({
      url: '/emails/view/' + (isNaN(messageId) ? '' : messageId),
      success: function(data) {
        content.html(data);
      },
      error: function(data) {
               alert('error getting page');
             }
    });
  });

  content.on('click', '.delete-message', function() {
    var messageId = parseInt($(this).data().id);
    $.ajax({
      url: '/emails/ajaxDeleteMessage/' + (isNaN(messageId) ? '' : messageId),
      success: function(data) {
        page();
      },
      error: function(data) {
               alert('error deleting message');
             }
    });
  });

    content.on('click', '.delete-messages', function(e) {
        e.preventDefault()
        var ids = '';
        var val;
        $.map($('.select_delete:checked'), function(checkbox, idx) {
            val = $(checkbox).data('id');
            if (val) ids += val + '/';
        });
        if (ids !== '') {
            $.ajax({
                url: '/emails/ajaxDeleteMessages/' + ids,
                success: function(data) { page(); },
                error: function(data) {
                    alert('error deleting messages');
                }
            });
        }
    });

    content.on('click', '.replyBtn', function(){
        var replyBtn = $(this);
        $('#replyText').slideDown('normal', function(){
            var subjectTxt = $('.message-subject').val();
            subjectTxt = 'Re: ' + subjectTxt;
            $('.message-subject').val(subjectTxt);
            $('.message-subject').removeAttr('readonly');
            replyBtn.removeClass('replyBtn');
            replyBtn.addClass('send-message');
            replyBtn.val('Send');
        });
    });

    content.on('click', '.send-message', function () {
        var data = $(this).data();

        var subject = $('.message-subject').text();
        if (subject === '') {
            subject = $('.message-subject').val();
        }

        var original = $('.message-content').html();
        if (original === null) {
            original = '';
        }
        var $this = $(this);
        $this.hide();
        $this.after('<span class="btn sendingMsg">Sending...</span>');
        $.post('/emails/ajaxSendMessage/',
            {
                subject: subject,
                toId: data['toId'],
                to: data['to'],
                message: $('.message-reply').val(),
                content: original,
                replyTo: data['replyTo']
            },
            function (data) {
                $('.message-reply').val('');
                $('#replyText').slideUp();
                $('.sendingMsg').remove();
                $this.removeClass('send-message');
                $this.addClass('replyBtn');
                $this.val('Reply');
                $this.show();
                showTopMsg('Message is successfully sent');
            }
        ).error(function (data) {
                alert('error sending message');
            });
    });

    content.on( 'click', 'form#sendMessage input#send', function(e){
        e.preventDefault();
        var form = $(this).closest('form'),
            $this = $(this);
        $this.hide();
        $this.after('<span class="btn sendingMsg">Sending...</span>');
        $.post(
            form.attr('action'),
            form.serialize(),
            function (data) {
                if (data.complete) {
                    $('.sendingMsg').remove();
                    $this.show();
                    showTopMsg('Message is successfully sent');
                } else if(data.errors) {
                    var textError = '';
                    $.each(data.errors, function (key, val) {
                        textError += key + ':' + val + '<br/>';
                    });
                    showTopMsg(textError, 'error');
                }
            }, 'json'
        );

    });

  content.on('click', '.write-message', function() {
    var data = $(this).data();
    $.ajax({
      url: '/emails/write/',
      success: function(data) {
        content.html(data);
      },
      error: function(data) {
               alert('error getting page');
             }
    });
  });

  content.on('change', 'select.message-to', function() {
    var selected = $('select.message-to :selected');
    var data = selected.data();
    $('.send-message').data({
      toId: selected.val(),
      to: data['to']
    });
  });

    content.on('change', '.select_delete_all', function() {
        var $this = $(this), ch_boxes = $('.select_delete');
        if($this.prop('checked')) {
            ch_boxes.prop('checked', true);
        }else {
            ch_boxes.prop('checked', false);
        }
    });

    content.on('click', '.invitationsCal', function(e){
        e.preventDefault();
        var type = $(this).data('type'),
            idCal = $(this).data('calId');
        $.post("/calendars/invitations/"+idCal+'/'+type, '',
            function(data) {
                if(!data.error) {
                    var msg = (type == 'accept') ? 'Now you accept part in this event' : 'You rejected the request for taking part';
                    showTopMsg(msg);
                } else {
                    showTopMsg(data.errDesc, 'error');
                }

            },
            "json"
        );
    });
//    content.on('submit', '.messages_form', function(e) {
//        e.preventDefault();
//        var ids = [];
//        $('.select_delete:checked').each(function(){
//            ids.push($(this).data('id'));
//        });
//    });

});
