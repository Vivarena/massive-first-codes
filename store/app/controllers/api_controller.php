<?php
/**
* Created by Slava Basko
* Email: basko.slava@gmail.com
* Date: 5/27/13
* Time: 6:52 PM
*
* @property RequestHandlerComponent $RequestHandler
* @property CrypterComponent        $Crypter
* @property CartComponent           $Cart
* @property EmailComponent          $Email
* @property MessengerComponent      $Messenger
* @property PaypalComponent      $Paypal
* @property ProductImage            $ProductImage
* @property Product                 $Product
* @property Category                $Category
* @property OrderProduct            $OrderProduct
*/

class ApiController extends AppController {

    public $name = "Api";

    public $uses = array("Product", "Category", "User");

    public $components  = array('Crypter', "RequestHandler", "Messenger", 'Email', 'Paypal', 'Cart');

    private function _saveData() {


        Configure::write('debug', 0);

        if($this->RequestHandler->isPost()) {
            $this->data = $_POST;
        }
        if ($this->data) {
            $this->data = $this->Crypter->Decrypt($this->data['data']);
            $this->data['Product'] = $this->data['UserProduct'];
            if($this->Product->add($this->data)) {
                exit('<?xml version="1.0" encoding="UTF-8" ?><data><success>Item saved!</success><id>'.$this->Product->id.'</id></data>');
            } else {
                exit('<?xml version="1.0" encoding="UTF-8" ?><data><error>Errors occured!</error></data>');
            }
        }
    }

    public function add() {

        $this->_saveData();
    }

