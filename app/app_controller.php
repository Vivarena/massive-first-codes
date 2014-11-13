<?php

/**
 * @property SessionComponent $Session
 * @property RequestHandlerComponent $RequestHandler
 * @property AuthComponent $Auth
 * @property AclComponent $Acl
 * @property GoodAclComponent $GoodAcl
 * @property SimpleAuthComponent $SimpleAuth
 * @property GraphComponent $Graph
 * @property ConnectComponent $Connect
 * @property User $User
 * @property UserInfo $UserInfo
 *
 * @property Group $Group
 * @property SiteMenu $SiteMenu
 * @property Menu $Menu
 */

class AppController extends Controller
{
    public $helpers = array('Session', 'Html', 'Form', 'Time');
    public $components = array(
        'Session', 'RequestHandler', 'DebugKit.Toolbar',
        'SimpleAuth', 'Auth', 'Cookie', 'Messenger'
    );
    public $view = 'cakephp-twig.Twig';

    public $languages = array(
        'spa' => 'Spanish',
        'eng' => 'English',
        'por' => 'Portuguese'
    );

    public $loginAuth;

    public $ajaxResponse = array(
        'error' => false,
        'errDesc' => ''
    );

    public $store_url;

    public function afterAjax()
    {
        Configure::write("debug", 0);
        $this->autoRender = false;
        if (!empty($this->ajaxResponse['errDesc'])) $this->ajaxResponse['error'] = true;
        if (isset($this->ajaxResponse['content'])) {
            $this->ajaxResponse['content'] = $this->render($this->ajaxResponse['content'], 'ajax');
            $this->output = '';
        }
        exit(json_encode($this->ajaxResponse));

    }

    public function beforeFilter() {
        $this->SimpleAuth->initAuth();

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        SiteConfig::initialize();

        if (!defined('SUPPORTEMAIL')) define('SUPPORTEMAIL', SiteConfig::read('email'));

        if ($this->plugin != 'admin' && $this->Auth->user()) {
            $this->_setInfoUser();
        }

        # cakephp_static_user
        App::import('Model', 'User');
        User::store($this->Auth->user());

        $autologinId = $this->Cookie->read('autologin');
        if (!$this->Auth->user() && $autologinId) {
          $this->loadModel('User');
          $user = $this->User->find('first', array('contain' => false, 'conditions' => array('id' => $autologinId)));
          if ($user) {
            $this->_login($user);
          }
        }
        $this->store_url = 'http://store.'.str_replace("www.","", env('HTTP_HOST'));
    }

    public function isAuthorized() {
        $user = $this->Auth->user();
        return $this->SimpleAuth->isAuth($user['User']);
    }

    /**
     * Programmatical login.
     */
    protected function _login($user) {
      $this->Auth->login($user);
      $this->_setupSession($user);
    }

    protected function _setupSession() {
      if ($this->Auth->user()) {
        # name, avatar, etc.
        $this->loadModel('UserInfo');
        $userInfo = $this->UserInfo->getBasicInfo($this->Auth->user('id'));
        $this->Session->write('Auth.User.info', $userInfo);

      }
    }

    public function beforeRender()
    {
        parent::beforeRender();
        if ($this->Session->check('OnlyMessage')) {
            $this->set('onlyMessage', $this->Session->read('OnlyMessage'));
            $this->Session->delete('OnlyMessage');
        }
        $this->set('currentUrl', $this->here);
        $this->set('host', 'http://'.$_SERVER['HTTP_HOST']);
        $this->set('loginAuth', $this->loginAuth);
        $infoID = $this->_getLoginAndId();

        if (!empty($infoID['login']) && !$infoID['youIsOwnerPage']) {
            $this->_setAvaBoxInfo($infoID['user_id'], $infoID['login']);
        }
        $sponsorOnPage = array('/community', '/messages', '/profile/requests', '/profile/edit', '/messages/inbox', '/messages/sent');
        if (!empty($infoID['user_id'])) {
            $this->_setActiveSponsor($infoID['user_id']);
            $this->_setActiveGear($infoID['user_id']);
        } elseif(in_array($this->here, $sponsorOnPage)) {
            $this->_setActiveSponsor($this->Auth->user('id'));
            $this->_setActiveGear($this->Auth->user('id'));
        }

        $this->_setCurrentLink();
        $this->_setCountFriends();
        $this->set('user_type', $this->_setUserType());
        $this->set('store_url', $this->store_url);
    }

