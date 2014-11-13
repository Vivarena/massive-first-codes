<?php
define('ADMIN',1);
/**
* Created by Slava Basko
* Email: basko.slava@gmail.com
* Date: 5/8/13
* Time: 5:58 PM
 *
 * @property StoreComponent $Store
 * @property SessionComponent $Session
 * @property CrypterComponent $Crypter
 * @property Message $Message
 * @property UserFriend $UserFriend
 * @property EmailComponent $Email
*/

class ShopController extends AppController {

    public $name = 'Shop';

    public $uses = array('User', 'UserInfo', 'UserFriend');

    public $components = array('Store', 'Crypter', 'SwiftMailer', 'Email');

    public $helpers = array('Number');

    public $myId;

    public $used;

    public $services;

    public function beforeFilter() {
        parent::beforeFilter();
        $this->layout = 'store';
        $this->myId = $this->Session->read('Auth.User.id');

        $this->used = (isset($this->params['used']))?
            $this->params['used']:
            false;

        $this->services = (isset($this->params['services']))?
            $this->params['services']:
            false;
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->set('myID', $this->myId);
        $this->set('categories', $this->Store->GetCategories());
    }

    /**
     * @route /shop --> {"controller": "shop", "action": "index"}
     */
    public function index() {
        $data = $this->Store->GetProducts(8);
        $this->set('data', $data);
        $this->set('action', 'index');
        $this->set('isUsedProducts',$this->used);
        $this->set('isServices',$this->services);
    }

    /**
     * @param int $page
     * @route /shop/page/* --> {"controller": "shop", "action": "page"}
     */
    public function page($page = 1) {
        $page = (int) $page;
        $data = $this->Store->GetProducts(8, $page);
        $this->set('data', $data);
        $this->set('action', 'index');
        $this->set('isUsedProducts', $this->used);
        $this->render('index');
    }

    /**
     * @param $slug
     * @route /shop/product/* --> {"controller": "shop", "action": "product"}
     */
    public function product($slug) {
        $tmp = explode('-', $slug);
        $id = (int) $tmp[0];
        $data = $this->Store->GetProduct($id);

        if(!$this->UserFriend->isFriend($this->myId, $data['product']['user_id'])){
            $friendLogin = $this->User->read(array('fields'=>'login'), $data['product']['user_id']);
            $this->set('friendLogin',$friendLogin['User']['login']);
        }

        $this->set('data', $data);
    }

    /**
     * @route /shop/getQty --> {"controller": "shop", "action": "getQty"}
     */
    public function getQty() {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $this->autoRender = false;
        $data = $this->Store->GetQty();
        exit($data);
    }

    /**
     * @param $slug
     * @param null $fake_param
     * @param null $num_page
     * @route /shop/by-category/* --> {"controller": "shop", "action": "category"}
     */
    public function category($slug, $fake_param = null, $num_page = null) {
        $tmp = explode('-', $slug);
        $id = (int) $tmp[0];
        $page = 1;
        if($num_page) {
            $page = (int) $num_page;
        }
        $data = $this->Store->GetProducts(8, $page, (int) $id);

        $this->set(array(
            'data'           => $data,
            'action'         => 'category',
            'category'       => $slug,
            'isUsedProducts' => $this->used
        ));

        $this->render('index');
    }

    /**
     * @route /shop/cart --> {"controller": "shop", "action": "cart"}
     */
    public function cart() {
        if(!$this->RequestHandler->isPost()) {
            $this->data = null;
        }
        $cart_data = $this->Store->Cart($this->data);
        $this->set('data', $cart_data);
    }

