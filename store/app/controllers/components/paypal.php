<?php 
// 
//  Paypal.php
//  CakePHP 1.3 component for paypal website payments pro
//  PayPal Express and Direct Payments
//  Created by Mike S. VizualTech
//

class PaypalComponent extends Object {

    public $components = array('Session', 'Cart', 'Auth');

	// Live v Sandbox mode !important TODO: set to false before load to server
	public $sandboxMode = false;

    /*public $config = array(
		'webscr' => 'https://www.paypal.com/webscr/',
		'endpoint' => 'https://api-3t.paypal.com/nvp', // https://api.paypal.com/nvp/',
		'password' => 'PHXQNSMGUGFNXWYD',
		'email' => 'rihernanp_api1.gmail.com',
		'signature' => 'A.qaZqZcH9zv4byR5uwmxKHWcIjuAtMBZo90IArr.6QgwP7JplPMPQS-',
        'returnUrl' => 'http://vivarena.project-release.info/shop/thank-you',
        'cancelUrl' => 'http://vivarena.project-release.info/shop/cart'
	);*/

	// Live paypal API config
	public $config = array(
		'webscr' => 'https://www.paypal.com/webscr/',
		'endpoint' => 'https://api-3t.paypal.com/nvp', // https://api.paypal.com/nvp/',
		'password' => 'HUBTR7TNYSZCTYH5',
		'email' => 'payment_api1.vivarena.com',
		'signature' => 'A53MkFS2GA0vV4NXKlU4vbSI9w19ACAuimBjP9zaJpSyYvORO-g5IKPF',
        'vivarenaPayPalAccEmail' => 'payment@vivarena.com'
	);

	// Sandbox paypal API config
	public $sandboxConfig = array(
		'webscr' => 'https://www.sandbox.paypal.com/cgi-bin/webscr/',
		'endpoint' => 'https://api-3t.sandbox.paypal.com/nvp',
		'password' => '1356085668',
		'email' => 'feitos_1356085649_biz_api1.mail.ru',
		'signature' => 'An5ns1Kso7MWUdW4ErQKJJJ4qi4-A8iApSJ1SaE-.8P7No9Kes67cIRG',
        'vivarenaPayPalAccEmail' => 'vivadev@vizualtech.com'
	);

	// API version
	public $apiVersion = '53.0';

	// Default Currency code
	public $currencyCode = 'USD';

	//The amount of the transaction For example, EUR 2.000,00 must be specified as 2000.00 or 2,000.00.	
	public $amount = null;
	
	// Customise Express checkout with a description (api version > 53)
	public $itemName = '';
	
	// Customise Express checkout with a description (api version > 53)
	public $orderDesc = '';
	
	// optional quantity
	public $quantity = 1;
	
	// The token returned from payapl and used in subsequesnt reuqest
	public $token = null;
	
	// The payers paypal ID 
	public $payerId = null;
	
	// Credit card details
  	public $creditCardNumber = '';
	public $creditCardType = '';
	public $creditCardExpires = '';
	public $creditCardCvv = '';
	
	// Customer details
	public $customerSalutation = '';
	public $customerFirstName = '';
	public $customerMiddleName = '';
	public $customerLastName = '';
	public $customerSuffix = '';
	
	// Billing details
	public $billingAddress1 = '';
	public $billingAddress2 = '';
	public $billingCity = '';
	public $billingState = '';
	public $billingCountryCode = '';
	public $billingZip = '';
    public $billingEmail = '';
    public $billingDiscount = '';
    public $phone = '';
    public $shippingState = '';
    public $shippingCountry = '';

	// Users IP address
	public $ipAddress = '';
	
	// controller reference
	protected $_controller = null;

    public $allProducts = array();
    public $taxAmt;
    public $shippingAmt;
    public $handlingTotal;
    public $itemAmt;


    public $billingShippingInfo;

    public $hostSite;

	/**
	 * Start up, gets an instance on the controller class (needed for redirect) sets 
	 * the config (live or sandbox) and sets the users IP 
	 *
	 * @return void
	 **/

