<?php
/**
 * @author: Dmitry404
 *
 * @property OrderProduct $OrderProduct
 *
 */
class Order extends AppModel implements PaymentsPlugin_IPaymentEventListener
{
    public $name = 'Order';
    public $hasMany = array('OrderProduct' => array(
		'dependent' => true
	));
    private $_lowQty = 10;

    public $actsAs = array('Containable');
    protected $_statuses = array(
        1  => "Canceled_Reversal",
        2  => "Completed",
        3  => "Denied",
        4  => "Expired",
        5  => "Failed",
        6  => "In-Progress",
        7  => "Partially_Refunded",
        8  => "Pending",
        9  => "Processed",
        10 => "Refunded",
        11 => "Reversed",
        12 => "Voided",
    );
    
    function paymentEventPerformed($data)
    {
        $this->log($data, "debug");
        if(!empty($data['EventOrderId'])) {
            if($this->isOrderExists($data['EventOrderId'])) {
                $this->id = $data['EventOrderId'];
                if ($this->save(array('status' => $this->getStatus($data['OrderOriginStatus'])))) {
                    $this->changeQty($data['EventOrderId']);
                }
            }
        }
    }

    public function isOrderExists($id)
    {
        return (bool) $this->find('count', array(
                                                'id' => $id,
                                           ));
    }

    public function getStatus($var) {
        $data = array_flip($this->_statuses);
        return $data[$var];
    }

    public function changeQty($data=null) {
        $classProducAttr = ClassRegistry::init('ProductAttribute');
        $id = $this->OrderProduct->find("all",
            array(
                "conditions" => array(
                    "OrderProduct.order_id" => $data
                ),
                "fields" => array(
                    "OrderProduct.product_id", "OrderProduct.quantity", "OrderProduct.attributes", "OrderProduct.model"
                )
            )
        );
        $getAttributesId = array();
        $notificationAloneProducts = array();
        $notificationAloneProductsAttr = array();
        foreach ($id as $attribute) {
            $productId = $attribute['OrderProduct']['product_id'];
            $mainProductQty = $this->_getProductQty($productId);
            if ($mainProductQty) {
                $notificationAloneProducts[] = $this->_saveMainProductQty($mainProductQty, $productId, $attribute['OrderProduct']['quantity']);
            } else {
                $i = 0;
                foreach ($id as $attribute) {
                    $tmpAttr = $attribute['OrderProduct']['model'];
                    $tmpAttr = explode('.', $tmpAttr);
                    $tmpAttr = explode('-', $tmpAttr[1]);
                    $getAttributesId[$i]['idAttr'] = $tmpAttr;
                    $getAttributesId[$i]['decrQty'] = $attribute['OrderProduct']['quantity'];
                }
            }
        }
        unset($attribute);

        if (is_array($getAttributesId) && count($getAttributesId) > 0) {
            foreach ($getAttributesId as $oneOrderProduct) {
                $getProductAttr = $classProducAttr->find('all', array(
                    'conditions' => array('ProductAttribute.id' => $oneOrderProduct['idAttr']),
                    'fields' => array('quantity', 'id')
                ));
                if ($getProductAttr) {
                    foreach ($getProductAttr as $productAttr) {
                        $oldQty = $classProducAttr->read('quantity', $productAttr['ProductAttribute']['id']);
                        if ($oldQty['ProductAttribute']['quantity'] >= $oneOrderProduct['decrQty']) {
                            $newQty = $oldQty['ProductAttribute']['quantity'] - $oneOrderProduct['decrQty'];
                            if ($newQty < $this->_lowQty) {
                                $notificationAloneProductsAttr[] = $productAttr['ProductAttribute']['id'];
                            }
                            $classProducAttr->id = $productAttr['ProductAttribute']['id'];
                            $classProducAttr->saveField('quantity', $newQty);
                        }
                    }
                }
            }
        }



        return array(array(
            "products"    => $notificationAloneProducts,
            "attributes"  => $notificationAloneProductsAttr
        ));
    }

