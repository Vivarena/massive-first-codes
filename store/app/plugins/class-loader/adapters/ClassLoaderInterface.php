<?php
namespace Vt\Plugin\ClassLoader;
 
interface ClassLoaderAdapter
{
    public function registerNamespace($namespace, $paths);
    public function registerNamespaces(array $namespaces);

    public function registerPrefix($prefix, $paths);
    public function registerPrefixes(array $prefixes);

    public function register($prepend = false);
}