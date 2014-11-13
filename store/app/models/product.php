<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 02.08.11
 * Time: 12:05
 *
 * @property Click                 $Click
 * @property ProductImage          $ProductImage
 * @property ProductAttributeGroup $ProductAttributeGroup
 *
 */
 
class Product extends AppModel {
    public $name                = "Product";
    public $hasMany             = array("ProductImage", 'ProductAttributeGroup', 'Click');
    public $hasOne              = array('CategoriesProduct');
    public $actsAs              = array("Tree", "Containable");
    public $hasAndBelongsToMany = array('Category');

    public $validate            = array(
        'title' => array(
            'rule'      => 'notEmpty',
            'required'  => true
        ),
        /*'overview' => array(
            'rule'      => 'notEmpty',
            'required'  => true
        ),*/
        'description' => array(
            'rule'      => 'notEmpty',
            'required'  => true
        ),
        'price' => array(
            'rule'      => 'notEmpty',
            'required'  => true
        ),
//        'quantity' => array(
//            'rule'      => 'notEmpty',
//            'required'  => true
//        ),
        /*'image' => array(
            'rule'      => 'notEmpty',
            'required'  => true
        ),*/
        /*'rprice' => array(
            'rule'      => 'notEmpty',
            'required'  => true
        ),*/
//        'category_id' => array(
//            'rule'      => 'notEmpty',
//            'required'  => true,
//            'message'   => 'Select product category'
//        ),
    );

    public function getProduct($id) {
        $data = $this->find("first",
            array(
                "conditions" => array(
                    "Product.id" => $id
                ),
           )
        );

        return $data;
    }

    public function getRelatedProducts($id, $users_id = null) {
        $data = $this->find("all",
            array(
                "conditions" => array(
                    "Product.id <>"  => $id,
                    "Product.active" => 1,
                    "Product.user_id" => $users_id
                ),
                "fields" => array(
                    "Product.id", "Product.title", "Product.image", "Product.user_id"
                ),
                "order RND()",
                "limit" => 8,
            )
        );
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Set::extract('/Product/.', $data);
        return $data;
    }

    public function getProductList() {
        $data = $this->find("list");
        return $data;
    }

    public function getExclusiveProducts()
    {
        $data = $this->find("all",
            array(
                "conditions" => array(
                    "Product.active"    => 1,
                    "Product.exclusive" => 1,
                ),
                "fields" => array(
                    "Product.id", "Product.title", "Product.image"
                )
            )
        );
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Set::extract("/Product/.", $data);
        return $data;
    }

    public function getSearchResult($data)
    {
        $result = $this->find("all",
          array(
              "conditions" => array(
                   "AND" => array(
                       "Product.active"       => 1,
                   ),
                   "OR" => array(
                      "Product.title LIKE"        => "%{$data['search']}%",
                      "Product.overview LIKE"     => "%{$data['search']}%",
                      "Product.description LIKE"  => "%{$data['search']}%",
                   )
              ),
              "fields" => array(
                  "DISTINCT Product.id", "Product.title", "Product.price",
                  "Product.image", "Product.description", "Product.rprice"
              )
          )
        );
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $result = Set::extract('/Product/.', $result);
        return $result;
    }

