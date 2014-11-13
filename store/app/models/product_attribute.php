<?php
/**
 * User: Vitaliy Kh.
 * Date: Feb 9, 2010
 * Time: 3:40:31 PM
 */

class ProductAttribute extends AppModel
{
    public $name        = 'ProductAttribute';
    public $actsAs      = array('Containable');
    public $belongsTo   = array("ProductAttributeGroup");
    public $validate    = array(
        'product_attribute_group_id'   => array(
            'rule'      => 'numeric',
            'required'  => true
        ),
        'value'                     => array(
            'rule'      => 'notEmpty'
        )
    );

    public function getDependedProduct($id) {
        $classProduct = ClassRegistry::init('Product');
        $res = $this->read(null, $id);
        $productId = $res['ProductAttributeGroup']['product_id'];
        $productName = $classProduct->read(array('title', 'fullfilment'), $productId);
        $return = array(
            "product"   => $productName['Product']['title'],
            'attrGroup' => $res['ProductAttributeGroup']['name'],
            'attrTitle' => $res['ProductAttribute']['title'],
            'attrValue' => $res['ProductAttribute']['value'],
            'email'     => $productName['Product']['fullfilment'],
        );
        return $return;
    }

    public function getSku($productId, $attributes) {
        $classProduct = ClassRegistry::init('Product');
        $productScu = $classProduct->read("suncoast_scu", $productId);
        if ($productScu["Product"]['suncoast_scu'] != null && !empty($productScu["Product"]['suncoast_scu'])) {
            return $productScu["Product"]['suncoast_scu'];
        } else {
            return $this->_getAttributeSku($attributes);
        }
    }


    private function _getAttributeSku($attributes) {
        $attributes = explode(".", $attributes);
        $attributes = explode("-", $attributes[1]);

        foreach ($attributes as $tmp) {
            $result = $this->read("suncoast_scu", $tmp);
            if (isset($result['ProductAttribute']['suncoast_scu']) && !empty($result['ProductAttribute']['suncoast_scu'])) {
                return $result['ProductAttribute']['suncoast_scu'];
            }
        }
        unset($tmp);
        return false;
    }
}