    public function startup(&$controller)
    {
        $this->_controller = $controller;
        $this->hostSite = str_replace("www.","", env('HTTP_HOST'));

        $this->ipAddress = $_SERVER['REMOTE_ADDR'];
        if($this->sandboxMode) {
            $this->config = $this->sandboxConfig;
        }

        $this->config['returnUrl'] = 'http://'.str_replace("store.","", $this->hostSite).'/shop/thank-you';
        $this->config['cancelUrl'] = 'http://'.str_replace("store.","", $this->hostSite).'/shop/cart';

    }
	
	
	/**
	 * Generated a fresh token and redirects the use to the paypal page
	 *
	 **/
	public function expressCheckout($fromCart = false) {

		// We dont have a valid amount	
		if(!isset($this->amount) || empty($this->amount) || !is_numeric($this->amount)) {
            $token['error'] = 'Invalid amount - must be numeric in the format 1234.00';
            return $token;
			//throw new Exception(__('Invalid amount - must be numeric in the format 1234.00'));
		}
		
		// Call the SetExpressCheckout method to get a fresh token
		if ($fromCart) {
            $token = $this->setExpressCheckoutParallel();
        } else {
            $token = $this->setExpressCheckout();
        }

		
		// We have a token, redirect to paypals web server (not the URL is different to the API endpoint)
		if(array_key_exists('TOKEN', $token) && $token['TOKEN']) {
            $billingShipping = $this->Session->read("BillingShipping");
            if ($fromCart) {
                $billingShipping['PayPal']['parallels_payments_details'] = $token['parallels_payments_details'];
                $billingShipping['PayPal']['taxTotal'] = $token['taxTotal'];
            }
            $token = $token['TOKEN'];
            $order = ClassRegistry::init('Order');
            $billingShipping['Shipping']['state'] = $this->shippingState;
            $billingShipping['Shipping']['country'] = $this->shippingCountry;
            $billingShipping['PayPal']['debug'] = (int)$this->sandboxMode;
            $billingShipping['PayPal']['TOKEN'] = $token;
            $billingShipping['userID'] = ($this->Auth->user('id')) ? $this->Auth->user('id') : '';
            if ($order->orderSave($billingShipping)) {
//                $this->_controller->redirect($this->config['webscr'].'?cmd=_express-checkout&token='.$token);
                // return URL (don't redirect if use as API)
                return $this->config['webscr'].'?cmd=_express-checkout&token='.$token;
            } else {
                $this->log($token , 'paypal');
            }

		} else {
			$this->log($token , 'paypal');

			//throw new Exception(__('The was a problem with the payment gateway'));
		}
        return $token;
	}

    public function ShortExpressCheckout() {

        $this->hostSite = str_replace("store.","", $this->hostSite);

        // We dont have a valid amount
        if(!isset($this->amount) || empty($this->amount) || !is_numeric($this->amount)) {
            $token['error'] = 'Invalid amount - must be numeric in the format 1234.00';
            return $token;
            //throw new Exception(__('Invalid amount - must be numeric in the format 1234.00'));
        }

        // Call the SetExpressCheckout method to get a fresh token
        $token = $this->setExpressCheckout(array(
            'returnUrl' => 'http://'.$this->hostSite.'/profile/products?success',
            'cancelUrl' => 'http://'.$this->hostSite.'/profile/products?return'
        ));

        // We have a token, redirect to paypals web server (not the URL is different to the API endpoint)
        if(array_key_exists('TOKEN', $token) && $token['TOKEN']) {
            $token = $token['TOKEN'];
            return $this->config['webscr'].'?cmd=_express-checkout&token='.$token;
        } else {
            $this->log($token , 'paypal');

            //throw new Exception(__('The was a problem with the payment gateway'));
        }
        return $token;
    }

    public function ShortRepostExpressCheckout() {

        $this->hostSite = str_replace("store.","", $this->hostSite);

        // We dont have a valid amount
        if(!isset($this->amount) || empty($this->amount) || !is_numeric($this->amount)) {
            $token['error'] = 'Invalid amount - must be numeric in the format 1234.00';
            return $token;
            //throw new Exception(__('Invalid amount - must be numeric in the format 1234.00'));
        }

        // Call the SetExpressCheckout method to get a fresh token
        $token = $this->setExpressCheckout(array(
            'returnUrl' => 'http://'.$this->hostSite.'/profile/complete-repost-product?success',
            'cancelUrl' => 'http://'.$this->hostSite.'/profile/complete-repost-product?return'
        ));

        // We have a token, redirect to paypals web server (not the URL is different to the API endpoint)
        if(array_key_exists('TOKEN', $token) && $token['TOKEN']) {
            $token = $token['TOKEN'];
            return $this->config['webscr'].'?cmd=_express-checkout&token='.$token;
        } else {
            $this->log($token , 'paypal');

            //throw new Exception(__('The was a problem with the payment gateway'));
        }
        return $token;
    }
	
	
	/**
	 * To set up an Express Checkout transaction, you must invoke the SetExpressCheckout API 
	 * operation to provide sufficient information to initiate the payment flow and redirect 
	 * to PayPal if the operation was successful with the token sent back from Paypal
	 *
	 * @return string $token A token to be used when redirecting the user to PayPal
	 * @author Rob Mcvey
	 **/

