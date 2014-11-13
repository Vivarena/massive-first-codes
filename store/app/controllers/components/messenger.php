<?php
/**
 * Messenger
 * Sends emails
 *
 * @author Vitaliy Kh.
 * @property Order          $Order
 * @property Product        $Product
 * @property EmailComponent $Email
 */
class MessengerComponent extends Object
{
    public $name        = 'Messenger';
    public $components  = array('Email');
    public $controller;

    public function initialize(&$controller, $settings = array()) {

        $this->controller =& $controller;
        $this->Email->layout = "email/html/default";

        /*
         * --- Uncomment if you use SMTP server ---
         */
//        $this->Email->smtpOptions = array(
//            'host'      => EMAILHOST,
//            'port'      => '25',
//            'timeout'   => '30'
//        );
//        $this->Email->delivery = 'smtp';
        /*
         * --- End ---
         */
    }

/**
 * sent_mail
 * Sends a simple email
 * 
 * $data parans:
 * to       - email address
 * cc       - email addresses
 * from     - email address
 * subject  - subject message
 * body     - body message
 * 
 * @param array $data
 * @return bool
 */
    public function sent_mail($data = array()) {
        if(empty($data)) {
            return false;
        }

        $this->Email->to = $data["to"];

        if(!empty($data["cc"])) {
            $this->Email->cc = explode(",", $data["cc"]);
        }

        $this->Email->from = $data["from"];
        $this->Email->subject = $data["subject"];
        $this->Email->template = "lowstock";
        $this->Email->layout = "confirmation";
        $this->Email->sendAs = "html";

        $this->controller->set("message", $data["body"]);

        return $this->Email->send();
    }

/**
 * sent_notice
 * Sends a notice email to user and copi to admin
 *
 * $data parans:
 * to           - email address
 * name         - full name
 * invoice_num  - invoice number
 *
 * @param array $data
 * @return bool
 */
    public function sent_notice($data = array())
    {
        if(empty($data)) {
            return false;
        }

        $this->Email->to        = $data["to"];
        $this->Email->bcc       = array(SUPPORTEMAIL, 'fulfillment@vivarena.com', ' info@vivarena.com', 'info@dsxpress.com', 'info@vizualtech.com');
        $this->Email->from      = SUPPORTEMAIL;
        $this->Email->subject   = "Thank you for the purchase on {$_SERVER["SERVER_NAME"]}";
        $this->Email->template  = "memail";
        $this->Email->layout    = "notice";
        $this->Email->sendAs    = "html";

        $message = <<<EOF
Dear {$data['name']},

Thank for your purchase, please click here to see the invoice: <a href="http://{$_SERVER["SERVER_NAME"]}/products/orders/{$data["invoice_num"]}">http://{$_SERVER["SERVER_NAME"]}/products/orders/{$data["invoice_num"]}</a>
EOF;
        $this->controller->set("message", $message);

        return $this->Email->send();
    }

/**
 * sent_notice_status
 * Notice of change of status of order
 *
 * $data parans:
 * to       - email address
 * body     - body message
 * status   - order status
 *
 * @param array $data
 * @return bool
 */
    public function sent_notice_status($data = array())
    {
        if(empty($data)) {
            return false;
        }

        $this->Email->to        = $data["to"];
        $this->Email->bcc        = array("victor@vizualtech.com");
        $this->Email->from      = SUPPORTEMAIL;
        switch($data["status"]) {
            case 1:
                $this->Email->subject = "Your Order is Complete!";
                break;
            case 2:
                $this->Email->subject = "Your Order Unprocessed!";
                break;
            case 3:
                $this->Email->subject = "Your Order is Canceled!";
                break;
            case 4:
                $this->Email->subject = "Your Order is Failed!";
                break;
            case 5:
                $this->Email->subject = " Your Order is Pending!";
                break;
            case 6:
                $this->Email->subject = " Your Order in Processing!";
                break;
            case 7:
                $this->Email->subject = " Your Order in Shipping!";
                break;
            default:
                $this->Email->subject = " Your Order is Pending!";
                break;
        }

        if ($data["status"] == 7) {
            $this->Email->template  = "shipping";
            $this->Email->layout    = "confirmation";
            $this->Order = ClassRegistry::init('Order');

            $order = $this->Order->find("first",
                array(
                    "conditions" => array(
                        "Order.id" => $data["orderId"]
                    )
                )
            );

            $shippingMethod = explode("-", $order['Order']['shipping_method']);
            $shippingMethod = trim($shippingMethod[0]);

            $this->controller->set('shippingMethod', $shippingMethod);
            $this->controller->set('order', $order);

        } else {
            $this->Email->template  = "user_memail";
            $this->Email->layout    = "notice";
            $this->controller->set("message", $data["body"]);
            $this->controller->set("server_name", env("SERVER_NAME"));
        }


        $this->Email->sendAs    = "html";



        return $this->Email->send();
    }

/**
 * contact_us
 * Message from Contuct Us form
 *
 * $data parans:
 * from     - email address
 * body     - body message
 *
 * @param array $data
 * @return bool
 */
    public function contact_us($data = array())
    {
        if(empty($data)) {
            return false;
        }

        $this->Email->to = SUPPORTEMAIL;
        $this->Email->from = $data["from"];
        $this->Email->subject = "Contact Us form on: {$_SERVER["SERVER_NAME"]}";
        $this->Email->template = "memail";
        $this->Email->sendAs = "html";
        $this->controller->set("message", $data["body"]);

        return $this->Email->send();
    }