    public function getOrderDataInInterval($data)
    {
        if (!empty($data['to']) && !empty($data['from'])) {
            $conditions = array(
                "Order.created <=" => $data['to'],
                "Order.created >=" => $data['from'],
            );
        } elseif (!empty($data['to']) && empty($data['from'])) {
            $conditions = array(
                "Order.created <=" => $data['to'],
            );
        } elseif (empty($data['to']) && !empty($data['from'])) {
            $conditions = array(
                "Order.created >=" => $data['from'],
            );
        } else {
            $conditions = array();
        }

        $productConditions = array();

        if (isset($data['charity']) && !empty($data['charity'])) {
            $productConditions = array_merge($productConditions, array("charity" => $data['charity']));
        }
        if (isset($data['category']) && !empty($data['category'])) {
            $productConditions = array_merge($productConditions, array("category" => $data['category']));
        }
        if (isset($data['name']) && !empty($data['name'])) {
            $productConditions = array_merge($productConditions, array("name" => $data['name']));
        }

        if (count($productConditions) > 0) {
            $productConditions = array("AND" => $productConditions);
        }

        $data = $this->find("all",
            array(
                "contain" => array(
                    "OrderProduct" => array(
                        "conditions" => $productConditions
                    )
                ),
                "conditions" => $conditions,
                "order"      => "Order.created DESC",
            )
        );




        foreach ($data as $key=>$del) {
            if(count($del['OrderProduct']) == 0) {
                unset($data[$key]);
            };
        }


        $i = 0;
        $temp = array();
        foreach ($data as $tmp) {
            $temp[$i] = $tmp;
            $i++;
        }

        $data = $temp;


        if (count($data) == 0) {
            return false;
        }

        $donationSumm = 0;
        $total = 0;
        $total_profit = 0;
        foreach ($data as &$orders) {
            $total += $orders['Order']['total'];
            foreach ($orders['OrderProduct'] as &$orderProduct) {
                $charity = (float)end(explode("-", $orderProduct['charity']));

                $donation = ($charity * $orderProduct['price'] * $orderProduct['quantity']) / 100;
                $donationSumm += $donation;

                $totalProfit = (($orderProduct['price'] - $orderProduct['networth']) * $orderProduct['quantity']) - $donation;
                $total_profit += $totalProfit;

                $orderProduct['itemPrice'] = "$" . $this->_formatPrice($orderProduct['price'] * $orderProduct['quantity']);
                $orderProduct['net_profit'] = "$" . $this->_formatPrice($orderProduct['price'] - $orderProduct['networth']);
                $orderProduct['price'] = "$" . $this->_formatPrice($orderProduct['price']);
                $orderProduct['networth'] = "$" . $this->_formatPrice($orderProduct['networth']);

                if(!empty($orderProduct['charity'])) {
                    $orderProduct['charity'] = $orderProduct['charity']
                              . "% ($"
                              . $this->_formatPrice($donation)
                              . ")";
                } else {
                    $orderProduct['charity'] = 0;
                }

            }
        }
        $data[0]['donationSumm'] = "$" . $this->_formatPrice($donationSumm);
        $data[0]['total'] = "$" . $this->_formatPrice($total);
        $data[0]['total_profit'] = "$" . $this->_formatPrice($total_profit);

        foreach ($data as &$tmpProduct) {
            foreach($tmpProduct['OrderProduct'] as &$tmpOrder) {
                $tmpOrder['attributes'] = unserialize($tmpOrder['attributes']);
            }
            unset($tmpOrder);
        }
        unset($tmpProduct);
        return $data;
    }

    private function _formatPrice($number)
    {
        return number_format($number, 2, '.', ',');
    }

    private function _getProductQty($productId) {
        $classProduct = ClassRegistry::init('Product');
        $qty = $classProduct->read("quantity", $productId);
        $qty = $qty['Product']['quantity'];
        if (!is_null($qty) && $qty != 0) {
            return $qty;
        } else {
            return false;
        }
    }

    private function _saveMainProductQty($mainProductQty, $productId, $decrement) {
        $id = null;
        $classProduct = ClassRegistry::init('Product');
        if ($mainProductQty > $decrement) {
            $newQty = $mainProductQty - $decrement;
            if ($newQty < $this->_lowQty) {
                $id = $productId;
            }
            $classProduct->id = $productId;
            $classProduct->saveField('quantity', $newQty);
        }
        return $id;

    }

