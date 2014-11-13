<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 02.08.11
 * Time: 11:53
 *
 * @property User                  $User
 * @property Order                 $Order
 * @property Product               $Product
 * @property Category              $Category
 * @property ProductImage          $ProductImage
 * @property OrderProduct          $OrderProduct
 * @property CartComponent         $Cart
 * @property AuthorizeNetComponent $AuthorizeNet
 *
 */
 
class AdminProductsController extends AdminAppController{
    public $name = "AdminProducts";
    public $uses = array("Product", "Category", "User");
    public $components = array("Cart", "AuthorizeNet");
    private $_authorizeDebug = false;

    public function beforeFilter() {
        parent::beforeFilter();
        $this->_setHoverFlag("product");
        $this->_setLeftMenu("product");
    }

    public function index() {



        $conditions = array();
        if (isset($this->data['Product']['search'])) {
            if (!empty($this->data['Product']['search'])) {
                $this->Session->write('search', $this->data['Product']['search']);
            } else {
                $this->Session->delete('search');
            }
            $conditions = array_merge(
                $conditions,
                array(
                    "Product.title LIKE " => "%{$this->data['Product']['search']}%"
                )
            );
        }

        if ($this->Session->check('search')) {
            $this->data['Product']['search'] = $this->Session->read('search');
            $conditions = array_merge(
                $conditions,
                array(
                    "Product.title LIKE " => "%{$this->Session->read('search')}%"
                )
            );
        }

        $this->paginate = array(
            "contain"    => array(),
            "conditions" => $conditions,
            "order"      => array(
                "Product.lft"
            )
        );
        $data = $this->paginate("Product");

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Set::extract("/Product/.", $data);
        $this->set('items', $data);
    }

    public function add($cat_id=null, $copy_id=null) {
        $this->_saveData();

        if (isset($cat_id) && !is_null($cat_id) && !empty($cat_id) && $cat_id != 'copy') {
            $this->data['Product']['category_id'] = $cat_id;
        }
        if (isset($copy_id) && !is_null($copy_id)) {
            $this->_getProductData($copy_id);
            $this->set('copy', 'copy');
            //unset($this->data['Product']['id']);
        }
        $this->set('productList', $this->Product->getProductList());
        $this->set('categories', $this->getCategories());
    }

