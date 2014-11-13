<?php
/**
* Created by Slava Basko
* Email: basko.slava@gmail.com
* Date: 7/24/13
* Time: 5:07 PM
 *
 * @property PaypalComponent $Paypal
 * @property StoreComponent $Store
*/

class ProductPayComponent extends Component {

    public $components = array('Paypal', 'Store');

    public $_controller;

    /**
     * @param object $controller
     */
    public function startup(&$controller) {
        $this->_controller = $controller;
    }

    public function PayForPublishProduct($product_id, array $user_info) {}

}