    public function sent_confirmation_mail($data) {
        if(empty($data)) {
            return false;
        }

        $this->Email->to = $data["to"];

        if(!empty($data["cc"])) {
            $this->Email->cc = explode(",", $data["cc"]);
        }

        $this->Email->from = $data["from"];
        $this->Email->subject = $data["subject"];
        $this->Email->template = "fullfillment";
        $this->Email->layout = "confirmation";
        $this->Email->sendAs = "html";

        $this->controller->set("price", $data["price"]);
        $this->controller->set("billing", $data["billing"]);
        $this->controller->set("shippingType", $data["shippingType"]);
        $this->controller->set("paymentType", $data["paymentType"]);
        $this->controller->set("orderId", $data["orderId"]);
        $this->controller->set("message", $data["body"]);
        $this->controller->set("qty", $data["qty"]);
        $this->controller->set("server_name", env("SERVER_NAME"));
        if (isset($data['attributes']) && is_array($data['attributes']) && count($data['attributes'])) {
            $this->controller->set("attributes", $data["attributes"]);
        }

        return $this->Email->send();
    }

    public function sendUserOrderConfirmation($orderId) {
        $this->Order = ClassRegistry::init('Order');
        $this->Product = ClassRegistry::init('Product');

        $orderData = $this->Order->find("first",
            array(
                "conditions" => array(
                    "Order.id" => $orderId
                )
            )
        );

        $vendors = array();
        $productIds = Set::extract("/OrderProduct/product_id", $orderData);
        foreach($productIds as $id){
            $tmpId = $this->Product->read('vendor', $id);
            if (!in_array($tmpId['Product']['vendor'], $vendors)) {
                $vendors = array_merge($vendors, array($tmpId['Product']['vendor']));
            }
        }
        unset($id);
        unset($productIds);
        if (count($vendors) > 1) {
            $this->controller->set('diffVendors', true);
        }


        foreach ($orderData['OrderProduct'] as &$item) {
            $item['attributes'] = unserialize($item['attributes']);
        }
        unset($item);

        $this->log($orderData, "order_data");

        $this->Email->reset();
        $this->Email->to = $orderData['Order']['email'];
        $this->Email->from = SUPPORTEMAIL;
        $this->Email->return = "info@vivarena.com";
        $this->Email->subject = 'Thank you for the purchase on ' . env('SERVER_NAME');
        $this->Email->bcc = array('info@vizualtech.com', 'info@dsxpress.com');
        $this->Email->template = "user_confirmation";
        $this->Email->layout = 'confirmation';
        $this->Email->sendAs = 'html';

        $this->controller->set('order', $orderData);

        $this->Email->send();

    }

    function sentToAdmin($orderId) {
        $this->Email->to = SUPPORTEMAIL;
        $this->Email->return = "info@vivarena.com";
        $this->Email->subject = 'Purchase on ' . env('SERVER_NAME');
        $this->Email->bcc = array('info@vizualtech.com', 'info@dsxpress.com');
        $this->Email->send('New order: '.$orderId);
    }
}