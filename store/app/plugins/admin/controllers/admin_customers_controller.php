<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 23.11.11
 * Time: 0:44
 *
 * @property Customer $Customer
 *
 */

class AdminCustomersController extends AdminAppController
{
    public $uses = array("Customer");

    public function beforeFilter() {
        parent::beforeFilter();
        $this->_setLeftMenu("product");
        $this->_setHoverFlag("customer");
    }

    public function index() {
        $conditions = array();

        if ($this->data) {

            if (isset($this->data['Customer']['name']) && !empty($this->data['Customer']['name'])) {
                $conditions = array_merge($conditions,
                    array(
                        "Customer.name LIKE" => "%{$this->data['Customer']['name']}%"
                    )
                );
            }

            if (isset($this->data['Customer']['email']) && !empty($this->data['Customer']['email'])) {
                $conditions = array_merge($conditions,
                    array(
                        "Customer.email LIKE" => "%{$this->data['Customer']['email']}%"
                    )
                );
            }

            if (isset($this->data['Customer']['phone']) && !empty($this->data['Customer']['phone'])) {
                $conditions = array_merge($conditions,
                    array(
                        "Customer.phone LIKE" => "%{$this->data['Customer']['phone']}%"
                    )
                );
            }

            if (isset($this->data['Customer']['address']) && !empty($this->data['Customer']['address'])) {
                $conditions = array_merge($conditions,
                    array(
                        "Customer.address LIKE" => "%{$this->data['Customer']['address']}%"
                    )
                );
            }
        }


        $this->paginate = array(
            "conditions" => $conditions
        );
        $customers = $this->paginate();
        $customers = Set::extract("/Customer/.", $customers);
        $this->set('items', $customers);
    }

}
