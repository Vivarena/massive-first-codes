<?php
class PaymentsPlugin_PaymentFactory {
    public static function create($gatewayName, $requestType = 'curl')
    {
        $className = 'PaymentsPlugin_Gw' . ucfirst($gatewayName);
        if(class_exists($className)) {
            return new $className(PaymentsPlugin_RequestFactory::create($requestType));
        } else {
            return null;
        }
    }
}