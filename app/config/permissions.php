<?php

$config = array(
    'AuthPermissions' => array(
        'publicActions' => array(
            'Pages' => array('display', 'index', 'faq', 'investments', 'privacy', 'terms', 'about', 'careers', 'contact'),

            'Users' => array('login', 'ajax_register', 'forgot_pass', 'share_this',
                             'getInversionesUsers', 'addUserToNetwork', 'linkedInFriends', 'sendLinkedInvite',
                             'logout', 'available', 'signup', 'fbProcess', 'linkedInProcess'),
            'Facebook' => '*',
            'LinkedIn' => '*',
            'Twitter' => '*'
        ),
        'commonAuthAccess' => array(
            'Events' => "*",
            'Pages' => '*',
            'Users' => '*',
            'Community' => '*',
            'Emails' => '*',
            'Events' => '*',
            'Posts' => '*',
            'Twitter' => '*',
            'Albums' => '*',
            'Sponsors' => '*',
            'Shop' => '*',
            'News' => '*',
            'Calendars' => '*'
        ),
        'authGroups' => array(
            'user' => array(
                'group_id' => 2,
                'accesses' => array()
            )
        )
    )
);