    private function _setUserType() {
        $this->loadModel('UserType');
        $type_id = $this->Auth->user('user_type_id');
        $type_name = $this->UserType->find('first', array('recursive' => -1, 'conditions' => array('UserType.id' => $type_id)));
        $type_name = $type_name['UserType']['name'];
        $file = ROOT.DS.APP_DIR.DS.WEBROOT_DIR.DS.'img'.DS.'user_types'.DS.strtolower($type_name).'_icon.png';
        if(file_exists($file)) {
            return '/img/user_types/'.strtolower($type_name).'_icon.png';
        }else {
            return strtolower($type_name);
        }
    }

    private function _setCountFriends() {
        $this->loadModel('UserFriend');
        if(isset($this->viewVars['otherUserInfo']['id'])) {
            $id = $this->viewVars['otherUserInfo']['id'];
        }else {
            $id = $this->Auth->user('id');
        }
        $this->set('countFriends', $this->UserFriend->GetCount($id));
    }

    private function _setActiveSponsor($uID) {
        $this->loadModel('UserSponsor');
        $this->set('activeSponsor', $this->UserSponsor->getActive($uID, 'sponsor', true));
        $this->set('allSponsor', $this->UserSponsor->getAll($uID, 'sponsor'));
    }

    private function _setActiveGear($uID) {
        $this->loadModel('UserSponsor');
        $this->set('activeGear', $this->UserSponsor->getActive($uID, 'gear', true));
        $this->set('allGear', $this->UserSponsor->getAll($uID, 'gear'));
    }

    private function _getLoginAndId(){
        $result['login'] = (isset($this->params['login'])) ? $this->params['login'] : null;
        $result['user_id'] = null;
        $result['youIsOwnerPage'] = false;
        $getAuthLogin = $this->Auth->user('login');

        if (!empty($result['login'])) {

            if ($result['login'] != $getAuthLogin) {
                $result['user_id'] = (isset($this->viewVars['album']['User'])) ? $this->viewVars['album']['User'] : null;  // 1. If view Album of user
                if (empty($userId)) {
                    $this->loadModel('User');
                    $result['user_id'] = $this->User->getUIDByLogin($result['login']);
                }
            } else {
                $result['user_id'] = $this->Auth->user('id');
                $result['youIsOwnerPage'] = true;
                $this->set('youIsOwnerPage', true);
            }
            $this->set('loginThisPage', $result['login']);
        }
        return $result;
    }

    private function _setAvaBoxInfo($uID, $login)
    {
        $this->loadModel('UserInfo');
        $userInfo = $this->UserInfo->getBasicInfo($uID);
        if ($userInfo) {
            $userInfo['loginThisPage'] = $login;
        }
        $this->set('otherUserInfo', $userInfo);
    }

    public function _setRightSideFriends($id){
        $this->_checkLoadedModels(array('UserFriend', 'UserPhoto', 'UserVideo'));
//        $countFriends = $this->UserFriend->getCount($id);
//        $this->set('countFriends', $countFriends);
        $friends = $this->_getFriends($id, 8, array('Friend.login', 'UserInfo.photo', 'UserInfo.avatar', 'Friend.id', 'UserInfo.first_name', 'UserInfo.last_name'));
        $this->set('friends', $friends);
        $getLastPhotos = $this->UserPhoto->getLastPhoto($id);
        $this->set('lastPhotos', $getLastPhotos);
        $getLastVideos = $this->UserVideo->getLastVideo($id);
        $this->set('lastVideos', $getLastVideos);

    }

