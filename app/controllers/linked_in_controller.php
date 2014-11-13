<?php
/**
 *
 * @property LinkedIn $LinkedIn
 * @property SessionComponent $Session
 *
 */
class LinkedInController extends AppController {

  var $name = 'LinkedIn';
  var $uses = array('User', 'LinkedIn');

  # Cookie name contains the LinkedIn App id
  private $_liCookie = 'linkedin_oauth_p9fny1la7ju4';

  public function beforeFilter() {
    parent::beforeFilter();
  }

  public function index() {
    // LinkedIn passes data in a secure cookie (https only)
    if (empty($_COOKIE[$this->_liCookie])) {
      $this->log("No LI cookie.");
      $this->redirect(array('controller' => 'users', 'action' => 'registration'));
      return;
    }

    $credentials = json_decode($_COOKIE[$this->_liCookie]);
    if (!$this->LinkedIn->validateCredentials($credentials)) {
      $this->redirect(array('controller' => 'users', 'action' => 'registration'));
      return;
    }
    $tokens = $this->LinkedIn->exchangeAccessToken($credentials->access_token, true);
    $this->Session->write('linkedin.oauth_access_token', $tokens['oauth_token']);
    $this->Session->write('linkedin.oauth_token_secret', $tokens['oauth_token_secret']);

    $liprofile = $this->LinkedIn->profile($tokens['oauth_token'], $tokens['oauth_token_secret']);

    $profile = $this->LinkedIn->parseLinkedInProfile($liprofile);

    $this->User->recursive = -1;
    $this->User->Behaviors->detach('Privatizable');
    $user = $this->User->findByLinkedinId($liprofile->id);
    if ($user) {
      #$this->log($user);

      # don't care about the result, just that we got something instead of an error
      # if you'd like to update the user record based on updated linkedin data, you could do it here
      if ($liprofile->id) {
        # log the user in
        $this->_login($user);
      } 

      $this->redirect('/profile');
    } else {
      # save the linkedin user info in the session to prefill the registration
      $this->Session->write('linkedin.user', $profile);

      $this->redirect(array('controller' => 'users', 'action' => 'registration'));
    }
  }
}
?>
