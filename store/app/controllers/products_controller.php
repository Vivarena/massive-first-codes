<?php
/**
 * Created by CNR Miami.
 * User: nike
 * Date: 02.08.11
 * Time: 11:38
 *
 *
 * @property User                    $User
 * @property Order                   $Order
 * @property State                   $State
 * @property Coupon                  $Coupon
 * @property Region                  $Region
 * @property Country                 $Country
 * @property Product                 $Product
 * @property Customer                $Customer
 * @property OrderProduct            $OrderProduct
 * @property CartComponent           $Cart
 * @property FedexComponent          $Fedex
 * @property EmailComponent          $Email
 * @property ProductAttribute        $ProductAttribute
 * @property MessengerComponent      $Messenger
 * @property PaymenTechComponent     $PaymenTech
 * @property AuthorizeNetComponent   $AuthorizeNet
 * @property RequestHandlerComponent $RequestHandler
 *
 *
 */
 
class ProductsController extends AppController{
    public $name = "Products";
    public $uses = array("Product", "Coupon", 'Category');
    public $helpers     = array('Number');
    public $components  = array("Cart", "RequestHandler", "AuthorizeNet", "PaymenTech", "Messenger", 'Email', "Fedex", "Usps");
    public $authorize_debug = false;


    public function GetCategories() {
       $this->set('categories', $this->_initProductMenu());
    }


    /*public function index($limit = 5, $used = 'null', $user_id = null, $services = false, $both = false) {*/
    public function index($limit = 5) {

        if ($this->Session->check("sort")) {
            $sort = $this->Session->read("sort");
        }  else {
            $sort = "ASC";
        }

        if ($this->data) {
            $limit = $this->data['select'];
            $this->Session->write("limit", $limit);
            $sort = $this->data['sort'];
            $this->Session->write("sort", $sort);
        }

        $users_id = (isset($this->params['form']['friendsId']))?
            $this->params['form']['friendsId'] : null;

        $options = (isset($this->params['form']['options']))?
            $this->params['form']['options'] : array();

        if(!isset($options['user_id']) || empty($options['user_id'])){
            $user_id = null;
        }else{
            $user_id = $options['user_id'];
        }

        $users_id[] = 0;

        $this->set("num", $limit);
        $this->set("sortFlag", $sort);

        $cond = array();
        $cond['limit'] = $limit;
        $cond['order'] = 'Product.price '.$sort;
        $cond['group'] = 'Product.id';
        $cond['conditions']['Product.active'] = 1;
        $cond['conditions']['Product.type'] = $options['type'];
        $cond['conditions']['Product.quantity >'] = '0';

//        if(!$both){
//            if($services){
//                $cond['conditions']['Product.is_service'] = 1;
//            }else{
//                $cond['conditions']['Product.is_service'] = 0;
//                if($used == '1') {
//                    $cond['conditions']['Product.used'] = 1;
//                }elseif($used == '0') {
//                    $cond['conditions']['Product.used'] = 0;
//                }
//            }
//        }

        if(!is_null($user_id)) {
            $cond['conditions']['Product.user_id'] = $user_id;
        }else{
            $cond['conditions']['Product.user_id'] = $users_id;
        }

        //exit (print_r($cond));

        $this->paginate = $cond;

        /*$this->paginate = array(
            "conditions" => array(
                "Product.active" => 1,
                'Product.used' => $used,
                'OR' => array(
                    'Product.user_id' => $users_id,
                    'Product.user_id' => null
                )
            ),
            "limit" => $limit,
            "order" => "Product.price " . $sort,
            "group" => "Product.id"
        );*/

        $data = $this->paginate("Product");

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Set::extract("/Product/.", $data);
        $this->set('items', $data);
    }

