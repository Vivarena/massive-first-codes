<?php
/**
 * @author: Dmitry404
 */
class OrderProduct extends AppModel {
    public $name = 'OrderProduct';
    public $belongsTo = array('Order', 'Product');
    public $actsAs = array('Containable');

    public function getCharityDataInInterval($data)
    {
        $data = $this->find("all",
            array(
                "fields" => array(
                    "charity", "quantity"
                )
            )
        );
        $data = Set::extract("/OrderProduct/.", $data);
        $charity = array();
        foreach ($data as &$tmp) {
            $temp = explode("-", $tmp['charity']);
            $temp[1] = trim($temp[1]) * $tmp['quantity'];
            $tmp = $temp;
            unset ($tmp['quantity']);
            $charity[trim($tmp[0])] = $tmp[1];
        }

        $charityByName = array();
        $tmp = "";
        foreach ($charity as $key=>$tmp) {
            pr($tmp);
        }
        return $data;
    }

    public function getLastSaledProducts($limit) {
        $data = $this->find("all",
            array(
                "contain" => array(
                    "Product" => array(
                        "fields" => array(
                            "Product.id", "Product.title", "Product.price", "Product.image", "Product.quantity"
                        )
                    )
                ),
                "group" => "product_id",
                "limit" => $limit,
                "order" => "OrderProduct.id DESC"
            )
        );
        $data = Set::extract('/Product/.', $data);
        return $data;
    }
}