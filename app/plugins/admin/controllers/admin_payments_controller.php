<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 10.11.11
 * Time: 16:07
 *
 * @property Package $Package
 * @property Payment $Payment
 *
 */
 
class AdminPaymentsController extends AdminAppController{
    public $uses = array("Payment");

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setLeftMenu('package');
        $this->_setHoverFlag('payments');
    }

    public function index() {
        $this->paginate = array(
            "order" => "Payment.created DESC"
        );
        $orders = $this->paginate();
        $this->set('orders', $orders);
    }

    public function ajaxDelete($id) {
        Configure::write("debug", 0);
        if(empty($id) && !is_numeric($id))
        {
            return false;
        }

        if($this->Payment->delete($id))
        {

            exit(
            json_encode(
                array(
                    "status" => true,
                    "count"    => $this->Payment->find("count")
                )
            )
            );
        }

        return false;
    }

    public function view($id) {
        $this->loadModel("Package");
        $order = $this->Payment->read(null, $id);
        $order['Payment']['attributes'] = unserialize($order['Payment']['attributes']);

        foreach ($order['Payment']['attributes'] as &$tmp) {
            $tmp['id'] = $this->Package->read(null, $tmp['id']);
        }
        $this->set('data', $order);
    }

}
