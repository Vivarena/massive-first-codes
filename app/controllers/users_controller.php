<?php
/**
 * @property SessionComponent $Session
 * @property Interest $Interest
 * @property User $User
 * @property UserInfo $UserInfo
 * @property UserType $UserType
 * @property UserFriend $UserFriend
 * @property UserInterest $UserInterest
 * @property UserPrivateInfo $UserPrivateInfo
 * @property UserFinanceInfo $UserFinanceInfo
 * @property AuthComponent $Auth
 * @property ImageComponent $Image
 */
class UsersController extends AppController
{
    /**
     *
     * @var string
     */
    public $name = "Users";

    /**
     * @var array
     */
    public $components = array('RequestHandler', 'Session', 'Email', 'Image');

    /**
     * @var array
     */
    public $uses = array('User', 'UserInfo', 'Facebook');

    public $myId;

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->myId = $this->Session->read('Auth.User.id');
    }

    public function denied() {}

    /**
     * Login user
     *
     * @param bool $redirect
     */
    function login($redirect = true) {
        # remember_me autologin
        if (isset($this->data['UserLogin'])) {
            $this->data['User'] = $this->data['UserLogin'];
            $this->data['User']['password'] = $this->Auth->password($this->data['UserLogin']['password']);
            unset($this->data['UserLogin']);
            if (!$this->Auth->login($this->data)) {
                $this->_setFlashMsg('Error! Invalid email or password', 'error');
                $this->redirect($this->referer());
            }
        }

        if (isset($this->data['User']['remember']) && $this->data['User']['remember']) {
            $this->Cookie->write('autologin', $this->Auth->user('id'), true, '7 days');
        }

        $groupId = $this->Auth->user("group_id");
        if (!empty($groupId)) {
            $this->_setupSession();
        }

        if ($redirect) {
            switch ($groupId) {
                case 1:
                    $this->redirect("/admin");
                    break;
                case 2:
                    if ($this->Session->check('ShareThis')) {
                        $this->redirect('/profile/messaging');
                    } else {
                        $this->redirect('/community');
                    }
                    break;
                default:
                    $this->_setFlashMsg('Please login or register', 'error');
                    $this->redirect("/");
                    break;
            }
        }
    }

    public function fbProcess() {
        $uid = $this->params['data']['FB']['uid'];
        $accessToken = $this->params['data']['FB']['accessToken'];

        // get user data from facebook
        $fbUser = $this->Facebook->user($uid, $accessToken);
        $fbUser = $this->Facebook->parseFacebookUser($fbUser);

        // check if user exist in local database
        $localUser = $this->User->find('first', array(
            'conditions' => array(
                $this->User->alias.'.facebook_id' => $fbUser['facebook_id']
            )
        ));

        if(empty($localUser)) {
            $response = array(
                'UserInfo' => array(
                    'first_name' => $fbUser['first_name'],
                    'last_name' => $fbUser['last_name']
                ),
                'User' => array(
                    'email' => $fbUser['email'],
                    'facebook_id' => $fbUser['facebook_id']
                )
            );
            exit(json_encode($response));
        }else {
            $this->Auth->login($localUser);
            $this->_setupSession();
            exit(json_encode(array('status' => true)));
        }
    }

    public function linkedInProcess() {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $linkedInData = $this->params['data']['LinkedIn'];

        // check if user exist in local database
        $localUser = $this->User->find('first', array(
            'conditions' => array(
                $this->User->alias.'.linkedin_id' => $linkedInData['id']
            )
        ));

        if(empty($localUser)) {
            $response = array(
                'UserInfo' => array(
                    'first_name' => $linkedInData['firstName'],
                    'last_name' => $linkedInData['lastName']
                ),
                'User' => array(
                    'email' => $linkedInData['emailAddress'],
                    'linkedin_id' => $linkedInData['id']
                )
            );
            exit(json_encode($response));
        }else {
            $this->Auth->login($localUser);
            $this->_setupSession();
            exit(json_encode(array('status' => true)));
        }
    }

    /**
     * Logout user
     *
     * @return void
     */
    function logout() {
      # keep lang on logout
      $lang = $this->Session->read("lang");
      session_destroy();
      $this->Session->write("lang", $lang);
      Configure::write('Config.language', $lang);

      # delete autologin
      $this->Cookie->delete('autologin');

      if ($this->RequestHandler->isAjax()) {
        exit();
      } else {
        $this->redirect($this->Auth->logout());
      }
    }


    public function share_this()
    {

        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $redirect = '';
        $locationURL = (isset($this->params['form']['locationURL'])) ? $this->params['form']['locationURL'] : null;
        $shareType = (isset($this->params['form']['shareType'])) ? $this->params['form']['shareType'] : null;
        if (!empty($locationURL)) {
            $this->Session->write('ShareThis.location', $locationURL);
            $this->Session->write('ShareThis.shareType', $shareType);
        }

        if ($this->Auth->user('id')) {
            $redirect = '/profile/messaging';
        } else {
            $err_desc = __('Sorry, but you must be logged', true);
        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'redirect' => $redirect,
            'error' => $err,
            'err_desc' => $err_desc,
        );
        exit(json_encode($result));

    }

    /**
     * Registration page
     */
    public function signup() {
        $user = $this->Auth->user();
            if (isset($user)) {
            $this->redirect('/profile');
            return;
        }



        //$this->layout = "registration";
    }

    public function join_us()
    {
        SiteConfig::invalidate();
        $this->set('cloudSpongeDomain', SiteConfig::read('Api.cloud_sponge'));

        $this->layout = "registration";
    }

    /**
     * Registration AJAX validator
     */
    public function ajax_register() {
        Configure::write('debug', 0);
        $this->layout = false;
        $out = array('status' => false);

        # mass assignment protection
        $this->data['User']['group_id'] = 2;


        # generate a random password for sso users
        if (!empty($this->data['User']['facebook_id']) || !empty($this->data['User']['linkedin_id']) || !empty($this->data['User']['twitter_id'])) {
            if($this->_login($this->User->getUserByEmail($this->data['User']['email'])))
            {
                $out = array('status' => true);
                exit(json_encode($out));
            }
            else
            {
                $this->data['User']['cpassword'] = $this->_generatePassword();
                $this->data['User']['password'] = $this->Auth->password($this->data['User']['cpassword']);
            }
        }
        /*$userPrivateInfo = $this->data['UserPrivateInfo'];
        if (!empty($userPrivateInfo['birthday'])) {
            $this->data['UserPrivateInfo']['birthday'] = date('Y-m-d', strtotime($userPrivateInfo['birthday']));
        }*/
        $this->loadModel('UserPrivateInfo');
        $this->User->set($this->data);
        $this->UserInfo->set($this->data);
        $this->UserPrivateInfo->set($this->data);
        $validUser = $this->User->validates();
        $validUserInfo = $this->UserInfo->validates();
        if ($validUser && $validUserInfo) {
            $this->User->saveAll($this->data);
            $loginUser['User']['login'] = $this->User->checkAndSetLogin($this->data['UserInfo'], $this->User->id);
            $loginUser['User']['id'] = $this->User->id;
            $this->User->save($loginUser, false);

            $this->_login($this->data);

            # ...and send the user an email
            #$this->_sendRegistrationEmail($this->data);

            # ... and send the user a default message (which triggers an email)
            $this->_sendWelcomeMessage($this->User->id, $this->data);

            # ... and clear any SSO sessions
            $this->Session->delete('facebook.user');
            $this->Session->delete('linkedin.user');
            $this->Session->delete('twitter.user');

            $out = array('status' => true);
        } else {
            unset($this->data['User']['password']);
            unset($this->data['User']['cpassword']);
            $out = array('status' => false,
                'errors' => array_merge($this->User->validationErrors, $this->UserInfo->validationErrors));
        }
        exit(json_encode($out));
    }

    public function getVivarenaUsers() {
      Configure::write('debug', 0);
      $this->layout = false;

      if ($this->RequestHandler->isAjax() ) {
        if (isset($this->params['form']['uids'])){
          $friendUIDs = array_filter(explode(',', $this->params['form']['uids']));

          $listUsers = $this->User->find('all', array(
            'contain' => array(
              # CakePHP bug: virtual field doesn't work with User fields specified
              'UserInfo' => array('fields' => array(/*'UserInfo.username',*/ 'UserInfo.first_name', 'UserInfo.last_name', 'UserInfo.avatar', 'UserInfo.photo')),
            ),
            'fields' => array('id', 'login', 'facebook_id'),
            'conditions' => array('User.facebook_id' => $friendUIDs),
          ));
          $this->loadModel('UserFriend');
          foreach ($listUsers as &$oneUser)
            $oneUser['UserFriend'] = $this->UserFriend->setFriendshipStatus($oneUser['User']['id'], $this->myId);
          $this->set('inviteFriendsFB', $listUsers);
          $contentInvite = $this->render('/elements/users/inviteFriendsFB', 'ajax');
          $this->output = '';
          exit(json_encode(array('content' => $contentInvite)));
        }
      }

      //exit($this->render('/elements/users/inviteFriendsFB'));
    }

    function addUserToNetwork()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $status = false;
        $msg = '';

        $this->loadModel('UserFriend');
        if ($this->Session->check('userReg.id')) {
            if ($this->RequestHandler->isAjax() ) {
                $data = (isset($this->data['id'])) ? $this->data : null;
                if (!empty($data)) {
                    $addTo = $this->UserFriend->addToNetwork($this->Session->read('userReg.id'), $data['id']);
                    $status = $addTo['status'];
                    $msg = $addTo['message'];
                }

            }
        } else {
            $status = false;
            $msg = 'Access denied!';
        }


        $result = array(
            'status' => $status,
            'message' => $msg,
        );

        exit(json_encode($result));
    }

    public function edit() {
        $this->layout = 'community';

        $id = $this->Auth->user('id');

        if (is_null($id)) {
            $this->redirect('/');
            return;
        }

        if ($this->data) {
            unset($this->User->validate['password']);
            unset($this->User->validate['cpassword']);
            unset($this->User->validate['email']['email-rule-3']);
            $this->loadModel('UserPrivateInfo');
            $this->User->set($this->data);
            $this->UserInfo->set($this->data);
            $this->UserPrivateInfo->set($this->data);
            $validUser = $this->User->validates();
            $validUserInfo = $this->UserInfo->validates();
            $usedEmail = $this->User->checkAuthEmail($this->data['User']['email'], $this->myId);
            if ($usedEmail) { $this->User->validationErrors['email'] = 'Someone has registered with this email before.'; }
            if ($validUser && $validUserInfo && !$usedEmail) {
                $this->data['User']['id'] = $this->myId;
                $this->User->save($this->data['User']);
                $this->UserInfo->save($this->data['UserInfo']);
                if($this->Session->read('Auth.User')) {
                    $this->Session->write('Auth.User.user_type_id', $this->data['User']['user_type_id']);
                }
                $this->UserPrivateInfo->save($this->data['UserPrivateInfo']);
                $out = array('status' => true, 'new_name' => $this->data['UserInfo']['first_name'].' '.$this->data['UserInfo']['last_name']);
            } else {
                $out = array('status' => false,
                    'errors' => array_merge($this->User->validationErrors, $this->UserInfo->validationErrors));
            }
            if ( $this->RequestHandler->isAjax() ) {
                Configure::write('debug', 0);
                exit(json_encode($out));
            }

        }

        $user = $this->User->find('first', array(
            'conditions' => array('User.id' => $id),
            'contain' => array('UserInfo', 'UserPrivateInfo')
        ));

        $this->set('sex', array('M' => 'Man', 'F' => 'Woman'));

        $this->data = $user;

        $this->loadModel('UserFriend');
        $friendRequestsCount = $this->UserFriend->countRequests($id);
        $this->data = array_merge((array)$this->data, array('requests' => $friendRequestsCount));

        $this->loadModel('Message');
        $unreadCount = $this->Message->getUnreadMessages($this->myId);
        $this->data = array_merge((array)$this->data, array('unread' => $unreadCount));

        $this->loadModel('UserType');
        $this->set('user_types', $this->UserType->find('list'));


        /*$this->loadModel('Country');
        $countries = $this->Country->find('list', array(
            'order' => 'name'
        ));
        $this->set('countries', $countries);*/
        $this->_setRightSideFriends($id);

    }

    /**
     * User info editing function
     *
     * @return void
     */
    public function ajax_edit() {
      $id = $this->Auth->user('id');

      // mass assignment security
      unset($this->data['User']['group_id']);
      unset($this->data['UserFriend']);
      $this->data['User']['id'] = $id;

      Configure::write('debug', 0);
      $this->layout = false;
      $out = array();
      if ($this->data) {

        if (isset($this->data['Social'])) {
          $this->loadModel('SocialNotification');
          $this->SocialNotification->deleteAll(array('SocialNotification.user_id' => $id));
          if (!empty($this->data['Social'])) {
              foreach ($this->data['Social'] as $typeSocial => $arrSocial)
              {
                  $toSocialTable = array();
                  $i = 0;
                  if (is_array($arrSocial)) {
                      foreach ($arrSocial as $socItem)
                      {
                          $toSocialTable[$i]['user_id'] = $id;
                          $toSocialTable[$i]['social_type'] = $typeSocial;
                          $toSocialTable[$i]['type'] = $socItem;
                          $i++;
                      }
                      $this->SocialNotification->saveAll($toSocialTable);
                  }
              }

          }
        }

        # Password changes only by forgot password form.
        #if (($user['password'] == $this->Auth->password('')) && empty($user['cpassword'])) {
        #  # because they're blank, but validation says they're still required
        unset($this->User->validate['password']);
        unset($this->User->validate['cpassword']);

        # don't clobber the password in the db
        unset($this->data['User']['password']);
        unset($this->data['User']['cpassword']);
        #}

        $userPrivateInfo = $this->data['UserPrivateInfo'];
        if (!empty($userPrivateInfo['birthday'])) {
          $this->data['UserPrivateInfo']['birthday'] = date('Y-m-d', strtotime($userPrivateInfo['birthday']));
        }

        $userFinanceInfo = $this->data['UserFinanceInfo'];
        if (isset($userFinanceInfo['accounts']) && is_array($userFinanceInfo['accounts'])) {
          $this->data['UserFinanceInfo']['accounts'] = implode(', ', $userFinanceInfo['accounts']);
        }

        $this->User->bindModel(array(
          'hasOne' => array(
            'UserPrivacy' 
          )
        ), false);

        $this->User->UserInterest->deleteAll(array('UserInterest.user_id' => $id));
        $this->User->UserType->deleteAll(array('UserType.user_id' => $id));

        # allow undo custom url
        if (empty($this->data['User']['login'])) {
          unset($this->User->validate['login']);
          $this->data['User']['login'] = null;
        } else {
          $this->data['User']['login'] = strtolower($this->data['User']['login']);
        }

        if ($this->User->saveAll($this->data)) {
          $out['status'] = (isset($out['status']) ? $out['status'] : true) && true;
        } else {
          $out['status'] = false;
          $out['errors'] = isset($out['errors']) ?
            array_merge($out['errors'], $this->User->validationErrors) :
            $this->User->validationErrors;
        }
      } else {
        $out['status'] = false;
      }
      exit(json_encode($out));
    }

    public function edit_photo()
    {

        $this->layout = 'community';

        $photo = $this->UserInfo->find('first', array(
            'conditions' => array('UserInfo.user_id' => $this->myId),
            'fields' => array('UserInfo.photo', 'UserInfo.avatar')
        ));
        $photo = $photo['UserInfo'];



        $this->set('photo', $photo);

    }

    function uploadPhotoAjax(){

        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $file = $_FILES['file'];
        $file_name = 'photo_' . uniqid() . '.jpg';
        $dirToPhotos = DS . 'uploads' . DS . 'userfiles' . DS . 'user_' . $this->myId;
        if (!is_dir(rtrim(WWW_ROOT, DS) . $dirToPhotos)) {
            mkdir(rtrim(WWW_ROOT, DS) . $dirToPhotos, 0777, true);
        }
        $pathToFile = $dirToPhotos . DS . $file_name;
        if(move_uploaded_file($file['tmp_name'], rtrim(WWW_ROOT, DS) . $pathToFile)) {
            if ($userInfoID = $this->UserInfo->getIdByUser($this->myId)) {
                $this->UserInfo->id = $userInfoID;
                $data['UserInfo']['photo'] = $pathToFile;
                $data['UserInfo']['avatar'] = '';
                if (!$this->UserInfo->save($data)) {
                    $err_desc .= 'An error occurred when saving photo!';
                }
            } else {
                $err_desc .= 'An error occurred with user ID!';
            }
        } else {
            $err_desc .= 'An error occurred with file!';
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

    public function cropPhoto()
    {

        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        if (isset($this->data['crop'])) {
            $crop = $this->Image->cropPhoto($this->data, $this->myId);
            $err_desc = (isset($crop['err_desc'])) ? $crop['err_desc'] : '';
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

    public function forgot_pass($hash = null)
    {
        $this->layout = 'default_static';
        $this->User->Behaviors->detach('Privatizable');

        # TODO: refactor this - use "short circuiting" to reduce if/else nesting
        if (empty($hash)) {
            if (!empty($this->data)) {
                // password reset request data

                if (!empty($this->data['User']['pass']['hash'])) {
                    # recovery hash is defined
                    if ((!empty($this->data['User']['pass']['new_pass'])) && (!empty($this->data['User']['pass']['confirm_pass']))) {
                        # passwords aren't blank
                        if ($this->data['User']['pass']['new_pass'] == $this->data['User']['pass']['confirm_pass']) {
                            # password match
                            $userData = $this->User->find('first', array(
                                'conditions' => array(
                                    'User.recovery_hash' => $this->data['User']['pass']['hash']
                                ),
                                'fields' => array(
                                    'User.id', 'User.email', 'UserInfo.first_name', 'UserInfo.last_name'
                                )
                            ));

                            if ($userData) {
                                # reset password
                                $this->User->id = $userData['User']['id'];
                                $this->User->saveField('recovery_hash', '');
                                $this->User->saveField('password', $this->Auth->password($this->data['User']['pass']['new_pass']));

                                $this->set('msg', 'New password has been saved!');
                                return;
                            } else {
                                $this->set('msg', "We Couldn't Find Your Account");
                                return;
                            }
                        } else {
                            $this->set('msg', 'Passwords do not match');
                            return;
                        }
                    } else {
                        $this->set('msg', 'Fill all fields, please');
                        return;
                    }
                }

                $userData = $this->User->find('first', array(
                    'contain' => array('UserInfo'),
                    'conditions' => array(
                        'User.email' => $this->data['User']['pass']['email']
                    ),
                    'fields' => array(
                        'User.id', 'User.email', 'UserInfo.first_name', 'UserInfo.last_name'
                    )
                ));

                if ($userData) {
                    # generate hash
                    $userData['User']['recovery_hash'] = md5(uniqid(env('SERVER_NAME'), true));

                    $this->User->id = $userData['User']['id'];
                    $this->User->saveField('recovery_hash', $userData['User']['recovery_hash']);

                    $this->_sendConfirmPasswordEmail($userData);

                    $this->set('msg', 'Thank you, please check your email!');
                } else {
                    // unknown email
                    $this->set('msg', "We Couldn't Find Your Account");
                    return;
                }
            } else {
                $this->set('asd', 'asd');
                # just render the empty form
            }
        } else {
            // change password form
            $userData = $this->User->find('first', array(
                'conditions' => array(
                    'User.recovery_hash' => $hash
                ),
                'fields' => array(
                    'User.id', 'User.email', 'UserInfo.first_name', 'UserInfo.last_name'
                )
            ));

            if ($userData) {
                $this->set('hash', $hash);
            } else {
                $this->set('msg', "User with that hash not found!");
            }
        }
    }

    /**
     * @param $fromID
     * @param $toEmail
     * @return bool
     */
    private function _sendInviteEmail($fromID, $toEmail)
    {
        $this->set('server', 'http://'.$_SERVER['SERVER_NAME']);
        $data = $this->UserInfo->find('first', array('conditions' => array('UserInfo.user_id' => $fromID), 'fields' => array('UserInfo.username')));

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $this->SwiftMailer->to = $toEmail;
        $this->SwiftMailer->sendAs = 'html';

        try {
          $this->SwiftMailer->send('invite', $data['UserInfo']['username'] . ' ' . __('invites you to join', true));
          return true;
        } catch (Exception $e) {
          $this->log($e->getMessage());
          $this->log($e->getTraceAsString());
          return false;
        }
    }

    private function _sendConfirmPasswordEmail($userData)
    {
        $this->Email->smtpOptions = array(
            'port'=> '465',
            'timeout'=> '30',
            'host' => 'ssl://smtp.gmail.com',
            'username'=> 'vivarena.site@gmail.com',
            'password'=> 'vivaQaz987'
        );
        $this->Email->delivery = 'smtp';

        $this->Email->to = $userData['User']['email'];
        $this->Email->from = 'noreply@' . $_SERVER['SERVER_NAME'];
        $this->Email->subject = 'Password recovery request';
        $this->Email->template = 'password_request';
        $this->Email->layout = 'default';
        $this->Email->sendAs = 'html';

        $this->set('data', $userData);
        $this->set('server_name', env('SERVER_NAME'));
        $this->set('server', 'http://'.$_SERVER['SERVER_NAME']);

        try {
          $this->Email->send();
          return true;
        } catch (Exception $e) {
          $this->log($e->getMessage());
          $this->log($e->getTraceAsString());
          return false;
        } 
    }

    private function _sendResetPasswordSuccessEmail($userData)
    {
        $this->SwiftMailer->to = $userData['User']['email'];

        $this->set('data', $userData);
        $this->set('server_name', env('SERVER_NAME'));
        $this->set('server', 'http://'.$_SERVER['SERVER_NAME']);

        try {
          $this->SwiftMailer->send('password_reset_success', 'Vivarena - New Password!');
          return true;
        } catch (Exception $e) {
          $this->log($e->getMessage());
          $this->log($e->getTraceAsString());
          return false;
        }  
    }

    private function _generatePassword($length = 8)
    {
        $password = '';
        $possibleChars = '0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ';
        $len = strlen($possibleChars);
        while(strlen($password) < $length)
        {
            $char = substr($possibleChars, mt_rand(0, $len - 1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
            }
        }

        return $password;
    }

    private function _sendRegistrationEmail($userData)
    {
        $this->SwiftMailer->to = $userData['User']['email'];

        $this->set('data', $userData);
        $this->set('server_name', env('SERVER_NAME'));
        $this->set('server', 'http://'.$_SERVER['SERVER_NAME']);

        try {
          $this->SwiftMailer->send('registration', __('Welcome to Vivarena.com!', true));
          return true;
        } catch (Exception $e) {
          $this->log($e->getMessage());
          $this->log($e->getTraceAsString());
          return false;
        }
    }

    private function _sendWelcomeMessage($id, $data)
    {
        $this->loadModel('Message');
        $subject = 'Congratulations and Welcome to Vivarena.com!';
        $content = file_get_contents(APP . 'views/elements/email/html/welcome.htm');

        $this->Message->set(array(
            'to_id' => $id,
            'from_id' => 1, // from /Concierge
            'subject' => $subject,
            'content' => $content,
            'status' => false
        ));

        if ($this->Message->saveAll()) {
            $this->Email->to = $data['User']['email'];
            $this->Email->from = 'noreply@' . $_SERVER['SERVER_NAME'];
            $this->Email->subject = $subject;
            $this->Email->template = 'message';
            $this->Email->layout = 'default';
            $this->Email->sendAs = 'html';

            $this->set('server', 'http://' . $_SERVER['SERVER_NAME']);
            $this->set('sender', 'Vivarena.com');
            $this->set('subject', $subject);
            $this->set('message', $content);

            $this->Email->smtpOptions = array(
                'port'=> '465',
                'timeout'=> '30',
                'host' => 'ssl://smtp.gmail.com',
                'username'=> 'vivarena.site@gmail.com',
                'password'=> 'vivaQaz987'
            );
            $this->Email->delivery = 'smtp';

            $this->Email->send();

        } else {
            $this->log('error saving message');
            $this->log($this->Message->validationErrors);
        }
    }

    public function available() {
      Configure::write('debug', 0);
      $this->autoRender = false;

      $this->User->unbindModel(
        array('belongsTo' => array('Group'))
      );
      $this->User->unbindModel(
        array('hasOne' => array('UserInfo', 'UserPrivateInfo', 'UserFinanceInfo'))
      );
      $this->User->set(array('login' => strtolower($this->params['slug'])));
      $status = $this->User->validates(array('fieldList' => array('login')));
      if ($status) {
        $error = null;
      } else {
        $error = $this->User->validationErrors;
      }
      $result = array(
        'status' => $status,
        'error' => __($error['login'], true),
      );
      exit(json_encode($result));
    }
}
