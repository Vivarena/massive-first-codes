<?php
class FacebookController extends AppController {

  var $name = 'Facebook';
  var $uses = array('User', 'Facebook');

  public function beforeFilter() {
    parent::beforeFilter();
  }

  public function index() {
    $url = $this->params['url'];
    $uid = $url['uid'];
    $access_token = $url['accessToken'];
    $access_token = $this->Facebook->exchangeToken($access_token);
    $this->Session->write('facebook.token', $access_token);

    $this->User->recursive = -1;
    $this->User->Behaviors->detach('Privatizable');
    $user = $this->User->findByFacebookId($uid);
    $fbuser = $this->Facebook->user($uid, $access_token);
    #$this->log($fbuser);
    if ($user) {
      #$this->log($user);

      # don't care about the result, just that we got something instead of an error
      # if you'd like to update the user record based on updated fb data, you could do it here
      if ($fbuser->id) {
        # log the user in
        $this->_login($user);
      } 

      $this->redirect('/profile');
    } else {
      # save the fb user info in the session to prefill the registration
      $this->Session->write('facebook.user', $this->Facebook->parseFacebookUser($fbuser));

      $this->redirect(array('controller' => 'users', 'action' => 'registration'));
    }

  }
}
?>