    public function _checkLoadedModels($models = array()) {
        foreach ($models as $model)
            if (!ClassRegistry::isKeySet($model)) $this->loadModel($model);

    }

    public function _getFriends($uID, $limit = 4, $fields = '*', $additionalConditions = null) {

        $conditions = array(
            'UserFriend.user_id' => $uID,
            'UserFriend.approved' => 1,
            'Friend.id <>' => null,
        );

        if (!empty($additionalConditions) && is_array($additionalConditions)) {
            $conditions = array_merge($conditions, $additionalConditions);
        }

        $this->paginate = array(
            'joins' => array(
                array(
                    'table' => 'bs_users',
                    'alias' => 'Friend',
                    'type' => 'left',
                    'conditions' => array('UserFriend.friend_id = Friend.id'),
                ),
                array(
                    'table' => 'bs_user_infos',
                    'alias' => 'UserInfo',
                    'type' => 'left',
                    'conditions' => array('UserInfo.user_id = Friend.id'),
                )
            ),
            'conditions' => $conditions,
            'fields' => $fields,
            'recursive' => -1,
            'limit' => $limit,
        );
        $data = $this->paginate('UserFriend');

      return $data;
    }

    private function _setCurrentLink()
    {
        $currentLink = array();

        switch($this->here) {
            case '/community':
                $currentLink['community'] = 'current';
                break;
            case '/profile/edit':
                $currentLink['community'] = 'current';
                break;
            case "/{$this->loginAuth}/albums":
                $currentLink['albums'] = 'current';
                break;
            case '/profile/requests':
                $currentLink['requests'] = 'current';
                break;
            case '/messages':
                $currentLink['messages'] = 'current';
                break;
            case "/{$this->loginAuth}/friends":
                $currentLink['friends'] = 'current';
                break;
            default:
                break;
        }

        $this->set('currentLink', $currentLink);
    }

    private function _setInfoUser()
    {
        if ($this->Session->read('FB_logoutLink')) {
            $this->set('FB_logoutLink', true);
        }
        $uInfo = $this->Auth->user();
        $uInfo = $uInfo['User'];

        $this->loadModel('Message');
        $this->loadModel('UserFriend');
        $unreadCount = $this->Message->getUnreadMessages($uInfo['id']);
        $friendRequestsCount = $this->UserFriend->countRequests($uInfo['id']);
        $this->set('notify', array('unread' => $unreadCount, 'requests' => $friendRequestsCount));

        $this->loginAuth = (!empty($uInfo['login'])) ? $uInfo['login'] : 'profile-'.$uInfo['id'];
    }


    /**
     * Set meta tags
     * @param $metaTags
     *
     * @internal param array $tags
     */
    protected function _setMetaTags($metaTags)
    {
        if(is_array($metaTags)) {
            foreach($metaTags as $tag => $content)
            {
                if(!empty($content)) {
                    $this->set('_tag_' . $tag, $content);
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
            'message' => $msg
        );

        if($this->Session->check('FlashMessage.' . $key)) {
            $flashData = $this->Session->read('FlashMessage.' . $key);

            array_push($flashData, $flash);
        } else {
            $flashData[] = $flash;
        }

        $this->Session->write('FlashMessage.' . $key, $flashData);
    }
    
    public function _setFlashMsg($msg, $type = 'message') {
        $types = array('error', 'warning', 'message', 'success');
        if(!in_array($type, $types)) {
            $type = 'message';
        }
        $this->Session->write('OnlyMessage.type', $type);
        $this->Session->write('OnlyMessage.message', $msg);
    }

    public function _initDynamicMenu($name)
    {
        $this->loadModel("SiteMenu");
        $this->loadModel("Menu");
        $data = $this->SiteMenu->find("first",
            array(
                "conditions" => array(
                    "SiteMenu.active" => 1,
                    "SiteMenu.name" => $name,
                )
            )
        );

        $menu = $this->Menu->get($data['SiteMenu']['id'], true, true);
        return $menu;
    }


}
