<?php
/**
 * User: Dmitry404
 * Date: 08.02.2010
 * Time: 14:16:11
 *
 * @property SessionComponent $Session
 *
 */
class CartComponent extends Object 
{
    public $components = array('Session', 'RequestHandler');

    /**
     * Cart session name
     * @access private
     */
    private $_cartName = 'Cart';
    /**
     * Name of variable for Controller::set
     * @access private
     */
    private $_varName = 'Cart';
    /**
     * Data of products in cart
     * @access private
     */
    private $_products = array();
    /**
     * Current Sub-total value
     * @access private
     */
    private $_subtotal = 0;
    /**
     * Current Shipping value
     * @access private
     */
    private $_shipping = 0;
    /**
     * Current Discount value
     * @access private
     */
    private $_discount = 0;

    /**
     * Current Charity value
     * @access private
     */
    private $_charity = 0;
    
    /**
     * Current Tax value
     * @access private
     */
    private $_tax = 0;
    
    private $_extra = array();
    /**
     * Cart output status
     * @access private
     */
    private $_outputDisabled = false;
/**
 * Discount coupons array
 * @access private
 * @var array
 */
    private $coupons = array();

    /**
     * called before Controller::beforeFilter()
     * @param  $controller
     * @param array $settings
     * @return void
     */
    function initialize(&$controller, $settings = array())
    {
        if($this->RequestHandler->isAjax() == true) {
            $this->_outputDisabled = true;
        }

        $this->controller = $controller;
        $this->ProductAttribute = ClassRegistry::init('ProductAttribute');
        $this->Product = ClassRegistry::init('Product');
        $this->Coupon = ClassRegistry::init('Coupon');
//        $this->ProductAttribute = ClassRegistry::init('ProductAttribute');

        if(isset($settings['cartName']) && !empty($settings['cartName'])) {
            $this->_cartName = $settings['cartName'];
        }
        if(isset($settings['varName']) && !empty($settings['varName'])) {
            $this->_varName = $settings['varName'];
        }

        if(!$this->Session->check("{$this->_cartName}")) {
            $this->Session->write("{$this->_cartName}", null);
        }

		$this->_restoreSavedParams();
    }

    /**
     * called after Controller::beforeFilter()
     * @param  $controller
     * @return void
     */
    function startup(&$controller)
    {
    }

    /**
     * called after Controller::beforeRender()
     * @param  $controller
     * @return void
     */
    function beforeRender(&$controller)
    {
        if($this->_outputDisabled == false) {
            $controller->set($this->_varName, $this->in_cart());
        }
    }

    /**
     * Save item to session
     * @access private
     * @param  $key
     * @param  $value
     * @return boolean
     */
    private function __saveItem($key, $value)
    {
        $this->coupons = $value;
        return $this->Session->write("{$this->_cartName}.{$key}", $value);
    }

    /**
     * Delete item from session
     * @access private
     * @param  $key
     * @return boolean
     */
    private function __deleteItem($key)
    {
        return $this->Session->delete("{$this->_cartName}.{$key}");
    }

    /**
     * Read item from session
     * @access private
     * @param  $key
     * @return mixed
     */
    private function __readItem($key)
    {
        if($this->__checkItem($key)) {
            return $this->Session->read("{$this->_cartName}.{$key}");
        } else {
            return false;
        }
    }

    /**
     * Checks existence of item
     * @access private
     * @param  $key
     * @return boolean
     */
    private function __checkItem($key)
    {
        return $this->Session->check("{$this->_cartName}.{$key}");
    }

	/**
	 * Restore saved Cart params from session
	 * @access private
	 */
	private function _restoreSavedParams()
	{
		if($this->__checkItem('Shipping')) {
			$this->_shipping = $this->__readItem('Shipping');
		}
		if($this->__checkItem('Discount')) {
			$this->_discount = $this->__readItem('Discount');
		}
		if($this->__checkItem('Tax')) {
			$this->_tax = $this->__readItem('Tax');
		}
		if($this->__checkItem('Extra')) {
			$this->_extra = $this->__readItem('Extra');
		}
	}

	/**
	 * Calculate and return total sum
	 * @return int
	 */
	private function _calcTotal()
	{
		$total = $this->_subtotal;
		$total += $this->_shipping;
		$total += $this->_tax;

		$total -= $this->_discount;

		return $total;
	}