    public function edit($id) {
        $this->_saveData();
        if (isset($id) && is_numeric($id)) {

            $this->Product->bindModel(array(
                    'hasMany' => array(
                        'CategoriesProduct'))
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $this->Product->contain(array(
                'CategoriesProduct'
            ));
            $this->data = $this->Product->find('first', array(
                'conditions' => array(
                    'Product.id' => $id
                ),

            ));

            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $this->data['CategoriesProduct']['category_id'] = Set::extract('/CategoriesProduct/./category_id', $this->data);

            $this->set('productList', $this->Product->getProductList());
        }
    }

    public function ActiveProduct($id = 0) {
        $result['errorTxt'] = null;
        $tag = 'success';
        $token = (isset($_POST['token'])) ? $_POST['token'] : null;
        $payerId = (isset($_POST['payerId'])) ? $_POST['payerId'] : null;
        if (!empty($token) && !empty($payerId)) {
            $this->Paypal->token = $token;
            $this->Paypal->payerId = $payerId;
            $details['PayPal'] = $this->Paypal->getExpressCheckoutDetails();
            if (array_key_exists('ACK', $details['PayPal']) &&  $details['PayPal']['ACK'] == 'Success') {
                $this->Paypal->amount = $details['PayPal']['AMT'];
                $this->Paypal->currencyCode = 'USD';
                $this->Paypal->token = $token;
                $this->Paypal->payerId = $payerId;
                $response = $this->Paypal->doExpressCheckoutPayment();
                if ($response['PAYMENTSTATUS'] == 'Completed') {
                    $res = $this->Product->updateAll(array(
                        'Product.active' => 1
                    ), array(
                        'Product.id' => (int) $id
                    ));
                    if($res) {
                        $result['success'] = 'Item activated!';
                    } else {
                        $result['errorTxt'] = 'Error! Please contact the administrator!';
                    }
                } else {
                    $result['errorTxt'] = 'Error! Incomplete payment process!';
                    $result['errDetails'] = $response;
                }
            } else {
                $result['errorTxt'] = 'Error when get details of payment!';
                $result['errDetails'] = $details;

            }
        } else $result['errorTxt'] = 'No token and payerID!';
        $this->log($result, 'fromActivateProduct');
        if (!empty($result['errorTxt'])) {
            $tag = 'error'; $msg = $result['errorTxt'];
        } else $msg = $result['success'];
        exit('<?xml version="1.0" encoding="UTF-8" ?><data><'.$tag.'>'.$msg.'</'.$tag.'></data>');
    }

    public function delete($id) {
        if($this->Product->delete((int)$id)) {
            exit('<?xml version="1.0" encoding="UTF-8" ?><data><success>Item deleted!</success></data>');
        }else {
            exit('<?xml version="1.0" encoding="UTF-8" ?><data><error>Errors occured!</error></data>');
        }
    }

    public function EmailAfterPayment($orderId) {
        /*
        SELECT `op`.`id`, `p`.`user_id`
        FROM bs_order_products AS op
        LEFT JOIN bs_products AS p ON (`op`.`product_id` = `p`.`id`)
        */
        $this->loadModel('Order');
        $this->loadModel('OrderProduct');
        $this->loadModel('Product');
        /*$data = $this->OrderProduct->find('all', array(
            'joins' => array(
                array(
                    'table' => 'bs_products',
                    'alias' => 'Product',
                    'type'  => 'left',
                    'conditions' => array(
                        'OrderProduct.product_id = Product.id'
                    ),
                )
            ),
            'fields' => '*'
        ));*/
        $data = $this->OrderProduct->query('
        SELECT `OrderProduct`.`id`, `Product`.`user_id`
        FROM bs_order_products AS OrderProduct
        LEFT JOIN bs_products AS Product ON (`OrderProduct`.`product_id` = `Product`.`id`)
        WHERE `OrderProduct`.`order_id` = '.$orderId.'
        ');

        $sellers = array();
        foreach ($data as $key => $node) {
            $sellers[] = $node['Product']['user_id'];
        }
        $this->set('sellers_ids', $sellers);
        $this->log($sellers, 'data_for_email');

        $buyer = $this->Order->find('first', array(
            'conditions' => array(
                'Order.id' => $orderId
            ),
            'fields' => array('Order.email'),
            'recursive' => -1
        ));
        $this->set('buyer_email', $buyer['Order']['email']);
        $this->log($buyer, 'data_for_email');

        $this->Email->to = SUPPORTEMAIL;
        $this->log(SUPPORTEMAIL, 'admin_emails');
        $this->Email->subject = 'New order on Vivarena';
        $this->Email->send('New order with number: '.$orderId);

        $this->render('email_after_payment');
    }

    public function AddProductProcess() {
        //for API
        if(empty($this->data)) {
            $this->data = $_POST;
        }
        //

        $product = $this->Product->findById($this->data['product_id']);

        $total = ($product['Product']['price'] * $product['Product']['quantity']) + $product['Product']['shipping'] + $product['Product']['tax'];
        $onePercent = ($total * 1)/100;

        $this->Paypal->amount = number_format($onePercent, 2);
        $this->Paypal->shippingAmt = 0;
        $this->Paypal->taxAmt = 0;
        $this->Paypal->itemAmt = number_format($onePercent, 2);
        $this->Paypal->billingDiscount = 0;

        $this->Paypal->currencyCode = 'USD';

        //
        $this->Paypal->allProducts[0]['title'] = $product['Product']["title"];
        $this->Paypal->allProducts[0]['discount'] = 0;
        $this->Paypal->allProducts[0]['desc'] = $product['Product']["title"];
        if(isset($product['Product']['rprice'])) {
            $this->Paypal->allProducts[0]['amount'] = $onePercent;
        }else {
            $this->Paypal->allProducts[0]['amount'] = $onePercent;
        }
        $this->Paypal->allProducts[0]['qty'] = 1;
        //

        $this->Paypal->customerFirstName = $this->data['user_data']['User']['info']['first_name'];
        $this->Paypal->customerLastName = $this->data['user_data']['User']['info']['last_name'];
        $this->Paypal->billingAddress1 = $this->data['user_data']['User']['info']['first_name'];
        $this->Paypal->billingCity = '';
        $this->Paypal->billingState = '';
        $this->Paypal->billingCountryCode = '';
        $this->Paypal->billingZip = '';
        $this->Paypal->billingEmail = $this->data['user_data']['User']['email'];
        $result = $this->Paypal->ShortExpressCheckout();
        // for API
        exit('<?xml version="1.0" encoding="UTF-8" ?><data><paypal_url>'.filter_var($result, FILTER_SANITIZE_SPECIAL_CHARS).'</paypal_url></data>');
        $this->set('paypal_url', $result);
        $this->render('huy');
        //
        if (isset($result['error']) && !empty($result['error'])) {
            $this->set('errorMsg', $result['error']);
            $this->render("payment");
        }
    }

    /**
     * @param $id
     * @param $qty
     */
    public function RepostProductProcess($id, $qty) {
        $product = $this->Product->findById($id);

        $total = ($product['Product']['price'] * (int)$qty) + $product['Product']['shipping'] + $product['Product']['tax'];
        $onePercent = ($total * 1)/100;

        $this->Paypal->amount = number_format($onePercent, 2);
        $this->Paypal->shippingAmt = 0;
        $this->Paypal->taxAmt = 0;
        $this->Paypal->itemAmt = number_format($onePercent, 2);
        $this->Paypal->billingDiscount = 0;

        $this->Paypal->currencyCode = 'USD';

        //
        $this->Paypal->allProducts[0]['title'] = $product['Product']["title"];
        $this->Paypal->allProducts[0]['discount'] = 0;
        $this->Paypal->allProducts[0]['desc'] = $product['Product']["title"];
        if(isset($product['Product']['rprice'])) {
            $this->Paypal->allProducts[0]['amount'] = $onePercent;
        }else {
            $this->Paypal->allProducts[0]['amount'] = $onePercent;
        }
        $this->Paypal->allProducts[0]['qty'] = 1;
        //

        $this->Paypal->customerFirstName = $this->data['user_data']['User']['info']['first_name'];
        $this->Paypal->customerLastName = $this->data['user_data']['User']['info']['last_name'];
        $this->Paypal->billingAddress1 = $this->data['user_data']['User']['info']['first_name'];
        $this->Paypal->billingCity = '';
        $this->Paypal->billingState = '';
        $this->Paypal->billingCountryCode = '';
        $this->Paypal->billingZip = '';
        $this->Paypal->billingEmail = $this->data['user_data']['User']['email'];
        $result = $this->Paypal->ShortRepostExpressCheckout();
        // for API
        exit('<?xml version="1.0" encoding="UTF-8" ?><data><paypal_url>'.filter_var($result, FILTER_SANITIZE_SPECIAL_CHARS).'</paypal_url></data>');
        $this->set('paypal_url', $result);
        $this->render('huy');
        //
        if (isset($result['error']) && !empty($result['error'])) {
            $this->set('errorMsg', $result['error']);
            $this->render("payment");
        }
    }

    /**
     * @param $id
     * @param $qty
     */
    public function CompleteRepostProduct($id, $qty) {
        $result['errorTxt'] = null;
        $tag = 'success';
        $token = (isset($_POST['token'])) ? $_POST['token'] : null;
        $payerId = (isset($_POST['payerId'])) ? $_POST['payerId'] : null;

        if (!empty($token) && !empty($payerId)) {
            $this->Paypal->token = $token;
            $this->Paypal->payerId = $payerId;
            $details['PayPal'] = $this->Paypal->getExpressCheckoutDetails();
            if (array_key_exists('ACK', $details['PayPal']) &&  $details['PayPal']['ACK'] == 'Success') {
                $this->Paypal->amount = $details['PayPal']['AMT'];
                $this->Paypal->currencyCode = 'USD';
                $this->Paypal->token = $token;
                $this->Paypal->payerId = $payerId;
                $response = $this->Paypal->doExpressCheckoutPayment();
                if ($response['PAYMENTSTATUS'] == 'Completed') {
                    $res = $this->Product->updateAll(array(
                        'Product.quantity' => (int)$qty
                    ), array(
                        'Product.id' => (int) $id
                    ));
                    if($res) {
                        $result['success'] = 'Product successfully activated!';
                    } else {
                        $result['errorTxt'] = 'Error! Please contact the administrator!';
                    }
                } else {
                    $result['errorTxt'] = 'Error! Incomplete payment process!';
                    $result['errDetails'] = $response;
                }
            } else {
                $result['errorTxt'] = 'Error when get details of payment!';
                $result['errDetails'] = $details;
            }
        } else $result['errorTxt'] = 'No token and payerID!';

        $this->log($result, 'fromRepostProduct');
        if (!empty($result['errorTxt'])) {
            $tag = 'error'; $msg = $result['errorTxt'];
        } else $msg = $result['success'];
        exit('<?xml version="1.0" encoding="UTF-8" ?><data><'.$tag.'>'.$msg.'</'.$tag.'></data>');
    }

    /**
     * @param null $user_id
     * @return array
     */
    public function GetLastInsertedProduct($user_id = null) {
        $sql = 'SELECT * FROM `bs_products` ORDER BY `id` DESC LIMIT 1';
        if(ctype_digit($user_id)) {
            $sql = 'SELECT * FROM `bs_products` WHERE `user_id` = '.(int)$user_id.' ORDER BY `id` DESC LIMIT 1';
        }
        return $this->Product->query($sql);
    }

    public function DecrementProductsQty($orderId = null, $cart) {
        $orderId = $_POST['order_id'];
        $cart = $_POST['cart_products'];
        if(is_null($orderId)) {
            exit('<?xml version="1.0" encoding="UTF-8" ?><data><error>Invalid order ID</error></data>');
        }
        $this->log($orderId, 'decrementProductsQty');
        $this->log($cart, 'decrementProductsQty');
        $this->loadModel('Order');
        $order = $this->Order->findById($orderId);
        $products = $order['OrderProduct'];

        /*$cart['Products'] = array(
            '72.0' => array(
                'id' => 72,
                'title' => 'slav test mega prod2',
                'image' => '/uploads/userfiles/user_31/photo_5203aa5aafda3.jpg',
                'qty' => 1,
                'price' => 100,
                'shipping' => 5,
                'tax' => 5,
                'user_id' => 31,
                'networth' => '',
                'pay_pal_acc' => 'apidev3@vizualtech.com',
                'available' => 1,
                'charity' => '',
                'attributes' => array()
            ),
            '63.0' => array(
                'id' => 63,
                'title' => 'admin prod',
                'image' => '/uploads/userfiles/images.jpeg',
                'qty' => 1,
                'price' => 30,
                'shipping' => 10,
                'tax' => 0,
                'user_id' => 0,
                'networth' => 20,
                'pay_pal_acc' => '',
                'available' => 1,
                'charity' => '',
                'attributes' => array()
            )
        );*/

        if(empty($cart['Products'])) {
            exit('<?xml version="1.0" encoding="UTF-8" ?><data><error>Cart is empty</error></data>');
        }
        foreach ($products as $key => $node) {
            foreach ($cart['Products'] as $k => $n) {
                $realId = explode('.', $k);
                if((int)$node['product_id'] == (int)$realId[0]) {
                    $prod = $this->Product->findById($node['product_id']);
                    $this->log($prod['Product'], 'updateQty');
                    if((int)$prod['Product']['quantity'] > 0) {
                        $updatedQty = (int)$prod['Product']['quantity'] - $n['qty'];
                        if($this->Product->updateAll(array(
                            'Product.quantity' => (int)$updatedQty
                        ), array(
                            'Product.id' => (int)$node['product_id']
                        ))) {
                            $this->log('Update SUCCESS qty product: '.(int)$node['product_id'].' to: '.$prod['Product']['quantity'].' - '.$n['qty'].' = '.$updatedQty, 'updateQty');
                        }else {
                            $this->log('Update FAILED qty product: '.(int)$node['product_id'], 'updateQty');
                        }
                    }
                }
            }
        }
    }

}