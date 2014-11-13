<?php
/**
* Example usuage of Paypal Component
* @property PaypalComponent $Paypal
* @property MessengerComponent $Messenger
* @property Order $Order
* @property Cart $Cart
*/

class PaymentsExpressController extends AppController {

	// Include the Payapl component
    public $uses = array("Coupon");
	public $components = array('Paypal', "Cart", "Messenger", "RequestHandler");
    public $helpers     = array('Number', 'Jquery');
  
  	// Set the values and begin paypal process
    public function express_checkout2()
    {
        var_dump($this->Paypal->getExpressCheckoutDetails());
        exit();
    }
  	public function express_checkout() {

        // if $fromCart TRUE - it's parallel payments / FALSE - it's normal payment method
        $fromCart = (isset($this->params['fromCart']) && $this->params['fromCart']) ? true : false;

        $cart = $this->Cart->in_cart(false,'',false);
        $billing = $this->Session->read("BillingShipping");

        if (!empty($billing) && !empty($cart)) {

            $this->loadModel('Country');
            $this->loadModel('Region');
            $this->loadModel('Order');
            $this->loadModel('OrderProduct');
//            if ($billing['Billing']['customRegion'] == 1) {
//                $regionNameBilling = $paypalRegion = $billing['Billing']['addonRegion'];
//            } else {
                $regionNameBilling = $this->Region->getRegionName($billing["Billing"]["state"]);
                $paypalRegion = $this->Region->getIsoCode($billing["Billing"]["state"]);
//            }
//            $regionNameShipping = ($billing['Shipping']['customRegion'] == 1) ? $billing['Shipping']['addonRegion'] : $this->Region->getRegionName($billing["Shipping"]["state"]);
            $regionNameShipping = $regionNameBilling;

            $billingInfo = array(
              "fname"     => $billing["Billing"]["first_name"],
              "lname"     => $billing["Billing"]["name"],
              "address" => "{$billing["Billing"]["address1"]} {$billing["Billing"]["address2"]}",
              "paypal_address" => $billing["Billing"]["address1"],
              "city" => $billing["Billing"]["city"],
              "state" => $regionNameBilling,
              "paypal_state" => $paypalRegion,
              "zip" => $billing["Billing"]["zip"],
              "country" => $this->Country->getCountryName($billing["Billing"]["country"]),
              "paypal_country" => $this->Country->getCountryCode($billing["Billing"]["country"]),
              "email" => $billing["Billing"]["email"],
              "phone" => $billing["Billing"]["phone"],
            );

            $description = "";
            //$this->log($cart, 'inCart');
            foreach ($cart["Products"] as $product) {
              $description .= $product["title"];
//              foreach ($product["attributes"] as $key => $value) {
//                  $description .= " $key: '$value'";
//              }
              $description .= "; ";
            }

            $this->Paypal->amount = $cart["Total"];
            $this->Paypal->shippingAmt = $cart['Shipping'];
            $this->Paypal->taxAmt = $cart['Tax'];
//            $this->Paypal->taxAmt=0;
            $this->Paypal->itemAmt = $cart['Subtotal']-$cart['Discount'];
            $this->Paypal->billingDiscount = $cart['Discount'];

            $this->Paypal->currencyCode = 'USD';
//            $this->Paypal->returnUrl = Router::url(array('action' => 'get_details'), true);
//            $this->Paypal->cancelUrl = 'http://' . env('SERVER_NAME'). '/payments_express/cancelURL';//Router::url($this->here, true);

            $i = 0;
            foreach ($cart["Products"] as $product) {
                $this->Paypal->allProducts[$i]['title'] = $product["title"];
                $description = "";
//                foreach ($product["attributes"] as $key => $value) {
//                    $description .= " $key: '$value'";
//                }
                $description .= "; ";
                $this->Paypal->allProducts[$i]['discount'] = (isset($product["discount"]['sum'])) ? $product["discount"]['sum'] : 0;
//                $this->Paypal->allProducts[$i]['desc'] = $description;
                $this->Paypal->allProducts[$i]['desc'] = $product["title"];
                if(isset($product['rprice'])) {
                    $this->Paypal->allProducts[$i]['amount'] = $product['rprice'];
                }else {
                    $this->Paypal->allProducts[$i]['amount'] = $product['price'];
                }
                $this->Paypal->allProducts[$i]['qty'] = $product['qty'];
                $this->Paypal->allProducts[$i]['tax'] = $product['tax'];
                $this->Paypal->allProducts[$i]['shipping'] = $product['shipping'];
                $this->Paypal->allProducts[$i]['pay_pal_acc'] = (!empty($product['pay_pal_acc'])) ? $product['pay_pal_acc'] : $this->Paypal->config['vivarenaPayPalAccEmail'];
                $i++;
            }
            //$this->log($this->Paypal->allProducts, 'allProducts');
            /**
             *
             * 'SHIPTONAME' => $this->customerFirstName.' '.$this->customerLastName,
             'SHIPTOSTREET' => $this->billingAddress1,
             'SHIPTOCITY' => $this->billingCity,
             'SHIPTOSTATE' => $this->billingState,
             'SHIPTOCOUNTRYCODE' => $this->billingCountryCode,
             'SHIPTOZIP' => $this->billingZip
             *
             */
            $this->Paypal->customerFirstName = $billingInfo['fname'];
            $this->Paypal->customerLastName = $billingInfo['lname'];
            $this->Paypal->billingAddress1 = $billingInfo['paypal_address'];
            $this->Paypal->billingCity = $billingInfo['city'];
            $this->Paypal->billingState = $billingInfo['paypal_state'];
            $this->Paypal->billingCountryCode = $billingInfo['paypal_country'];
            $this->Paypal->billingZip = $billingInfo['zip'];
            $this->Paypal->billingEmail = $billingInfo['email'];
            $this->Paypal->phone = $billingInfo['phone'];
            //$this->Paypal->billingShippingInfo = array('Billing' => $billingInfo, 'Shipping' => $shippingInfo);
            $this->Paypal->shippingCountry = $this->Country->getCountryName($billing["Shipping"]["country"]);
            $this->Paypal->shippingState = $regionNameShipping;

            $result = $this->Paypal->expressCheckout($fromCart);

            $this->log($result, 'returnToSiteData');

            if (is_array($result) && array_key_exists('error', $result)) {
//            if (isset($result['error']) && !empty($result['error'])) {
                $this->log($result, 'fuckError');
                // for API
                exit('<?xml version="1.0" encoding="UTF-8" ?><data><error>'.$result['error'].'</error></data>');
                //
            }

            // for API
            exit('<?xml version="1.0" encoding="UTF-8" ?><data><paypal_url>'.filter_var($result, FILTER_SANITIZE_SPECIAL_CHARS).'</paypal_url></data>');
            //

        } else {
            $this->redirect('http://' . env('SERVER_NAME'));
        }

  	}