    public function edit($id=null) {
        $this->_saveData();
        if (isset($id) && is_numeric($id)) {

            $this->Product->bindModel(array(
                'hasMany' => array(
                    'CategoriesProduct'))
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $this->Product->contain(array(
                'CategoriesProduct',
                'ProductAttributeGroup' => array(
                    'ProductAttribute' => array(
                        'order' => 'ProductAttribute.id'
                    ),
                    'order' => 'ProductAttributeGroup.id'
                ),
                'ProductImage',
            ));
            $this->data = $this->Product->find('first', array(
                'conditions' => array(
                    'Product.id' => $id
                ),

            ));

            $max = 0;
            foreach ($this->data['ProductAttributeGroup'] as $groups) {
                foreach ($groups['ProductAttribute'] as $prop) {
                     if ($prop['id'] > $max) {
                        $max = $prop['id'];
                    }
                }
            }
            $this->set("propMax", $max);



            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $this->data['CategoriesProduct']['category_id'] = Set::extract('/CategoriesProduct/./category_id', $this->data);

            if (isset($this->data['Product']['charity'])) {
                $this->data['Product']['charity'] = unserialize($this->data['Product']['charity']);
            }
            
            $this->set('productList', $this->Product->getProductList());
            $this->set('categories', $this->getCategories());
            $this->render("add");
        }
    }

    public function _getProductData($id) {
        $this->Product->bindModel(array(
            'hasMany' => array(
                'CategoriesProduct'))
        );
        /** @noinspection PhpUndefinedMethodInspection */
        $this->Product->contain(array(
            'CategoriesProduct',
            'ProductAttributeGroup' => array(
                'ProductAttribute' => array(
                    'order' => 'ProductAttribute.id'
                ),
                'order' => 'ProductAttributeGroup.id'
            ),
            'ProductImage',
        ));
        $this->data = $this->Product->find('first', array(
            'conditions' => array(
                'Product.id' => $id
            ),

        ));

        $max = 0;
        foreach ($this->data['ProductAttributeGroup'] as $groups) {
            foreach ($groups['ProductAttribute'] as $prop) {
                if ($prop['id'] > $max) {
                    $max = $prop['id'];
                }
            }
        }
        $this->set("propMax", $max);



        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $this->data['CategoriesProduct']['category_id'] = Set::extract('/CategoriesProduct/./category_id', $this->data);


        if (isset($this->data['Product']['charity'])) {
            $this->data['Product']['charity'] = unserialize($this->data['Product']['charity']);
        }
        $this->loadModel("ProductImage");
        $productImages = $this->ProductImage->getAllPictures($id);
        $this->set('productImages', $productImages);
    }

    private function _saveData() {
        if ($this->data) {

            $tmpAttr = Set::extract($this->data, "{attr}");
            if (count($tmpAttr) > 0) {
                $this->data['Product']['quantity'] = 0;
            }

            if (isset($this->data['Product']['charity'])) {
                $this->data['Product']['charity'] = serialize($this->data['Product']['charity']);
            } else {
                $this->data['Product']['charity'] = null;
            }

            if (isset($this->data['Product']['id']) && !empty($this->data['Product']['id']) && is_numeric($this->data['Product']['id'])) {
                $this->loadModel("ProductImage");
                $this->ProductImage->deleteAll(array("ProductImage.product_id" => $this->data['Product']['id']));
            }


            if($this->Product->add($this->data)) {
                $this->_setFlash('Item successfully edited', 'success');
                $this->redirect("index");
            } else {
                $this->_setFlash('Errors occured, please see below', 'error');
            }
    
        }
    }
    public function ajaxDelete($id)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 0);
        if(empty($id) && !is_numeric($id))
        {
            return false;
        }

        if($this->Product->delete($id))
        {
            exit("okey");
        }

