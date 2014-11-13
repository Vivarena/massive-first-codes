<?php
/*
    Youtube Helper
    Returns embedded youtube video, related videos and video images based on video id
*/
    App::import('Helper', 'Html');
    class YoutubeHelper extends HtmlHelper {
//        public $helpers = array('Html');
        var $api_links = array(
            'image'   => 'http://i.ytimg.com/vi/%s/%s.jpg',                     // Location of youtube images
            'video'   => 'http://gdata.youtube.com/feeds/api/videos/%s',        // Location of youtube videos
            'player'  => 'http://www.youtube.com/v/%s?%s',                      // Location of youtube player
            'channel' => 'http://www.youtube.com/user/%s',                      // Location of youtube user channel
            'related' => 'http://gdata.youtube.com/feeds/api/videos/%s/related' // Location of related youtube videos
        );

        // All these settings can be changed on the fly using the $player_variables option in the video function
        var $player_variables = array(
            'type'              => 'application/x-shockwave-flash',
            'width'             => 420,  // Sets player width
            'height'            => 340,  // Sets player height
            'allowfullscreen'   => 'true', // Gives script access to fullscreen (This is required for the fs setting to work)
            'allowscriptaccess' => 'always'
        );

        // All these settings can be changed on the fly using the $player_settings option in the video function
        var $player_settings = array(
            'fs'          => true,  // Enables / Disables fullscreen playback
            'hd'          => true,  // Enables / Disables HD playback (480p, 720p (Default), 1080p)
            'egm'         => false, // Enables / Disables advanced context (Right-Click) menu
            'rel'         => false, // Enables / Disables related videos at the end of the video
            'loop'        => false, // Loops video once its finished
            'start'       => 0,     // Start the video at X seconds
            'version'     => 2,     // 3 = Chromeless Note: Chromeless player does not support the hd attribute at this time
            'autoplay'    => false, // Automatically starts video when page is loaded
            'showinfo'    => false, // Enables / Disables information like the title before the video starts playing
            'disablekb'   => false, // Enables / Disables keyboard controls
        );

        function getImage($video_url, $size = 'small', $options = array(), $alt = '') {
            $video_id = $this->getIdFromVideo($video_url);
            // Array of allowed image sizes ()
            $accepted_sizes = array(
                'small'  => 'default',
                'large'  => 0,
                'thumb1' => 1, // Alternate small image
                'thumb2' => 2, // Alternate small image
                'thumb3' => 3  // Alternate small image
            );

            // Build url to image file
            $image_url = sprintf($this->api_links['image'], $video_id, $accepted_sizes[$size]);

            // If raw is set to true in options return url only else return complete image
            if(isset($options['raw']) && $options['raw']){
                return $image_url;
            }else{
                $options['alt'] = $alt;
                return $this->image($image_url, $options);
            }
        }

        function video($video_url, $player_settings = array(), $player_variables = array()) {
            $video_id = $this->getIdFromVideo($video_url);
            // Sets flash player settings if different than default
            $this->player_settings = am($this->player_settings, $player_settings);

            // Sets flash player variables if different than default
            $this->player_variables = am($this->player_variables, $player_variables);

            // Sets src variable for a valid object
            $this->player_variables['src'] = sprintf($this->api_links['player'], $video_id, http_build_query($this->player_settings));

            // Returns embedded video

            return '<object style ="height: '.$this->player_variables['height'].'px; width: '.$this->player_variables['width'].'px" >
                    <param name = "movie" value ="'.$this->player_variables['src'].'" >
                    <param name = "allowFullScreen" value ="'.$this->player_variables['allowfullscreen'].'" >
                    <param name = "allowScriptAccess" value ="'.$this->player_variables['allowscriptaccess'].'" >
                    <embed src = "'.$this->player_variables['src'].'" type = "application/x-shockwave-flash" allowfullscreen = "'.$this->player_variables['allowfullscreen'].'" allowScriptAccess = "'.$this->player_variables['allowscriptaccess'].'" width = "'.$this->player_variables['width'].'" height ="'.$this->player_variables['height'].'" >
                    </object >';

        }

        function getIdFromVideo($video_url){
            $url = parse_url($video_url);
            if (((strtolower($url['path'])) == '/watch') && isset($url['query'])) {
                $id_url = explode('&', $url['query']);
                $id_url = substr($id_url[0], 2);
                return $id_url;
            } elseif (((strtolower($url['path'])) != '/watch') && !isset($url['query']) && ($url['path'] != '/') && !empty($url['path'])) {
                $id_url = substr($url['path'], 1);
                return $id_url;
            }
            return '';
        }

    }
?>