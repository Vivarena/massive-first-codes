<?php
/**
 * Created by JetBrains PhpStorm.
 * User: nike
 * Date: 02.10.11
 * Time: 20:46
 *
 * @property Click $Click
 *
 */ 
class AdminReportsController extends AdminAppController {
    public $name = "AdminReports";
    public $uses = array("Products");

    public function beforeFilter() {
        parent::beforeFilter();
        $this->_setHoverFlag("product");
        $this->_setLeftMenu("product");
    }

    public function low_stock() {
        $categories = $this->getCategories();
        $this->set("categories", $categories );

        $defaultQuantity = 15;

        $conditionsQuantity = array(
            "ProductAttribute.quantity <= " => $defaultQuantity
        );
        $conditions = array();

        if ($this->data) {

            $conditions = array();
            $data = $this->data['Products'];

            if (isset($data['category']) && !empty($data['category'])) {
                $conditions = array_merge($conditions, array('CategoriesProduct.category_id' => $data['category']));
            }

            if (isset($data['name']) && !empty($data['name'])) {
                $conditions = array_merge($conditions, array("Product.title LIKE" => "%" . $data['name'] . "%"));
            }

            if (isset($data['vendor']) && !empty($data['vendor'])) {
                $conditions = array_merge($conditions, array("vendor" => $data['vendor']));
            }

            if (isset($data['qty_from']) && !empty($data['qty_from']) &&
                isset($data['qty_to']) && !empty($data['qty_to'])
               ) {
                $qty = array(
                    "AND" => array(
                        "ProductAttribute.quantity >=" =>  $data['qty_from'],
                        "ProductAttribute.quantity <=" =>  $data['qty_to'],
                    )
                );
                $conditionsQuantity = array_merge($conditionsQuantity, $qty);
            } elseif(!empty($data['qty_from']) && empty($data['qty_to'])
            ) {
                $qty = array(
                    "ProductAttribute.quantity >=" =>  $data['qty_from'],
                );
                $conditionsQuantity = array_merge($conditionsQuantity, $qty);
            } elseif(empty($data['qty_from'])&& !empty($data['qty_to'])
            ) {
                $qty = array(
                    "ProductAttribute.quantity <=" =>  $data['qty_to'],
                );
                $defaultQuantity = $data['qty_to'];
                $conditionsQuantity = array_merge($conditionsQuantity, $qty);
            }

        }

        $results = $this->Product->find("all", array(
            "contain" => array(
                "CategoriesProduct",
                'ProductAttributeGroup' => array(
                    'ProductAttribute' => array(
                        "conditions" => $conditionsQuantity
                    ),
                ),
            ),
            "conditions" => $conditions,
            "fields"     => array(
                "Product.id", "Product.title", "Product.quantity"
            ),
            "group" => "Product.id",
            "order" => "Product.title"
        ));


        foreach ($results as $key=>$attributes) {
            if (count($attributes['ProductAttributeGroup']) == 0) {
                $quantity = $attributes['Product']['quantity'];
                if (isset($data)) {
                    if ($quantity > $data['qty_to'] || $quantity < $data['qty_from']) {
                        unset($results[$key]);
                    }
                } else {
                    if ($quantity > $defaultQuantity) {
                        unset($results[$key]);
                    }
                }
            }
        }

        foreach ($results as &$attributes) {
            foreach ($attributes['ProductAttributeGroup'] as $keyAttr=>$value) {
                if (count($value['ProductAttribute']) == 0) {
                    unset($attributes['ProductAttributeGroup'][$keyAttr]);
                }
            }

        }

        $this->set("results", $results);
        $this->set("defaultQuantity", $defaultQuantity);
    }

    public function most_viewed() {

        $categories = $this->getCategories();
        $this->set("categories", $categories );

        $this->loadModel("Click");
        $conditions = array();
        $conditionsCategory = array();

        if ($this->data) {
            $data = $this->data["Click"];
            if (isset($data['category']) && !empty($data['category'])) {
                $conditionsCategory = array_merge($conditionsCategory, array('Category.id' => $data['category']));
            }

            $data["to"]   = (empty($data["to"]))?false:date("Y-m-d", strtotime($data['to']));
            $data["from"] = (empty($data["from"]))?false:date("Y-m-d", strtotime($data['from']));

            if (!empty($data['to']) && !empty($data['from'])) {
                $conditions = array(
                    "Click.modified <=" => $data['to'],
                    "Click.modified >=" => $data['from'],
                );
            } elseif (!empty($data['to']) && empty($data['from'])) {
                $conditions = array(
                    "Click.modified <=" => $data['to'],
                );
            } elseif (empty($data['to']) && !empty($data['from'])) {
                $conditions = array(
                    "Click.modified >=" => $data['from'],
                );
            }
        }

        $clicks = $this->Click->find("all", array(
            "contain" => array(
                "Product" => array(
                    "Category" => array(
                        "conditions" => array(
                            "Category.id" => $conditionsCategory
                        )
                    ),
                )
            ),
            "conditions" => $conditions,
            "order" => "Click.modified DESC"
        ));

        if (count($conditionsCategory) > 0) {
            $clicks = $this->Category->fuckEmptyCategories($clicks);
        }

        $this->set("clicks", $clicks);
    }


}