    public function setExpressCheckoutParallel() {

        // Build the NVPs (Named value pairs)
        $getUniqueEmails = array_unique(Set::extract('{n}.pay_pal_acc', $this->allProducts));

        $i = 0;
        $setExpressCheckoutNvp = array();
        $parallelsPaymentsDetails = array();
        $taxTotal = 0;
        foreach ($getUniqueEmails as $emailAcc) {
            $getProductByAcc = Set::extract('/.[pay_pal_acc='.$emailAcc.']/..', $this->allProducts);
            $j = 0;
            $itemAmt = 0;
            $taxAmt = 0;
            $shippAmt = 0;
            foreach ($getProductByAcc as $oneProduct) {
                $this->_controller->log($oneProduct, 'oneProduct');
                $setExpressCheckoutNvp["L_PAYMENTREQUEST_{$i}_NAME{$j}"] = $oneProduct['title'];
                $setExpressCheckoutNvp["L_PAYMENTREQUEST_{$i}_QTY{$j}"] = $oneProduct['qty'];
                $setExpressCheckoutNvp["L_PAYMENTREQUEST_{$i}_AMT{$j}"] = $oneProduct['amount'];
                $setExpressCheckoutNvp["L_PAYMENTREQUEST_{$i}_TAXAMT{$j}"] = $oneProduct['tax'];
                $setExpressCheckoutNvp["L_PAYMENTREQUEST_{$i}_DESC{$j}"] = $oneProduct['desc'];
                $shippAmt += $oneProduct['shipping'];
                $itemAmt += $oneProduct['qty'] * $oneProduct['amount'];
                $taxAmt += $oneProduct['tax'] * $oneProduct['qty'];
                $j++;
            }
            $paymentRequestId = uniqid();
            $parallelsPaymentsDetails[$i]['amt'] = $itemAmt + $taxAmt;
            $parallelsPaymentsDetails[$i]['paymentReqId'] = $paymentRequestId;
            $parallelsPaymentsDetails[$i]['emailAcc'] = $emailAcc;

            $setExpressCheckoutNvp["PAYMENTREQUEST_{$i}_CURRENCYCODE"] = $this->currencyCode;
            $setExpressCheckoutNvp["PAYMENTREQUEST_{$i}_AMT"] = $itemAmt + $taxAmt + $shippAmt;
            $setExpressCheckoutNvp["PAYMENTREQUEST_{$i}_ITEMAMT"] = $itemAmt;
            $setExpressCheckoutNvp["PAYMENTREQUEST_{$i}_TAXAMT"] = $taxAmt;
            $setExpressCheckoutNvp["PAYMENTREQUEST_{$i}_PAYMENTACTION"] = 'Order';
            $setExpressCheckoutNvp["PAYMENTREQUEST_{$i}_DESC"] = 'Products';
            $setExpressCheckoutNvp["PAYMENTREQUEST_{$i}_SELLERPAYPALACCOUNTID"] = $emailAcc;
            $setExpressCheckoutNvp["PAYMENTREQUEST_{$i}_PAYMENTREQUESTID"] = $paymentRequestId;

            $setExpressCheckoutNvp["PAYMENTREQUEST_{$i}_SHIPPINGAMT"] = $shippAmt;

            $taxTotal += $taxAmt;
            $i++;
        }

        $setExpressCheckoutNvp['USER'] = $this->config['email'];
        $setExpressCheckoutNvp['PWD'] = $this->config['password'];
        $setExpressCheckoutNvp['SIGNATURE'] = $this->config['signature'];
        $setExpressCheckoutNvp['METHOD'] = 'SetExpressCheckout';
        $setExpressCheckoutNvp['RETURNURL'] = $this->config['returnUrl'];
        $setExpressCheckoutNvp['CANCELURL'] = $this->config['cancelUrl'];
        $setExpressCheckoutNvp['VERSION'] = 93;
        $this->log($setExpressCheckoutNvp, 'setExpressCheckoutParallel');
        $result = $this->_sendPostToPayPal($setExpressCheckoutNvp);

        if (!isset($result['error'])) {
            $result['parallels_payments_details'] = $parallelsPaymentsDetails;
            $result['taxTotal'] = $taxTotal;
        }

        $this->log($result, 'FromPayPal');
        return $result;
    }


