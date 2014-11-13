<?php
/**
 * Created by CNR.
 * User: Nike
 * @property MessengerComponent $Messenger
 * @property SessionComponent $Session
 * @property CurlComponent    $Curl
 * @property CartComponent    $Cart
 * @property Order            $Order
 * @property OrderProduct     $OrderProduct
 * @property Coupon           $Coupon
 * @property Subscriber       $Subscriber
 */
class PaymentsController extends AppController{
    public $name = "Payments";
    public $components = array("Curl", "Cart", "Messenger");
    public $uses = array("Order", "OrderProduct", 'Coupon', "Product");
    public $layout = 'payment';

    private $_gcSandboxUrl = "https://sandbox.google.com/checkout/api/checkout/v2/checkoutForm/Merchant";
    private $_gcProductionUrl = "https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant";

    private $_gcSandboxReportUrl = "https://sandbox.google.com/checkout/api/checkout/v2/reportsForm/Merchant";
    private $_gcProductionReportUrl = "https://checkout.google.com/api/checkout/v2/reportsForm/Merchant";

    private $_paypalSandboxUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    private $_paypalProductionUrl = 'https://www.paypal.com/cgi-bin/webscr';

    private $_gcMerchantID;
    private $_gcMerchantKey;

    private $_payPalMerchantEmail;

    private $_gcDebug;
    private $_payPalDebug;

    public function beforeFilter()
    {
        parent::beforeFilter();

        $this->_initialize();
        $this->disableCache();
    }

    private function _initialize()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::load('payment');

        $this->_gcDebug = Configure::read('Payment.GC.debugMode');
        $this->_payPalDebug = Configure::read('Payment.PayPal.debugMode');

        $gcDebugPrefix = $this->_gcDebug ? 'debug' : '';
        $payPalDebugPrefix = $this->_payPalDebug ? 'debug' : '';

        $this->_gcMerchantID = Configure::read("Payment.GC.{$gcDebugPrefix}MerchantId");
        $this->_gcMerchantKey = Configure::read("Payment.GC.{$gcDebugPrefix}MerchantKey");