    public function cancelURL()
    {
        $token = $this->params['url']['token'];
        $this->loadModel('Order');
        $this->Order->deleteAll(array('Order.payment_provider_order_number' => $token));
        $this->redirect('http://' . env('SERVER_NAME'));
    }



	// Use the token in the return URL to fetch details
  	public function get_details($token = null, $PayerID = null) {
      $customer_details = array();
        if($token == null || $PayerID == null) {
            $this->Paypal->token = $this->params['url']['token'];
            $this->Paypal->payerId = $this->params['url']['PayerID'];
        }else {
            $this->Paypal->token = $token;
            $this->Paypal->payerId = $PayerID;
        }
        $customer_details['PayPal'] = $this->Paypal->getExpressCheckoutDetails();
        //$this->log($customer_details, 'express');
        if (array_key_exists('ACK', $customer_details['PayPal']) &&  $customer_details['PayPal']['ACK'] == 'Success') {
            $this->loadModel('Order');
            $this->loadModel('OrderProduct');


            $orderFromDB = $this->Order->findByPaymentProviderOrderNumber($this->Paypal->token);
            //$this->log($orderFromDB, 'debug');
            //$customer_details['PayPal'] = $customer_details;
            $customer_details['orderID'] = $orderFromDB['Order']['id'];
            //TODO read from session Billing&Shipping
            $userInfo = $this->Session->read("BillingShipping");
            $customer_details['Billing'] = $userInfo['Billing'];
            $customer_details['Shipping'] = $userInfo['Shipping'];
            $orderToDB = $this->Order->orderSave($customer_details);
            if ($orderToDB) {

                $cart = $this->Cart->in_cart(false,'',false);

                $delivery = $this->Session->read('DeliveryMethod');

                $orderId = $customer_details['orderID'];
                foreach($cart["Products"] as $key => $product) {
                    $product_id = explode('.', $key);
                    if(count($product_id) > 1) {
                        $this->OrderProduct->save(array(
                            "id"            => "",
                            "order_id"      => $orderId,
                            "product_id"    => $product["id"],
                            "name"          => $product["title"],
                            "model"         => $key,
                            "attributes"    => serialize($product["attributes"]),
                            "price"         => $product["price"],
                            "total"         => $product["price"] * $product["qty"],
                            "quantity"      => $product["qty"],
                            "tax"           => $product["tax"],
                            'delivery_method' => $delivery[$product_id[0]]['track-'.$product["id"]]
                        ));
                    }else {
                        $this->OrderProduct->save(array(
                            "id"            => "",
                            "order_id"      => $orderId,
                            "product_id"    => $product["id"],
                            "name"          => $product["title"],
                            "model"         => $key,
                            "attributes"    => serialize($product["attributes"]),
                            "price"         => $product["rprice"],
                            "total"         => $product["rprice"] * $product["qty"],
                            "quantity"      => $product["qty"],
                            "tax"           => $product["tax"],
                            'delivery_method' => $delivery[$product["id"]][0]
                        ));
                    }

                }
                $parallelsDetails = (isset($orderFromDB['Order']['parallels_payments_details']) && !empty($orderFromDB['Order']['parallels_payments_details'])) ? $orderFromDB['Order']['parallels_payments_details'] : null;
                $paymentStatus = $this->complete_express_checkout($this->Paypal->token, $this->Paypal->payerId, $customer_details['PayPal']['AMT'], $parallelsDetails);
                if ($paymentStatus == 'Completed') {
                    //$this->Cart->clear();
                    /*$this->Messenger->sent_notice(
                        array(
                           "to"            => $customer_details['PayPal']["EMAIL"],
                           "name"          => $customer_details['PayPal']["SHIPTONAME"],
                           "invoice_num"   => $orderId
                      ));*/

                    $this->Order->contain("OrderProduct");

                    // NOTICE: if uncomment maybe error occurred
                    $this->Messenger->sentToAdmin($orderId);
                    //$this->Order->changeQty($orderId);
                    $this->Order->id = $orderId;
                    $this->Order->saveField('status', 2);
                    exit('<?xml version="1.0" encoding="UTF-8" ?><data><order_id>'.$orderId.'</order_id></data>');
                    //$this->redirect('http://' . env('SERVER_NAME') . '/thank-you/' . $orderId);
                } else {
                    exit('<?xml version="1.0" encoding="UTF-8" ?><data><error>Payment failed!<br/>For any questions about your order('.$orderId.'), please <a href="/contact-us">contact us</a></error></data>');
                    //$this->set('errorMsg', 'Payment failed!<br/>For any questions about your order('.$orderId.'), please <a href="/contact-us">contact us</a>');
                    //$this->render("payment");
                }
            }else {
                exit('<?xml version="1.0" encoding="UTF-8" ?><data><error>Something wrong with saving your order!</error></data>');
            }
        }
        exit('<?xml version="1.0" encoding="UTF-8" ?><data><error>Oops!</error></data>');
        //$this->redirect('http://' . env('SERVER_NAME'));
  	}
  