    /**
     * Set product information to $this->_products variable item
     * @access private
     * @param  $productId
     * @param  $attributes
     * @return void
     */
    private function _setProductInfo($productId, $attributes)
    {

        $idAttributes = array();
        foreach($attributes as $id => $qty){
            if (!empty($id)) {
                $multipleId = explode('-', $id);
                foreach($multipleId as $idOneAttr){
                    $idAttributes[] = $idOneAttr;
                }
            }
        }
        $this->Product->contain(array(
            'ProductAttributeGroup' => array(
                'ProductAttribute' => array(
                    'order' => 'ProductAttribute.id',
                    'fields' => array(
                        'id', 'value', 'price', 'prefix', 'quantity'
                    )),
                'order' => 'ProductAttributeGroup.id'
            )));
                
        $productsData = $this->Product->find('all', array(
            'conditions' => array(
                'Product.id' => $productId),
            'fields' => array(
                'Product.id', 'Product.title', "Product.charity",
		        'Product.image', 'Product.tax',
                'Product.price', 'Product.shipping', 'Product.tax','Product.networth', 'Product.user_id', 'Product.pay_pal_acc',
                'Product.quantity AS available'
            )
        ));



        foreach($attributes as $itemKey => $itemQty)
        {
            if(!empty($productsData[0]['Product'])) {
                $productKey = "{$productId}.{$itemKey}";
                $this->_products[$productKey]['id'] = $productsData[0]['Product']['id'];
                $this->_products[$productKey]['title'] = $productsData[0]['Product']['title'];
                $this->_products[$productKey]['image'] = $productsData[0]['Product']['image'];
                $this->_products[$productKey]['qty'] = $itemQty;
                //'price' and 'available' can be overwritten below (if product has attributes)

                $this->_products[$productKey]['price'] = $productsData[0]['Product']['price'];

                $this->_products[$productKey]['shipping'] = $productsData[0]['Product']['shipping'];
                $this->_products[$productKey]['tax'] = $productsData[0]['Product']['tax'];

                $this->_products[$productKey]['user_id'] = $productsData[0]['Product']['user_id'];

                $this->_products[$productKey]['networth'] = $productsData[0]['Product']['networth'];
                $this->_products[$productKey]['pay_pal_acc'] = $productsData[0]['Product']['pay_pal_acc'];
                $this->_products[$productKey]['tax'] = $productsData[0]['Product']['tax'];
                $this->_products[$productKey]['available'] = $productsData[0]['Product']['available'];

                $charity = unserialize($productsData[0]['Product']['charity']);

                $this->_products[$productKey]['charity'] = $charity;
                $this->_products[$productKey]['available'] = $productsData[0]['Product']['available'];

                if($this->_products[$productKey]['available'] == 0)
                {
                     $temp = $this->ProductAttribute->find('first',
                        array(
                            'conditions' => array(
                                'ProductAttribute.id' => $itemKey,
                            ),
                            'fields' => "ProductAttribute.quantity"
                        )
                    );
                    $this->_products[$productKey]['available'] = $temp['ProductAttribute']['quantity'];

                }

                $this->_products[$productKey]['attributes'] = array();
                $multipleKeys = explode('-', $itemKey);
                foreach($multipleKeys as $attrKey)
                {
                    $attrData = Set::extract("/ProductAttributeGroup/ProductAttribute[id={$attrKey}]", $productsData);
                    if(!empty($attrData)) {
                        $attrData = $attrData[0]['ProductAttribute'];
                        $attrGroupName = Set::extract("/ProductAttributeGroup[id={$attrData['product_attribute_group_id']}]/name", $productsData);
                        $attrGroupName = $attrGroupName[0];

                        if(empty($this->_products[$productKey]['attributes']) || !array_key_exists($attrGroupName, $this->_products[$productKey]['attributes'])) {
                            $this->_products[$productKey]['attributes'] += array(
                                $attrGroupName => $attrData['value']
                            );
                        }

						if(!empty($attrData['quantity']) && ($attrData['quantity'] < $this->_products[$productKey]['available'])) {
							$this->_products[$productKey]['available'] = $attrData['quantity'];
						}

                        if($attrData['prefix'] == '+') {
                            $this->_products[$productKey]['price'] += $attrData['price'];
                        } elseif($attrData['prefix'] == '-') {
                            $this->_products[$productKey]['price'] -= $attrData['price'];
                        }
                    }
                }
                
                $productsId = $this->Session->read("Cart");
                $productsId = $productsId['Products'];

                foreach ($productsId as $key=>$tmp) {
                    if ($this->Session->check("gift" . $key)) {
                        $giftInfo = $this->Session->read("gift" . $key);
                        if ($giftInfo == $productKey) {
                            $this->_products[$productKey]['price'] = 0;
                            $this->_products[$productKey]['available'] = 1;
                            $this->_products[$productKey]['qty'] = 1;
                        }
                    }
                }
                unset($key);
                unset($tmp);

            }
        }
    }

