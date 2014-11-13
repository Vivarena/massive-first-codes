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

/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/views/pages/home.ctp)...
 */

    Router::parseExtensions('html', 'php', 'rss', 'xml');

//	Router::connect('/', array('controller' => 'pages', 'action' => 'index'));
//	Router::connect('/', array('plugin' => 'admin', 'controller' => 'admin_products'));
    Router::connect('/api/:action/*',      array('controller' => 'api'));
    Router::connect('/paypal/*',           array('controller' => 'payments_express', 'action' => 'express_checkout'));
    Router::connect('/paypal_cart/*',      array('controller' => 'payments_express', 'action' => 'express_checkout', 'fromCart' => true));
    Router::connect('/payment',            array('controller' => 'products', 'action' => 'payment'));
    Router::connect('/invoice/*',          array('controller' => 'products', 'action' => 'invoice'));
    Router::connect('/checkout',           array('controller' => 'products', 'action' => 'checkout'));
    Router::connect('/products',           array('controller' => 'products', 'action' => 'index'));
    Router::connect('/products/used/by_category/*',    array('controller' => 'products', 'action' => 'by_category'));
    Router::connect('/products/used/*',    array('controller' => 'products', 'action' => 'index'));
    Router::connect('/product/*',          array('controller' => 'products', 'action' => 'view'));
    Router::connect('/thank-you',          array('controller' => 'products', 'action' => 'thankyou'));
    Router::connect('/shopping-cart',      array('controller' => 'products', 'action' => 'cart'));
    Router::connect('/by-category/*',      array('controller' => 'products', 'action' => 'by_category'));
    Router::connect('/new-arrivals/*',     array('controller' => 'pages', 'action' => 'new_arrivals'));

    Router::connect('/', array('plugin' => 'admin', 'controller' => 'admin_products'));

/**
 * ...and connect the rest of 'Pages' controller's urls.
 */
    Router::connect('/admin', array('plugin' => 'admin', 'controller' => 'admin_products'));
    Router::connect('/kcaptcha', array('plugin' => 'kcaptcha'));
    Router::connect('/thumbs/*', array('plugin' => 'thumbs', 'controller' => 'thumbs'));
    Router::connect('/:key', array('controller' => 'pages', 'action' => 'display'), array('key' => '[-_a-zA-Z0-9]+'));