	public function setExpressCheckout($customOptions = array()) {
		// Build the NVPs (Named value pairs)
		$setExpressCheckoutNvp = array(
			'METHOD' => 'SetExpressCheckout',
			'VERSION' => $this->apiVersion,
			'USER' => $this->config['email'],
            'EMAIL' => $this->billingEmail,
            'BUYEREMAILOPTINENABLE' => 1,
			'PWD' => $this->config['password'],									
			'SIGNATURE' => $this->config['signature'],
			'CURRENCYCODE' => $this->currencyCode,
			'RETURNURL' => (isset($customOptions['returnUrl'])) ? $customOptions['returnUrl'] : $this->config['returnUrl'],
			'CANCELURL' => (isset($customOptions['cancelUrl'])) ? $customOptions['cancelUrl'] : $this->config['cancelUrl'],
			'PAYMENTACTION' => 'ORDER',
			'PAGESTYLE' => 'VIVARENA',
			'AMT' => number_format($this->amount, 2),
            'ITEMAMT' => number_format($this->itemAmt, 2),
            'SHIPPINGAMT' => number_format($this->shippingAmt, 2),
            'TAXAMT' => number_format($this->taxAmt, 2),
            'SOLUTIONTYPE' => 'Sole',
            'LANDINGPAGE' => 'Billing',
            'ADDRESSOVERRIDE' => 1,
            'SHIPTONAME' => $this->customerFirstName.' '.$this->customerLastName,
            'SHIPTOSTREET' => $this->billingAddress1,
            'SHIPTOCITY' => $this->billingCity,
            'SHIPTOSTATE' => $this->billingState,
            'SHIPTOCOUNTRYCODE' => $this->billingCountryCode,
            'SHIPTOZIP' => $this->billingZip,
            'SHIPTOPHONENUM' => $this->phone,
            'SHIPPINGDISCAMT' => number_format(($this->billingDiscount*-1), 2)
		);
        $forPayPalProduct = array();
        $i = 0;
        foreach ($this->allProducts as $item)
        {
            $forPayPalProduct['L_NAME'.$i] = 'Add Product - '.$item['title'];
            $forPayPalProduct['L_DESC'.$i] = 'Payment form adding product.';
            $forPayPalProduct['L_AMT'.$i] = number_format((number_format($item['amount'], 2)-number_format($item['discount'], 2)), 2);
            $forPayPalProduct['L_QTY'.$i] = $item['qty'];
            $forPayPalProduct['PAYMENTREQUEST_'.$i.'_SHIPDISCAMT'] = number_format(($item['discount']*-1), 2);
            $i++;
        }
        $setExpressCheckoutNvp = array_merge($setExpressCheckoutNvp, $forPayPalProduct);

		// HTTPSocket class
        App::import('Core', 'HttpSocket');
		$httpSocket = new HttpSocket();	

		// Post the NVPs to the relevent endpoint
		$response = $httpSocket->post($this->config['endpoint'] , $setExpressCheckoutNvp);
		
		// Parse the guff that comes back from paypal
		parse_str($response, $parsed);
		$result = array();

		if(array_key_exists('TOKEN', $parsed) && array_key_exists('ACK', $parsed) && $parsed['ACK'] == 'Success') {
            $result['TOKEN'] = $parsed['TOKEN'];
		}
		elseif(array_key_exists('ACK', $parsed) && array_key_exists('L_LONGMESSAGE0', $parsed) && $parsed['ACK'] != 'Success') {
            $result['error'] = $parsed['ACK'] . ' : ' . $parsed['L_LONGMESSAGE0'];
		}
		elseif(array_key_exists('ACK', $parsed) && array_key_exists('L_ERRORCODE0', $parsed) && $parsed['ACK'] != 'Success') {
            $result['error'] = $parsed['ACK'] . ' : ' . $parsed['L_ERRORCODE0'];
		}
		else {
            $result['error'] = 'There is a problem with the payment gateway. Please try again later.';
		}
        $this->log($parsed , 'paypal');
        return $result;
	}
	
	
	/**
	 * To obtain details about an Express Checkout transaction, you can invoke the 
	 * GetExpressCheckoutDetails API operation. 
	 * 
	 * @return array $parsed An array of fields with the customers details, or throws and exception
	 * @author Rob Mcvey
	 **/
	public function getExpressCheckoutDetails() {
			
		// Build the NVPs (Named value pairs)	
		$getExpressCheckoutDetailsNvp = array(
			'METHOD' => 'GetExpressCheckoutDetails' , 
			'TOKEN' => $this->token,
			'VERSION' => $this->apiVersion,
			'USER' => $this->config['email'],
			'PWD' => $this->config['password'],									
			'SIGNATURE' => $this->config['signature'],
		);

        return $this->_sendPostToPayPal($getExpressCheckoutDetailsNvp);

	}
	
	
	/**
	 * To complete an Express Checkout transaction, you must invoke the 
	 * DoExpressCheckoutPayment API operation.
	 *
	 * @return array $parsed An array of fields with the payment info, or throws and exception
	 * @author Rob Mcvey
	 **/
	public function doExpressCheckoutPayment() {
		
		// Build the NVPs (Named value pairs)	
		$doExpressCheckoutPaymentNvp = array(
			'METHOD' => 'DoExpressCheckoutPayment' ,
			'USER' => $this->config['email'],
			'PWD' => $this->config['password'],									
			'SIGNATURE' => $this->config['signature'],
			'VERSION' => $this->apiVersion,
			'TOKEN' => $this->token,
			'PAYERID' => $this->payerId,	
			'PAYMENTACTION' => 'Sale',
			'CURRENCYCODE' => $this->currencyCode,
			'AMT'=> $this->amount													
		);
		
        return $this->_sendPostToPayPal($doExpressCheckoutPaymentNvp);
	}

