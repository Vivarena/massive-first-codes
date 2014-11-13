<?php

App::import('Lib', 'class-loader.ClassLoader');
App::import('lib', 'cakephp-twig.autoloader');
CakePhpTwig_Autoloader::register();

/**
 * @var $loader Vt\Plugin\ClassLoader\ClassLoaderAdapter
 */
$loader = Vt\Plugin\ClassLoader\ClassLoader::getInstance();

$loader->registerNamespace('Monolog', APP . 'vendors');

$loader->register();