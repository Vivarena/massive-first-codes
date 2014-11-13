<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 15.08.11
 * Time: 12:23
 *
 * @property Order              $Order
 * @property OrderProduct       $OrderProduct
 * @property MessengerComponent $Messenger
 *
 */
 
class AdminOrdersController extends AdminAppController{
    public $uses = array("Order", "OrderProduct");
    public $components  = array("Messenger");
    public $helpers = array('Number');

    public function beforeFilter() {
        parent::beforeFilter();
        $this->_setHoverFlag("orders");
        $this->_setLeftMenu("product");
    }

    public function index() {
        $conditions = array();

        if ($this->data) {
            if (isset($this->data['Order']['order_id']) && !empty($this->data['Order']['order_id'])) {
                $conditions = array_merge($conditions, array("Order.id" => $this->data['Order']['order_id']));
            }
            if (isset($this->data['Order']['name']) && !empty($this->data['Order']['name'])) {
                $conditions = array_merge($conditions,
                    array(
                        "OR" => array(
                            "Order.payment_name LIKE"  => "%{$this->data['Order']['name']}%",
                            "Order.payment_fname LIKE" => "%{$this->data['Order']['name']}%"
                        )
                    )
                );
            }
        }

        $this->paginate = array(
            "conditions" => $conditions,
            "order"      => "id DESC"
        );
        $orders = $this->paginate("Order");
        $orders = Set::extract('/Order/.', $orders);

        $this->set(
            array(
                'items'         => $orders,
                'statuses'      => $this->_statuses,
                'productList'   => $this->_getList("name"),
                'charityList'   => $this->_getList("charity"),
                'categoryList' => $this->_getList("category"),
            )
        );

        $this->set('items', $orders);
        $this->set('statuses', $this->_statuses);
    }

    public function show($id=null) {

        if (isset($id) && is_numeric($id)) {
            $this->Order->contain('OrderProduct');
            $orderData = $this->Order->read('*', $id);

            if($orderData) {
                $this->data = $orderData;
                $this->set('statuses', $this->_statuses);
                $this->set('order', $orderData['Order']);
                $products = Set::extract('/OrderProduct/.', $orderData);

                foreach ($products as &$product) {
                    if (isset($product['attributes'])) {
                        $product['attributes'] = unserialize($product['attributes']);
                    }
                }
                $this->set('products', $products);
            } else {
                $this->redirect('index');
            }
        } else {
            $this->redirect("index");
        }
    }

    function edit()
    {
        if($this->data) {


            $fields = array(
                "Order.status"   => $this->data["Order"]["status"],
                "Order.notify"   => $this->data["Order"]["notify"],
                "Order.tracking" => $this->data["Order"]["tracking"]
            );
            $comment = "";
            if(!empty($this->data["Order"]["comment"])) {
                $comment = $this->data["Order"]["comment"];

                $comToSave = $this->Order->read("comment", $this->data["Order"]["id"]);
                $this->data["Order"]["comment"] = $comToSave["Order"]["comment"] ."<br />". $comment;

                //$this->data["Order"]["comment"] = "concat('{$this->data["Order"]["comment"]}\n\n', Order.comment)";
                //$fields += array("Order.comment" => $this->data["Order"]["comment"]);
            }

            /*$this->Order->unbindModel(array("hasOne" => array("User")));
            $result = $this->Order->updateAll(
                $fields,
                array(
                     "Order.id" => $this->data["Order"]["id"]
                )
            );*/

            $result = $this->Order->save($this->data);


//            if ($result) {
                if(!empty($this->data["Order"]["notify"])) {
                    $this->Messenger->sent_notice_status(array(
                        "to"           => $this->data["Order"]["email"],
                        "body"         => $comment,
                        "status"       => $this->data['Order']['status'],
                        "orderId"      => $this->data["Order"]["id"]
                    ));
                }
//            }

            unset($this->data["Order"]);
        }

        $this->redirect($this->referer());
    }

    public function print_preview($result, $data) {
        $this->layout="empty";

        $this->Session->write("pdf_result", $result);
        $this->Session->write("pdf_dates", $data);

        $this->set(
            array(
                "result" => $result,
                "dates"   => $data
            )
        );

    }

    /**
     * Method is intended to print the data in PDF, depending
     * on the parameters passed will be printed or orders or donations
     * in the selected interval
     * @asset $_POST = array("from", "to", "type")
     * @return void
     */
    public function pdf() {
        if ($this->data) {
            $data         = $this->data['Order'];
            $data["to"]   = (empty($data["to"]))?false:date("Y-m-d", strtotime($data['to']));
            $data["from"] = (empty($data["from"]))?false:date("Y-m-d", strtotime($data['from']));

            $result = array();
            switch($data['type']) {
                case 'orders':
                    $result = $this->Order->getOrderDataInInterval($data);
                    if (!$result) {
                        $this->_setFlash("Orders for a certain period of no entries", "error");
                        $this->redirect($this->referer());
                    }
                    $this->setAction("print_preview", $result, $data);
                    //$this->_printOrdersPdf($result, $data);
                    break;
                case 'donations':
                    $result = $this->OrderProduct->getCharityDataInInterval($data);
                    $this->_printCharityPdf($result);
                    break;
            }
        }
    }

    private function _initPdf() {

        $this->view="View";
        $mpdf=new MYPDF('l', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->set('mpdf', $mpdf);
        $this->layout="pdf";
    }

    /**
     * In $result we have prepared data
     *
     * @class \AdminOrdersController
     * @internal param $result
     * @internal param $dates
     * @return void
     */
    //private function _printOrdersPdf($result, $dates)
    public function printPdf()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 0);
        if ($this->Session->check("pdf_result")) {
            $result = $this->Session->read("pdf_result");

            $data = $this->Session->read("pdf_dates");
//            $this->Session->delete("pdf_result");
//            $this->Session->delete("pdf_dates");
            $this->_initPdf();
            $this->set('result', $result);
            $this->set('dates', $data);
            $this->render("orders_pdf");
        } else {
            $this->redirect("index");
        }

    }

    /**
     * In $result we have prepared data
     *
     * @class \AdminOrdersController
     * @param $result
     * @return void
     */
    private function _printCharityPdf($result)
    {
        pr($result);
        die;
    }

    private function _testPdf($result) {
        Configure::write("debug", 0);
        $this->layout="empty";
        $this->set('result', $result);
        $this->render("test");
    }

    private function _getList($field)
    {
        $data = $this->OrderProduct->find("list",
            array(
                "fields" => array(
                    "OrderProduct.id", "OrderProduct.{$field}"
                ),
                "group" => "OrderProduct.{$field}"
            )
        );
        $preData = array();
        foreach ($data as $tmp) {
            $preData[$tmp] = $tmp;
        }
        return $preData;
    }
}
App::import('Vendor', 'tcpdf');
class MYPDF extends TCPDF {
    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}