  	// Complete the payment, pass back the token and payerId
  	public function complete_express_checkout($token,$payerId, $amount, $parallelsDetails = null) {
          $this->Paypal->amount = $amount;
          $this->Paypal->currencyCode = 'USD';
          $this->Paypal->token = $token;
          $this->Paypal->payerId = $payerId;
          if (!empty($parallelsDetails)) {
              $response = $this->Paypal->doExpressCheckoutParallelsPayment($parallelsDetails);
          } else {
              $response = $this->Paypal->doExpressCheckoutPayment();
          }


          $this->log($response, 'express_complete'); //debug($response);

          return $response['PAYMENTSTATUS'];

  	}


    private function _saveUnprocessedOrderNum($orderId)
    {
        $this->Session->write($this->_unprocessedOrderSessionKey, $orderId);
    }
  	
  	// Do a direct credit card payment
  	/*public function charge_card() {
  		try {
	  		$this->Paypal->amount = 10.00;
			$this->Paypal->currencyCode = 'CAD';
			$this->Paypal->creditCardNumber = 'xxxxxxxxxxxx1234';
			$this->Paypal->creditCardCvv = '123';
			$this->Paypal->creditCardExpires = '012020';
			$this->Paypal->creditCardType = 'Visa';
			$result = $this->Paypal->doDirectPayment();
			debug($result);
  		} catch(Exception $e) {
			$this->Session->setFlash($e->getMessage());
		}
  	}*/
    public function thank_you($invoiceId = null) {

              if (!empty($invoiceId)) {
                  $this->set('orderId', $invoiceId);
              } else {
                  $this->redirect('http://' . env('SERVER_NAME'));
              }
    }

