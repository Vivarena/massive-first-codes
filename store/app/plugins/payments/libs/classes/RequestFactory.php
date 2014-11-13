<?php
class PaymentsPlugin_RequestFactory {
    public static function create($name)
    {
        $className = 'PaymentsPlugin_' . ucfirst(strtolower($name)) . 'Request';
        if(class_exists($className)) {
            return new $className();
        } else {
            return null;
        }
    }
}