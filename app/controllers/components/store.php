<?php
/**
 * Created by Slava Basko
 * Email: basko.slava@gmail.com
 * Date: 5/8/13
 * Time: 14:32 PM
 *
 * @property MegaCurlComponent $MegaCurl
 * @property SessionComponent $Session
 */

class StoreComponent extends Object {

    /**
     * @var Controller
     */
    private $controller;

    public $components = array('MegaCurl', 'Session');

    private $store_url;
    
    public $cookie_file;

    /**
     * @param $controller Controller
     */
    function initialize(&$controller) {
        $this->controller =& $controller;
        $this->store_url = 'http://store.'.str_replace("www.","", env('HTTP_HOST'));
     // $this->store_url = 'http://'.str_replace("www.","", env('HTTP_HOST')) .'/store/';
        $this->cookie_file = TMP.'cookie_user'.$this->Session->read('Auth.User.id').'.txt';
    }

    /**
     * Some serious logic for convert xml to php array =)
     *
     * @param $data
     * @return array
     */
    private function XmlToArray($data) {
//        if(Configure::read('debug') == 0) {
            libxml_use_internal_errors(true);
//        }
        if($xml = simplexml_load_string($data))
            return (array) json_decode(json_encode($xml), true);
        return false;
    }

    /**
     * Return categories list from store
     *
     * @return array
     */
    public function GetCategories() {
        $data = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/products/GetCategories.xml')
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->SetHttpMethod('get')
            ->Execute();
        $data = $this->XmlToArray($data);
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Set::extract('/category/.', $data);
        self::recursive_array($data);
        /*$data = array_map($self = function($node) use (&$self) {
            if(!isset($node['children'])) {
                return $node;
            }
            return $self($node['children']);
        }, $data);*/
        return $data;
    }

    /**
     * Get paginated products from store
     *
     * @param int $qty
     * @param int $page
     * @param null $category
     * @return array
     */
    public function GetProducts($qty = 8, $page = 1, $category = null) {

//        if($this->controller->services){
//            $url = $this->store_url.'/products/index/'.(int)$qty.'/0/null/1/page:'.(int)$page.'.xml';
//
//        }elseif($this->controller->used){
//            $url = $this->store_url.'/products/used/'.(int)$qty.'/1/page:'.(int)$page.'.xml';
//
//        }else{
//            $url = $this->store_url.'/products/index/'.(int)$qty.'/0/page:'.(int)$page.'.xml';
//
//        }

//        $url = ($this->controller->used)?
//            $this->store_url.'/products/used/'.(int)$qty.'/1/page:'.(int)$page.'.xml':
//            $this->store_url.'/products/index/'.(int)$qty.'/0/page:'.(int)$page.'.xml';

//        if($category) {
//            $url = ($this->controller->used)?
//                $this->store_url.'/products/used/by_category/'.(int)$category.'/1/page:'.(int)$page.'.xml':
//                $this->store_url.'/products/by_category/'.(int)$category.'/0/page:'.(int)$page.'.xml';
//        }

        $url = $this->store_url.'/products/index/'.(int)$qty.'/page:'.(int)$page.'.xml';

        $options = array();

        if($category) {
            $url = $this->store_url.'/products/by_category/'.(int)$category.'/page:'.(int)$page.'.xml';
        }


        if($this->controller->services){
            $options['type'] = 'service';
        }elseif($this->controller->used){
            $options['type'] = 'used';
        }else{
            $options['type'] = 'new';
        }


        $data = $this->MegaCurl
            ->SetRequestUrl($url)
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->ExecutePost(array(
                'friendsId' => $this->controller->UserFriend->getFriendsId($this->controller->myId),
                'options' => $options
            ));

        $data = $this->XmlToArray($data);

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $products = Set::extract('/product/.', $data['products']);
        $paging = $data['pagination'];
        return compact('products', 'paging');
    }

    /**
     * Get information about product
     *
     * @param $id
     * @return mixed
     */
    public function GetProduct($id) {
        $data = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/products/view/'.(int)$id.'.xml')
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->ExecutePost(array('friendsId' => $this->controller->UserFriend->getFriendsId($this->controller->myId)));

        $data = $this->XmlToArray($data);
        if(isset($data['related_products']['product'])) {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $data['related_products'] = Set::extract('/related_products/product/.', $data);
        }
        return $data;
    }

    /**
     * Get product qty
     *
     * @return bool|mixed
     */
    public function GetQty() {
        $data = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/products/getQty')
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->ExecutePost($this->controller->params['form']);
        return $data;
    }

    /**
     * Put or get cart data
     *
     * @param array $data
     * @return array
     */
    public function Cart(array $data = null) {
        if(is_array($data)) {
            $this->MegaCurl
                ->SetRequestUrl($this->store_url.'/products/cart.xml')
                ->OneSession($this->cookie_file)
                ->SetOptions(array(
                    'FOLLOWLOCATION' => true
                ))
                ->ExecutePost($data);
        }
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/products/cart.xml')
            ->OneSession($this->cookie_file)
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->SetHttpMethod('get')
            ->Execute();
        $response = $this->XmlToArray($response);
        return $response;
    }