  	public function thank_you_old($invoiceId = null) {

          if (!empty($invoiceId)) $this->set('orderId', $invoiceId);
          $cart = $this->Cart->in_cart();

          if (!empty($this->data) && !empty($cart)) {
              $this->Session->write("Credit", $this->data);

              $invoice = mktime();
              $this->pageTitle .= " :: Shopping cart";

              $billing = $this->Session->read("BillingShipping");

              $this->loadModel('Country');
              $this->loadModel('Region');
              $this->loadModel('Order');
              $this->loadModel('OrderProduct');
              $regionNameBilling = ($billing['Billing']['customRegion'] == 1) ? $billing['Billing']['addonRegion'] : $this->Region->getRegionName($billing["Billing"]["state"]);
              $regionNameShipping = ($billing['Shipping']['customRegion'] == 1) ? $billing['Shipping']['addonRegion'] : $this->Region->getRegionName($billing["Shipping"]["state"]);
              $billingInfo = array(
                  "address" => "{$billing["Billing"]["address1"]} {$billing["Billing"]["address2"]}",
                  "city" => $billing["Billing"]["city"],
                  "state" => $regionNameBilling,
                  "zip" => $billing["Billing"]["zip"],
                  "country" => $this->Country->getCountryName($billing["Billing"]["country"]),
                  "email" => $billing["Billing"]["email"],
                  "phone" => $billing["Billing"]["phone"],
                  'name' => $billing["Billing"]["name"]
              );

              $shippingInfo = array(
                  "address" => "{$billing["Shipping"]["address1"]} {$billing["Shipping"]["address2"]}",
                  "city" => $billing["Shipping"]["city"],
                  "state" => $regionNameShipping,
                  "zip" => $billing["Shipping"]["zip"],
                  "country" => $this->Country->getCountryName($billing["Shipping"]["country"])
              );

              $description = "";
              foreach ($cart["Products"] as $product) {
                  $description .= $product["name"];
                  foreach ($product["attributes"] as $key => $value) {
                      $description .= " $key: '$value'";
                  }
                  $description .= "; ";
              }
              $credit = $this->data['Credit'];
              $toSave = array(
                  "id" => $invoice,
                  "phone" => $billing["Billing"]["phone"],
                  "email" => $billing["Billing"]["email"],
                  "shipping_name" => $billing["Shipping"]["name"],
                  "shipping_address_1" => $billing["Shipping"]["address1"],
                  "shipping_address_2" => $billing["Shipping"]["address2"],
                  "shipping_city" => $billing["Shipping"]["city"],
                  "shipping_country" => $shippingInfo["country"],
                  "shipping_state" => $shippingInfo["state"],
                  "shipping_postcode" => $billing["Shipping"]["zip"],
                  "shipping_method" => 'Undefined', //$this->Session->read("Cart.ShippingMethod"),
                  "payment_name" => $billing["Billing"]["name"],
                  "payment_address_1" => $billing["Billing"]["address1"],
                  "payment_address_2" => $billing["Billing"]["address2"],
                  "payment_city" => $billing["Billing"]["city"],
                  "payment_state" => $billingInfo["state"],
                  "payment_country" => $billingInfo["country"],
                  "payment_postcode" => $billing["Billing"]["zip"],
                  "payment_method" => $credit["type"] . " "
                      . substr(
                          $credit["number"],
                          strlen($credit["number"]) - 4
                      ),
                  "shipping" => $cart["Shipping"],
                  "subtotal" => $cart["Subtotal"],
                  "discount" => $cart["Discount"],
                  "total" => $cart["Total"],
                  'tax' => $cart["Tax"],
                  "status" => "6",
                  "is_test_order" => (int)$this->Paypal->sandboxMode,
                  "user_id" => ($this->Auth->user('id')) ? $this->Auth->user('id') : ''
              );


              $this->set('siteName', env('SERVER_NAME'));

              //$this->set('urlToPaymentProvider', $this->_payPalDebug ? $this->_paypalSandboxUrl : $this->_paypalProductionUrl);

              $this->Paypal->customerFirstName = $billing["Billing"]["name"];
              $this->Paypal->billingAddress1 = $billing["Billing"]["address1"];
              $this->Paypal->billingAddress2 = $billing["Billing"]["address2"];
              $this->Paypal->billingCity = $billing["Billing"]["city"];
              $this->Paypal->billingState = $billingInfo["state"];
              $this->Paypal->billingZip = $billing["Billing"]["zip"];

              $this->Paypal->amount = $cart['Total'];
              $this->Paypal->currencyCode = 'USD';
              $this->Paypal->creditCardNumber = $this->data["Credit"]["number"];
              $this->Paypal->creditCardCvv = $this->data["Credit"]["code"];
              $this->Paypal->creditCardExpires = $this->data["Credit"]["date"]["month"] . $this->data["Credit"]["date"]["year"];
              $this->Paypal->creditCardType = $this->data["Credit"]["type"];
              $result = $this->Paypal->doDirectPayment();
              //debug($result);
              $error = false;
              $errorText = null;
              if (array_key_exists('ACK', $result) && $result['ACK'] == 'Success') {
                  $this->set('cart', $cart);
                  $this->set('invoice', $invoice);
                  $this->set('merchantEmail', $billing["Billing"]["email"]);
                  if ($this->Order->save($toSave)) {

                      $orderId = $invoice; //$this->Order->id;

                      foreach ($cart["Products"] as $key => $product) {
                          $this->OrderProduct->save(array(
                              "id" => "",
                              "order_id" => $orderId,
                              "product_id" => $product["id"],
                              "name" => $product["name"],
                              "model" => $key,
                              "attributes" => serialize($product["attributes"]),
                              "price" => $product["price"],
                              "total" => $product["price"] * $product["qty"],
                              "quantity" => $product["qty"]
                          ));
                      }


                      $this->set("orderId", $orderId);

                      $this->Messenger->sent_notice(array(
                          "to" => $billing["Billing"]["email"],
                          "name" => $credit["Credit"]["holder"],
                          "invoice_num" => $invoice
                      ));
                      $this->loadModel("Order");

                      $this->Messenger->sentToAdmin($this->Order->orderForInvoice($orderId));
                      $this->Order->contain("OrderProduct");
                      $this->Order->changeQty($orderId);
                      $this->Cart->clear();
                  }

                  $forOrderLog = $billingInfo;
                  $forOrderLog['total'] = $cart["Total"];
                  $forOrderLog['order_id'] = $invoice;
                  $this->orderLogSave($forOrderLog);
                  $this->redirect('http://' . env('SERVER_NAME') . '/thank-you/' . $invoice);

              } elseif (array_key_exists('L_LONGMESSAGE0', $result) && $result['ACK'] != 'Success') {
                  $errorText = $result['L_LONGMESSAGE0'];
              } elseif (array_key_exists('ERROR_TEXT', $result)) {
                  $errorText = $result['ERROR_TEXT'];
              }
              $error = (!empty($errorText)) ? true : false;
              $forOrderLog = $billingInfo;
              $forOrderLog['order_id'] = $invoice;
              if ($error) {
                  $forOrderLog['error_msg'] = $errorText;
                  $this->orderLogSave($forOrderLog);
                  $this->set('errorMsg', $forOrderLog['error_msg']);
                  $this->set('prefixHttp', 's');

                  $this->render("payment");
              }
          } else {
              if (empty($invoiceId)) $this->redirect("/");
          }
  	}

    public function orderLogSave($data)
    {
        $this->loadModel('OrderLog');
        $this->OrderLog->set(array(
            'gateway' => 'PayPal - DoDirectPayment',
            'order_num' => (isset($data['order_id'])) ? $data['order_id'] : null,
            'order_date' => strtotime('now'),
            'payer_name' => (isset($data['name'])) ? $data['name'] : null,
            'payer_email' => (isset($data['email'])) ? $data['email'] : null,
            'amount' => (isset($data['total'])) ? $data['total'] : null,
            'error_message' => (isset($data['error_msg'])) ? $data['error_msg'] : null
        ));
        $this->OrderLog->save();
    }

}