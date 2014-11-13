<?php
/**
 * Routes Configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

    Router::parseExtensions('html', 'php', 'rss');

    App::import('Lib', 'MegaRouter');
    new MegaRouter();

    Router::connect('/', array('controller' => 'pages', 'action' => 'index'));

    # conferences
    Router::connect('/conferences', array('controller' => 'events'));
    Router::connect('/conferences/:year/:month/:title', array('controller' => 'events', 'action'=> 'view'));



    # static pages
    Router::connect(
        '/:key',
        array(
            'controller' => 'pages',
            'action' => 'display'
        ),
        array(
            'key' => 'contact-us|privacy|terms|about-us|advertise',
            'pass' => array(
                'static_page', 'page'
            )
        )
    );


    Router::connect('/getFeeds/*', array('controller' => 'community', 'action'=>'AjaxGetFeeds'));

    # emails
    Router::connect('/community/chat', array('controller' => 'emails', 'action'=>'write'));
    Router::connect('/messages/inbox', array('controller' => 'emails', 'action' => 'inbox'));
    Router::connect('/messages/sent', array('controller' => 'emails', 'action' => 'outbox'));
    Router::connect('/messages/writeTo', array('controller' => 'emails', 'action' => 'writeTo'));
    Router::connect('/messages/write/*', array('controller' => 'emails', 'action' => 'write'));
    Router::connect('/messages/view/*', array('controller' => 'emails', 'action' => 'view'));
    Router::connect('/messages/*', array('controller' => 'emails', 'action' => 'inbox'));

    # community
    Router::connect('/community', array('controller' => 'community'));
    Router::connect('/community/search', array('controller' => 'community', 'action'=>'search'));
    Router::connect('/community/chat', array('controller' => 'emails', 'action'=>'write'));
    Router::connect('/community/post/*', array('controller' => 'community', 'action'=>'view_post'));
    Router::connect('/profile/feed/*', array('controller' => 'community', 'action' => 'feed'));
    Router::connect('/profile-:id/suggestions', array('controller' => 'community', 'action' => 'suggestions'), array('pass' => array('id')));
    Router::connect('/suggestions/*', array('controller' => 'community', 'action' => 'my_suggestions'));
    Router::connect('/profile/requests/*', array('controller' => 'community', 'action' => 'my_requests'));
    Router::connect('/profile/suggestions', array('controller' => 'community', 'action' => 'my_suggestions'));
    Router::connect('/profile/suggest', array('controller' => 'community', 'action' => 'suggest'));
    Router::connect('/profile/polls/*', array('controller' => 'community', 'action' => 'polls'));
    Router::connect('/profile/getSuggestions', array('controller' => 'community', 'action' => 'getSuggestions'));
    #Router::connect('/profile/edit/*', array('controller' => 'community', 'action' => 'edit_profile'));
    Router::connect('/profile', array('controller' => 'community', 'action' => 'feed'));
    Router::connect('/profile-:id/contacts', array('controller' => 'community', 'action' => 'contacts'), array('pass' => array('id')));
    Router::connect('/profile-:id/page/:user_page', array('controller' => 'pages', 'action' => 'user_page'), array('pass' => array('id', 'user_page')));
    Router::connect('/profile-:id/activity', array('controller' => 'community', 'action' => 'activity'), array('pass' => array('id')));
    Router::connect('/profile-:id/*', array('controller' => 'community', 'action' => 'profile'), array('pass' => array('id')));
    Router::connect('/profile/:id', array('controller' => 'community', 'action' => 'profile'), array('id' => '[0-9]+', 'pass' => array('id')));
    Router::connect('/vote', array('controller' => 'community', 'action' => 'vote'));
    Router::connect('/profile/network', array('controller' => 'community', 'action' => 'network'));
    # Custom pages of user
    Router::connect('/profile/pages', array('controller' => 'pages', 'action' => 'list_pages'));
    Router::connect('/profile/create-page', array('controller' => 'pages', 'action' => 'create_page'));
    Router::connect('/profile/editPage/*', array('controller' => 'pages', 'action' => 'edit_page'));
    Router::connect('/profile/deletePage/*', array('controller' => 'pages', 'action' => 'delete_page'));
    Router::connect('/profile/edit-photo', array('controller' => 'users', 'action' => 'edit_photo'));

    # users
    Router::connect('/registration', array('controller' => 'users', 'action' => 'registration'));
    Router::connect('/forgotten-password/*', array('controller' => 'users', 'action' => 'forgot_pass'));
    Router::connect('/reset-password/*', array('controller' => 'users', 'action' => 'forgot_pass'));
    Router::connect('/profile/edit', array('controller' => 'users', 'action' => 'edit'));
    Router::connect('/join-us/*', array('controller' => 'users', 'action' => 'join_us'));
    Router::connect('/available/:slug', array('controller' => 'users', 'action' => 'available'));

    # Shop - used products
    Router::connect('/shop/used', array('controller' => 'shop', 'action' => 'index','used' => true));
    Router::connect('/shop/used/by-category/*', array('controller' => 'shop', 'action' => 'category','used' => true));
    Router::connect('/shop/used/page/*', array('controller' => 'shop', 'action' => 'page','used' => true));

    # Shop - services
    Router::connect('/shop/services', array('controller' => 'shop', 'action' => 'index','services' => true));
    Router::connect('/shop/services/by-category/*', array('controller' => 'shop', 'action' => 'category','services' => true));
    Router::connect('/shop/services/page/*', array('controller' => 'shop', 'action' => 'page','services' => true));

    # Shop - invoice
    Router::connect('/shop/invoice/*', array('controller' => 'shop', 'action' => 'invoice'));

    # User gear/sponsor review
    Router::connect('/save-review/*', array('controller' => 'sponsors', 'action' => 'save_review'));
    Router::connect('/delete-review/*', array('controller' => 'sponsors', 'action' => 'delete_review'));

    # Calendars
    Router::connect('/calendar', array('controller' => 'calendars', 'action' => 'index'));

    # Sport news
    Router::connect('/sport-news', array('controller' => 'news', 'action' => 'google_news'));

    # Events
    Router::connect('/events', array('controller' => 'events', 'action' => 'index'));

    # internal routes
    Router::connect('/admin', array('plugin' => 'admin', 'controller' => 'admin_pages'));
    Router::connect('/thumbs/*', array('plugin' => 'thumbs', 'controller' => 'thumbs'));
    Router::connect('/kcaptcha', array('plugin' => 'captcha_plugin', 'controller' => 'k_captcha'));

    # profile slugs

    Router::connect('/:login/friends', array('controller' => 'community', 'action' => 'contacts'));
    Router::connect('/:login/activity', array('controller' => 'community', 'action' => 'activity'));

    # view sponsors
    Router::connect('/:login/gears/view/*', array('controller' => 'sponsors', 'action' => 'view', 'gear'));
    Router::connect('/:login/sponsors/view/*', array('controller' => 'sponsors', 'action' => 'view', 'sponsor'));
    # edit sponsors
    Router::connect('/:login/gears/edit/*', array('controller' => 'sponsors', 'action' => 'edit', 'gear'));
    Router::connect('/:login/sponsors/edit/*', array('controller' => 'sponsors', 'action' => 'edit', 'sponsor'));

    Router::connect('/:login/sponsors', array('controller' => 'sponsors', 'action' => 'index', 'sponsor'));
    Router::connect('/:login/gears', array('controller' => 'sponsors', 'action' => 'index', 'gear'));



    Router::connect('/:login/albums/:type/:id', array('controller' => 'albums', 'action' => 'view'), array('login', 'type', 'id'));
    Router::connect('/:login/albums/photos', array('controller' => 'albums', 'action' => 'index', 'photos'));
    Router::connect('/:login/albums/videos', array('controller' => 'albums', 'action' => 'index', 'videos'));

//    Router::connect('/:login/page/:user_page', array('controller' => 'pages', 'action' => 'user_page'), array('pass' => array('login', 'user_page')));
    Router::connect('/:login', array('controller' => 'community', 'action' => 'profile'));



    /**
     * Here, we are connecting '/' (base path) to controller called 'Pages',
     * its action called 'display', and we pass a param to select the view file
     * to use 
     */
    #Router::connect('/:key', array('controller' => 'pages', 'action' => 'display'), array('key' => '[-_a-zA-Z0-9]+'));
