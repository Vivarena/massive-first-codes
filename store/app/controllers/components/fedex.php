<?php

/**
 * Fedex component class
 * @author Nike
 *
 * @property Region           $Region
 * @property Country          $Country
 * @property CartComponent    $Cart
 * @property SessionComponent $Session
 *
 */
class FedexComponent extends Object {

    public $components = array("Cart", "Session");

    /**
     * Authentication Key
     * @var
     */
    private $authKey = "0ZkmtifI1bJpKaUV";

    /**
     * Production Meter Number
     * @var
     */
    private $meter = "103414856";

    /**
     * Production Password
     * @var
     */
    private $password = "KVUjH07SgdCpEgGfzDDlV9Nif";

    /**
     * FedEx Account Number
     * @var
     */
    private $shippAccount = "310146579";

    /**
     * Sender address
     * @var array
     */
    private $senderAddress = array(
        'StreetLines' => array('4400 Route 9 South Suite 1000'),
        'City' => 'Freehold',
        'StateOrProvinceCode' => 'NJ',
        'PostalCode' => '07728',
        'CountryCode' => 'US'
    );

    /**
     * @var array
     */
    private $recipientAddress = array();

    /**
     * FedEx Payer Number
     * @var
     */
    private $billingAccount;

    /**
     * Array of products to shipping
     * @var array
     */
    private $products = array();


    /**
     * called before Controller::beforeFilter()
     * @param  $controller
     * @param array $settings
     * @return void
     */
    function initialize(&$controller, $settings = array())
    {
        $this->Region = ClassRegistry::init('Region');
        $this->Country = ClassRegistry::init('Country');
    }