	public function doExpressCheckoutParallelsPayment($parallelsDetails) {

		// Build the NVPs (Named value pairs)
        $parallelsDetails = json_decode($parallelsDetails, true);
        if (is_array($parallelsDetails) && !empty($parallelsDetails)) {
            $i = 0;
            foreach($parallelsDetails as $onePayment) {
                $doExpressCheckoutPaymentNvp["PAYMENTREQUEST_{$i}_AMT"] = $onePayment['amt'];
                $doExpressCheckoutPaymentNvp["PAYMENTREQUEST_{$i}_CURRENCYCODE"] = $this->currencyCode;
                $doExpressCheckoutPaymentNvp["PAYMENTREQUEST_{$i}_SELLERPAYPALACCOUNTID"] = $onePayment['emailAcc'];
                $doExpressCheckoutPaymentNvp["PAYMENTREQUEST_{$i}_PAYMENTREQUESTID"] = $onePayment['paymentReqId'];
                $i++;
            }
            $doExpressCheckoutPaymentNvp['USER'] = $this->config['email'];
            $doExpressCheckoutPaymentNvp['PWD'] = $this->config['password'];
            $doExpressCheckoutPaymentNvp['SIGNATURE'] = $this->config['signature'];
            $doExpressCheckoutPaymentNvp['METHOD'] = 'DoExpressCheckoutPayment';
            $doExpressCheckoutPaymentNvp['VERSION'] = 93;
            $doExpressCheckoutPaymentNvp['TOKEN'] = $this->token;
            $doExpressCheckoutPaymentNvp['PAYERID'] = $this->payerId;

            $result = $this->_sendPostToPayPal($doExpressCheckoutPaymentNvp);
            $countPayments = count($parallelsDetails);
            $success = 0;
            for ($i = 0; $i < $countPayments; $i++) {
                if (isset($result["PAYMENTINFO_{$i}_PAYMENTSTATUS"]) && $result["PAYMENTINFO_{$i}_PAYMENTSTATUS"] == 'Completed') $success++;
            }
            if ($success == $countPayments) $result['PAYMENTSTATUS'] = 'Completed';
            return $result;
        }

        return array('error' => 'No parallels payment!');

	}