    public function indexUser($limit = 5) {

        if ($this->Session->check("sort")) {
            $sort = $this->Session->read("sort");
        }  else {
            $sort = "ASC";
        }

        if ($this->data) {
            $limit = $this->data['select'];
            $this->Session->write("limit", $limit);
            $sort = $this->data['sort'];
            $this->Session->write("sort", $sort);
        }

        $users_id = (isset($this->params['form']['friendsId']))?
            $this->params['form']['friendsId'] : null;

        $options = (isset($this->params['form']['options']))?
            $this->params['form']['options'] : array();

        if(!isset($options['user_id']) || empty($options['user_id'])){
            $user_id = null;
        }else{
            $user_id = $options['user_id'];
        }

        $users_id[] = 0;

        $this->set("num", $limit);
        $this->set("sortFlag", $sort);

        $cond = array();
        $cond['limit'] = $limit;
        $cond['order'] = 'Product.price '.$sort;
        $cond['group'] = 'Product.id';
        $cond['conditions']['Product.active'] = 1;
        $cond['conditions']['Product.type'] = $options['type'];

        if(!is_null($user_id)) {
            $cond['conditions']['Product.user_id'] = $user_id;
        }else{
            $cond['conditions']['Product.user_id'] = $users_id;
        }

        $this->paginate = $cond;

        $data = $this->paginate("Product");

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Set::extract("/Product/.", $data);
        $this->set('items', $data);
    }

    public function view($productId) {
        $this->set('referer', $this->referer());
        $this->loadModel("Category");
        if(isset($productId) && !empty($productId))
        {
            $this->Product->addClick($productId);
            $data = $this->Product->find("first",
                     array(
                         "contain" => array(
                             "Category",
                             'ProductImage' => array('fields' => array('path')),
                             'ProductAttributeGroup' => array(
                                 'ProductAttribute' => array(
                                     'order' => 'ProductAttribute.id'
                                 ),
                                 'order' => 'ProductAttributeGroup.id'
                             ),
                         ),
                          "conditions" => array(
                              "Product.active" => 1,
                              "Product.id" => $productId
                          ),
                     )
            );

            $this->set(array(
                "_tag_meta_title"       => $data['Product']['title'],
                "_tag_meta_description" => $data['Product']['description']
            ));

            if (isset($data['ProductAttributeGroup'][0]['ProductAttribute'])) {
                $this->set('sizes', count($data['ProductAttributeGroup'][0]['ProductAttribute']));
            }
            if(count($data['ProductAttributeGroup']) > 0) {
                $qty = 0;
                foreach ($data['ProductAttributeGroup'] as $attrGroup) {
                    if ($attrGroup['name'] == 'Size' ) {

                        foreach ($attrGroup['ProductAttribute'] as $attr) {
                            $qty += $attr['quantity'];
                        }
                    }
                }
                $data['Product']['quantity'] = $qty;
            }

            $data = $this->Product->getGift($data);

            $this->set('data', $data);

            $users_id = (isset($this->params['form']['friendsId']))?
                $this->params['form']['friendsId'] : null;

            $users_id[] = 0;

            $relatedProduct = $this->Product->getRelatedProducts($productId, $users_id);
            $this->set(
                array(
                    'item'           => $data,
                    'relatedProduct' => $relatedProduct
                )
            );
        } else {
            $this->redirect("index");
        }
    }

    public function set_shipping($value = null, $type = null, $blockId = null)
    {
        if (!is_null($blockId)) {
            $this->Session->write("blockId", $blockId);
        }
        if(empty($value)) {
            exit();
        }
        $this->loadModel("Coupon");
        Configure::write('debug', 0);

        $products   = $this->Cart->in_cart();
        if (is_array($products['Coupons']['ship4free9125']))
                $this->Cart->setShipping(0, "FREE");
        else
        	$this->Cart->setShipping($value, $type);
        $this->Cart->in_cart();
        exit($this->_formatPrice($this->Cart->getTotal()));
    }

