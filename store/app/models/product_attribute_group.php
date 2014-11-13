<?php
/**
 * User: Vitaliy Kh.
 * Date: Feb 9, 2010
 * Time: 2:53:38 PM
 *
 * @property ProductAttribute $ProductAttribute
 */

class ProductAttributeGroup extends AppModel
{
    public $name        = 'ProductAttributeGroup';
    public $hasMany     = array(
        'ProductAttribute' => array(
            'dependent' => true
        )
    );
    public $actsAs      = array('Containable');
    public $validate    = array(
        'product_id' => array(
            'rule'      => 'numeric',
            'required'  => true
        ),
        'name'       => array(
            'rule'      => 'notEmpty'
        )
    );

    /**
     * saveGroups
     * Saved and deleted a group of attributes and attributes in these groups.
     * If successful execution returns true else false
     *
     * @author Vitaliy Kh.
     *
     * @param  array $data
     * @param  int $productId
     * @return boolean
     * @access public
     */
    public function saveGroups($data, $productId = null)
    {

        if(empty($data) || empty($productId)) {
            return false;
        }

        $copyFlag = false;
        if (isset($data['Product']['copy'])) {
            if ($data['Product']['copy'] == 'copy') {
                $copyFlag = true;
            }
        }

        $error = false;

        $productIdArray = $this->find("all",
            array(
                "conditions" => array(
                    "ProductAttributeGroup.product_id" => $productId
                ),
                "fields" => "id"
            )
        );
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $productIdArray = Set::extract("/ProductAttributeGroup/id", $productIdArray);


        $this->deleteAll(array("product_id" => $productId));
        if (!$copyFlag) {

            foreach ($productIdArray as $groupId) {
                $this->ProductAttribute->deleteAll(array("product_attribute_group_id" => $groupId));
            }
        }



        $data = Set::extract($data, "{attr}");
        foreach($data as &$value) {



            $value['ProductAttributeGroup']['product_id'] =
                !empty($value['ProductAttributeGroup']['product_id']) ?
                    $value['ProductAttributeGroup']['product_id'] :
                    $productId;

            if ($copyFlag) {
                $value['ProductAttributeGroup']['product_id'] = $productId;
                unset($value['ProductAttributeGroup']['id']);
            }



            if (!isset($value['ProductAttributeGroup']['id'])) {
                $this->create();
            }
            if ($this->save($value)) {
                if (isset($value['ProductAttribute']) && count($value['ProductAttribute']) > 0) {
                    $id = $this->id;
                    foreach ($value['ProductAttribute'] as &$attr) {

                        if (!isset($attr['id']) || $copyFlag) {
                            $this->ProductAttribute->create();
                            unset($attr['id']);
                        }
                        $attr['product_attribute_group_id'] = $id;
                        $this->ProductAttribute->save($attr);
                    }

                }


            }
        }

        return $error ? false : true;
    }





}