    public function orderSave($data)
    {
        $invoice = mktime();
        $tmpTxt = 'A waiting info';
//        $this->log('orderSave', 'debug');
        if (isset($data['orderID'])) {
            return $this->save(array(
                "id" => $data['orderID'],
                "payment_fname"                 => (isset($data["PayPal"]["FIRSTNAME"])) ? $data["PayPal"]["FIRSTNAME"] : $tmpTxt,
                "payment_name"                  => (isset($data["PayPal"]["LASTNAME"])) ? $data["PayPal"]["LASTNAME"] : $tmpTxt,
                "payment_address_1"                  => (isset($data["PayPal"]["SHIPTOSTREET"])) ? $data["PayPal"]["SHIPTOSTREET"] : $tmpTxt,
                "payment_city"                       => (isset($data["PayPal"]["SHIPTOCITY"])) ? $data["PayPal"]["SHIPTOCITY"] : $tmpTxt,
                "payment_state"                      => (isset($data['PayPal']["SHIPTOSTATE"])) ? $data["PayPal"]["SHIPTOSTATE"] : $tmpTxt,
                "payment_country"                    => (isset($data['PayPal']["SHIPTOCOUNTRYNAME"])) ? $data["PayPal"]["SHIPTOCOUNTRYNAME"] : $tmpTxt,
                "payment_postcode"                   => (isset($data['PayPal']["SHIPTOZIP"])) ? $data["PayPal"]["SHIPTOZIP"] : $tmpTxt,
                "payer_id"                           => (isset($data['PayPal']["PAYERID"])) ? $data["PayPal"]["PAYERID"] : $tmpTxt,
                "shipping"                           => (isset($data['PayPal']["SHIPPINGAMT"])) ? $data["PayPal"]["SHIPPINGAMT"] : $tmpTxt,
                "subtotal"                           => (isset($data['PayPal']["ITEMAMT"])) ? $data["PayPal"]["ITEMAMT"] : $tmpTxt,
                "total"                              => (isset($data['PayPal']["AMT"])) ? $data["PayPal"]["AMT"] : 0,
                'tax'                                => (isset($data['PayPal']["TAXAMT"])) ? $data["PayPal"]["TAXAMT"] : 0,
                //"payment_provider_order_number"      => (isset($data['PayPal']['TOKEN'])) ? $data["PayPal"]['TOKEN'] : $tmpTxt
            ));
        }

        if (isset($data['PayPal']['taxTotal'])) {
            $taxTotal = $data['PayPal']['taxTotal'];   // In priority!!
        } else {
            $taxTotal = (isset($data['PayPal']["TAXAMT"])) ? $data["PayPal"]["TAXAMT"] : 0;   // And then this
        }
        return $this->save(array(
            "id"                                 => (isset($data['orderID'])) ? $data['orderID'] : $invoice,
            "phone"                              => $data["Billing"]["phone"],
            "email"                              => $data["Billing"]["email"],
            "shipping_fname"                => $data["Shipping"]["first_name"],
            "shipping_name"                 => $data["Shipping"]["name"],
            "shipping_address_1"                 => $data["Shipping"]["address1"],
            "shipping_address_2"                 => $data["Shipping"]["address2"],
            "shipping_city"                      => $data["Shipping"]["city"],
            "shipping_country"                   => $data["Shipping"]["country"],
            "shipping_state"                     => $data["Shipping"]["state"],
            "shipping_postcode"                  => $data["Shipping"]["zip"],
            "shipping_method"                    => 'Not specified',
            "payment_first_name"                 => (isset($data["PayPal"]["FIRSTNAME"])) ? $data["PayPal"]["FIRSTNAME"] : $tmpTxt,
            "payment_last_name"                  => (isset($data["PayPal"]["LASTNAME"])) ? $data["PayPal"]["LASTNAME"] : $tmpTxt,
            "payment_address_1"                  => (isset($data["PayPal"]["SHIPTOSTREET"])) ? $data["PayPal"]["SHIPTOSTREET"] : $tmpTxt,
            "payment_address_2"                  => $data["Billing"]["address2"],
            "payment_city"                       => (isset($data["PayPal"]["SHIPTOCITY"])) ? $data["PayPal"]["SHIPTOCITY"] : $tmpTxt,
            "payment_state"                      => (isset($data['PayPal']["SHIPTOSTATE"])) ? $data["PayPal"]["SHIPTOSTATE"] : $tmpTxt,
            "payment_country"                    => (isset($data['PayPal']["SHIPTOCOUNTRYNAME"])) ? $data["PayPal"]["SHIPTOCOUNTRYNAME"] : $tmpTxt,
            "payment_postcode"                   => (isset($data['PayPal']["SHIPTOZIP"])) ? $data["PayPal"]["SHIPTOZIP"] : $tmpTxt,
            "payment_method"                     => 'PayPal ExpressCheckout card',
            "shipping"                           => (isset($data['PayPal']["SHIPPINGAMT"])) ? $data["PayPal"]["SHIPPINGAMT"] : $tmpTxt,
            "subtotal"                           => (isset($data['PayPal']["ITEMAMT"])) ? $data["PayPal"]["ITEMAMT"] : $tmpTxt,
            "discount"                           => 0,//$billingShipping['PayPal']["Discount"],
            "total"                              => (isset($data['PayPal']["AMT"])) ? $data["PayPal"]["AMT"] : 0,
            'tax'                                => $taxTotal,
            "status"                             => "6",
            "is_test_order"                      => (isset($data['PayPal']['debug'])) ? $data["PayPal"]['debug'] : 1,
            "user_id"                            => $data['userID'],
            "payment_provider_order_number"      => (isset($data['PayPal']['TOKEN'])) ? $data["PayPal"]['TOKEN'] : $tmpTxt,
            "parallels_payments_details"         => (isset($data['PayPal']['parallels_payments_details'])) ? json_encode($data['PayPal']['parallels_payments_details']) : null
        ));
    }


}