    public function add_discount($coupon)
    {
        Configure::write('debug', 0);
        $this->loadModel("Coupon");
        if(isset($coupon))
        {
            if(!$this->Cart->add_coupon($coupon)) {
                exit(false);
            }
            else
            {
                $tmp = $this->Cart->in_cart();
                $total    = $this->_formatPrice($tmp['Total']);
                $discount = $this->_formatPrice($tmp['Discount']);
                $result = array("discount" => $discount, "total" => $total);
                exit(json_encode($result));
            }
        }
    }

    public function del_item($id)
    {
        Configure::write('debug', 0);

        $this->Product->contain();
        $idForGiftFinder = explode('.', $id);
        $gift = $this->Product->read("gift", $idForGiftFinder[0]);
        $gift = $gift['Product']['gift'];
        //$id = explode('.', $id);

        if($this->Cart->delProduct($id)) {

            if (isset($gift) && is_numeric($gift)) {
                $this->Cart->delProduct($gift);
                $this->Session->write("gift", $gift);
            }

            $this->_sendCartInfo(null, $singleton = true);
        } else {
            exit("false");
        }
    }
    /**
     *  :-))
     * @return void
     */
    public function setQuantity()
    {
        //$this->Cart->clear();
        $this->layout = null;
        $this->autoRender = false;

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);

        $productId = isset($this->params['form']['productId']) ? $this->params['form']['productId'] : null;
        $direction = isset($this->params['form']['direction']) ? $this->params['form']['direction'] : null;
        $qtyStep = 1;

        $attrId  = explode(".", $productId);

        if (isset($attrId[1]) && !empty($attrId[1])) {
            $this->loadModel("ProductAttribute");
            $qty = $this->ProductAttribute->read('quantity', $attrId[1]);
            $qty = $qty['ProductAttribute']['quantity'];
            //$this->params['form']['idAttr']
        } else {
            $qty = $this->Product->read("quantity", $attrId[0]);
            $qty = $qty['Product']['quantity'];
        }





        $status = false;
        if(!is_null($productId)) {
            $oldQty = $this->Cart->getQuantity($productId);

            if($oldQty) {
                $newQty = $oldQty;
                if ($direction == '+' /*&& $oldQty < $this->Cart->getAvailable($productId)*/) {
                    $newQty = $oldQty + $qtyStep;
                } elseif($direction == '-') {
                    if (($oldQty - $qtyStep) <= 0) {
                        $newQty = 1;
                    } else {
                        $newQty = $oldQty - $qtyStep;
                    }
                }
                $status = $this->Cart->setQuantity($productId, $newQty);
            }
        }

