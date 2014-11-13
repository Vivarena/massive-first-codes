<?php
/**
 * @property Group $Group
 * @property User $User
 * @property Friend $Friend
 * @property Interest $Interest
 * @property Message $Message
 * @property UserFriend $UserFriend
 * @property UserInfo $UserInfo
 * @property EmailComponent $Email
 * @property StoreComponent $Store
 * @property ActivityWall $ActivityWall
 * @property UserActivityWall $UserActivityWall
 * @property UserPost $UserPost
 *
 * @property SessionComponent $Session
 * @property RequestHandlerComponent $RequestHandler
 */
class CommunityController extends AppController
{
    public $name = 'Community';

    public $uses = array('User', 'UserInfo', 'UserFriend');

    public $components = array('Email', 'Store');

    public $helpers = array('Number');

    public $myId;

    public $used = false;

    public $services = false;

    public $uinfo_paginate = array(
        'fields' => array(
            'UserInfo.user_id',
            'UserInfo.username',
            'UserInfo.country_id',
            'UserInfo.photo',
            'UserInfo.avatar'),
        'limit' => 5
    );

    private $forIgnoreInBefore = array();

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->layout = 'community';
        $this->myId = $this->Session->read('Auth.User.id');

        // beforeFilter for all /profile/:id/* paths
        $id = isset($this->params['id']) ? $this->params['id'] : null;
        if (is_null($id) && isset($this->params['pass'][0])) {
          $id = $this->params['pass'][0];
        }
        if (is_null($id) && isset($this->params['login'])) {
          $user = $this->User->find('first', array(
            'conditions' => array($this->User->alias.'.login' => $this->params['login']),
            'fields' => array('id'),
            'contain' => false,
          ));
          $id = $user['User']['id'];
        }

