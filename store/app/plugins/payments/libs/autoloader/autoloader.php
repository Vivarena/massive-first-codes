<?php
/**
 *
 */
class PaymentsPlugin_Autoloader
{
    static public function register()
    {
        spl_autoload_register(array(new self, 'autoload'));
    }

    static public function autoload($className)
    {
        if(strpos($className, 'PaymentsPlugin_') !== 0) {
            return;
        }

        $fileName = self::getFilePathByClassName($className);
        if(file_exists($fileName)) {
            require $fileName;
        }
    }

    static private function getFilePathByClassName($className)
    {
        $libsDir = realpath(dirname(__FILE__) . DS . '..' . DS);
        $nameWithoutPrefix = str_replace('PaymentsPlugin_', '', $className);

        if(substr($nameWithoutPrefix, 0, 1) == 'I') {
            $subDir = 'interfaces';
        } else if(substr($nameWithoutPrefix, 0, 2) == 'Gw') {
            $subDir = 'gateways';
        } else if(substr($nameWithoutPrefix, 0, 2) == 'Ex') {
            $subDir = 'exceptions';
        } else {
            $subDir = 'classes';
        }

        return $libsDir . DS . $subDir . DS . $nameWithoutPrefix . '.php';
    }
}
