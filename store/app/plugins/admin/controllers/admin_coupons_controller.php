<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 02.08.11
 * Time: 22:37
 *
 * @property Coupon $Coupon
 * @property Product $Product
 *
 */
 
class AdminCouponsController extends AdminAppController {
    public $name = "AdminCoupons";
    public $uses = array("Coupon");
    public $helpers = array("Number");

    public function beforeFilter() {
        parent::beforeFilter();
        $this->_setLeftMenu("product");
        $this->_setHoverFlag("coupons");
    }

    public function index() {
        $data = $this->paginate("Coupon");
        $data = Set::extract("/Coupon/.", $data);
        $this->set('items', $data);
    }

    public function add() {
        $this->_setHoverFlag("coupon_add");
        $this->_saveData();
        $this->_setProductList();
    }

    public function edit($id=null) {
        $this->_setHoverFlag("coupon_add");
        $this->_saveData();

        if (isset($id) && is_numeric($id)) {
            $this->Coupon->bindModel(array(
                                        'hasMany' => array(
                                            'CouponsProduct'))
            );
            $data = $this->Coupon->find("first",
                                      array(
                                           "conditions" => array(
                                               "Coupon.id" => $id,
                                           )
                                      )
            );
            $data['CouponsProduct']['product_id'] = Set::extract('/CouponsProduct/product_id', $data);


            $this->data = $data;

            $this->_setProductList();
            $this->render("add");
        }

    }

    public function _saveData() {
        if ($this->data) {
            if($this->Coupon->edit($this->data)) {
                $this->_setFlash('Item successfully edited', 'success');
                $this->redirect("index");
            } else {
                $this->_setFlash('Errors occured, please see below', 'error');
            }
        }
    }

    private function _setProductList() {
        $this->loadModel("Product");
        $productList = $this->Product->getProductList();
        $this->set('productList', $productList);
    }

    public function fill() {
        $this->Coupon->contain();
        $coupons = $this->Coupon->find("all");
        foreach ($coupons as $coupon) {
            $this->Coupon->id = $coupon['Coupon']['id'];
            $this->Coupon->saveField("type", "per");
        }
        die("Done");
    }

}
