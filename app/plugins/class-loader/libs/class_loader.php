<?php
namespace Vt\Plugin\ClassLoader;

require_once __DIR__ . '/../adapters/ClassLoaderInterface.php';
require_once __DIR__ . '/../adapters/Sf2ClassLoaderAdapter.php';

require_once __DIR__ . '/../vendors/Sf2ClassLoader/UniversalClassLoader.php';

class ClassLoader
{
    private static $instance = null;

    /**
     * @static
     * @return \Vt\Plugin\ClassLoader\ClassLoaderAdapter
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Sf2ClassLoaderAdapter();
        }

        return self::$instance;
    }
}