    /**
     * Make composite product id from ($id and $options)
     * @access private
     * @param  $id
     * @param  $options
     * @return string
     */
    private function _getCompositeId($id, $options)
    {

        if(!empty($options)) {
            asort($options);
            $optionsString = implode('-', $options);
            return "{$id}.{$optionsString}";
        } else {
            return "$id.0";
        }
    }

    /**
     * Set product quantity (id product not exists, then create it)
     * (Service method)
     * @access private
     * @param  $id
     * @param  $quantity
     * @return boolean
     */
    private function _setQuantity($id, $quantity)
    {

        return $this->__saveItem("Products.{$id}", $quantity);
    }

    private function _setAvailable($id, $quantity)
    {
        return $this->__saveItem("Products.{$id}.available", $quantity);
    }

    /**
     * Set product charity
     * (Service method)
     * @access private
     * @param  $id
     * @param  $value
     * @return boolean
     */
    private function _setCharity($id, $value)
    {
        return $this->__saveItem("Products.{$id}.avl_charity", $value);
    }



    /**
     * Return product quantity
     * (Service method)
     * @access private
     * @param  $id
     * @return int
     */
    private function _getQuantity($id)
    {
        return $this->__readItem("Products.{$id}");
    }

    

    /**
     * Update cart values
     * @access private
     * @return void
     */
    private function _updateCart()
    {
        $this->_subtotal = 0;

        if(is_array($this->_products)) {
            foreach($this->_products as $product) {
                $this->_subtotal += $product['price'] * $product['qty'];
            }
        }
        //$this->_subtotal += $this->_subtotal * ($this->_charity/100);
    }

    /**
     * Checks existence of product
     * @param  $id
     * @param array $options
     * @return boolean
     */
    public function checkProduct($id, $options = array())
    {
        if(!empty($options)) {
            $key = $this->_getCompositeId($id, $options);
        } else {
            $key = $id;
        }
        return $this->__checkItem("Products.{$key}");
    }

    /**
     * Return product quantity if exists or false
     * @param  $id
     * @return int | false
     */
    

    /**
     * Set products quantity if exists or false
     * @param  $id
     * @param  $quantity
     * @return boolean
     */
    public function setQuantity($id, $quantity)
    {
        if($this->checkProduct($id)) {
            $this->_setQuantity($id, $quantity);

            $this->_updateCart();
            return true;
        } else {
            $this->_updateCart();
            return false;
        }
    }

    /**
     * Set charity
     * @param $id
     * @param $value
     *
     * @internal param $qty
     *
     * @internal param $shipping
     *
     * @internal param $method
     * @return bool
     */
    public function setCharity($id, $value)
    {

        if($this->checkProduct($id)) {

            $this->_setCharity($id, $value);

            $this->_updateCart();

            return true;
        } else {
            return false;
        }

    }

