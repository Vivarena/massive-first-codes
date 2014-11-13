<?php

/**
 * @property SessionComponent $Session
 * @property GoodAclComponent $GoodAcl
 */

class AppController extends Controller
{
    public $helpers = array('Session', "jGrowl", 'Html', 'Form');
    public $components = array(
        'Session', 'RequestHandler', 'DebugKit.Toolbar', 'Auth'
    );
    public $view = 'cakephp-twig.Twig';


    private $_publicActions = array(
        'Pages' => "*",
        'Api' => "*",
        'Users' => array('login', 'logout', 'registration', 'reg_validate'/*, 'edit'*/),
        'Products' => "*",
        'Ups'      => "*",
        'Payments' => "*",
        'PaymentsExpress' => '*'

    );


    protected $_unprocessedOrderSessionKey = 'UnprocessedOrderNum';
    public $_statuses = array(
        0 => 'Reversed',
        1 => 'Voided',
        2 => 'Canceled_Reversal',
        3 => 'Completed',
        4 => 'Denied',
        5 => 'Pending'
    );


    private $forAuthUser = array(
        'user' => array(
            'Pages' => array('index', 'contact', 'display'),
            'Users' => array('login', 'logout', 'edit'),
        )
    );


    public function beforeRender()
    {

        $this->set('product_menu', $this->_initProductMenu());

    }

    public function beforeFilter() {
        parent::beforeFilter();
        SiteConfig::initialize();
        if (!defined('SUPPORTEMAIL')) define('SUPPORTEMAIL', SiteConfig::read('email'));

        $this->_initAuth();

    }

    /**
     * Initialize Auth component
     * @return void
     */
    private function _initAuth()
    {

        $this->Auth->fields = array(
            'username' => 'username',
            'password' => 'password'
        );
        $this->Auth->loginRedirect = array(
            'plugin' => 'admin',
            'controller' => 'admin_products',
        );
        $this->Auth->logoutRedirect = array(
            'controller' => 'pages',
            'action' => 'home'
        );

        // for anonimous users (authorized users used ACL rules)
        if($this->Auth->user() == null) {
            foreach($this->_publicActions as $controller => $action)
            {
                $curControllerName = $this->plugin ? "{$this->plugin}.{$this->name}" : $this->name;
                if($controller == $curControllerName) {
                    if($action == '*' || in_array($this->action, $action)) {
                        $this->Auth->allow($this->action);
                    }
                }
            }
        }
    }



    public function _initProductMenu()
    {
        $this->loadModel("Category");
        $menu = $this->Category->getProductMenu();
        return $menu;
    }

    /**
     * Set meta tags
     * @param $metaTags
     *
     * @internal param array $tags
     */
    protected function _setMetaTags($metaTags)
    {
        $defaultMetaTag = "GTL GTL Lifestyle Official situation situation online store situation online sorrentino store sorrentino online sorrentino merchandise";
        if(is_array($metaTags)) {
            foreach($metaTags as $tag => $content)
            {
                if(!empty($content)) {
                    $this->set('_tag_' . $tag, $content . $defaultMetaTag);
                }
            }
        }
    }

    function _setFlash($msg, $type = 'message', $key = 'flash') {
        $types = array('error', 'warning', 'message', 'success');

        if(!in_array($type, $types)) {
            $type = 'message';
        }

        if(empty($key)) {
            $key = 'flash';
        }

        $flash = array(
            'type' => $type,
            'message' => __($msg, true)
        );

        if($this->Session->check('FlashMessage.' . $key)) {
            $flashData = $this->Session->read('FlashMessage.' . $key);

            array_push($flashData, $flash);
        } else {
            $flashData[] = $flash;
        }

        $this->Session->write('FlashMessage.' . $key, $flashData);
    }

    public function _getCountryName($id){
        $this->loadModel("Country");
        $countryName = $this->Country->read("name", $id);
        return $countryName['Country']['name'];
    }
    public function _getRegionName($id){
        $this->loadModel("Region");
        $regionName = $this->Region->read("name", $id);
        return $regionName['Region']['name'];
    }
    public function _getStateName($initials){
        $this->loadModel("State");
//        $regionName = $this->State->read("initials", $initials);
        $regionName = $this->State->find('first', array(
            'conditions'=>array("initials" => $initials)
        ));
        return $regionName['State']['name'];
    }

    public function _getCityName($id){
        $this->loadModel("City");
        $regionName = $this->City->read("name", $id);
        return $regionName['City']['name'];
    }

}