    /**
     * @param int $limit Limit
     * @param $ids
     * @return array
     */
    public function getActiveProducts($limit = 6, $ids)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->contain();
        $data = $this->find("all",
            array(
                "conditions" => array(
                    "Product.active"  => 1,
                    "Product.sale"    => 0,
                    "Product.id NOT"  => $ids
                ),
                "order" => "id DESC",
                "limit" => $limit
            )
        );
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return Set::extract("/Product/.", $data);
    }

    public function getProductCategoryName($product_id)
    {
        $data = $this->find("first",
            array(
                "conditions" => array(
                    "Product.id" => $product_id
                ),
                "fields" => "Category.title"
            )
        );
        return $data['Category']['title'];
    }

    public function getTopProduct()
    {
        $data = $this->find("all",
            array(
                "contain" => array(),
                "conditions" => array(
                    "Product.exclusive" => 1
                ),
                "order"  => "Product.top DESC",
                "fields" => array(
                    "Product.id", "Product.title", "Product.price", "Product.image", "Product.quantity", "Product.rprice"
                )
            )
        );
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return Set::extract('/Product/.', $data);
    }

    public function getSaleProducts($ids)
    {
        $this->contain();
        $data = $this->find("all",
            array(
                "conditions" => array(
                    "AND" => array(
                        "Product.active"    => 1,
                        "Product.sale"      => 1,
                        "Product.id NOT"     => $ids
                    )
                ),
                "fields" => array(
                    "Product.id", "Product.title", "Product.price", "Product.image", "Product.quantity", "Product.rprice"
                ),
                "limit" => 6
            )
        );

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Set::extract("/Product/.", $data);
        return $data;
    }


    /**
         * Add new product
         * @param array $data
         * @return boolean
         */
        public function add($data)
        {
             if(!empty($data['CategoriesProduct']['category_id'])) {
                $categories_products = $data['CategoriesProduct']['category_id'];
                $formattedCats = array();
                foreach($categories_products as $key=>$category_id)
                {
                    $formattedCats[$key]['category_id'] = $category_id;
                }
                unset($data['CategoriesProduct']);
                $data['Category'] = $formattedCats;
            }

            $this->set($data);

            if($this->saveAll($data)) {
                $this->ProductAttributeGroup->saveGroups($data, $this->id);
                return true;
            } else {
                $this->log($data, 'while_save_product');
                return false;
            }
        }

        /**
         * Edit product data
         * @param int $id
         * @param array $data
         * @return boolean
         */
        public function edit($id, $data)
        {

            if(!is_numeric($id)) {
                return false;
            } else {
                $data['Product']['id'] = $id;
            }

            $this->ProductImage->deleteAll(array(
                "ProductImage.product_id" => $id
            ), false);


            /** @noinspection PhpUndefinedMethodInspection */
            $this->contain();
            $cntOfProducts = $this->find('count', array(
                'conditions' => array(
                    'Product.id' => $id)
            ));
            if($cntOfProducts == 0) {
                return false;
            }

            if(!empty($data['CategoriesProduct']['category_id'])) {
                $categories_products = $data['CategoriesProduct']['category_id'];
                $formattedCats = array();
                foreach($categories_products as $key => $category_id)
                {
                    $formattedCats[$key]['category_id'] = $category_id;
                }
                unset($data['CategoriesProduct']);
                $data['Category'] = $formattedCats;
            }


    //        if (empty($data['Product']['color'])) $data['Product']['color'] = 'other';
            $this->set($data);
            if($this->saveAll($data) !== false) {

                    if(isset($data['ProductImage']))
                    {
                        $images = $data['ProductImage'];
                        unset($data['ProductImage']);
                        foreach ($images as $img) {
                            $img['product_id'] = $this->id;
                            $this->ProductImage->id = false;
                            $this->ProductImage->save($img);
                        }
                    }

    //            $this->ProductOptionGroup->saveGroups($data, $id);
                $this->ProductAttributeGroup->saveGroups($data, $id);

                return true;
            } else {
                return false;
            }
        }

    public function addClick($productId)
    {
        $now = date("Y-m-d", mktime());
        $recordsCount = $this->Click->find("count",
            array(
                "conditions" => array(
                    "Click.product_id" => $productId,
                    "Click.modified"   => $now
                )
            )
        );
        if ($recordsCount > 0) {
            $dayClicks = $this->Click->find("first",
                array(
                    "conditions" => array(
                        "Click.product_id" => $productId,
                        "Click.modified"   => $now
                    )
                )
            );

            $id = $dayClicks["Click"]['id'];
            $dayClicks = $dayClicks["Click"]['clicks'];
            
            $this->Click->id = $id;
            $this->Click->saveField("clicks", $dayClicks + 1);
        } else {
            $this->Click->save(array("clicks" => 1, "product_id" => $productId));
        }
        return;
    }

    public function getGift($data)
    {
        $giftId = $data['Product']['gift'];
        $gift   = $this->read(array("id", "title"), $giftId);
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data['Product']['gift'] = $gift['Product'];
        return $data;
    }

    public function getGiftId($id)
    {
        $giftId = $this->read(array("gift"), $id);
        return $giftId['Product']['gift'];
    }

    public function getActiveProductsByCategory($limit, $categoriesId, $ids)
    {
        $cid = array();
        foreach ($categoriesId as $tmp) {
            $tempIds = $this->Category->children($tmp);
            $tempIds = Set::extract("/Category/id", $tempIds);
            $cid = array_merge($cid, $tempIds);
            $cid = array_merge($cid, array($tmp));
        }
        unset($tmp);

        $data = $this->find("all",
            array(
                "contain" => array(
                    "Category" => array(
                        "conditions" => array(
                            "Category.id " => $cid
                        )
                    )
                ),
                "conditions" => array(
                    "AND" => array(
                        "Product.id NOT" => $ids,
                        "Product.active" => 1,
//                        "Product.sale"   => 0,
                    )
                ),
                "order" => "RAND()"
            )
        );

        foreach ($data as $key=>$tmp) {
            if (count($tmp['Category']) == 0 ) {
                unset($data[$key]);
            }

        }
        unset($tmp);
        unset($key);
        $data = array_slice($data, 0, 3);

        $ids = Set::extract("/Product/id", $data);
        $products = Set::extract("/Product/.", $data);

        foreach ($products as $key=>$product) {
            if (count($product) == 0) {
                unset($products[$key]);
            }
        }

        return array(
            $ids, $products
        );
    }

    public function getItemsOfTheWeek($limit, $ids) {
        $this->contain();
        $data = $this->find("all",
            array(
                "conditions" => array(
                    "AND" => array(
                        "Product.active"     => 1,
                        "Product.week_item"  => 1,
                        "Product.id NOT"     => $ids
                    )
                ),
                "fields" => array(
                    "Product.id", "Product.title", "Product.price", "Product.image", "Product.quantity", "Product.rprice"
                ),
                "limit" => $limit,
                "order" => "RAND()"
            )
        );

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Set::extract("/Product/.", $data);
        return $data;
    }

    public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
        $parameters = compact('conditions', 'recursive');

        if (isset($extra['group'])) {
            $parameters['fields'] = $extra['group'];

            if (is_string($parameters['fields'])) {
                // pagination with single GROUP BY field
                if (substr($parameters['fields'], 0, 9) != 'DISTINCT ') {
                    $parameters['fields'] = 'DISTINCT ' . $parameters['fields'];
                }
                unset($extra['group']);
                $count = $this->find('count', array_merge($parameters, $extra));
            } else {
                // resort to inefficient method for multiple GROUP BY fields
                $count = $this->find('count', array_merge($parameters, $extra));
                $count = $this->getAffectedRows();
            }
        } else {
            // regular pagination
            $count = $this->find('count', array_merge($parameters, $extra));
        }
        return $count;
    }

    /**
     * Getter for product Suncoast SKU
     * @param $productId
     * @return String
     */
    public function getSuncoastSku($productId) {
        $data = $this->read("suncoast_scu", $productId);
        return $data['Product']['suncoast_scu'];
    }
}
