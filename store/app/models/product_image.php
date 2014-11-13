<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 16.08.11
 * Time: 16:16
 */
 
class ProductImage extends AppModel{
    public $name = "ProductImage";
    public $belongsTo = array("Product");
    public $actsAs = array("Tree");

    public function getAllPictures($id)
    {
        $data = $this->find("all",
            array(
                "conditions" => array(
                    "ProductImage.product_id" => $id
                ),
                "order" => "ProductImage.lft"
            )
        );
        return Set::extract("/ProductImage/.", $data);
    }
}
