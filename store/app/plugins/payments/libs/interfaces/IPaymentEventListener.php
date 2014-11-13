<?php
interface PaymentsPlugin_IPaymentEventListener
{
    function paymentEventPerformed($data);
}