        $this->_payPalMerchantEmail = Configure::read("Payment.PayPal.{$payPalDebugPrefix}MerchantEmail");
    }

    public function google_checkout()
    {
        $invoice = mktime();
        $cart = $this->Cart->in_cart();

        $toSave = array(
            "id"                    => $invoice,
            "shipping_method"       => $this->_getShippingMethod(),
            "payment_method"        => 'Google Checkout',
            "shipping"              => $cart["Shipping"],
            "subtotal"              => $cart["Subtotal"],
            "discount"              => $cart["Discount"],
            "total"                 => $cart["Total"],
            "status"                => "1",
            'debug'         => (int)$this->_gcDebug,
        );

        if($this->Order->save($toSave)) {
            $this->Cart->clear();
            if($cart['Products']){
                foreach($cart["Products"] as $product) {
                    $productToSave = array(
                        "id"            => "",
                        "order_id"      => $invoice,
                        "product_id"    => $product["id"],
                        "name"          => $product["name"],
                        "attributes"    => serialize($product["attributes"]),
                        "price"         => $product["price"],
                        "total"         => $product["price"],
                        "quantity"      => $product["qty"]
                    );

                    $this->OrderProduct->save($productToSave);
                }
            }

            $this->_saveUnprocessedOrderNum($invoice);
        }

        $this->set('cart', $cart);
        $this->set('invoice', $invoice);
        $this->set('merchantId', $this->_gcMerchantID);
        $this->set('urlToPaymentProvider', $this->_gcDebug ? $this->_gcSandboxUrl : $this->_gcProductionUrl);
    }

    public function google_ipn()
    {
        $this->layout = null;
        $this->autoRender = false;

        $xml_response = isset($HTTP_RAW_POST_DATA)? $HTTP_RAW_POST_DATA : file_get_contents("php://input");
        $url = ($this->_gcDebug) ? $this->_gcSandboxReportUrl : $this->_gcProductionReportUrl;

        $data = '_type=notification-history-request&'.$xml_response;

        $this->Curl->start();
        $request = $this->Curl->response($url, $data, $this->_gcMerchantID, $this->_gcMerchantKey );
        $this->Curl->stop();

        $data = $this->_delim($request);

        if($data['_type'] == 'new-order-notification') {
            $orderId = end(explode("#", urldecode($data["shopping-cart.items.item-1.item-description"])));

            $toSave = array(
                "payment_provider_order_number" => $data["google-order-number"],
                "status"                       => $this->_getGCStatus($data['financial-order-state']),
                "phone"                        => urldecode($data["buyer-billing-address.phone"]),
                "email"                        => urldecode($data["buyer-billing-address.email"]),
                "shipping_firstname"           => urldecode($data["buyer-shipping-address.contact-name"]),
                "shipping_address_1"           => urldecode($data["buyer-shipping-address.address1"]),
                "shipping_address_2"           => urldecode($data["buyer-shipping-address.address2"]),
                "shipping_city"                => urldecode($data["buyer-shipping-address.city"]),
                "shipping_state"               => urldecode($data["buyer-shipping-address.region"]),
                "shipping_postcode"            => urldecode($data["buyer-shipping-address.postal-code"]),
                "payment_firstname"                 => urldecode($data["buyer-billing-address.contact-name"]),
                "payment_address_1"            => urldecode($data["buyer-billing-address.address1"]),
                "payment_address_2"            => urldecode($data["buyer-billing-address.address2"]),
                "payment_city"                 => urldecode($data["buyer-billing-address.city"]),
                "payment_state"                => urldecode($data["buyer-billing-address.region"]),
                "payment_postcode"             => urldecode($data["buyer-billing-address.postal-code"]),
            );

            if(!empty($orderId) && is_numeric($orderId)) {
                $this->Order->id = $orderId;
                $this->Order->save($toSave);

                $this->_saveCustomerToSubscribers($toSave);
                $this->_actionAfterPurchase($toSave);
            }

        } else if($data['_type'] == 'order-state-change-notification') {
            $orderId = $this->_getOrderIdByGCNumber($data['google-order-number']);
            if($orderId) {
                $status = ($data['new-fulfillment-order-state'] == 'DELIVERED') ? 'DELIVERED' : $data['new-financial-order-state'];
                $this->_changeGCStatus($orderId, $this->_getGCStatus($status));
            }
        } else if($data['_type'] == 'charge-amount-notification') {
            $orderId = $this->_getOrderIdByGCNumber($data['google-order-number']);
            if($orderId) {
                $this->Order->id = $orderId;
                $this->Order->saveField('total', $data['total-charge-amount']);
            }
        }
    }

    private function _getOrderIdByGCNumber($gCNumber)
    {
        $order = $this->Order->find('first', array(
            'contain' => array(),
            'conditions' => array(
                'payment_provider_order_number' => $gCNumber
            ),
            'fields' => array('Order.id')
         ));

        return ($order == false) ? null : $order['Order']['id'];
    }

    private function _changeGCStatus($orderId, $status)
    {
        $this->Order->id = $orderId;
        $this->Order->saveField('status', $status);
    }

    private function _getGCStatus($status)
    {
        /*
         * Google Checkout statuses
         * REVIEWING - Google Checkout is reviewing the order.
         * CHARGEABLE - The order is ready to be charged.
         * CHARGING - The order is being charged; you may not refund or cancel an order until is the charge is completed.
         * CHARGED - The order has been successfully charged; if the order was only partially charged, the buyer's account page will reflect the partial charge.
         * PAYMENT_DECLINED - The charge attempt failed.
         * CANCELLED - Either the buyer or the seller canceled the order. An order's financial state cannot be changed after the order is canceled. Learn more about when an order may be canceled.
         * CANCELLED_BY_GOOGLE - Google canceled the order. Google may cancel orders due to a failed charge without a replacement credit card being provided within a set period of time or due to a failed risk check. If Google cancels an order, you will be notified of the reason the order was canceled in the reason parameter of an order-state-change-notification.
        **/
        switch($status)
        {
            case "REVIEWING":
            case "CHARGEABLE":
            case "CHARGING":
                        return '6';

            case "CHARGED":
                        return '3';

            case "PAYMENT_DECLINED":
                        return '4';

            case "CANCELLED":
            case "CANCELLED_BY_GOOGLE":
                        return 2;
            case 'DELIVERED':
                        return 7;

            default:
                return "6";

        }
    }

    public function paypal()
    {
        if ($this->Session->check("Charity")) {
            $charity = $this->Session->read("Charity");

            $tmp = array();
            foreach ($charity as $key=>$value) {
                foreach($value as $temp=>$empty) {
                    $tmp[$key] = str_replace(",",".",$temp);;
                }
            }
            $charity = $tmp;
        }


        $invoice = mktime();
        $cart = $this->Cart->in_cart();

        if (is_array($cart['Coupons']) && count($cart['Coupons']) > 0) {
            $coupons = array();
            foreach ($cart['Coupons'] as $key=>$coupon) {
                $coupons = array_merge($coupons, array($key));
            }
            unset($coupon);
        }

            $this->loadModel("Order");
            $this->loadModel("OrderProduct");
            $billing    = $this->Session->read("BillingShipping");
            $toSave = array(
                "id"                    => $invoice,
                "phone"                 => $billing["Billing"]["phone"],
                "email"                 => $billing["Billing"]["email"],
                "shipping_fname"        => $billing["Shipping"]["first_name"],
                "shipping_name"         => $billing["Shipping"]["name"],
                "shipping_address_1"    => $billing["Shipping"]["address1"],
                "shipping_address_2"    => $billing["Shipping"]["address2"],
                "shipping_country"      => $this->_getCountryName($billing["Shipping"]["country"]),
                "shipping_city"         => $billing["Shipping"]["city"],
                "shipping_state"        => $this->_getRegionName($billing["Shipping"]["state"]),
                "shipping_postcode"     => $billing["Shipping"]["zip"],
                "shipping_method"       => $this->_getShippingMethod(),
                "payment_fname"         => $billing["Billing"]["first_name"],
                "payment_name"          => $billing["Billing"]["name"],
                "payment_address_1"     => $billing["Billing"]["address1"],
                "payment_address_2"     => $billing["Billing"]["address2"],
                "payment_country"       => $this->_getCountryName($billing["Billing"]["country"]),
                "payment_city"          => $billing["Billing"]["city"],
                "payment_state"         => $this->_getRegionName($billing["Billing"]["state"]),
                "payment_postcode"      => $billing["Billing"]["zip"],
                "payment_method"        => "PayPal",
                "shipping"              => $cart["Shipping"],
                "tax"                   => $cart["Tax"],
                "subtotal"              => $cart["Subtotal"],
                "discount"              => $cart["Discount"],
                "total"                 => $cart["Total"],
                "status"                => 5,
                'is_test_order'         => (int)$this->_payPalDebug,
                'coupon'                => (isset($coupons[0])) ? $coupons[0] : null
            );
        if($this->Order->save($toSave)) {
            $this->Cart->clear();
            $this->Session->delete("shippingFlag");
            $this->Session->delete("blockId");
            if($cart['Products']) {
                foreach($cart["Products"] as $key => $product) {

                    $this->_sendConfirmationNotification($product, $invoice, $this->Session->read("Cart.ShippingMethod"), "PayPal", $billing, $product["price"] * $product["qty"]);

                    if (isset($product['discount']['coupon'])) {
                        $this->Coupon->couponStatus($product['discount']['coupon']);
                    }

                    $charityValue = "";
                    if (isset($charity[$product["id"]]) && !empty($charity[$product["id"]])) {
                        $charityValue =  $charity[$product["id"]];
                    }
                    //$category = $this->Product->getProductCategoryName($product["id"]);
                    $this->OrderProduct->save(array(
                        "id"            => "",
                        "order_id"      => $invoice,
                        "product_id"    => $product["id"],
                        "name"          => $product["title"],
                        "model"         => $key,
                        "attributes"    => serialize($product["attributes"]),
                        "networth"      => $product["networth"],
                        "price"         => $product["price"],
                        "total"         => $product["price"] * $product["qty"],
                        "quantity"      => $product["qty"],
                        "charity"       => $charityValue
                    ));
                    $this->Product->contain();
                    $topId = $this->Product->read("top", $product["id"]);

                    $this->Product->id = $product["id"];
                    $this->Product->saveField("top", $topId['Product']['top']+1);
                }
            }

            $this->_saveUnprocessedOrderNum($invoice);

        }

        $this->addUserToConstantContract($billing);
        $this->set('siteName', env('SERVER_NAME'));
        $this->set('cart', $cart);
        $this->set('invoice', $invoice);
        $this->set('merchantEmail', $this->_payPalMerchantEmail);
        $this->set('urlToPaymentProvider', $this->_payPalDebug ? $this->_paypalSandboxUrl : $this->_paypalProductionUrl);
    }

    public function paypal_ipn()
    {
        $this->layout = null;
        $this->autoRender = false;

        $data = $this->params['form'];

        if($this->_checkPayPalNotify($data) == 'VERIFIED') {
            $this->Order->contain();
            $existsPaymentsCount = $this->Order->find('count', array(
                'conditions' => array(
                    'Order.id' => $data['custom']
                )
            ));

              if($existsPaymentsCount == 1) {
                  $paymentStatus = $this->params['form']['payment_status'];
                  $businessEmail = $this->params['form']['business'];

                  /* PayPal statuses
                   *  Canceled_Reversal
                   *  Completed
                   *  Denied
                   *  Expired
                   *  Failed
                   *  In-Progress
                   *  Partially_Refunded
                   *  Pending
                   *  Processed
                   *  Refunded
                   *  Reversed
                   *  Voided
                   */
                  switch($paymentStatus) {
                      case 'Completed':
                          $status = '1';
                          break;
                      case 'Canceled_Reversal':
                          $status = '3';
                          break;
                      case 'Denied':
                          $status = '4';
                          break;
                      case 'Pending':
                          $status = '5';
                          break;

                      default:
                          $status = '6';
                  }
                  $toSave = array(
                      'status' => $status,
                      'payment_provider_order_number' => $data['txn_id'],
                  );

                  /** @noinspection PhpUndefinedMethodInspection */
                  //$this->log($toSave, "debug");
                  
                  $this->Order->id = $orderId = $data['custom'];
                  if($businessEmail == $this->_payPalMerchantEmail && $this->Order->save($toSave)) {
                      $forNotification = $this->Order->changeQty($orderId);
                      $this->Messenger->sendUserOrderConfirmation($orderId);
                      $this->_processLowProductNotification($forNotification[0]);
                      $this->_saveCustomerToSubscribers($toSave);
                      $this->_actionAfterPurchase($orderId);
                  } else {
                      $this->_failResponseForPayPalIpn();
                  }
              }
        }
    }

    /**
     * Send fullfillment notification for selled product on specifed emails
     * @param $product
     * @param $orderId
     * @param $shippingType
     * @param $paymentType
     * @param $billing
     * @param $price
     */
    private function _sendConfirmationNotification($product, $orderId, $shippingType, $paymentType, $billing, $price) {
        $emails = $this->Product->read("confirmation", $product['id']);

        $billing["Shipping"]["country"] = $this->_getCountryName($billing["Shipping"]["country"]);
        $billing["Shipping"]["state"] = $this->_getRegionName($billing["Shipping"]["state"]);

        $billing["Billing"]["country"] = $this->_getCountryName($billing["Billing"]["country"]);
        $billing["Billing"]["state"] = $this->_getRegionName($billing["Billing"]["state"]);

        $emails = explode(",", $emails['Product']['confirmation']);
        $mainEmail = array_shift($emails);
        $copyEmails = trim(rtrim(implode(",", $emails), ", "));

        $dataToSend = array(
            'to'           => $mainEmail,
            'cc'           => $copyEmails,
            'from'         => SUPPORTEMAIL,
            'subject'      => "An order has been made on officialsituation.com. The following items have been sold.",
            'body'         => $product['title'],
            'qty'          => $product['qty'],
            'orderId'      => $orderId,
            'shippingType' => $shippingType,
            'paymentType'  => $paymentType,
            'billing'      => $billing,
            'price'        => $price
        );

        if (isset($product['attributes']) && is_array($product['attributes']) && count($product['attributes']) > 0) {
            $dataToSend = array_merge($dataToSend, array('attributes' => $product['attributes']));
        }

        $this->Messenger->sent_confirmation_mail($dataToSend);
    }


      private function _checkPayPalNotify($data)
      {
          /** @noinspection PhpDynamicAsStaticMethodCallInspection */
          App::import('Core', 'HttpSocket');
          $HttpSocket = new HttpSocket();
          $data['cmd'] = '_notify-validate';
          $url = $this->_payPalDebug ? $this->_paypalSandboxUrl : $this->_paypalProductionUrl;
          $responce = $HttpSocket->post($url, $data);
          return $responce;
      }

    private function _failResponseForPayPalIpn()
    {
file_put_contents("/tmp/ipntestfail","fail");
        @ob_end_clean();
        @header("HTTP/1.1 500 Internal Server Error");
        exit();
    }

    
    private function _getShippingMethod()
    {
        $defaultMethod = 'USPS priority mail domestic FREE';
        $currentMethod = $this->Session->read("Cart.ShippingMethod");

        return empty($currentMethod) ? $defaultMethod : $currentMethod;
    }


    private function _delim($data)
    {
        $data = preg_split('/&/', $data);
        $result = array();
        foreach($data as &$tmp)
        {
            $arr = explode("=", $tmp);
            $result[$arr[0]] = $arr[1];
        }
        return $result;
    }

    private function _saveUnprocessedOrderNum($orderId)
    {
        $this->Session->write($this->_unprocessedOrderSessionKey, $orderId);
    }

    private function _saveCustomerToSubscribers($data)
    {
        $this->loadModel('Subscriber');

        $this->Subscriber->addCustomer($data['email'], $data['payment_firstname']);
    }

    private function _actionAfterPurchase($orderId) {
//        $this->Qnet->sendQuery($sku);
    }

    private function _processLowProductNotification($data) {
        $products = $data['products'];
        $this->_getProductInfoForNotificationAndSendIt($products);
        $attributes = $data['attributes'];
        $this->_getProductAttributeInfoForNotificationAndSendIt($attributes);
    }

    private function _getProductInfoForNotificationAndSendIt($products) {
        foreach ($products as $product) {
            $data = $this->Product->read(array('title', 'fullfillment', "sitch_style", "quantity"), $product);
            $this->_sendProductLowNotification($data['Product']);
        }
        unset($product);
    }

    private function _sendProductLowNotification($data) {
        // Fillfillment emails
        $emails = explode(",", $data['fullfillment']);
        $mainEmail = array_shift($emails);
        $copyEmails = trim(rtrim(implode(",", $emails), ", "));

        $dataToSend = array(
            'to'      => $mainEmail,
            'cc'      => $copyEmails,
            'from'    => SUPPORTEMAIL,
            'subject' => "Product has gone below 10 items. Time to reorder.",
            'body'    => $data
        );
        $this->Messenger->sent_mail($dataToSend);

    }

    private function _getProductAttributeInfoForNotificationAndSendIt($attributes) {
        foreach ($attributes as $attribute) {
            $this->_sendProductAttributeLowNotification($attribute);
        }
        unset($attribute);
    }

    private function _sendProductAttributeLowNotification($attribute) {
        $this->loadModel("ProductAttribute");
        $attributeInfo = $this->ProductAttribute->read(null, $attribute);
        $this->Product->contain();
        $productInfo = $this->Product->read(array("title", "fullfillment", "vendor", "sitch_style"), $attributeInfo['ProductAttributeGroup']['product_id']);
        $productInfo = $productInfo['Product'];
        $productInfo['attrData'] = array(
            'title'         => $productInfo['title'],
            'attrGroupName' => $attributeInfo["ProductAttributeGroup"]['name'],
            'attrTitle'     => $attributeInfo['ProductAttribute']['title'],
            'attrQty'       => $attributeInfo['ProductAttribute']['quantity'],
        );
        $this->_sendProductLowNotification($productInfo);
    }
}
