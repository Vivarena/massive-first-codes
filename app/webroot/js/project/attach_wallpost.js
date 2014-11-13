
/**
 *  @author Mike S. <misilakov@gmail.com>
 *
 *  Depends on: colorbox and plu_uploader
 */
function AttachWallPost(){

    var settings, fixSettings, initAttach, closeVideoAttach, closeImageAttach, bindUploader, bindClicks;
    settings = {
        wallContent: '#activityPage',
        videoDiv: '#attachVideoDiv',
        imageDiv: '#attachImageDiv',
        imageBlock: '#attachImageBlock',
        viewAlbumBtn: '.view_album',
        chooseImg: '.choose_img',
        chooseVideo: '.choose_video',
        inputVideoUrl: '#urlVideo',
        imagePlace: '#imagePlace',
        selectElement: '#getTypePost',
        attachVideoBtn: '#attachVideo',
        attachImageBtn: '#attachImageFromAlbum',
        attachVideoBtnA: '#attachVideoFromAlbum',
        cacheVideoCover: null,
        uploader: null
    };

    fixSettings = function(options){
        settings = $.extend(settings, options);
    };

    initAttach = function (options) {
        settings = $.extend(settings, options);

        bindClicks();
    };

    closeVideoAttach = function(){
        $(settings.imagePlace).slideUp().empty();
        $(settings.inputVideoUrl).val('');
        settings.cacheVideoCover = null;
        $(settings.videoDiv).slideUp();
        $(settings.imageDiv).slideUp();
    };

    closeImageAttach = function () {
        $(settings.imagePlace).slideUp().empty();
        $(settings.imageDiv).slideUp();
        $(settings.videoDiv).slideUp();
    };

    bindUploader = function(){

        settings.uploader = new plupload.Uploader({
            runtimes : 'gears,html5,flash,silverlight,browserplus',
            browse_button : 'attachImage',
            chunk_size: '3mb',
            container : 'imagePlace',
            max_file_size : '3mb',
            url : '/posts/attachImage',
            flash_swf_url : '/js/jquery/plugins/plu_uploader/plupload.flash.swf',
            filters : [
                {title : "Image files", extensions : "jpg,gif,png,jpeg"}
            ],
            multi_selection:false
        });

        settings.uploader.init();

        settings.uploader.bind('FilesAdded', function(up, files) {
            $.each(files, function(i, file) {
                $('#filelist').append(
                    '<div id="' + file.id + '" class="rowFile"><span class="fileName">' +
                    file.name + '</span> - <b>0</b>' +
                '<div class="p_bar"><div></div></div></div>');
            });
            up.start();
        });

        settings.uploader.bind('UploadProgress', function(up, file) {
            $('#' + file.id + " b").html(file.percent + "%");
            $('#' + file.id + " .p_bar div").css({
                width: file.percent + '%'
           });
        });

        settings.uploader.bind('Error', function(up, err) {
            showTopMsg(err.message + ' File: ' + err.file.name, 'error');
            up.refresh(); // Reposition Flash/Silverlight
        });

        settings.uploader.bind('FileUploaded', function(up, file, info) {
            var rowFile = $('#' + file.id),
                txtForFile = rowFile.find('b'),
                result = $.parseJSON(info.response);
            txtForFile.html("100%");
            if (!result.error) {
                var appendImagePlace;
                appendImagePlace = '<img src="/thumbs/123x92'+ result.name +'"/>';
                appendImagePlace += '<input type="hidden" name="data[AttachImage]" value="'+ result.name +'"/>';
                $(settings.imagePlace).empty();
                $(settings.imagePlace).append(appendImagePlace);
                $(settings.imagePlace).slideDown();

                $(settings.imageDiv).slideUp();

            } else showTopMsg(result.err_desc, 'error');
            rowFile.fadeOut('normal').remove();
        });
    };

    bindClicks = function () {

        $(settings.wallContent).on('click', settings.attachImageBtn+', '+settings.attachVideoBtnA, function () {
            var userName = $(this).attr('data-username');
            var type = $(this).attr('data-type');
            $.get('/albums/index/'+type+'/ajax', function(response) {
                if(response.error == false) {
                    $(settings.imageBlock).html(response.content);
                }
                $(settings.imageBlock).slideDown();
            }, 'json');
        });

        $(settings.wallContent).on('click', settings.viewAlbumBtn, function (e) {
            e.preventDefault();
            var album_id = $(this).attr('data-id');
            var type = $(this).attr('data-type');
            $(settings.imageBlock).slideUp();
            $.get('/albums/view/'+type+'/'+album_id+'/ajax', function(response) {
                if(response.error == false) {
                    $(settings.imageBlock).html(response.content);
                }
                $(settings.imageBlock).slideDown();
            }, 'json');
        });

        $(settings.wallContent).on('click', settings.chooseImg+', '+settings.chooseVideo, function (e) {
            e.preventDefault();
            var url = $(this).attr('data-url');
            var type = $(this).attr('data-type');
            if(type == 'photos') {
                $(settings.imagePlace).html('<img src="/thumbs/123x92'+url+'"><input type="hidden" value="'+url+'" name="data[AttachImage]">').show();
                $(settings.imageBlock).slideUp().html('');
                $(settings.imageDiv).slideUp();
            }
            if(type == 'videos') {
                var data = {
                    videoURL: $(this).attr('data-url')
                };
                $.post('/albums/getYoutubeCover/small/ajax', {data: data}, function(response) {
                    if(response) {
                        $(settings.inputVideoUrl).val(url);
                        $(settings.imagePlace).html(response).show();
                        $(settings.imageBlock).slideUp().html('');
                        $(settings.videoDiv).slideUp();
                    }
                });
            }
        });

        $(settings.wallContent).on('change', settings.selectElement, function () {
            var $this = $(this),
                typeSelected = $this.val();
            if (typeSelected == 'video') {
                closeImageAttach();
                $(settings.videoDiv).slideDown();
            }else if(typeSelected == 'image') {
                closeVideoAttach();
                $(settings.imageDiv).slideDown('normal', function(){
                    /*if (settings.uploader == null) {
                        bindUploader();
                    }*/
                    bindUploader();
                });
            } else {
                closeImageAttach();
                closeVideoAttach();
            }
        });

        $(settings.wallContent).on('click', settings.attachVideoBtn, function(){
            var $this = $(this),
                videoURL = $(settings.inputVideoUrl).val();
            if (videoURL != '' && settings.cacheVideoCover != videoURL) {
                var videoID = videoURL.match(/http:\/\/(?:youtu\.be\/|(?:[a-z]{2,3}\.)?youtube\.com\/watch(?:\?|#\!)v=)([\w-]{11}).*/);
                if (videoID != null) {
                    videoID = videoID[1];
                    $(settings.imagePlace).load('/albums/getYoutubeCover/small', {videoURL: videoURL}, function(){
                        $('#youtubeCoverHref').attr('href', 'http://youtube.com/embed/'+videoID);
                        $(".videoPlay").colorbox({iframe:true, innerWidth:425, innerHeight:344});
                        $(settings.inputVideoUrl).attr('value', 'http://youtube.com/embed/'+videoID);
                        $(settings.imagePlace).slideDown();
                    });
                }
                settings.cacheVideoCover = videoURL;
            } else if (settings.cacheVideoCover == videoURL) {
                $(settings.imagePlace).slideDown();
            }

        });


    };

    return {
        initAttach: initAttach,
        fixSettings: fixSettings,
        closeVideoAttach: closeVideoAttach,
        closeImageAttach: closeImageAttach
    };


}