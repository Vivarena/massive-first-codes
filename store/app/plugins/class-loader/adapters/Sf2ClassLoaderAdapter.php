<?php
namespace Vt\Plugin\ClassLoader;

use Symfony\Component\ClassLoader\UniversalClassLoader;
 
class Sf2ClassLoaderAdapter implements ClassLoaderAdapter
{
    private $loader;

    public function __construct()
    {
        $this->loader = new UniversalClassLoader();
    }

    public function registerNamespace($namespace, $paths)
    {
        $this->loader->registerNamespace($namespace, $paths);
    }

    public function registerNamespaces(array $namespaces)
    {
        $this->loader->registerNamespaces($namespaces);
    }

    public function registerPrefix($prefix, $paths)
    {
        $this->loader->registerPrefix($prefix, $paths);
    }

    public function registerPrefixes(array $prefixes)
    {
        $this->loader->registerPrefixes($prefixes);
    }

    public function register($prepend = false)
    {
        $this->loader->register($prepend);
    }
}
