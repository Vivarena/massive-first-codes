<?php
/**
 * User: Nike
 * Date: May 32, 2011
 * Time: 12:51:10 PM
 *
 * @property CouponsProduct $CouponsProduct
 *
 */

class Coupon extends AppModel
{
    public $name        = "Coupon";
    public $hasMany     = array(
        "CouponsProduct"        => array('dependent' => true)
    );
    public $actsAs      = array('Containable');
    public $validate    = array(
        "code"  => array(
            0 => array('rule' => 'notEmpty'),
            1 => array('rule' => 'isUnique')
        ),
        "value" => array('rule' => 'notEmpty', 'required'  => true),
    );

/**
 * edit
 * If not specified $coupon_id creates a new coupon
 * for another editing an existing one.
 *
 * @param array $data
 * @param int $coupon_id
 * @return boolean
 */
    public function edit($data = array(), $coupon_id = null)
    {
        if(empty($data)) {
            return false;
        }

        /**
         * Check coupon status
         */
        if(
            empty($data['Coupon']['start'])
            && empty($data['Coupon']['stop'])
        ) {

            $data['Coupon']['status']   = true;
            $data['Coupon']['start']    = null;
            $data['Coupon']['stop']     = null;
        } elseif(
            empty($data['Coupon']['start'])
            && !empty($data['Coupon']['stop'])
            && $data['Coupon']['stop'] > date("Y-m-d H:i:s")
        ) {

            $data['Coupon']['status']   = true;
            $data['Coupon']['start']    = null;
        } elseif(
            !empty($data['Coupon']['start'])
            && $data['Coupon']['start'] <= date("Y-m-d H:i:s")
            && empty($data['Coupon']['stop'])
        ) {

            $data['Coupon']['status']   = true;
            $data['Coupon']['stop']     = null;
        } elseif(
            !empty($data['Coupon']['start'])
            && $data['Coupon']['start'] <= date("Y-m-d H:i:s")
            && !empty($data['Coupon']['stop'])
            && $data['Coupon']['stop'] > date("Y-m-d H:i:s")
            && $data['Coupon']['start'] < $data['Coupon']['stop']
        ) {

            $data['Coupon']['status'] = true;
        } elseif(
            empty($data['Coupon']['start'])
            && !empty($data['Coupon']['stop'])
        ) {

            $data['Coupon']['status']   = false;
            $data['Coupon']['start']    = null;
        } elseif(
            !empty($data['Coupon']['start'])
            && empty($data['Coupon']['stop'])
        ) {

            $data['Coupon']['status']   = false;
            $data['Coupon']['stop']     = null;
        }
        /*---End---*/

        /**
         * Formation cross data
         */

        if(!empty($data['CouponsProduct']['product_id'])) {
            $tmp = array('CouponsProduct' => array());

            foreach($data['CouponsProduct']['product_id'] as $value) {
                $tmp['CouponsProduct'][] = array('product_id' => $value);
            }

            $data['CouponsProduct'] = $tmp['CouponsProduct'];
        } else {
            unset($data['CouponsProduct']);
        }
        /*---End---*/

        /**
         * Remove obsolete cross data
         */
        if(!empty($coupon_id)) {
            $this->CouponsProduct->deleteAll(array(
                'coupon_id' => $coupon_id
            ));
        }
        /*---End---*/

        return $this->saveAll($data);
    }

    public function check($coupon_id = null, $product_id = null)
    {
        if(empty($coupon_id) || empty($product_id)) {
            return false;
        }

        $this->bindModel(array(
            "hasOne" => array(
                "CouponsProduct"

            )
        ));

        $to_all = $this->find(
            array("Coupon.id" => $coupon_id),
            array(
                "Coupon.status",
                "CouponsProduct.id",
            )
        );

        if(
            !empty($to_all)
            && empty($to_all["CouponsProduct"]["id"])
        ) {

            return false;
            //return $to_all["Coupon"]["status"] ? 1 : false;
        } elseif(empty($to_all)) {
            return false;
        }

        $this->bindModel(array(
            "hasOne" => array(
                "CouponsProduct" => array(
                    "foreignkey"    => false,
                    "conditions"    => "CouponsProduct.coupon_id = Coupon.id AND CouponsProduct.product_id = $product_id"
                )
            )
        ));
        $to_products = $this->find(
            array(
                "Coupon.id"                 => $coupon_id,
                "CouponsProduct.product_id" => $product_id
            ),
            "Coupon.status"
        );

        if(!empty($to_products)) {
            return $to_products['Coupon']['status'] ? 4 : false;
        }

        return false;
    }

    /**
     * Decriment for coupon uses. Check for zero after decriment, if true then status False
     * @param $couponName
     */
    public function couponStatus($couponName) {
        $this->contain();
        $coupon = $this->find("first",
            array(
                "conditions" => array(
                    'code' => $couponName
                ),
                "fields" => array(
                    "Coupon.id", "Coupon.uses",
                )
            )
        );

        if ($this->checkIfNotZero($coupon['Coupon']['uses'])) {
            $uses = $coupon['Coupon']['uses'] - 1;

            $this->id = $coupon['Coupon']['id'];
            $this->saveField("uses", $uses);

            $this->checkStatus($uses, $coupon['Coupon']['id']);
            return true;

        }
        $this->checkStatus($coupon['Coupon']['uses'], $coupon['Coupon']['id']);
        return false;
    }

    private function checkIfNotZero($uses) {
        if ($uses > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function checkStatus($uses, $id) {
        if (!$this->checkIfNotZero($uses)) {
            $this->id = $id;
            $this->saveField("status", false);
        }
    }

    public function getProductCount($id) {
        $count = $this->CouponsProduct->find("count",
            array(
                "conditions" => array(
                    "coupon_id" => $id
                )
            )
        );
        return ($count>0) ? true : false;
    }
}