    public function getRate() {
        //Configure::write("debug", 0);
        $this->_applyShippingAddress();
        $this->_applyProductsFromCart();

        $path_to_wsdl = ROOT . DS .  APP_DIR . DS . "xml/TrackService_v2.wsdl";
        ini_set("soap.wsdl_cache_enabled", "0");
        $client = new SoapClient($path_to_wsdl, array('trace' => 1)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information


        $request['WebAuthenticationDetail'] =
                array('UserCredential' => array(
                        'Key' => $this->authKey,
                        'Password' => $this->password
                    )
                );
        $request['ClientDetail'] =
                array(
                    'AccountNumber' => $this->shippAccount,
                    'MeterNumber' => $this->meter
                );
        $request['TransactionDetail'] =
                array(
                    'CustomerTransactionId' => ' *** Rate Available Services Request v10 using PHP ***'
                );
        $request['Version'] =
                array(
                    'ServiceId' => 'crs',
                    'Major' => '10',
                    'Intermediate' => '0',
                    'Minor' => '0'
                );
        $request['ReturnTransitAndCommit'] = true;
        $request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
        $request['RequestedShipment']['ShipTimestamp'] = date('c');

        // Service Type and Packaging Type are not passed in the request
        $request['RequestedShipment']['Shipper'] =
                array(
                    'Address'=> $this->senderAddress
                );
        $request['RequestedShipment']['Recipient'] =
                array(
                    'Address'=> $this->recipientAddress
                );
        $request['RequestedShipment']['ShippingChargesPayment'] =
                array(
                    'PaymentType' => 'SENDER',
                    'Payor' => array(
                        'AccountNumber' => $this->shippAccount, // Replace 'XXX' with payor's account number
                        'CountryCode' => 'US'
                    )
                );
        $request['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT';
        $request['RequestedShipment']['RateRequestTypes'] = 'LIST';
        $request['RequestedShipment']['PackageCount'] = count($this->products);

        foreach ($this->products as $key=>$product) {
            $request['RequestedShipment']['RequestedPackageLineItems'][$key] = array(
                'SequenceNumber' => $key + 1,
                'GroupPackageCount' => 1,
                'Weight' => array(
                    'Value' => 0.5 * $product['qty'],
                    'Units' => 'LB'
                ),
                'Dimensions' => array(
                    'Length' => 0.1 * $product['qty'],
                    'Width' => 0.1 * $product['qty'],
                    'Height' => 0.1 * $product['qty'],
                    'Units' => 'IN'
                )
            );
        }

        try {
            $response = $client ->getRates($request);
            $notifications = array();
            if(isset($response->Notifications->Code)) {
                if ($response -> HighestSeverity == 'WARNING' && $response->Notifications->Code == 556) {
                    $errors = $response->Notifications->LocalizedMessage;
                    return array(
                        "error" => array($errors)
                    );
                } else {
                    $notifications = array($response->Notifications->LocalizedMessage);
                }
            } else {
                $responses = (array)$response->Notifications;

                foreach ($responses as $object) {
                    $tmp = (array)$object;
                    $notifications[] = $tmp['Message'];
                }
                unset($object);
            }


            if ($response -> HighestSeverity == 'FAILURE' || $response -> HighestSeverity == 'ERROR') {
                $error = $response->Notifications->LocalizedMessage;
                return array(
                    "error" => $error
                );
            }

            $fakeResponse = array();
            if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR')
            {
                foreach ($response -> RateReplyDetails as $rateReply)
                {
                    if(array_key_exists('DeliveryTimestamp',$rateReply)){
                        @$deliveryDate= $rateReply->DeliveryTimestamp;
                    }else{
                        @$deliveryDate= $rateReply->TransitTime;
                    }
                    $fakeResponse[] = array(
                        'serviceType'   => $rateReply -> ServiceType,
                        'amount'        => number_format($rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount,2,".",","),
                        'deliveryDate'  => date("d/m/Y h:i:s A", strtotime($deliveryDate))
                    );
                }

            }
            return array(
                "fakeResponse"  => $fakeResponse,
                "notifications" => $notifications
            );
        } catch (SoapFault $exception) {
            $this->log($exception, "fedex_exception");
        }
        return false;
    }

    private function _applyShippingAddress() {
        $address = $this->Session->read("BillingShipping.Shipping");
        $this->recipientAddress = array(
            'StreetLines' => array($address['address1'], $address['address2']),
            'City' => $address['city'],
            'StateOrProvinceCode' => $this->Region->getIsoCode($address['state']),
            'PostalCode' => $address['zip'],
            'CountryCode' => $this->Country->getCountryIsoCode($address['country'])
        );
    }

    private function _applyProductsFromCart() {
        $products = $this->Cart->in_cart();
        foreach ($products['Products'] as $product) {
            $this->products[] = $product;
        }
    }

    function tracking_status_Fedex($tracking_number = "999999999999"){ //

        $newline = "\n";

        $path_to_wsdl = ROOT . DS .  APP_DIR . DS . "xml/TrackService_v4.wsdl";
        ini_set("soap.wsdl_cache_enabled", "0");



        $client = new SoapClient($path_to_wsdl, array('trace' => 1));

        $request['WebAuthenticationDetail'] = array('UserCredential' => array('Key' => $this->authKey, 'Password' => $this->password));

        $request['ClientDetail'] = array('AccountNumber' => $this->shippAccount, 'MeterNumber' => $this->meter);

        $request['TransactionDetail'] = array('CustomerTransactionId' => '*** Track Request v4 using PHP ***');

        $request['Version'] = array('ServiceId' => 'trck', 'Major' => '4', 'Intermediate' => '0', 'Minor' => '0');

        $request['PackageIdentifier'] = array('Value' => $tracking_number, 'Type' => 'TRACKING_NUMBER_OR_DOORTAG');

        $request['IncludeDetailedScans'] = 1;


        try{

            $response = $client ->track($request);

            if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){

                return ucwords(strtolower($response -> TrackDetails -> StatusDescription));

            }else{

                echo 'Error in processing transaction.'. $newline. $newline;

                foreach ($response -> Notifications as $notification)

                {

                    if(is_array($response -> Notifications)){

                        echo $notification -> Severity;

                        echo ': ';

                        echo $notification -> Message . $newline;

                    }else{

                        echo $notification . $newline;

                    }

                }

            }

        }catch(SoapFault $exception){

            pr($exception, $client);

        }

    }

}