        $this->_sendCartInfo($status, $singleton = true);
    }


    public function ajaxSetCharity() {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        //$this->Session->delete("Charity");
        $id = isset($this->params['form']['productId']) ? $this->params['form']['productId'] : null;
        $charity = isset($this->params['form']['value']) ? $this->params['form']['value'] : null;

        $realId = $id;
        $realCharity = explode("-", $charity);
        $realCharity = trim($realCharity[1]);


        $cart = $this->Cart->in_cart();
        $price = $cart['Products'][$realId]['price'];
        $qty  = $cart['Products'][$realId]['qty'];
        $percent = ($price * $realCharity * $qty)/100;


        $id = explode(".", $id);
        $id = $id[0];

        $charity = str_replace(".",",",$charity);

        //$charity = serialize($charity);
        $this->Session->delete("Charity.{$id}");
        $this->Session->write("Charity.{$id}.{$charity}");

//        Configure::write("debug", 0);
//        if ($this->Cart->setCharity($id,$charity)) {
//            $this->_sendCartInfo();
//        }
        $percent = "$" . $this->_formatPrice($percent);
        $result = array(
            'percent' => $percent,
            'id'      => $id
        );
        exit(json_encode($result));
    }

    public function updateCart()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $this->_sendCartInfo();
    }


    private function _sendCartInfo($status = null, $singleton = false)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $this->layout = null;
        $this->autoRender = false;
        $cartInfo = $this->Cart->in_cart($singleton);

        $charities = array();
        if ($this->Session->check("Charity")) {
            $charities = $this->Session->read("Charity");
            $cart = $this->Session->read("Cart");
            $tmp = array();
            foreach ($charities as $key=>$value) {
                $prodId = "";
                foreach ($cart['Products'][$key] as $prodKey=>$prodEmpty) {
                    $prodId = $prodKey;
                }
                foreach($value as $temp=>$empty) {
                    $percent = explode("-", $temp);
                    $tmp[$key . "." . $prodId] = trim(end($percent));
                }
            }
            $charities = $tmp;

        }


        $infoToSend = array();
        $infoToSend['Products'] = array();
        foreach($cartInfo['Products'] as $id => $product) {

//            $price = empty($product['price']) ? $product['rprice'] : $product['price'];
            $price = empty($product['price']) ? 0 : $product['price'];
            $percent = 0.00;
            if (isset($charities[$id])) {
                $percent = ($price * $charities[$id] * $product['qty'])/100;
            }
            $percentId = explode(".", $id);
            $percentId = $percentId[0];
            $infoToSend['Products'][] = array(
                'id' => $id,
                'qty' => $product['qty'],
                'price' => $this->_formatPrice($price),
                'available' => $product['available'],
                'total' => $this->_formatPrice($product['qty'] * $price),
                'percent' => $this->_formatPrice($percent),
                'percentId' => $percentId,
            );
        }

        $infoToSend['Shipping']     = $this->_formatPrice($cartInfo['Shipping']);
        $infoToSend['Discount']     = $this->_formatPrice($cartInfo['Discount']);
        $infoToSend['Subtotal']     = $this->_formatPrice($cartInfo['Subtotal']);
        $infoToSend['Total']        = $this->_formatPrice($cartInfo['Total']);
        if ($this->Session->check("gift")) {
            $infoToSend['Gift']     = $this->Session->read("gift");
        }


        if(!is_null($status)) {
            $infoToSend['status'] = (bool)$status;
        }

        $this->RequestHandler->setContent('json');
        exit(json_encode($infoToSend));
    }

    private function _formatPrice($number)
    {
        return number_format($number, 2, '.', ',');
    }


    public function checkout()
    {
        $this->loadModel("State");
        $this->Cart->setTax(0);
        if($this->RequestHandler->isPost()) {
            $this->data = $_POST;
        }
        if(!empty($this->data)) {

            if($this->data["Order"]["same"]) {
                $this->data["Order"] = $this->data["User"];
            }

            $this->loadModel("User");
            $this->User->validate = array(
                'name'      => array('rule' => 'notEmpty'),
                'address1'   => array('rule' => 'notEmpty'),
                'country'      => array('rule' => 'notEmpty'),
                'city'      => array('rule' => 'notEmpty'),
                'state'     => array('rule' => 'notEmpty'),
                'zip'       => array('rule' => 'notEmpty'),
                'phone'     => array('rule' => 'notEmpty'),
//                'email'     => array(
//                    1 => array('rule' => 'email'),
//                    0 => array('rule' => 'notEmpty')
//                )
            );
            $this->User->set($this->data);

            $this->loadModel("Order");
            $this->Order->validate = array(
                'name'      => array('rule' => 'notEmpty'),
                'address1'   => array('rule' => 'notEmpty'),
                'country'      => array('rule' => 'notEmpty'),
                'city'      => array('rule' => 'notEmpty'),
                'state'     => array('rule' => 'notEmpty'),
                'zip'       => array('rule' => 'notEmpty'),
                'phone'     => array('rule' => 'notEmpty'),
//                'email'     => array(
//                    1 => array('rule' => 'email'),
//                    0 => array('rule' => 'notEmpty')
//                )
            );
            $this->Order->set($this->data);

            $validUser  = $this->User->validates();
            $validOrder = $this->Order->validates();

            if($validUser && $validOrder) {
                unset($this->data["Order"]["frombilling"]);

                $data["Billing"]  = $customerData = $this->data["User"];

                $this->loadModel("Customer");
                $customerData['country'] = $this->_getCountryName($customerData['country']);
                $customerData['state'] = $this->_getRegionName($customerData['state']);
                $this->Customer->save($customerData);

                $data["Shipping"]   = $this->data["Order"];
                $this->Session->write("BillingShipping", $data);

                if ($this->data['Order']['country'] == 153) { // 153 - USA
                    $this->loadModel("Region");
                    $cart = $this->Cart->in_cart();
                    $sshipping = 0;
                    $ttax = 0;
                    foreach ($cart['Products'] as $key => $product) {
                        if(isset($product['tax']) && $product['tax'] > $ttax) {
                            $sshipping += (int) $product['shipping'];
                            $ttax += (int) $product['tax'];
                        }
                    }
                    $tax = $this->Region->getStateTax($this->data['Order']['state'], $cart['Total']);
                    $this->Cart->setTax($ttax);
                    $this->Cart->setShipping($sshipping, '');
                } else {
                    $this->loadModel("Country");
                    $cart = $this->Cart->in_cart();
                    $sshipping = 0;
                    $ttax = 0;
                    foreach ($cart['Products'] as $key => $product) {
                        if(isset($product['tax']) && $product['tax'] > $ttax) {
                            $sshipping += (int) $product['shipping'];
                            $ttax += (int) $product['tax'];
                        }
                    }
                    $tax = $this->Country->getCountryTax($this->data['Order']['country'], $cart['Total']);
                    $this->set('vat', true);
                    $this->Cart->setTax($ttax);
                    $this->Cart->setShipping($sshipping, '');
                }

                  $this->set('step', 2);
//                $this->redirect("payment");
            }
        }

        if($this->Session->check("BillingShipping")) {
            $data = $this->Session->read("BillingShipping");
            $this->data["User"]     = $data["Billing"];
            $this->data["Order"]    = $data["Shipping"];
        }
        $this->set('parent_id', '1');
        //$this->set("states", $this->State->find("list", array("fields" => array("initials", "name"))));
        $this->loadModel("Country");
        $this->set('countries', $this->Country->find("list",
                array(
                    "fields" => array(
                        "id", "name"
                    ),
                    "order" => "name"
                )
            )
        );
    }

    public function payment() {
//        $this->Cart->clear();
//        $this->Session->delete("shippingFlag");
        $data = $this->Session->read("BillingShipping");

        $products   = $this->Cart->in_cart();

        $sshipping = 0;
        $ttax = 0;
        foreach ($products['Products'] as $key => $product) {
            $sshipping += (int) $product['shipping'];
            $ttax += (int) $product['tax'];
        }
        $this->Cart->setShipping($sshipping, '');
        $this->Cart->setTax($ttax);

        $this->log($products, 'paymentMethod');

        $data['Billing']["country"] = $this->_getCountryName($data['Billing']["country"]);
        $data['Billing']["state"] = $this->_getRegionName($data['Billing']["state"]);
        $data['Shipping']["country"] = $this->_getCountryName($data['Shipping']["country"]);
        $data['Shipping']["state"] = $this->_getRegionName($data['Shipping']["state"]);
        $this->set('addr', $data);
        $this->set('authorizeDebug', $this->authorize_debug);


	    if (is_array($products['Coupons']['ship4free9125'])) {
            $this->Cart->setShipping(0,"FREE");
        }

    }

    public function cart()
    {
        //for API
        if(empty($this->data)) {
            $this->data = $_POST;
        }
        //

        if(!empty($this->data['Product']['quantity']) && !isset($this->data['Product']['attributes']))
        {
            $giftId = $this->Product->getGiftId($this->data['Product']['id']);
            if (!is_null($giftId)) {
                $this->Cart->addProduct($giftId, 1);
                $this->Session->write("gift" . $this->data['Product']['id'], $giftId);
            }

            $this->Cart->addProduct($this->data['Product']['id'], $this->data['Product']['quantity']);
            //$this->redirect('/shopping-cart.html');
        }
        else
        {
            if(!empty($this->data)) {
                if(!empty($this->data['Product']['id'])) {
                    $giftId = $this->Product->getGiftId($this->data['Product']['id']);
                    if (!is_null($giftId)) {
                        $this->Cart->addProduct($giftId, 1);
                        $this->Session->write("gift" . $this->data['Product']['id'], $giftId);
                    }

                    $quantity = !empty($this->data['Product']['quantity']) ? $this->data['Product']['quantity'] : 1;
                    $attributes = array();
                    if (isset($this->data['Product']['attributes'])) {
                        //$this->loadModel('ProductAttribute');
                        foreach ($this->data['Product']['attributes'] as $attributeName => $attributeValue)
                        {
                            if (!empty($attributeValue)) $attributes[] = $attributeValue;
                        }
                    }
                    $this->Cart->addProduct($this->data['Product']['id'], $quantity, $attributes);
                    //$this->redirect('/shopping-cart.html');
                }
            } elseif (!empty($idProduct) && is_numeric($idProduct)) {
                $this->Cart->addProduct($idProduct, 1);
                //$this->redirect('/shopping-cart.html');
            }
        }

        if ($this->Session->check("Charity")) {
            $charities = $this->Session->read("Charity");
            $cart = $this->Session->read("Cart");
            $tmp = array();

            foreach ($charities as $key=>$value) {
                $prodId = "";
                foreach ($cart['Products'][$key] as $prodKey=>$prodEmpty) {
                    $prodId = $prodKey;
                }
                foreach($value as $temp=>$empty) {
                    $tmp[$key . "." . $prodId] = str_replace(",",".",$temp);
                }
            }
            $charities = $tmp;
            
            $this->set('Charity', $charities);
        }
    }



    public function by_category($id=null) {
        if (!isset($id) || empty($id) || is_null($id)) {
            $this->redirect("index");
        }

        $users_id = (isset($this->params['form']['friendsId']))?
            $this->params['form']['friendsId'] : null;

        $options = (isset($this->params['form']['options']))?
            $this->params['form']['options'] : array();

        $users_id[] = 0;

        $parents = $this->Category->getpath($id);
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $path = Set::extract("/Category/id", $parents);
        $this->set('path', $path);

        $num = 8;
        $sortFlag = 'ASC';
        $sort = 'Product.price ' . $sortFlag;
        if ($this->data) {
            $num = $this->data['select'];
            $sortFlag = $this->data['sort'];
            $sort = "Product.price " . $sortFlag;
            if (isset($num) && !empty($num)) {
                $this->Session->write("num", $num);
                $this->Session->write("sort", $sortFlag);
            }
        } else {
            if ($this->Session->check("num")) {
                $num = $this->Session->read("num");
            }
            if ($this->Session->check("sort")) {
                $sort = "Product.price " . $this->Session->read("sort");
            }

        }

        $this->set('num', $num);
        $this->set('sortFlag', $sortFlag);

        $this->paginate = array(
            "contain" => array(
                'ProductAttributeGroup' => array(
                    'ProductAttribute' => array(
                        'order' => 'ProductAttribute.id'
                    ),
                    'order' => 'ProductAttributeGroup.id'
                ),
                "CategoriesProduct",
                "Category"
            ),
            "conditions" => array(
                'CategoriesProduct.category_id' => $id,
                'Product.type' => $options['type'],
                'Product.active' => 1,
                'Product.user_id' => $users_id,
            ),
            "limit" => $num,
            "order" => $sort
        );
        //exit(print_r($this->paginate));

        $productsByCategory = $this->paginate("Product");
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Set::extract("/Product/.", $productsByCategory);
        $categoryName = $this->Category->getName($id);
        $this->set('items', $data);
        $this->set('current', $this->here);
        $this->set('categoryName', $categoryName);
        $this->render("index");
    }
    public function thankyou($orderId)
    {

        $products   = $this->Cart->in_cart();

        if(!empty($products)) {

            $this->Cart->clear();
            $this->Session->delete("shippingFlag");
            $this->Session->delete("Charity");
            $this->Session->delete("blockId");
            $this->set("orderId", $orderId);
            $this->set('server_name', env("SERVER_NAME"));

            $this->Messenger->sendUserOrderConfirmation($orderId);

            $this->log($orderId, 'order_ID');
            $this->log('thank you end', 'ty');
        } else {
            exit('<?xml version="1.0" encoding="UTF-8" ?><data><error>No items in cart!</error></data>');
        }
    }

    private function chargeCard($billinginfo, $shippinginfo, $total, $tax, $shipping, $invoice, $description = "The Situation") {
        $response = $this->AuthorizeNet->chargeCard(
            '9Ga24WRa5Hd', '95w8P24G7Bek22Aa', $this->data["Credit"]["number"],
            $this->data["Credit"]["date"]["month"], $this->data["Credit"]["date"]["year"],
            $this->data["Credit"]["code"], $this->authorize_debug, $total, $tax, $shipping, $description,
            $billinginfo, $billinginfo["email"], $billinginfo["phone"], $shippinginfo,
            $invoice
        );
        return $response;
    }


    public function invoice($id) {
        if (!isset($id) || empty($id)) {
            $this->redirect($this->referer());
        }
        $this->loadModel("Order");
        $this->Order->contain("OrderProduct");
        $data = $this->Order->find("first",
                                   array(
                                        "conditions" => array(
                                            "Order.id" => $id
                                        )
                                   )
        );
        $this->log($data, 'fromInvoice');
        foreach ($data['OrderProduct'] as &$tmpOrder) {
            $tmpOrder['attributes'] = unserialize($tmpOrder['attributes']);
        }
        unset($tmpOrder);

        $this->set('data', $data);
    }

    function getQty($isAjax = true)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $qty = array();

        if (isset($this->params['form']['idAttr']) && is_array($this->params['form']['idAttr'])){
            $id = end($this->params['form']['idAttr']);
            $this->loadModel('ProductAttribute');
            $qty = $this->ProductAttribute->find('all', array(
                                                 'conditions' => array('ProductAttribute.id' => $id),
                                                 'fields' => 'ProductAttribute.quantity'));

            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $qty = Set::extract('/ProductAttribute/quantity/.', $qty);

            $qty = min($qty);

        }


        if (!$qty) $err = true;
        $result = array(
            'error' => $err,
            'qty' => $qty,
            'err_desc' => $err_desc,
        );
        if (!$isAjax) return $result;

        exit(json_encode($result));
    }

    public function test() {
        $data = array(
            'products'   => array(20),
            'attributes' => array(1714, 1317)
        );
        $this->_processLowProductNotification($data);
    }
    private function _processLowProductNotification($data) {
        $products = $data['products'];
        $this->_getProductInfoForNotificationAndSendIt($products);
        $attributes = $data['attributes'];
        $this->_getProductAttributeInfoForNotificationAndSendIt($attributes);
    }

    private function _getProductInfoForNotificationAndSendIt($products) {
        foreach ($products as $product) {
            if (!is_null($product)) {
                $data = $this->Product->read(array('title', 'fullfillment', "vendor", "sitch_style", "quantity"), $product);
                $this->_sendProductLowNotification($data['Product']);
            }
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

    public function sendXml() {
        $xml = "<?xml version=\"1.0\" encoding=\"iso-8859-1\" standalone=\"yes\"?>

        ";
    }

    public function ajaxGetStates($id) {
        $this->loadModel('Region');
        Configure::write('debug', 0);
        $this->autoRender = false;

        $result = $this->Region->find('list', array(
            'fields' => array('id', 'name'),
            'conditions' => array('Region.country_id' => $id)
        ));

        exit(json_encode($result));
    }

}
 
