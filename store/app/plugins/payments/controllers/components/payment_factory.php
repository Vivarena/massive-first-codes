<?php
class PaymentFactoryComponent extends Object
{
    public function create($gatewayName, $requestType = 'curl')
    {
        return PaymentsPlugin_PaymentFactory::create($gatewayName, $requestType);
    }
}