        if (!is_null($id)) {
          $userInfoBefore = array('activity', 'contacts', 'suggestions');
          if (in_array($this->action, $userInfoBefore)) {
            $this->_setUserInfo($id);
          }
          $this->set('friendly', $this->UserFriend->isFriend($id, $this->myId));
          $this->set('me', ($this->myId == $id));
        }
    }

    public function beforeRender()
    {
        parent::beforeRender();
        if (!$this->RequestHandler->isAjax() ) {
            $this->_setBasicProfileData();
        }
        $this->set('myID', $this->myId);
    }

    private function _setBasicProfileData()
    {
      # TODO: refactor this
        $id = null;
        $login = (isset($this->params['login'])) ? $this->params['login'] : false;
        if ( !$login and (!(is_array($this->passedArgs) && isset($this->passedArgs[0])) or $this->action == 'polls' or $this->action == 'view_post')) {
            $id = $this->myId;
        } else {
            //check if user if friends and set needed flag
            if ($login) { $id = $this->User->getUIDByLogin($login); } else { $id = (isset($this->passedArgs[0])) ? $this->passedArgs[0] : null; }
            if (is_numeric($id)) {
                $this->_setFriendshipStatus($id);
            } else {
                $this->redirect('/profile');
            }
        }

        if (is_null($id)) {
          return;
        }

        //set up basic profile data for sidebar
        if ($this->Auth->user('id') == $id) {
            $userSessionData = $this->Session->read('Auth.User');
            $userData = $userSessionData['info'];
            $userData['user_id'] = $userSessionData['id'];
            $userData['login'] = $userSessionData['login'];
            $userData['country_name'] = (isset($userSessionData['info']['Country']['name'])) ? $userSessionData['info']['Country']['name'] : null;
        } else {
            $userData = $this->User->getBasicInfo($id);
        }


        $this->loadModel('UserPost');
        if ($this->Auth->user('id') == $id) {
            $status = $this->Session->read('userStatusText');
        } else {
            $status = $this->UserPost->getStatus($id);
        }

        $this->set('user_status', $status);

        $this->data = array_merge((array)$this->data, $userData);
        return $this->data;
    }

    public function index() {
        $this->loadModel('ActivityWall');
        $this->set('aWall', $this->ActivityWall->getActivity($this->myId));
        // TODO: uncomment this before load to server after all store will be DONE
        $products = $this->Store->GetProducts();
        $this->set('products', $products['products']);
    }

    /**
     * @param int $page
     */
    public function getFeeds($page = 1) {
        Configure::write('debug', 0);
        if ($this->RequestHandler->isAjax()) {
            $this->layout = false;
            $this->autoRender = false;
            $this->loadModel('ActivityWall');
            $feeds = $this->ActivityWall->getActivity($this->myId, false, null, $page);
            $this->set('aWall', $feeds);
            $view_output = $this->render('../elements/community/ajax_activityList', 'ajax');
            $this->output = '';
            exit(json_encode($view_output));
        }
    }

    public function activity($id = null)
    {
        //$this->layout = 'profile';

        $uid = (is_null($id)) ?  $this->myId : $id;

        $login = isset($this->params['login']) ? $this->params['login'] : null;
        if (!empty($login)) $uid = $this->User->getUIDByLogin($login);


        $countFriends=$this->UserFriend->getCount($uid);
        $this->set('countFriends', $countFriends);

        $this->set('feeds', $this->_getProfileWall($uid, true));
        $this->set('mineID', $this->myId);
    }


    public function view_post($idPost = null)
    {
        $this->loadModel('UserPost');
        $post = $this->UserPost->find('first', array(
            'conditions' => array('UserPost.id' => $idPost),
            'contain' => array('User' => array('UserInfo'), 'UserPostComment' => array('User' => array('UserInfo')))
        ));
        if ($post) {
            if ($this->myId == $post['UserPost']['user_id']) $this->set('isMyProfile', true);
        }
        $this->set('post', $post);

    }

    public function showPostComments()
    {
        $this->layout=false;
       // if (!empty($this->params['data']))
      //  {
            $data=$this->params['data'];
            $comments = $this->_getCommentsPost($data['post_id'], $data['user_id']);
            $this->set('commentItems', $comments);

            $this->loadModel('UserPost');
            $fullPostText = $this->UserPost->find('first', array(
                'contain' => array(),
                'conditions' => array('UserPost.id' => $data['post_id']),
                'fields' => 'UserPost.text'
            ));
            $this->set('fullPostText', $fullPostText);
      //  }
        $this->render('/elements/community/listPostComments');
    }

    public function addPostComment()
    {
        if ($this->RequestHandler->isAjax() ) {
            $this->loadModel('UserPostComment');
            $data['UserPostComment']['text']=$this->params['data']['comment_text'];
            $data['UserPostComment']['user_post_id']=$this->params['data']['user_post_id'];
            if (!empty($data)) {
                $data['UserPostComment']['user_id'] = $this->Auth->user('id');
                if ($this->UserPostComment->save($data)) {
                    $this->_setFlash(__('The post successfully added', true), 'success');
                } else {
                    $this->_setFlash(__('An error occurred when adding the post', true), 'error');
                }
            }
            $comments = $this->_getCommentsPost($data['UserPostComment']['user_post_id'], $this->Auth->user('id'));
            $this->set('commentItems', $comments);
            $this->render('/elements/community/listPostComments');

        } else {
            $this->redirect('/');
        }
    }

    public function deletePostComment()
    {
        if ($this->RequestHandler->isAjax() ) {

            $data = isset($this->data['id']) ? $this->data : null;
            if (!empty($data)) {
                $this->loadModel('UserPostComment');
                $this->loadModel('UserPost');
                $getOwnerPostID = $this->UserPost->find('first', array(
                    'contain' => array(),
                    'conditions' => array('UserPost.user_id' => $this->Auth->user('id'), 'UserPost.id' => $data['user_post_id']),
                    'fields' => 'UserPost.id'
                ));
                $getOwnerPostID = ($getOwnerPostID) ? $getOwnerPostID['UserPost']['id'] : false;

                if ($data['user_id'] == $this->Auth->user('id') || $getOwnerPostID) {
                    if ($this->UserPostComment->delete($this->data['id'])) {
                        $this->_setFlash(__('The comment successfully deleted', true), 'success');
                    } else {
                        $this->_setFlash(__('An error occurred when deleting the comment', true), 'error');
                    }
                }
                $comments = $this->_getCommentsPost($data['user_post_id'], $this->Auth->user('id'));
                $this->set('commentItems', $comments);
                $this->render('/elements/community/listPostCommentsAjax');
            }
        } else {
            $this->redirect('/');
        }
    }

    function deletePost()
    {
        Configure::write('debug', 0);
        //$this->autoRender = false;
        $err = false;
        $err_desc = '';

        if ($this->RequestHandler->isAjax() ) {
            $this->loadModel('DeletedActivityWall');

            $data = isset($this->data['activity_wall_id']) ? $this->data : null;

            if (empty($data))
                $err_desc = __('An error occurred with data', true);

            if ($err_desc=='')
                if ($this->DeletedActivityWall->save($this->data)){
                    $this->_setFlash(__('The post successfully from your dashboard page', true), 'success');
                    $this->set('feeds', $this->_getProfileWall($data['user_id'], false));
                    $this->render('/elements/community/activity_items', 'ajax');
                }
            else
                $err_desc = __('An error occurred when deleting the post', true);

            if (!empty($err_desc)) {
            $err = true;
            $result = array(
                'error' => $err,
                'err_desc' => $err_desc,
            );
            exit(json_encode($result));
            }
        }
    }

    private function _getCommentsPost($pID, $uID, $limit = 10)
    {

        $this->loadModel('UserPostComment');

        $data = $this->UserPostComment->find('all', array(
            'contain' => array(
                'User' =>  array(
                    'UserInfo' => array(
                        'fields' => array('first_name', 'last_name', 'photo', 'avatar')
                    )
                )
            ),
            'limit' => $limit,
            'order' => array('UserPostComment.created' => 'ASC'),
            'conditions' => array('UserPostComment.user_post_id' => $pID)
        ));

        return $data;
    }

    private function _getProfileWall($uid, $onlyFeeds = false) {

        //$this->loadModel('UserPost');
        $this->loadModel('ActivityWall');
        //$posts = $this->_getPosts($uid);
        $feeds = $this->ActivityWall->getActivity($uid, true, $this->myId);
        $newFeeds = array();
        foreach($feeds as $feed){
          $newFeeds[]['Feeds'] = $feed['ActivityWall'];
        }
        unset($feeds, $feed);

        return $newFeeds;

    }


    public function feed()
    {
        $this->loadModel('ActivityWall');

        $this->set('aWall', $this->ActivityWall->getActivity($this->myId));
        $this->set('mineID', $this->myId);
    }

    public function deleteItem(){
        if ($this->RequestHandler->isAjax()) {
        $this->loadModel('ActivityWall');
        $del = $this->ActivityWall->delete(array('id' => $this->data['id'], 'user_id' => $this->data['user_id']));
            if ($del) {
                $this->_setFlash(__('The item successfully deleted', true), 'success');
                $this->set('feeds', $this->_getProfileWall($this->data['user_id'], false));
                $this->render('/elements/community/activity_items', 'ajax');
            }
        }
    }

    public function deletingMultipleFeed() {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $errorMes = "";

        $this->loadModel('DeletedActivityWall');

        if($this->data){
            foreach($this->data['DeletedActivityWall'] as &$item){
                $item['user_id'] = $this->Auth->user('id');
            }
            if(!$this->DeletedActivityWall->saveAll($this->data['DeletedActivityWall'])){
                $errorMes = "An error occurred when deleting the post";
            }
        }

        $result = array(
            'error' => $errorMes,
            'result' => $this->data['DeletedActivityWall']
        );

        exit(json_encode($result));
    }

    public function deleteFeed($idF = null)
    {

        if ($this->_deleteFeed($idF)) {
            $this->_setFlash('Item successfully deleted', 'success');
            $this->set('aWall', $this->ActivityWall->getActivity($this->myId));
        } else {
            $this->_setFlash('Item has not been removed', 'error');
        }
        $this->render('/elements/community/activityList', "ajax");

    }

    function deleteFromFeed($idF)
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';

        if (!$this->_deleteFeed($idF, 'postWall')) {
            $err_desc = 'Item has not been removed';
        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    private function _deleteFeed($idF, $feed = null){

        if ($this->Auth->user('id')) {
            $this->loadModel('DeletedActivityWall');
            $this->loadModel('ActivityWall');
            $toSave['user_id'] = $this->myId;
            $toSave['activity_wall_id'] = $idF;
            if ($feed == 'postWall') {
                $toSave['from'] = 'post';
            }
            return $this->DeletedActivityWall->saveAll($toSave);
        }
        return false;
    }

    public function uploadAva()
    {

        $photo = $this->UserInfo->find('first', array(
            'conditions' => array('UserInfo.user_id' => $this->myId),
            'fields' => array('UserInfo.photo', 'UserInfo.avatar')
        ));
        $photo = $photo['UserInfo'];
        $this->set('photo', $photo);
        $this->render('/elements/community/uploadAva');


    }

    function uploadPhotoAjax(){
      Configure::write('debug', 0);
      $this->autoRender = false;
      $err = false;
      $err_desc = '';

      $userInfo = $this->data['UserInfo'];
      if (isset($userInfo['avatar'])) {
        $file = $userInfo['avatar'];
        $field = 'avatar';
      } elseif (isset($userInfo['bg_image'])) {
        $file = $userInfo['bg_image'];
        $field = 'bg_image';
      } else {
        $err_desc .= "No image specified.";
      }

      $pinfo = pathinfo($file['name']);
      $ext = strtolower($pinfo['extension']);
      if ($ext != 'jpg' && $ext != 'jpeg' && $ext != 'gif' && $ext != 'png') {
        $err_desc .= "Invalid file type: " . $ext . "\n";
      }

      /*$finfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
      if ($finfo != 'image/jpeg' && $finfo != 'image/gif' && $finfo != 'image/png') {
        $err_desc .= "Invalid mime type: " . $finfo . "\n";
      }*/
      $extList = array('image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/png' => 'png');
      $size = getimagesize($file['tmp_name']);

      $getType = (isset($size['mime'])) ? $size['mime'] : null;

      if (array_key_exists($getType, $extList)) {
          $ext = $extList[$getType];
      } else $err_desc .= "Invalid mime type: " . $getType . "\n";

      $file_name = 'photo_' . uniqid() . '.' . $ext;
      $dirToPhotos = DS . 'uploads' . DS . 'userfiles' . DS . 'user_' . $this->myId;
      if (empty($err_desc) && !is_dir(rtrim(WWW_ROOT, DS) . $dirToPhotos)) {
        mkdir(rtrim(WWW_ROOT, DS) . $dirToPhotos, 0777, true);
      }
      
      $pathToFile = $dirToPhotos . DS . $file_name;
      if(empty($err_desc) && move_uploaded_file($file['tmp_name'], rtrim(WWW_ROOT, DS) . $pathToFile)) {
        if ($userInfoID = $this->UserInfo->getIdByUser($this->myId)) {
          $this->UserInfo->id = $userInfoID;
          if ($field == 'avatar') {
            $data['UserInfo']['photo'] = $pathToFile;
            $data['UserInfo']['avatar'] = null;
          } elseif ($field == 'bg_image') {
            $data['UserInfo']['bg_image'] = $pathToFile;
          } else {
            $data['UserInfo']['bg_image'] = null;
            $data['UserInfo']['photo'] = null;
            $data['UserInfo']['avatar'] = null;
          }
          if (!$this->UserInfo->save($data)) {
            $err_desc .= 'An error occurred when saving photo!';
          }
        } else {
          $err_desc .= 'An error occurred with user ID!';
        }
      } else {
        if (empty($err_desc)) {
          $err_desc .= 'An error occurred with file!';
        }
      }
      if (!empty($err_desc)) $err = true;
      $result = array(
        'name' => $file_name,
        'photo' => $pathToFile,
        'error' => $err,
        'err_desc' => $err_desc
      );
      exit(json_encode($result));
    }

    function removePhoto()
    {
      Configure::write('debug', 0);
      $this->autoRender = false;

      $err = false;
      $err_desc = '';

      $this->UserInfo->id = $this->UserInfo->getIdByUser($this->myId);
      $data['UserInfo']['photo'] = null;
      $data['UserInfo']['avatar'] = null;

      if (!$this->UserInfo->save($data)) {
        $err_desc = "An error occurred when removing photo";
      }

      if (!empty($err_desc)) $err = true;
      $result = array(
        'error' => $err,
        'err_desc' => $err_desc,
      );

      exit(json_encode($result));
    }


    public function cropPhoto()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        if (isset($this->data['crop'])) {
            $x1 = $this->data['crop']['x1'];
            $x2 = $this->data['crop']['x2'];
            $y1 = $this->data['crop']['y1'];
            $y2 = $this->data['crop']['y2'];
            $dirToPhotos = rtrim(WWW_ROOT, DS) . DS . 'uploads' . DS . 'userfiles' . DS . 'user_' . $this->myId;
            $nameFile = end(explode('/', $this->data['crop']['src']));
            $avaFileName = DS . 'uploads' . DS . 'userfiles' . DS . 'user_' . $this->myId . DS . 'ava_' . $nameFile;

            if ($this->crop($dirToPhotos . DS . $nameFile, $dirToPhotos . DS .'ava_' . $nameFile, array($x1, $y1, $x2, $y2))) {
                $userInfoID = $this->UserInfo->getIdByUser($this->myId);
                $this->UserInfo->id = $userInfoID;
                $data['UserInfo']['avatar'] =  $avaFileName;
                $this->UserInfo->save($data);
                $this->Session->write('Auth.User.info.avatar', $avaFileName);
            } else {
                $err_desc = "An error occurred when saving coordinates!";
            }
        } else {
            $err_desc = "No coordinates for crop!";
        }

        if (!empty($err_desc)) $err = true;
        $result = array(
                'error' => $err,
                'err_desc' => $err_desc
            );
        exit(json_encode($result));
    }

    private function crop($file_input, $file_output, $crop, $size = 390) {
    	list($wOrig, $hOrig, $type) = getimagesize($file_input);
    	if (!$wOrig || !$hOrig) {
    		return false;
        }

        $types = array('','gif','jpeg','png');
        $ext = $types[$type];
        if ($ext) {
                $func = 'imagecreatefrom'.$ext;
                $img = $func($file_input);
        } else {
            return false;
        }

        list($x1, $y1, $x2, $y2) = $crop;
        $w = $x2 - $x1;
        $h = $y2 - $y1;

        $imgNewSize = ($wOrig > $size) ? $this->resizeImg($img, $wOrig, $hOrig, $size) : array('imgNewSize' => $img);
    	$img_o = imagecreatetruecolor($w, $h);

    	imagecopy($img_o, $imgNewSize['imgNewSize'], 0, 0, $x1, $y1, $w, $h);
        $img_o = $this->resizeImg($img_o, $w, $h, 120);
    	if ($type == 2) {
    		return imagejpeg($img_o['imgNewSize'],$file_output,100);
    	} else {
    		$func = 'image'.$ext;
    		return $func($img_o['imgNewSize'],$file_output);
    	}
    }

    private function resizeImg($img, $wOrig, $hOrig, $size) {

        $result['hDist'] = round($size/($wOrig / $hOrig));
        $result['wDist'] = $size;
        $result['imgNewSize'] = imagecreatetruecolor($result['wDist'], $result['hDist']);
        imagecopyresampled($result['imgNewSize'], $img, 0, 0, 0, 0, $result['wDist'], $result['hDist'], $wOrig, $hOrig);

        return $result;
    }

    public function profile($id = null)
    {

        //$this->layout = 'profile';

        $login = isset($this->params['login']) ? $this->params['login'] : null;

        if (is_null($id) && isset($this->params['id'])) {
            $id = $this->params['id'];
        }
        if (isset($id)) {
            $user = $this->User->getBasicInfo($id);
            if ($user['login']) {
                $this->redirect('/' . $user['login']);
                return;
            }
        } else if (isset($login)) {
            $user = $this->User->getBasicInfo($login, true);
        } else {
            $this->redirect('/');
        }

        if (empty($user)) {
            $this->redirect('/');
        } else {
            if (!isset($this->passedArgs[0])) {
                $this->passedArgs[0] = $user['user_id'];
            }
            if (!isset($id)) {
                $id = $user['user_id'];
            }
        }
        $this->set(compact('user'));

        $this->_setRightSideFriends($id);

        if ($this->myId == $id) $this->set('isMyProfile', true);
        $this->set('feeds', $this->_getProfileWall($id, true));

    }


    public function contacts()
    {
        $id = null;
        $conditions = null;

        if (isset($this->params['id'])) {
            $id = $this->params['id'];
        }
        // for paging
        if (is_null($id) && isset($this->params['named']['id'])) {
            $id = $this->params['named']['id'];
        }

        $login = null;
        if (isset($this->params['login'])) {
            $login = $this->params['login'];
        }
        // for paging
        if (is_null($login) && isset($this->params['named']['login'])) {
            $login = $this->params['named']['login'];
        }

        if (empty($id) && !empty($login)) {
            $id = $this->User->getUIDByLogin($login);
        }

        if (empty($id)) {
            $this->log('Missing id param.');
            return;
        } else {
            // for paging
            $this->params['named'] = array_merge($this->params['named'], array('id' => $id));
        }

        //$this->layout = 'profile';

        $countFriends = $this->UserFriend->getCount($id);
        $this->set('countFriends', $countFriends);

        $filterFriend = (isset($this->data['FilterFriend'])) ?  $this->data['FilterFriend'] : null;

        if (!empty($filterFriend)) {
            if (!empty($filterFriend['first_name'])) $conditions['UserInfo.first_name LIKE'] = "%{$filterFriend['first_name']}%";
            if (!empty($filterFriend['last_name'])) $conditions['UserInfo.last_name LIKE'] = "%{$filterFriend['last_name']}%";
            if (!empty($filterFriend['hometown'])) $conditions['UserInfo.hometown LIKE'] = "%{$filterFriend['hometown']}%";
            if (!empty($filterFriend['city'])) $conditions['UserInfo.city LIKE'] = "%{$filterFriend['city']}%";
        }

        # paginated friends
        $friends = $this->_getFriends($id, 8, '*', $conditions);
        $allFriends = $this->_getFriends($id, 8, '*');

        /*if ($friends) {
          $friends = Set::extract('/Friend/UserInfo/.', $friends);
        }*/
        $this->set(array(
            'friends' => $friends,
            'allFriends' => $allFriends
        ));
        if ($this->RequestHandler->isAjax()) {
            exit($this->render('/elements/profile/ajax-list-contacts', 'ajax'));
        }

        # user_info in beforeFilter
    }


    public function edit_profile() {
        $this->layout = 'default';
        $this->set('noAddThis', true);
        $id = $this->Auth->user('id');
        if (!is_null($id)) {
            $this->loadModel('Country');
            $countries = $this->Country->find('list', array(
                'order' => 'name'
            ));
            $this->set('riskProfile', $this->User->getCustomFields('riskProfile'));
            $this->set('prefLang', $this->User->getCustomFields('prefLang'));
            $this->set('newWorth', $this->User->getCustomFields('newWorth'));
            $this->set('goals', $this->User->getCustomFields('goals'));
            $accountsHeld['Select please'] = $this->User->getCustomFields('accounts');
            $this->set('accountsHeld', $accountsHeld);
            $this->set('roi', $this->User->getCustomFields('roi'));
            $this->set('marital', $this->User->getCustomFields('marital'));
            $this->set('salary', $this->User->getCustomFields('salary'));

            $this->set('countries', $countries);

            $this->loadModel('UserPrivateInfo');
            $this->loadModel('UserFinanceInfo');
            $this->loadModel('Interest');

            $usrIntrsts = $this->Interest->getActiveForUser($id);

            $this->set( 'interests', $this->Interest->getCheckedInterests($usrIntrsts) );

            $this->User->Behaviors->attach('Containable');
            $this->User->bindModel(array(
                'hasOne' => array('UserPrivateInfo', 'UserFinanceInfo'),
//                'hasMany' => array('UserInterest'),
            ));
            $this->User->unbindModel(array('belongsTo' => array('Group')));
            $this->data = $this->User->findById($id);
            $accountsTmp = isset($this->data['UserFinanceInfo']['accounts']) ? $this->data['UserFinanceInfo']['accounts'] : null;
            if (!empty($accountsTmp)) {
                $selectedAccounts = explode(', ', $accountsTmp);
                $this->set('selectedAccounts', $selectedAccounts);
            }
            unset($this->data['User']['password']);
            foreach($this->data as $model => $fields) {
                unset($this->data[$model]['created']);
                unset($this->data[$model]['modified']);
            }
            if (isset($this->data['UserPrivateInfo']['birthday'])) $this->data['UserPrivateInfo']['birthday'] = date('d M, Y', strtotime($this->data['UserPrivateInfo']['birthday']));

        }

        $this->loadModel('Privacy');
        $privacy_options = $this->Privacy->selectOptions();
        $this->set(compact('privacy_options'));
        $this->loadModel('UserPrivacy');

        $privacy = $this->UserPrivacy->findAllByUserId($id);
        $privacy = $privacy[0]['UserPrivacy'];
        $this->set(compact('privacy'));
    }



    public function search()
    {

        $limit = 40;
        $conditions = array();
        $searchText = (isset($this->data['Search']['text'])) ? trim($this->data['Search']['text']) : null;
        if (!empty($searchText)) {
            $this->Session->write('Search.text', $searchText);
        } elseif($this->Session->check('Search.text')){
            if (!empty($this->passedArgs)) {
                $searchText = $this->Session->read('Search.text');
            } else $this->Session->delete('Search');
        }
        if (isset($this->data['Search']['text']) || $this->Session->check('Search.text')) {
            $this->set('searchText', $searchText);
            $conditions = array('AND' => array(
                        'OR' => array(
                            'UserInfo.first_name LIKE' => "%$searchText%",
                            'UserInfo.last_name LIKE' => "%$searchText%",
                            'User.email LIKE' => "%$searchText%",
                    )
                )
            );
        }

        $paginate = array(
            'conditions' => array('User.group_id' => 2, 'User.id <> ' => $this->Auth->user('id')),
            'order' => array('User.created DESC'),
            'recursive' => 2,
            'fields' => array('DISTINCT User.id','*'),
            'limit' => $limit
        );

        $paginate['conditions'] = array_merge($paginate['conditions'], $conditions);
        $this->paginate = array_merge($this->paginate, $paginate);
        $items = array();
        //if (!empty($searchText)) {
            $items = $this->paginate('User');
            foreach ($items as &$oneUser)
            {
                $oneUser['User'] = $this->_setFriendshipStatus($oneUser['User']['id']);
            }
            $this->set('rel_users', $items);
        //}
        $count = (isset($this->params['paging']['User']['count'])) ? $this->params['paging']['User']['count'] : 0;


        $this->set('users', $items);
        $this->set('count', $count);

        if ($this->RequestHandler->isAjax() ) {
            $searchContent = $this->render('/elements/community/joinUsSearch', 'ajax');
            $this->output = '';
            exit(json_encode(array(
                'contentHtml' => $searchContent,
            )));
        }



    }

    public function sendInvite()
    {
        Configure::write('debug', 0);
        $err = false;
        $err_desc = '';

        if (isset($this->data['emails']) && !empty($this->data['emails'])) {
            $emails = explode(',', $this->data['emails']);
            $onlyEmails = array();
            foreach ($emails as $oneEmail){
                $tmpItem = trim($oneEmail);
                $arrByRn = explode("\r\n", $tmpItem);
                if (count($arrByRn) > 1) {
                    foreach($arrByRn as $oneTmpEmail)
                        if (!empty($oneTmpEmail)) $onlyEmails[] = $oneTmpEmail;
                } else {
                    $onlyEmails[] = $tmpItem;
                }
            }
            unset($emails);
            // A limit for sending invitations, a bit of protection from spam
            if (count($onlyEmails) < 30){
                if (!$this->_sendInviteEmail($onlyEmails)) {
                    $err_desc = 'An error occurred when sent email!';
                }
            } else $err_desc = 'Error! Too many e-mails!';
        } else {
            $err_desc = 'No email!';
        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    public function network() {
      if (isset($this->params['form']['page'])) $this->passedArgs['page'] = $this->params['form']['page'];

      $networkUsers = $this->_paginateWithSorting($this->myId);
      $this->set('rel_users', $networkUsers);

      if (isset($this->params['paging']['UserFriend'])) {
        $this->set('countFriends', $this->params['paging']['UserFriend']['count']);
        $this->set('countPages', $this->params['paging']['UserFriend']['pageCount']);
      }

      if (isset($this->params['form']['type']) && $this->params['form']['type'] == 'onlyFriends') {
        $this->render('/elements/community/listFriendsInNetwork');
      } else {
        $this->render('my_network');
      }
    }

    private function _paginateWithSorting($uID, $limit = 7, $approved = 1)
    {
        // Custom joins needed for Sorting by FirstName in Alphabetics.
        try{
            $this->paginate['UserFriend']['joins'] = array(
                array(
                  'table' => 'bs_users',
                  'alias' => 'Friend',
                  'type'  => 'left',
                  'conditions' => array('UserFriend.friend_id = Friend.id'),
                ),
                array(
                  'table' => 'bs_user_infos',
                  'alias' => 'UserInfo',
                  'type'  => 'left',
                  'conditions' => array('Friend.id = UserInfo.user_id'),
                ),
                array(
                  'table' => 'bs_countries',
                  'alias' => 'UserCountry',
                  'type'  => 'left',
                  'conditions' => array('UserInfo.country_id = UserCountry.id'),
                ),
                array(
                  'table' => 'bs_user_private_infos',
                  'alias' => 'UserPrivateInfo',
                  'type'  => 'left',
                  'conditions' => array('UserPrivateInfo.user_id = Friend.id'),
                )
            );
            $this->paginate['UserFriend']['conditions'] = array(
                'UserFriend.user_id' => $uID,
                'UserFriend.approved' => $approved,
//                'Friend.id <>' => null,
            );
            $this->paginate['UserFriend']['fields'] = '*';
            $this->paginate['UserFriend']['limit'] = $limit;
            $this->paginate['UserFriend']['order'] = array('UserInfo.first_name');
            $this->paginate['UserFriend']['recursive'] = 2;
            $this->UserFriend->unbindModel(
                array(
                    'belongsTo' => array('Friend')
                ), false
            );
            $data = $this->paginate('UserFriend');
        } catch (Exception $e) {
            $data = false;
        }

        return $data;

    }

    public function addToNetwork() {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      Configure::write('debug', 0);
      $out = array('status' => false);

        if ($this->RequestHandler->isAjax()) {
          $allowed = ($this->User->is_private($this->data['id']))? false:true;
          $out = $this->UserFriend->addToNetwork($this->myId, $this->data['id'], $allowed);
          if($allowed && $out['status'] == true){
              $this->Message->set(array(
                  'to_id' => $this->data['id'],
                  'from_id' => $this->myId,
                  'subject' => 'Add to network',
                  'content' => 'Hello, I added you to my network!',
                  'status' => false
              ));
              $this->Message->saveAll();
          }
      }

      if ($out['status']) {
          $email_options = array('is_private' => $allowed);
          $this->_sendNotificationEmail($this->data['id'],$email_options);
      }

      exit(json_encode($out));
    }

    public function confirmFriend() {
      Configure::write('debug', 0);
      $out = array('status' => false);

      $id = null;
      if (isset($this->params['form']['id'])) {
        $id = $this->params['form']['id'];
      }

      if ($this->RequestHandler->isAjax() && !is_null($id)) {
        $this->layout = false;
        $this->autoRender = false;
        $data = array( 'UserFriend' => array(
          'user_id' => $id,
          'friend_id' => $this->myId,
          'approved' => 1
        ) );
        $this->UserFriend->create($data);
        if ($this->UserFriend->validates()) {
          $this->UserFriend->save();
          //approving request on my side
          $data = $this->UserFriend->find('first', array(
            'contain' => false,
            'conditions' => array(
              'user_id' => $this->myId,
              'friend_id' => $data['UserFriend']['user_id']
            )
          ));
          $ufId = $data['UserFriend']['id'];
          $this->UserFriend->id = $ufId;
          $this->UserFriend->saveField('approved', 1);
          $out = array_merge( $out, array(
            'status' => true,
            'message' => __('Approved', true)
          ) );
        }
      }
      exit(json_encode($out));
    }

    public function cancelRequest() {
        Configure::write('debug', 0);
        $out = array('status' => false);
        if ($this->Auth->user()){
            if ($this->RequestHandler->isAjax()) {
                $this->layout = false;
                $this->autoRender = false;

                $del = $this->UserFriend->deleteAll(array('UserFriend.user_id' => $this->data['id'], 'UserFriend.friend_id' => $this->myId, 'UserFriend.approved' => 0));
                if ($del) {
                    $out = array_merge( $out, array(
                        'status' => true,
                        'message' => 'Canceled'
                    ) );
                }
            }
        }
        exit(json_encode($out));
    }


    public function listRequests() {
        Configure::write('debug', 0);
        if ($this->RequestHandler->isAjax()) {
            $this->layout = false;
            $this->autoRender = false;
            $this->set('page_name', 'Friend Requests');
            $this->paginate = array(
                'contain' => true,
                'conditions' => array(
                    'UserFriend.user_id' => $this->myId,
                    'UserFriend.approved' => 0
                ),
                'limit' => 5,
                'recursive' => 2
            );
            $data = $this->paginate('UserFriend');
            if ($data) {
                foreach ($data as &$oneUser)
                {
                    $oneUser['User'] = $this->_setFriendshipStatus($oneUser['UserFriend']['friend_id']);
                    $oneUser['UserInfo'] = $oneUser['Friend']['UserInfo'];
                }
            }

            $this->set('rel_users', $data);
            $this->render('/elements/community/network-container');
        } else {
            $this->redirect('/community.html');
        }
    }

    public function my_requests(){
        $data = $this->_paginateWithSorting($this->myId, 5, 0);
        if ($data) {
            foreach ($data as &$oneUser)
            {
                $oneUser['UserFriendship'] = $this->_setFriendshipStatus($oneUser['UserFriend']['friend_id']);
                $oneUser['UserInfo']['UserName']=$oneUser['UserInfo']['first_name']." ".$oneUser['UserInfo']['last_name'];
            }
            $this->set('rel_users', $data);
        }
    }


    private function _setFriendshipStatus($friendId, $no = true)
    {

        $friendStatus = $this->UserFriend->setFriendshipStatus($friendId, $this->myId);

        if ($no) {
            if ( $friendStatus['isMyFriend'] && $friendStatus['iAmFriend'] ) {
                $this->set('is_friend', 1);
            } elseif ($friendStatus['isMyFriend']) {
                $this->set('is_friend', 2);
            } elseif ($friendStatus['iAmFriend']) {
                $this->set('is_friend', 3);
            } else {
                $this->set('is_friend', 0);
            }
        }
        return $friendStatus;
    }

    public function unFriend() {
      Configure::write('debug', 0);
      $out = array('status' => false);

      $id = null;
      if (isset($this->params['form']['id'])) {
        $id = $this->params['form']['id'];
      }

      if ($this->RequestHandler->isAjax() && !is_null($id)) {
        $this->layout = false;
        $this->autoRender = false;
        $this->UserFriend->deleteAll(array(
          'OR' => array(
            array('user_id' => $this->myId, 'friend_id' => $id),
            array('user_id' => $id, 'friend_id' => $this->myId),
          )
        ));
        $out = array_merge($out, array('status' => true, 'text' => __('Deleted', true)));
      }

      exit(json_encode($out));
    }


    # TODO: there shouldn't be so many different places in the controllers through which to send emails
    #   create a model
    private function _sendNotificationEmail($toID,$options = array())
    {
        $this->set('server', 'http://' . $_SERVER['SERVER_NAME']);

        $myInfo = $this->Session->read('Auth.User.info');
        $poss = (strcmp($myInfo['sex'], 'M') == 0) ? 'his' : 'her';

        if(!empty($options) && isset($options['is_private'])){
            if($options['is_private']){
                $this->set('subject', $myInfo['username'] . ' ' . __('wants to add you to ' . $poss . ' network!', true));
            }else{
                $this->set('subject', $myInfo['username'] . ' ' . __('added you to ' . $poss . ' network!', true));
            }
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = $this->User->read('email', $toID);

        $this->Email->smtpOptions = array(
            'port'=> '465',
            'timeout'=> '30',
            'host' => 'ssl://smtp.gmail.com',
            'username'=> 'vivarena.site@gmail.com',
            'password'=> 'vivaQaz987'
        );
        $this->Email->delivery = 'smtp';

        $this->Email->to = $data['User']['email'];
        $this->Email->from = 'noreply@' . $_SERVER['SERVER_NAME'];
        $this->Email->subject = 'Someone wants to add you to ' . $poss . ' network!';
        $this->Email->template = 'request';
        $this->Email->layout = 'default';
        $this->Email->sendAs = 'html';
        $this->Email->send();

        return true;
    }

    /**
     * @param $toEmail
     * @return bool
     */
    private function _sendInviteEmail($toEmail)
    {
        $this->set('server', 'http://'.$_SERVER['SERVER_NAME']);
        $myInfo = $this->Session->read('Auth.User.info');
        $this->set('userName', $myInfo['username']);

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        if (count($toEmail) > 1) {
            $this->SwiftMailer->to = array_shift($toEmail);
            $this->SwiftMailer->bcc = $toEmail;
        } else $this->SwiftMailer->to = $toEmail;
        $this->SwiftMailer->sendAs = 'html';

        try {
          $this->SwiftMailer->send('invite', $myInfo['username'] . ' ' . __('invites you to join', true));
          return true;
        } catch (Exception $e) {
          $this->log($e->getMessage());
          $this->log($e->getTraceAsString());
          return false;
        }
    }

    private function _setUserInfo($id) {
      $this->UserInfo->recursive = -1;
      $user_info = $this->UserInfo->findByUserId($id);
      $user_info = Set::extract('/UserInfo/.', $user_info);
      $user_info = $user_info[0];

      # normalize the url fields
      $urlFields = array('url_facebook', 'url_linkedin', 'url_twitter', 'url_blog');
      foreach ($urlFields as $field) {
        $value = $user_info[$field];
        if (!$this->_startsWith($value, 'http://') && !$this->_startsWith($value, 'https://')) {
          $user_info[$field] = 'http://'.$value;
        }
      }

      $this->set('user_info', $user_info);
    }

    /*
     * http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
     */
    private function _startsWith($haystack, $needle)
    {
      $length = strlen($needle);
      return (substr($haystack, 0, $length) === $needle);
    }
}