    public function setAvailable($id, $quantity)
    {
        if($this->checkProduct($id)) {
            $this->_setAvailable($id, $quantity);

            $this->_updateCart();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Add product to cart
     * @param  $id
     * @param int $quantity
     * @param  $options
     * @return boolean
     */
    public function addProduct($id, $quantity = 1, $options = null)
    {
        if($this->checkProduct($id, $options)) {
            $pro = $this->Product->findById($id);
            $quantity += $this->_getQuantity($this->_getCompositeId($id, $options));
            $this->controller->log($quantity, 'qtyUpdateCart');
            if((int)$pro['Product']['quantity'] < (int)$quantity) {
                return $this->_updateCart();
            }
        }

        //if product not exists in cart, then it will be added
        $this->_setQuantity($this->_getCompositeId($id, $options), $quantity);
        

        $this->_updateCart();
    }

    /**
     * Delete product from cart
     * @param  $id
     * @return boolean
     */
    public function delProduct($id)
    {

        if($this->checkProduct($id)) {

            if(!empty($this->_products["{$id}"]["discount"])) {
                $this->_discount -= $this->_products["{$id}"]["discount"]["sum"];
            }

            if($this->__deleteItem("Products.{$id}")) {
                unset($this->_products[$id]);
				$rootId = substr($id, 0, strpos($id, '.'));
				$rootNode = $this->__readItem("Products.{$rootId}");
				if(empty($rootNode)) {
					$this->__deleteItem("Products.{$rootId}");
				}




                $this->_updateCart();
                return true;
            }
        }

        return false;
    }


    /**
     * Clear cart
     * @return void
     */
    public function clear()
    {
        if($this->Session->check($this->_cartName)) {
            $this->Session->write($this->_cartName, null);

            $this->_products    = array();
            $this->_extra       = null;
            $this->_subtotal    = 0;
            $this->_shipping    = 0;
            $this->_discount    = 0;
            $this->_tax         = 0;
            
        }
    }


    /**
     * Return current discount
     * @param boolean $percent
     * @return int
     */
    public function getDiscount($percent = true)
    {
        if($percent == true) {
            return $this->_discount;
        } else {
            return $this->_subtotal * ($this->_discount / 100); 
        }
    }

    /**
     * Return shipping value
     * @return int
     */
    public function getShipping()
    {
        return $this->_shipping;
    }

    /**
     * Set shipping value and method
     * @param   $shipping
     * @param   $method
     * @return  void
     */
    public function setShipping($shipping, $method)
    {
		$this->__saveItem('Shipping', $shipping);
        $this->__saveItem('ShippingMethod', $method);
		$this->_shipping = $shipping;
    }





    /**
     * Return tax value
     * @return int
     */
    public function getTax()
    {
        return $this->_tax;
    }

    /**
     * Set tax value
     * @param  $tax
     * @return void
     */
    public function setTax($tax)
    {
		$this->__saveItem('Tax', $tax);
        $this->_tax = $tax;
    }

	/**
	 * Return extra parameter value or false if not exists
	 * @param string $title
	 * @return boolean
	 */
    public function getExtraParam($title)
    {
		if($this->__checkItem("Extra.{$title}")) {
			return $this->__readItem("Extra.{$title}");
		} else {
			return false;
		}
    }

    public function getQuantity($id)
    {
        if($this->checkProduct($id)) {
            return $this->_getQuantity($id);
        } else {
            return false;
        }
    }



	/**
	 * Set extra parameter value
	 * @param string $name
	 * @param void $value 
	 */
    public function setExtraParam($name, $value)
    {
		$this->__saveItem("Extra.{$name}", $value);
    }

	/**
	 * Remove extra parameter
	 * @param string $name
	 * @return boolean
	 */
	public function delExtraParam($name)
	{
		if($this->__checkItem("Extra.{$name}")) {
			$this->__deleteItem("Extra.{$name}");
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks existence of extra parameter
	 * @param string $name
	 * @return boolean
	 */
	public function checkExtraParam($name)
	{
		return $this->__checkItem("Extra.{$name}");
	}

	/**
	 * Remove all extra parameters
	 * @return void
	 */
	public function clearExtraParameters()
	{
		if($this->__checkItem('Extra')) {
			$this->__deleteItem('Extra');
		}
	}

    /**
     * Disable Cart output
     * @return void
     */
    public function disableOutput()
    {
        $this->_outputDisabled = true;
    }

    /**
     * Enable Cart output
     * @return void
     */
    public function enableOutput()
    {
        $this->_outputDisabled = false;
    }

    /**
     * Return total value
     * @return int
     */
    public function getTotal()
    {
        return $this->_calcTotal();
    }

    /**
     * Return subtotal value
     * @return int
     */
    public function getSubtotal()
    {
        return $this->_subtotal;
    }

    /**
     * Return true if cart empty
     * @return boolean
     */
    public function isEmpty()
    {
		return ($this->__checkItem('Products') == false) ? true : false;
    }

    /**
     * in_cart
     * Get Cart Data
     *
     * @param bool $singleton
     * @return array
     */
    public function in_cart($singleton = false)
    {
        $this->_updateCart();
        $cart = $this->Session->read($this->_cartName);
        $singletonFlag = false;
        $isMagicProduct = false;
        if(!empty($cart['Products'])) {
            foreach($cart['Products'] as $productId => $attributes)
            {
                $this->_setProductInfo($productId, $attributes);

                /*
                 * --- Product discount ---
                 */
                $this->coupons = $this->Session->read("$this->_cartName.Coupons");

                if(!empty($this->coupons)) {
                    foreach($this->coupons as $code => $coupon) {
                        $discoutFlag =  $this->check_coupon($coupon["id"], $productId);


                        if($discoutFlag) {
                            $isMagicProduct = true;
                            foreach($attributes as $key => $qty) {
                                    if(!empty($this->_products["$productId.$key"]["discount"])) {
                                        if ($this->_products["$productId.$key"]["discount"]["coupon"] == $code) {
                                            $this->_discount -= $this->_products["$productId.$key"]["discount"]["sum"];
                                        } else {
                                            $this->_discount = 0;
                                        }
                                    }

                                    $price = $this->_products["$productId.$key"]["price"];

                                    $discount = 0;
                                    if ($price * $qty > $coupon["amount"] ) {
                                        if ($coupon["type"] == "per") {
                                            $discount   = $price * $coupon["value"] / 100 * $qty;
                                            $this->_discount += $discount;
                                        } else {
                                            $discount   = $coupon["value"];
                                            $this->_discount += $discount;
                                        }
                                            //$discount = 0;
                                    }

                                    $this->_products["$productId.$key"]["discount"] = array(
                                        "coupon"    => $code,
                                        "percent"   => $coupon["value"],
                                        "sum"       => $discount,
                                        "type"      => $discoutFlag
                                    );
                            }
                        } elseif(!$isMagicProduct && !$this->checkCouponExistingProduct($coupon["id"])) {
                            $this->_updateCart();
                            foreach($attributes as $key => $qty) {
                                $price = $this->_subtotal;
                                if ($price > $coupon["amount"] && !$singletonFlag) {
                                    if (!$singleton) {
                                        $singletonFlag = true;
                                    }

                                    if ($coupon["type"] == "per") {
                                        $discount   = $price * $coupon["value"] / 100;
                                        $this->_discount = $discount;
                                    } else {
                                        $discount   = $coupon["value"];
                                        $this->_discount = $discount;
                                    }
                                } else {
                                    if ($singleton) {
                                        $this->_discount = 0;
                                    }
                                }

                                $this->_products["$productId.$key"]["discount"] = array(
                                    "coupon"    => $code,
                                    "percent"   => 0,
                                    "sum"       => 0,
                                    "type"      => $discoutFlag
                                );
                            }
                        }
                    }
                }
                /*
                 * --- End ---
                 */
            }
        }

        $this->_updateCart();


        //set cart variable in controller
        $cartData['Products']   = $this->_products;
        $cartData['Subtotal']   = $this->_subtotal;
        $cartData['Shipping']   = $this->_shipping;
        $cartData['Discount']   = $this->_discount;
        $cartData['Tax']        = $this->_tax;
        $cartData['Total']      = $this->_calcTotal();
        $cartData['Coupons']    = $this->coupons;
        $this->log($cartData, 'cart_data');
        return $cartData;
    }

/**
 * add_coupon
 * Adds a Discount coupon. If there is a coupon returns TRUE else FALSE
 *
 * @author Vitaliy Kh.
 *
 * @param string $code
 * @return bool
 */
    public function add_coupon($code = null)
    {
        if(empty($code)) {
            return false;
        }

        $this->Session->delete("$this->_cartName.Coupons");
        $coupons = $this->Session->read("$this->_cartName.Coupons");
        if(empty($coupons) || !array_key_exists($code, $coupons)) {

            $couponsTmp = array();
            $coupons    = !empty($coupons) ? $coupons : array();

            $this->controller->Coupon->contain();
            $coupon = $this->controller->Coupon->find(
                array("Coupon.code" => $code), array(
                    "Coupon.id", "Coupon.value", "Coupon.type", "Coupon.amount",
                    "Coupon.start", "Coupon.stop",
                )
            );

            $now = date("Y-m-d", mktime());

            if(empty($coupon) || $coupon['Coupon']['start'] > $now || $coupon['Coupon']['stop'] < $now ) {
                return false;
            }

            $couponsTmp["$code"] = array(
                "id"      => $coupon["Coupon"]["id"],
                "value"   => $coupon["Coupon"]["value"],
                "type"    => $coupon["Coupon"]["type"],
                "amount"  => $coupon["Coupon"]["amount"],
            );

            $value = $coupons + $couponsTmp;
            $this->__saveItem('Coupons', $value);



        }
        $this->_updateCart();
        return true;
    }


    private function _delCoupon($key='Coupons', $value) {
        $this->__deleteItem($key, $value);
    }

/**
 * check_coupon
 * Check coupon discount on product
 *
 * @param int $coupon_id
 * @param int $product_id
 * @return bool
 */
    private function check_coupon($coupon_id = null, $product_id = null)
    {
        
        if(empty($coupon_id) || empty($product_id)) {
            return false;
        }

        $check = $this->controller->Coupon->check($coupon_id, $product_id);
        return $check;
    }

    private function checkCouponExistingProduct($coupon_id = null)
    {

        if(empty($coupon_id)) {
            return false;
        }

        $check = $this->controller->Coupon->getProductCount($coupon_id);
        return $check;
    }

    public function getCartItemsQuantity() {
        $items = $this->in_cart();
        return  count($items['Products']);
    }

    public function getAvailable($id)
    {
        if($this->checkProduct($id)) {
            return $this->_products[$id]['available'];
        } else {
            return false;
        }
    }
}