    /**
     * Delete item form cart
     *
     * @param $key
     * @return bool|mixed
     */
    public function DeleteItemFromCart($key) {
        return $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/products/del_item/'.$key)
            ->OneSession($this->cookie_file)
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->SetHttpMethod('get')
            ->Execute();
    }

    public function SetCharity($key) {}

    /**
     * Add discount by coupon
     *
     * @param $key
     * @return bool|mixed
     */
    public function AddDiscount($key) {
        $data = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/products/add_discount/'.$key)
            ->OneSession($this->cookie_file)
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->Execute();
        return $data;
    }

    /**
     * Set product quantity
     *
     * @return bool|mixed
     */
    public function SetQuantity() {
        $data = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/products/setQuantity')
            ->OneSession($this->cookie_file)
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->ExecutePost($this->controller->params['form']);
        return $data;
    }

    public function UpdateCart($key) {}

    public function SetShipping($key) {}

    /**
     * Without $data render billing and shipping form
     * With $data - put it in store and render pre payment page(pre invoice)
     *
     * @param null $data
     * @return array|bool|mixed
     */
    public function CheckOut($data = null) {
        if($data === null) {
            $response = $this->MegaCurl
                ->SetRequestUrl($this->store_url.'/products/checkout.xml')
                ->OneSession($this->cookie_file)
                ->SetOptions(array(
                    'FOLLOWLOCATION' => true
                ))
                ->Execute();
            $response = $this->XmlToArray($response);
        }else {
            $response = $this->MegaCurl
                ->SetRequestUrl($this->store_url.'/products/checkout.xml')
                ->OneSession($this->cookie_file)
                ->SetOptions(array(
                    'FOLLOWLOCATION' => true
                ))
                ->ExecutePost($data);
            if($response) {
                $payment_response = $this->MegaCurl
                    ->SetRequestUrl($this->store_url.'/products/payment.xml')
                    ->OneSession($this->cookie_file)
                    ->SetOptions(array(
                        'FOLLOWLOCATION' => true
                    ))
                    ->ExecutePost($data);
                $payment_response = $this->XmlToArray($payment_response);
                return $payment_response;
            }
        }
        return $response;
    }

