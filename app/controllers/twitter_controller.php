<?php
/**
 *
 * @property Twitter $Twitter;
 *
 * @property SessionComponent $Session
 *
 */
class TwitterController extends AppController {

  public $name = 'Twitter';
  public $uses = array('User', 'Twitter');

  /**
   * Redirect to Twitter to authorize the app.
   */
  public function index() {
    Configure::write('debug', 0);
    $this->autoRender = false;

    # get request tokens
    $response = $this->Twitter->authorize();
    $this->Session->write('twitter.oauth_token', $response['oauth_token']);
    $this->Session->write('twitter.oauth_token_secret', $response['oauth_token_secret']);

    $this->redirect($response['redirect']);
  }

  /**
   * oauth_callback
   */
  public function callback() {
    Configure::write('debug', 0);
    $this->autoRender = false;

    $urlParams = $this->params['url'];
    #$this->log($urlParams);
    #     [ext] => html
    #     [url] => twitter/callback
    #     [oauth_token] => ...
    #     [oauth_verifier] => ...

    $oauth_token = $this->Session->read('twitter.oauth_token');
    if ($oauth_token != $urlParams['oauth_token']) {
      $this->log('Invalid oauth_token in callback.');
      $this->render('/elements/close-popup');
      return;
    }
    $oauth_token_secret = $this->Session->read('twitter.oauth_token_secret');

    # get access tokens
    $response = $this->Twitter->authenticate($urlParams['oauth_verifier'], $oauth_token, $oauth_token_secret);
    $this->Session->write('twitter.oauth_token', $response['oauth_token']);
    $this->Session->write('twitter.oauth_token_secret', $response['oauth_token_secret']);

    $twitterUser = $this->Twitter->user($response['oauth_token'], $response['oauth_token_secret']);

    if (!empty($twitterUser)) {
      $this->User->recursive = -1;
      $this->User->Behaviors->detach('Privatizable');
      $user = $this->User->findByTwitterId($twitterUser->id);
      if ($user) {
        $this->_login($user);
        $this->render('/elements/close-popup');
        return;
      } else {
        $parseInfo = $this->Twitter->parseTwitterUser($twitterUser);
        $this->Session->write('twitter.user', $parseInfo);
        $this->render('/elements/close-popup');
        return;
      }
    } else {
      # Twitter API fail? Just close and refresh.
      $this->render('/elements/close-popup');
      return;
    }
  }
}