        return false;
    }


    public function ajaxDeleteImage($id)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 0);
        $this->loadModel("ProductImage");
        if(empty($id) && !is_numeric($id))
        {
            return false;
        }

        if($this->ProductImage->delete($id))
        {
            exit("okey");
        }

        return false;
    }

    public function addProductPicture() {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 0);
        $this->loadModel("ProductImage");
        $data = empty($_POST) ?"": $_POST;
        if (isset($data) && !empty($data)) {
            if ($this->ProductImage->save($data)) {
                $all = $this->ProductImage->getAllPictures($data['product_id']);
                exit(json_encode($all));
            }

        }
        return false;

    }

    function activate_exclusive($model, $id)
    {
        $this->loadModel("{$model}");
        if(!$id && !$this->data) {
            $this->_setFlash('Invalid Page', 'error');
            // TODO show error ?
            $this->render('empty');
        } else {
            $this->$model->id = $id;
        }

        $active = $this->$model->read('exclusive', $id);
        if($active["{$model}"]['exclusive'] == 1) {
            $this->$model->saveField('exclusive', 0);
        } else {
            $this->$model->saveField('exclusive', 1);
        }

        $this->redirect($this->referer());
    }

    function activate_sale($model, $id)
    {
        $this->loadModel("{$model}");
        if(!$id && !$this->data) {
            $this->_setFlash('Invalid Page', 'error');
            // TODO show error ?
            $this->render('empty');
        } else {
            $this->$model->id = $id;
        }

        $active = $this->$model->read('sale', $id);
        if($active["{$model}"]['sale'] == 1) {
            $this->$model->saveField('sale', 0);
        } else {
            $this->$model->saveField('sale', 1);
        }

        $this->redirect($this->referer());
    }

    function activate_week($model, $id)
    {
        $this->loadModel("{$model}");
        if(!$id && !$this->data) {
            $this->_setFlash('Invalid Page', 'error');
            // TODO show error ?
            $this->render('empty');
        } else {
            $this->$model->id = $id;
        }

        $active = $this->$model->read('week_item', $id);
        if($active["{$model}"]['week_item'] == 1) {
            $this->$model->saveField('week_item', 0);
        } else {
            $this->$model->saveField('week_item', 1);
        }

        $this->redirect($this->referer());
    }

    public function order_add() {




        $this->Product->contain();
        $data = $this->Product->find("list",
            array(
                "conditions" => array(
                    "Product.active" => 1
                ),
                "fields" => array(
                    "Product.id", "Product.title"
                )
            )
        );
        $this->set('products', $data);
    }

    public function ajax_get_attributes($id) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 0);

        if (empty($id)) {
            return false;
        }

        $data = $this->Product->find("first",
            array(
                "contain" => array(
                    'ProductAttributeGroup' => array(
                        'ProductAttribute' => array(
                            'order' => 'ProductAttribute.id'
                        ),
                        'order' => 'ProductAttributeGroup.id'
                    ),
                ),
                "conditions" => array(
                    "Product.active" => 1,
                    "Product.id" => $id
                ),
            )
        );

        if (is_array($data['ProductAttributeGroup']) && count($data['ProductAttributeGroup']) > 0) {
            exit(json_encode($data['ProductAttributeGroup']));
        }
        return false;

    }

    public function cart() {


        if(!empty($this->data['Product']['quantity']) && !isset($this->data['Product']['attributes']))
        {
            $this->Cart->addProduct($this->data['Product']['product'], $this->data['Product']['quantity']);
            $this->redirect('/admin/admin_products/order_add');
        }
        else
        {
            if(!empty($this->data)) {
                if(!empty($this->data['Product']['product'])) {
                    $quantity = !empty($this->data['Product']['quantity']) ? $this->data['Product']['quantity'] : 1;
                    $attributes = array();
                    if (isset($this->data['Product']['attributes'])) {
                        foreach ($this->data['Product']['attributes'] as $attributeName => $attributeValue)
                        {
                            if (!empty($attributeValue)) $attributes[] = $attributeValue;
                        }
                    }
                    $this->Cart->addProduct($this->data['Product']['product'], $quantity, $attributes);
                    $this->redirect('/admin/admin_products/order_add');
                }
            } elseif (!empty($idProduct) && is_numeric($idProduct)) {
                $this->Cart->addProduct($idProduct, 1);
                $this->redirect('/admin/admin_products/order_add');
            }
        }
        $this->redirect('/admin/admin_products/order_add');
    }

    public function checkout()
    {
        $this->loadModel("State");
        $this->Cart->setTax(0);
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

                $data["Shipping"]   = $this->data["Order"];
                $this->Session->write("BillingShipping", $data);

                if ($this->data['Order']['country'] == 153) { // 153 - USA
                    $this->loadModel("Region");
                    $total = $this->Cart->in_cart();
                    $tax = $this->Region->getStateTax($this->data['Order']['state'], $total['Total']);
                    $this->Cart->setTax($tax);
                } else {
                    $this->loadModel("Country");
                    $total = $this->Cart->in_cart();
                    $tax = $this->Country->getCountryTax($this->data['Order']['country'], $total['Total']);
                    $this->set('vat', true);
                    $this->Cart->setTax($tax);
                }
                $this->redirect("payment");
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

    public function saveOrder() {
        $this->Cart->setTax(0);
        if(!empty($this->data)) {

            if($this->data["Order"]["same"]) {
                $this->data["Order"] = $this->data["User"];
            }

            $this->loadModel("User");
            $this->User->validate = array(
                'name'      => array('rule' => 'notEmpty'),
                'address1'  => array('rule' => 'notEmpty'),
                'country'   => array('rule' => 'notEmpty'),
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

                $data["Shipping"]   = $this->data["Order"];
                $this->Session->write("BillingShipping", $data);

                if ($this->data['Order']['country'] == 153) { // 153 - USA
                    $this->loadModel("Region");
                    $total = $this->Cart->in_cart();
                    $tax = $this->Region->getStateTax($this->data['Order']['state'], $total['Total']);
                    $this->Cart->setTax($tax);
                } else {
                    $this->loadModel("Country");
                    $total = $this->Cart->in_cart();
                    $tax = $this->Country->getCountryTax($this->data['Order']['country'], $total['Total']);
                    $this->set('vat', true);
                    $this->Cart->setTax($tax);
                }


                $data = $this->Session->read("BillingShipping");


                //FedEx Code Start
                $shipRates = $this->Fedex->getRate();

                if (isset($shipRates['error'])) {
                    foreach ($shipRates['error'] as $error) {
                        $this->_setFlash($error, "error");
                    }
                    $this->redirect($this->referer());
                }

                $notifications = $shipRates['notifications'];
                $shipRates = array_reverse($shipRates['fakeResponse']);

                if (isset($shipRates) && is_array($shipRates)) {

                    $this->set('shipRates', $shipRates);

                }

                if (isset($notifications)) {
                    $this->set('notifications', $notifications);
                }
                // FedEx Code End

                // USPS Code Start
                if ($data['Shipping']["country"] == 153) { // 153 - USA
                    $uspsDomesticRates = $this->Usps->uspsDomestic();
                    $uspsDomesticRates['priority_mail'] = array_reverse($uspsDomesticRates['priority_mail']);
                    if (!$this->Session->check("shippingFlag")) {
                        $this->Cart->setShipping($uspsDomesticRates['priority_mail'][0]['RATE'], "USPS - " . $uspsDomesticRates['priority_mail'][0]['MAILSERVICE']);
                        $this->Session->write("blockId", 'priority_mail');
                    }
                    $this->Session->write("shippingFlag", 1);
                    $this->set('uspsDomasticRates', $uspsDomesticRates);
                } else {
                    $uspsInternationalRates = $this->Usps->uspsInternational();
                    $uspsInternationalRates['international'] = array_reverse($uspsInternationalRates['international']);
                    if (!$this->Session->check("shippingFlag")) {
                        $this->Cart->setShipping($uspsInternationalRates['international'][0]['RATE'], "USPS - " . $uspsInternationalRates['international'][0]['MAILSERVICE']);
                    }
                    $this->Session->write("shippingFlag", 1);
                    $this->Session->write("blockId", 'international');
                    $this->set('uspsDomasticRates', $uspsInternationalRates);
                }
                // USPS Code End

            }
        } else {
            $this->redirect("/admin/admin_orders");
        }
    }

    public function preCharge() {
        $products   = $this->Cart->in_cart();

        if(!empty($products)) {

            $billing    = $this->Session->read("BillingShipping");

            $this->loadModel("Order");
            $this->loadModel("OrderProduct");

            $description = "";
            foreach($products["Products"] as $product) {
                $description .= $product["title"];
                foreach($product["attributes"] as $key => $value) {
                    $description .= " $key: '$value'";
                }
                $description .= "; ";
            }

            $invoice = mktime();
            $coupons = array();
            if ($products['Coupons']) {

                foreach ($products['Coupons'] as $key=>$coupon) {
                    $coupons = array_merge($coupons, array($key));
                }
                unset($coupon);
            }


            $this->Order->id = $invoice;

                if($this->Order->save(array(
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
                    "shipping_method"       => $this->Session->read("Cart.ShippingMethod"),
                    "payment_fname"         => $billing["Billing"]["first_name"],
                    "payment_name"          => $billing["Billing"]["name"],
                    "payment_address_1"     => $billing["Billing"]["address1"],
                    "payment_address_2"     => $billing["Billing"]["address2"],
                    "payment_country"       => $this->_getCountryName($billing["Billing"]["country"]),
                    "payment_city"          => $billing["Billing"]["city"],
                    "payment_state"         => $this->_getRegionName($billing["Billing"]["state"]),
                    "payment_postcode"      => $billing["Billing"]["zip"],
                    "payment_method"        => "Custom order",
                    "shipping"              => $products["Shipping"],
                    "tax"                   => $products["Tax"],
                    "subtotal"              => $products["Subtotal"],
                    "discount"              => $products["Discount"],
                    "total"                 => $products["Total"],
                    "status"                => 1,
                    'is_test_order'         => false,
                    'payment_provider_order_number' => "not available",
                    'coupon'                => (isset($coupons[0])) ? $coupons[0] : false,
                    'custom_order'          => true
                ))) {

                    $orderId = $invoice; //$this->Order->id;

                    foreach($products["Products"] as $key => $product) {
                        $charityValue = "";
                        if (isset($charity[$product["id"]]) && !empty($charity[$product["id"]])) {
                            $charityValue =  $charity[$product["id"]];
                        }
                        $this->OrderProduct->save(array(
                            "id"            => "",
                            "order_id"      => $orderId,
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
                    $this->Cart->clear();
                    $this->Session->delete("shippingFlag");
                    $this->Session->delete("Charity");
                    $this->Session->delete("blockId");
                }
                $this->redirect("/admin/admin_orders");

        }


    }

    public function payment() {
        if ($this->data) {
            $this->loadModel("Order");
            $this->loadModel("OrderProduct");

            $productData = $this->Order->find("first",
                array(
                    "conditions" => array(
                        "Order.id" => $this->data['order_id']
                    )
                )
            );

            $billinginfo = array(
                "fname"     => $productData['Order']['payment_fname'],
                "lname"     => $productData['Order']['payment_name'],
                "address"   => "{$productData['Order']['payment_address_1']} {$productData['Order']['payment_address_2']}",
                "country"   => $productData['Order']['payment_country'],
                "city"      => $productData['Order']['payment_city'],
                "state"     => $productData['Order']['payment_state'],
                "zip"       => $productData['Order']['payment_postcode'],
                "email"     => $productData['Order']['email'],
                "phone"     => $productData['Order']['phone']
            );

            $shippinginfo = array(
                "fname"     => $productData['Order']['shipping_fname'],
                "lname"     => $productData['Order']['shipping_name'],
                "address"   => "{$productData['Order']['shipping_address_1']} {$productData['Order']['shipping_address_2']}",
                "country"   => $productData['Order']['shipping_country'],
                "city"      => $productData['Order']['shipping_city'],
                "state"     => $productData['Order']['shipping_state'],
                "zip"       => $productData['Order']['shipping_postcode'],
            );

            $description = "";
            foreach($productData["OrderProduct"] as $product) {
                $description .= $product["name"];
                $product["attributes"] = unserialize($product["attributes"]);
                foreach($product["attributes"] as $key => $value) {
                    $description .= " $key: '$value'";
                }
                $description .= "; ";
            }

            $invoice = $this->data['order_id'];
            //$this->_authorizeDebug = true;
            $pay = $this->chargeCard(
                $billinginfo, $shippinginfo, $productData['Order']['total'], $productData['Order']['tax'], $productData['Order']['shipping'],
                $invoice, $description
            );

            if($pay[1] == 1) {
                $paymentMethod = $pay[52] . " "
                        . substr(
                            $this->data["Credit"]["number"],
                                strlen($this->data["Credit"]["number"]) - 4
                        );
                $toSave = array(
                    'id'                            => $invoice,
                    'payment_method'                => "{$paymentMethod}",
                    'payment_provider_order_number' => "{$pay[38]}",
                );
                if ($this->Order->save($toSave)) {
                    $this->_setFlash("Payment complited", "success");
                } else {
                    $this->_setFlash(
                        "<strong>Error: {$pay[4]}&nbsp;&nbsp;&nbsp;Please check your info and try again.</strong>",
                        "error"
                    );
                }
            } else {
                $this->_setFlash(
                    "<strong>Error: {$pay[4]}&nbsp;&nbsp;&nbsp;Please check your info and try again.</strong>",
                    "error"
                );
            }
            $this->redirect("/admin/admin_orders");

        }
    }

    private function chargeCard($billinginfo, $shippinginfo, $total, $tax, $shipping, $invoice, $description = "The Situation") {
        $response = $this->AuthorizeNet->chargeCard(
            '9Ga24WRa5Hd', '95w8P24G7Bek22Aa', $this->data["Credit"]["number"],
            $this->data["Credit"]["date"]["month"], $this->data["Credit"]["date"]["year"],
            $this->data["Credit"]["code"], $this->_authorizeDebug, $total, $tax, $shipping, $description,
            $billinginfo, $billinginfo["email"], $billinginfo["phone"], $shippinginfo,
            $invoice
        );
        return $response;
    }
    



}