    /**
     * Return USA states list
     *
     * @param $country_id
     * @return bool|mixed
     */
    public function GetStates($country_id) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/products/ajaxGetStates/'.$country_id.'.xml')
            ->OneSession($this->cookie_file)
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->Execute();
        return $response;
    }

    /**
     * Return generated by PayPal URL to redirect
     *
     * @param $fromCart bool - if TRUE - it's parallels payments / FALSE - it's normal payment method
     * @return array
     */
    public function GetPaypalUrl($fromCart = false) {
        $urlPay = ($fromCart) ? '/paypal_cart.xml' : '/paypal.xml';
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.$urlPay)
            ->OneSession($this->cookie_file)
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->Execute();
        $response = $this->XmlToArray($response);
        return $response;
    }

    /**
     * Return details after payment
     *
     * @param null $token
     * @param null $payer_id
     * @return array
     */
    public function GetAfterPaymentDetails($token = null, $payer_id = null) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/payments_express/get_details/'.$token.'/'.$payer_id)
            ->OneSession($this->cookie_file)
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->Execute();
        $response = $this->XmlToArray($response);
        return $response;
    }

    /**
     * Return order ID
     *
     * @param $order_id
     * @return array
     */
    public function ThankYou($order_id) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/thank-you/'.$order_id)
            ->OneSession($this->cookie_file)
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->Execute();
        $response = $this->XmlToArray($response);
        return $response;
    }

    /**
     * Send email to admin, return buyer email and sellers id's
     *
     * @param $order_id
     * @return array
     */
    public function GetEmailAfterPayment($order_id) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/api/EmailAfterPayment/'.$order_id.'.xml')
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->Execute();
        $response = $this->XmlToArray($response);
        return $response;
    }

    /**
     * Restart user session on store
     *
     * @return bool
     */
    public function RenewStoreSession() {
        return $this->MegaCurl->RenewSession();
    }

    public function SendEmailToBuyer() {}

    /**
     * Return user products
     *
     * @param int $qty
     * @param int $page
     * @param null $user_id
     * @return array
     */
    public function GetUsersProducts($qty = 8, $page = 1, $user_id = null) {

        //$url = $this->store_url.'/products/index/'.(int)$qty.'/null/'.(int)$user_id.'/0/1/page:'.(int)$page.'.xml';
        $url = $this->store_url.'/products/indexUser/'.(int)$qty.'/page:'.(int)$page.'.xml';

        $options = array(
            'user_id' => $user_id,
            'type' => array('new','used','service','event')
        );

        $data = $this->MegaCurl
            ->SetRequestUrl($url)
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->ExecutePost(array(
                'friendsId' => $this->controller->UserFriend->getFriendsId($this->controller->myId),
                'options' => $options
            ));


//        $data = $this->MegaCurl
//            ->SetRequestUrl($url)
//            ->SetOptions(array('FOLLOWLOCATION' => true))
//            ->SetHttpMethod('post')
//            ->Execute();
        // xml to array
        /*print_r($data);exit();*/
        $data = $this->XmlToArray($data);
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $products = Set::extract('/product/.', $data['products']);
        $paging = $data['pagination'];
        return compact('products', 'paging');
    }

    /**
     * Start "Add Product" process on store
     *
     * @param array $data
     * @return array
     */
    public function UserAddProduct(array $data) {

        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/api/add.xml')
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->ExecutePost($data);
        $response = $this->XmlToArray($response);
        return $response;
    }

    /**
     * @param $id
     * @return array
     */
    public function UserEditProduct($id) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/api/edit/'.(int)$id.'.xml')
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->Execute();

        $response = $this->XmlToArray($response);
        return $response;
    }

    /**
     * Delete user product
     *
     * @param $id
     * @return array
     */
    public function UserDelProduct($id) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/api/delete/'.(int)$id.'.xml')
            ->SetOptions(array(
                'FOLLOWLOCATION' => true
            ))
            ->Execute();
        $response = $this->XmlToArray($response);
        return $response;
    }

    /**
     * @param array $arr
     */
    static function recursive_array(array &$arr){
        foreach ($arr as $key => &$val) {
            if(is_array($val)){
                if($key == 'category'){
                    if(!isset($val[0]) && !is_numeric($key)){
                        $val = array(0 => $val);
                        self::recursive_array($val[0]);
                    }else{
                        self::recursive_array($val);
                    }
                }else{
                    self::recursive_array($val);
                }
            }
        }
    }

    /**
     * Return invoice data for rendering on Vivarena
     *
     * @param $id
     * @return array
     */
    public function invoice($id){
        if(is_numeric($id)){
            $url = $this->store_url.'/invoice/'.(int)$id.'.xml';

            $data = $this->MegaCurl
                ->SetRequestUrl($url)
                ->SetOptions(array('FOLLOWLOCATION' => true))
                ->SetHttpMethod('get')
                ->Execute();

            $data = $this->XmlToArray($data);
            $this->log($data, 'fromCurlInvoice');
            return $data;
        }
    }

    /**
     * Return PayPal URL after user press "Add" button in "Add product" form
     *
     * @param $product_id
     * @param array $user_info
     * @return array
     */
    public function AddProductProcess($product_id, array $user_info) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/api/AddProductProcess.xml')
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->ExecutePost(array(
                'product_id' => $product_id,
                'user_data' => $user_info
            ));
        return $this->XmlToArray($response);
    }

    /**
     * Activate product
     *
     * @param int $id
     * @return array
     */
    public function ActiveProduct($id = 0, $token = null, $payerId = null) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/api/ActiveProduct/'.$id.'.xml')
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->ExecutePost(array(
                'token' => $token,
                'payerId' => $payerId
            ));
        return $this->XmlToArray($response);
    }

    /**
     * Start Re post user product
     *
     * @param $id
     * @param $qty
     * @return array
     */
    public function RepostProduct($id, $qty) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/api/RepostProductProcess/'.$id.'/'.$qty.'.xml')
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->Execute();
        return $this->XmlToArray($response);
    }

    /**
     * Complete Re post user product
     *
     * @param $id
     * @param $qty
     * @return array
     */
    public function CompleteRepostProduct($id, $qty, $token = null, $payerId = null) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/api/CompleteRepostProduct/'.$id.'/'.$qty.'.xml')
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->ExecutePost(array(
                'token' => $token,
                'payerId' => $payerId
            ));
        return $this->XmlToArray($response);
    }

    /**
     * Return user last inserted product
     *
     * @param null $user_id
     * @return array
     */
    public function GetLastInsertedProduct($user_id = null) {
        $url = $this->store_url.'/api/GetLastInsertedProduct.xml';
        if(ctype_digit($user_id)) {
            $url = $this->store_url.'/api/GetLastInsertedProduct/'.(int)$user_id.'.xml';
        }
        $response = $this->MegaCurl
            ->SetRequestUrl($url)
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->Execute();
        return $this->XmlToArray($response);
    }

    /**
     * Decrement process
     *
     * @param null $orderId Invoice ID
     * @param array $cartProducts Array of products from cart
     * @return array
     */
    public function DecrementProductsQty($orderId = null, array $cartProducts = array()) {
        $response = $this->MegaCurl
            ->SetRequestUrl($this->store_url.'/api/DecrementProductsQty.xml')
            ->SetOptions(array('FOLLOWLOCATION' => true))
            ->ExecutePost(array(
                'order_id' => (int)$orderId,
                'cart_products' => $cartProducts
            ));
        return $this->XmlToArray($response);
    }
}