    private function _sendPostToPayPal($nvpValues) {

        // HTTPSocket class
        App::import('Core', 'HttpSocket');
        $httpSocket = new HttpSocket();

        // Post the NVPs to the relevent endpoint
        $response = $httpSocket->post($this->config['endpoint'] , $nvpValues);

        // Parse the guff that comes back from paypal
        parse_str($response , $parsed);
        $result = array();

        // Return the token, or throw a human readable error
        if(array_key_exists('TOKEN', $parsed) && array_key_exists('ACK', $parsed) && $parsed['ACK'] == 'Success') {
            $result = $parsed;
        }
        elseif(array_key_exists('ACK', $parsed) && array_key_exists('L_LONGMESSAGE0', $parsed) && $parsed['ACK'] != 'Success') {
            $result['error'] = $parsed['ACK'] . ' : ' . $parsed['L_LONGMESSAGE0'];
        }
        elseif(array_key_exists('ACK', $parsed) && array_key_exists('L_ERRORCODE0', $parsed) && $parsed['ACK'] != 'Success') {
            $result['error'] = $parsed['ACK'] . ' : ' . $parsed['L_LONGMESSAGE0'];
        }
        else {
            $result['error'] = 'There is a problem with the payment gateway. Please try again later.';
        }
        $this->log($parsed , 'sendPostToPayPal');
        return $result;
    }


	/**
	 * The DoDirectPayment API Operation enables you to process a credit card payment.
	 *
	 * @return array $parsed An array of fields with the payment info, or throws and exception
	 * @author Rob Mcvey
	 **/
	public function doDirectPayment() {
			
		// Build the NVPs (Named value pairs)	
		$doDirectPaymentNvp = array(
		    'METHOD' => 'DoDirectPayment',
		    'PAYMENTACTION' => 'SALE',
		    'VERSION' => $this->apiVersion,
		    'AMT' => $this->amount,
		    'CURRENCYCODE' => $this->currencyCode,
		    'IPADDRESS' => $this->ipAddress,
		    'USER' => $this->config['email'],
			'PWD' => $this->config['password'],									
			'SIGNATURE' => $this->config['signature'],
			
		    // Credit Card Details
		    'CREDITCARDTYPE' => $this->creditCardType,
		    'ACCT' => $this->creditCardNumber,
		    'EXPDATE' => $this->creditCardExpires,
		    'CVV2' => $this->creditCardCvv,
		    
		    // Customer Details
		    'SALUTATION' => $this->customerSalutation,
		    'FIRSTNAME' => $this->customerFirstName,
		    'MIDDLENAME' => $this->customerMiddleName,
		    'LASTNAME' => $this->customerLastName,
		    'SUFFIX' => $this->customerSuffix,
		    
		    // Billing Address
		    'STREET' => $this->billingAddress1,
		    'STREET2' => $this->billingAddress2,
		    'CITY' => $this->billingCity,
		    'STATE' => $this->billingState,
		    'COUNTRYCODE' => $this->billingCountryCode,
		    'ZIP' => $this->billingZip,
		);	
		
		$this->log($doDirectPaymentNvp);
		

        $response = $this->send_post($this->config['endpoint'], $doDirectPaymentNvp);
		// Parse the guff that comes back from paypal
		parse_str($response , $parsed);
		
		// Return the token, or throw a human readable error
		if(array_key_exists('ACK', $parsed)) {
			return $parsed;
		} else {
			$this->log($parsed , 'paypal');
            $parsed['ERROR_TEXT'] = 'There is a problem with the payment gateway. Please try again later';
			//throw new Exception(__('There is a problem with the payment gateway. Please try again later.'));
		}

        return $parsed;
				
	}

    function send_post($post_url, $post_data)
    {

        $ch = curl_init($post_url);
        curl_setopt ($ch, CURLOPT_HEADER, 1);
        curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
        curl_setopt ($ch, CURLOPT_REFERER, $post_url);
        curl_setopt ($ch, CURLOPT_POST, 1);
        //curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_exec ($ch);
        $result = curl_multi_getcontent ($ch);

        curl_close ($ch);

        return $result;
    }

}