    /**
     * @route /shop/del_item/* --> {"controller": "shop", "action": "DeleteItemFromCart"}
     */
    public function DeleteItemFromCart($key) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $this->autoRender = false;
        $response = $this->Store->DeleteItemFromCart($key);
        $res = json_decode($response, true);
        if(empty($res['Products'])) {
            $this->Store->RenewStoreSession();
        }
        exit($response);
    }

    /**
     * @route /shop/ajaxSetCharity/* --> {"controller": "shop", "action": "SetCharity"}
     */
    public function SetCharity($key) {}

    /**
     * @route /shop/add_discount/* --> {"controller": "shop", "action": "AddDiscount"}
     */
    public function AddDiscount($key) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $this->autoRender = false;
        $response = $this->Store->AddDiscount($key);
        exit($response);
    }

    /**
     * @route /shop/setQuantity/* --> {"controller": "shop", "action": "SetQuantity"}
     */
    public function SetQuantity() {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $this->autoRender = false;
        $response = $this->Store->SetQuantity();
        exit($response);
    }

    /**
     * @route /shop/updateCart/* --> {"controller": "shop", "action": "UpdateCart"}
     */
    public function UpdateCart($key) {}

    /**
     * @route /shop/set_shipping/* --> {"controller": "shop", "action": "SetShipping"}
     */
    public function SetShipping($key) {}

    /**
     * @route /shop/checkout/* --> {"controller": "shop", "action": "CheckOut"}
     */
    public function CheckOut() {
        if($this->data) {
            $response = $this->Store->CheckOut($this->data);
            //pr($response);exit;
            if($response){
                $this->set('payment',$response);
                $this->render('payment');
            }else{
                $this->set('countries', $this->getCountries());
                $this->_setFlashMsg('Error! Please check your data. <br> Make sure that postal code is valid for chosen country.', 'error');
            }
        }else {
            $this->set('countries', $this->getCountries());
        }
    }

    /**
     * @route /shop/ajaxGetStates/* --> {"controller": "shop", "action": "AjaxGetStates"}
     */
    public function AjaxGetStates($country_id = null) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $this->autoRender = false;
        $states = $this->Store->GetStates((int) $country_id);
        exit($states);
    }

    /**
     * @param $method bool  - if TRUE - it's parallels payments / FALSE - it's normal payment method
     * @route /shop/paypal/* --> {"controller": "shop", "action": "GoToPaypal"}
     */
    public function GoToPaypal($method = false) {
        $data = $this->Store->GetPaypalUrl((bool)$method);
        if(isset($data['paypal_url'])) {
            $this->redirect($data['paypal_url']);
        }else {
            if((int)Configure::read('debug') > 0) {
                print_r($data);
                exit();
            }else {
                $this->redirect('/shop/cart');
            }

        }
    }

    /**
     * @route /shop/thank-you --> {"controller": "shop", "action": "AfterPayment"}
     */
    public function AfterPayment($order_id = null) {
        if($order_id == null) {
            $token = $this->params['url']['token'];
            $payer_id = $this->params['url']['PayerID'];
            $response_details = $this->Store->GetAfterPaymentDetails($token, $payer_id);

            // get products is cart for start decrement process
            $cart = $this->Store->Cart();
            $cartToSend = array();
            if(isset($cart['items']['item'][0])) {
                foreach ($cart['items']['item'] as $node) {
                    $cartToSend['Products'][$node['key']] = array(
                        'id' => $node['id'],
                        'quantity' => $node['quantity'],
                        'price' => $node['price'],
                        'shipping' => $node['shipping'],
                        'tax' => $node['tax']
                    );
                }
            }else {
                foreach ($cart['items'] as $node) {
                    $cartToSend['Products'][$node['key']] = array(
                        'id' => $node['id'],
                        'quantity' => $node['quantity'],
                        'price' => $node['price'],
                        'shipping' => $node['shipping'],
                        'tax' => $node['tax']
                    );
                }
            }

            //

            if(is_array($response_details) && array_key_exists('order_id', $response_details)) {
                $this->log('tyr call decrement with order:', 'AfterPaymentInfo');
                $this->log($response_details, 'AfterPaymentInfo');
                $this->log('adn cart data:', 'AfterPaymentInfo');
                $this->log($cartToSend, 'AfterPaymentInfo');
                $decr = $this->Store->DecrementProductsQty($response_details['order_id'], $cartToSend);
                $this->log($decr, 'AfterPaymentInfo');
                $response = $this->Store->ThankYou($response_details['order_id']);
                if(!isset($response['error'])) {
                    $this->Store->RenewStoreSession();
                    $this->set('order_id', $response_details['order_id']);
                    $res = $this->Store->GetEmailAfterPayment($response_details['order_id']);
                    $this->EmailAfterPayment($res, $response_details['order_id']);
                }else {
                    $this->set('error', $response_details['error']);
                }
            }else {
                $this->set('error', $response_details['error']);
            }
        }
    }

    private function EmailAfterPayment(array $data, $orderId = null) {
        $this->loadModel('Message');
        $invoiceLink = 'http://'.env('SERVER_NAME').'/shop/invoice/'.$orderId;
        $sellers_ids = array();
        if(is_array($data['sellers']['seller_id'][0]) && array_key_exists(0, $data['sellers']['seller_id'])) {
            if(is_array($data['sellers']['seller_id'])) {
                foreach ($data['sellers']['seller_id'] as $id) {
                    $email = $this->User->GetEmailById($id);
                    if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $sellers_ids[$email] = '';
                        $this->Message->create();
                        $this->Message->save(array(
                                'Message'=>array(
                                    'from_id'=> ADMIN,
                                    'to_id'  => $id,
                                    'content'=> 'Your product/service was sold on Vivarena. Additional info was sent to your email.',
                                    'subject'=> 'Your product/service was sold on Vivarena.'
                                ))
                        );
                    }
                }
            }
        }else {
            $email = $this->User->GetEmailById($data['sellers']['seller_id']);
            $sellers_ids[$email] = '';
            $this->Message->create();
            $this->Message->save(array(
                'Message'=>array(
                    'from_id'=> ADMIN,
                    'to_id'  => $data['sellers']['seller_id'],
                    'content'=> 'Your product/service was sold on Vivarena. Additional info was sent to your email.',
                    'subject'=> 'Your product/service was sold on Vivarena.'
                ))
            );
        }

        $this->Message->create();
        $this->Message->save(array(
                'Message'=>array(
                    'from_id'=> ADMIN,
                    'to_id'  => $this->myId,
                    'content'=> 'You made a purchase on Vivarena! Invoice was sent to your email.',
                    'subject'=> 'You made a purchase on Vivarena!'
                ))
        );

        $this->set('server',$_SERVER['SERVER_NAME']);
        $this->set('message', 'You made a purchase on Vivarena!. Your invoice: '.$invoiceLink);
        $this->SwiftMailer->to = $data['buyer_email'];
        $this->SwiftMailer->sendAs = 'html';
        try {
            $this->SwiftMailer->send('after_payment', __('You made a purchase on Vivarena!. Your invoice: '.$invoiceLink.'. Check your PayPal email for details.', true));
        } catch (Exception $e) {
            $this->log($e->getMessage());
            $this->log($e->getTraceAsString());
        }

        foreach ($sellers_ids as $email => $fake) {
            if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->set('message', 'Your product/service was sold on Vivarena!. Check your PayPal email for details.');
                $this->SwiftMailer->to = $email;
                $this->SwiftMailer->sendAs = 'html';
                try {
                    $this->SwiftMailer->send('after_payment', __('Your product/service was sold on Vivarena. Check your PayPal email for details.', true));
                } catch (Exception $e) {
                    $this->log($e->getMessage(), 'emailErrors');
                    $this->log($e->getTraceAsString(), 'emailErrors');
                }
                /*$this->Email->to = $email;
                $this->Email->subject = 'Your product was buyed on Vivarena';
                $this->Email->template = 'after_payment';
                $this->Email->sendAs = 'html';
                $this->Email->smtpOptions = array(
                    'host' => 'smtp.gmail.com',
                    'username'=> 'vt.api.test@gmail.com',
                    'password'=> '789512346'
                );
                $this->set('message', 'Your product was buyed on Vivarena. Invoice: '.$invoiceLink.' Check your PayPal email.');
                $this->Email->send();*/
            }
        }

    }

    private function getCountries(){
        $countries = null;
        $response = $this->Store->CheckOut();
        if(isset($response['countries']['country'])) {
            $countries = $response['countries']['country'];
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $countries = Set::combine($countries, '{n}.id', '{n}.name');
        }
        return $countries;
    }

    /**
     * @route /profile/products --> {"controller": "shop", "action": "UserProducts"}
     */
    public function UserProducts() {
        if($this->Session->check('userLastAddedProduct') && isset($_GET['return'])) {
            $this->Store->UserDelProduct($this->Session->read('userLastAddedProduct'));
            $this->Session->delete('userLastAddedProduct');
        }elseif($this->Session->check('userLastAddedProduct') && isset($_GET['success'])) {
            $res = $this->Store->ActiveProduct($this->Session->read('userLastAddedProduct'), $_GET['token'], $_GET['PayerID']);
            if(isset($res['success'])) {

                // Add product to wall
                $prod = $this->Store->GetProduct((int)$this->Session->read('userLastAddedProduct'));
                $lnk = Inflector::slug($prod['product']['title'], '-');
                $lnk = (string)$prod['product']['id'].'-'.$lnk;
                $feed['user_id'] = $prod['product']['user_id'];
                $serText = array(
                    'text' => 'added a new Product/Service',
                    'img' => $prod['product']['image'],
                    'link' => $lnk
                );
                $feed['id_affected_tables'] = $prod['product']['id'];
                $feed['activity_text'] = serialize($serText);
                $feed['type_feed'] = 'product';
                $ActivityWall = ClassRegistry::init('ActivityWall');
                $ActivityWall->create();
                $ActivityWall->save($feed);
                //

                $this->_setFlashMsg('Item added', 'success');
                $this->Session->delete('userLastAddedProduct');
                $this->redirect('/profile/products');
            }else {
                $this->_setFlashMsg('Error! Call tou your administrator', 'error');
            }
        }
        $this->layout = 'community';
        $data = $this->Store->GetUsersProducts(8, 1, $this->myId);
        $this->set('data', $data);
        $this->set('action', 'index');
        $this->set('isUsedProducts',$this->used);
    }

    /**
     * @param int $page
     * @route /profile/products/page/* --> {"controller": "shop", "action": "UserProductsPage"}
     */
    public function UserProductsPage($page = 1) {
        $page = (int) $page;
        $data = $this->Store->GetProducts(8, $page);
        $this->set('data', $data);
        $this->set('action', 'index');
        $this->set('isUsedProducts', $this->used);
        $this->render('index');
    }

    /**
     * @route /profile/add-product --> {"controller": "shop", "action": "UserAddProduct"}
     */
    public function UserAddProduct($edit = null) {
        Configure::write('debug', 0);
        $this->layout = 'community';
        $err = false;
        $err_desc = '';

        if($this->data) {

            if(is_array($this->data['UserProduct']['image']) && $this->data['UserProduct']['image']['error'] == 0){
                $file = $this->data['UserProduct']['image'];
                $pInfo = pathinfo($file['name']);
                $ext = strtolower($pInfo['extension']);

                if ($ext != 'jpg' && $ext != 'jpeg' && $ext != 'gif' && $ext != 'png')
                    $err_desc .= "Invalid file type: " . $ext . "\n";

                /*$fInfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);

                if ($fInfo != 'image/jpeg' && $fInfo != 'image/gif' && $fInfo != 'image/png')
                    $err_desc .= "Invalid mime type: " . $fInfo . "\n";*/

                $extList = array('image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/png' => 'png');
                $size = getimagesize($file['tmp_name']);

                $getType = (isset($size['mime'])) ? $size['mime'] : null;

                if (array_key_exists($getType, $extList)) {
                    $ext = $extList[$getType];
                } else $err_desc .= "Invalid mime type: " . $getType . "\n";

                $file_name = 'photo_' . uniqid() . '.' . $ext;
                $dirToPhotos = DS . 'uploads' . DS . 'userfiles' . DS . 'user_' . $this->myId;

                if (!is_dir(rtrim(WWW_ROOT, DS) . $dirToPhotos))
                    mkdir(rtrim(WWW_ROOT, DS) . $dirToPhotos, 0777, true);

                $pathToFile = $dirToPhotos . DS . $file_name;

                if(empty($err_desc) && move_uploaded_file($file['tmp_name'], rtrim(WWW_ROOT, DS) . $pathToFile)) {
                    $this->data['UserProduct']['image'] = $pathToFile;
                }else{
                    $this->data['UserProduct']['image'] = null;
                }
            }elseif(is_string($this->data['UserProduct']['image'])){

            }else{
                $this->data['UserProduct']['image'] = (isset($this->data['UserProduct']['prev_image']))?
                    $this->data['UserProduct']['prev_image']:
                    null;
            }

            $this->data['UserProduct']['user_id'] = $this->Auth->user('id');
           // $this->data['UserProduct']['quantity'] = (isset($this->data['UserProduct']['quantity']) && !empty($this->data['UserProduct']['quantity'])) ? $this->data['UserProduct']['quantity'] : 1;
            // product is inactive until the user does not pay for publishing him
            if($edit == null){
                $this->data['UserProduct']['active'] = 0;
            }
            else{
                if(   $this->data['UserProduct']['price'] > $this->Session->read('old_price')  ){
                    $this->_setFlashMsg('Price will not be  increased', 'error');
                    $this->redirect($this->referer());
                }
                else{
                    $data = array('data' => $this->Crypter->Crypt($this->data));
                    // add product to DB in store
                    $response = $this->Store->UserAddProduct($data);
                    if(isset($response['success'])) {
                        $this->_setFlashMsg('Product save success', 'success');
                        $this->redirect($this->referer());
                    }
                    else{
                        $this->_setFlashMsg('Error! Call tou your administrator', 'error');
                        $this->redirect($this->referer());
                    }

                }
            }
            $data = array('data' => $this->Crypter->Crypt($this->data));
            // add product to DB in store
            $response = $this->Store->UserAddProduct($data);
            if(isset($response['success'])) {
                $this->Session->write('userLastAddedProduct', $response['id']);
                // if INSERT success go to PayPal for 1% pay
                $response = $this->Store->AddProductProcess($response['id'], $this->Auth->user());
                $this->redirect($response['paypal_url']);
                $this->redirect('/profile/products');
            }else {
                $this->_setFlashMsg('Error! Call tou your administrator', 'error');
            }
        }
        $this->set('categories', $this->Store->GetCategories());
        $this->set('add_product_flag', true);
    }

    /**
     * @route /profile/edit-product/* --> {"controller": "shop", "action": "UserEditProduct"}
     */
    public function UserEditProduct($product_id = null) {
        $this->layout = 'community';
        if($this->data) {
            $this->UserAddProduct(true);
//            $this->data['UserProduct']['user_id'] = $this->Auth->user('id');
//            $data = array('data' => $this->Crypter->Crypt($this->data));
//            $response = $this->Store->UserEditProduct($data);
//            if(isset($response['success'])) {
//                $this->_setFlashMsg('Item edited', 'success');
//                $this->redirect('/profile/products');
//            }else {
//                $this->_setFlashMsg('Error! Call tou your administrator', 'error');
//            }
        }
        $data = $this->Store->UserEditProduct($product_id);
        $this->Session->write('old_price', $data['product']['price']);
      //  $this->data['old_price'] = $data['product']['price'];
//        if(isset($data['product']['price'])) {
//            unset($data['product']['price']);
//        }
//        if(isset($data['product']['rprice'])) {
//            unset($data['product']['rprice']);
//        }
//        if(isset($data['product']['quantity'])) {
//            unset($data['product']['quantity']);
//        }
        foreach ($data['product'] as $key => $val) {
            $this->data['UserProduct'][$key] = $val;
        }
        $this->data['CategoriesProduct']['category_id'][] = $data['category_product']['category_id']['id'];

        $this->set('categories', $this->Store->GetCategories());
        $this->set('edit_product_flag', true);
        $this->render('user_add_product');

    }

    /**
     * @route /profile/del-product/* --> {"controller": "shop", "action": "UserDeleteProduct"}
     */
    public function UserDeleteProduct($product_id) {
        $response = $this->Store->UserDelProduct($product_id);
        if(isset($response['success'])) {
            $this->_setFlashMsg('Item deleted', 'success');
            $this->redirect('/profile/products');
        }else {
            $this->_setFlashMsg('Error! Call tou your administrator', 'error');
            $this->redirect('/profile/products');
        }
    }

    /**
     * @route /profile/repost-product --> {"controller": "shop", "action": "UserRepostProduct"}
     */
    public function UserRepostProduct() {
        if($this->data) {
            $id = $this->data['Product']['id'];
            $qty = $this->data['Product']['quantity'];
            $this->Session->write('userRepostProductId', $id);
            $this->Session->write('userRepostProductQty', $qty);
            $response = $this->Store->RepostProduct($id, $qty);
            if(isset($response['paypal_url'])) {
                $this->redirect($response['paypal_url']);
            }else {
                $this->_setFlashMsg('Error! Call tou your administrator', 'error');
            }
        }
    }

    /**
     * @route /profile/complete-repost-product --> {"controller": "shop", "action": "CompleteRepostProduct"}
     */
    public function CompleteRepostProduct() {
        $id = $this->Session->read('userRepostProductId');
        $qty = $this->Session->read('userRepostProductQty');
        $token = (isset($_GET['token'])) ? $_GET['token'] : null;
        $payer_id = (isset($_GET['PayerID'])) ? $_GET['PayerID'] : null;
        if (!empty($token) && !empty($payer_id)) {
            $response = $this->Store->CompleteRepostProduct($id, $qty, $token, $payer_id);
            if(!isset($response['success'])) {
                $this->_setFlashMsg('Error! Call tou your administrator', 'error');
            }else {
                // if repost SUCCESS
                // Add product to wall
                $prod = $this->Store->GetProduct((int)$id);
                $lnk = Inflector::slug($prod['product']['title'], '-');
                $lnk = (string)$prod['product']['id'].'-'.$lnk;
                $feed['user_id'] = $prod['product']['user_id'];
                $serText = array(
                    'text' => 'reposted a Product/Service',
                    'img' => $prod['product']['image'],
                    'link' => $lnk
                );
                $feed['id_affected_tables'] = $prod['product']['id'];
                $feed['activity_text'] = serialize($serText);
                $feed['type_feed'] = 'product';
                $ActivityWall = ClassRegistry::init('ActivityWall');
                $ActivityWall->create();
                $ActivityWall->save($feed);
                //
            }
        } else $this->_setFlashMsg('Error! Call tou your administrator', 'error');
        $this->redirect('/profile/products');
    }

    public function invoice($id){
        $this->layout = 'print';
        $data = $this->Store->invoice($id);
        $this->set('data',$data);
    }

}