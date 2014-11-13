<?php
/**
 * Recommendations Controller
 */
class RecController extends AppController {

  var $name = 'Rec';
  var $uses = array('User', 'UserFriend');
  var $components = array('Session');

  public function beforeFilter() {
    parent::beforeFilter();
    $this->User->Behaviors->detach('Privatizable');
  }

  public function friends() {
    if ($this->RequestHandler->isAjax()) {
      # me
      $user = $this->Auth->user();
      $user = $user['User'];

      # my friends
      $friendIds = $this->UserFriend->find('all', array(
        'contain' => array(),
        'conditions' => array(
          'user_id' => $user['id']
        ),
        'fields' => array('friend_id')
      ));
      $friendIds = Set::extract('./UserFriend/friend_id', $friendIds);

      # my unapproved requests
      $requestIds = $this->UserFriend->find('all', array(
        'contain' => array(),
        'conditions' => array(
          'friend_id' => $user['id'],
          'approved' => false
        ),
        'fields' => array('user_id')
      ));
      $requestIds = Set::extract('./UserFriend/user_id', $requestIds);

      # exclude me, my friends, and my requests from the results
      $excludes = array_merge($friendIds, $requestIds, array($user['id']));

      # the results
      $friendRecs = array();
      $maxRecs = 10;

      # get facebook recs
      $friendRecs = array_merge($friendRecs, $this->facebookFriends($user, $maxRecs, $excludes));

      # if I need more, get linkedin recs
      $numRecs = count($friendRecs);
      if ($numRecs < $maxRecs) {
        # exclude previously rec'd
        $excludes = array_merge($excludes, Set::extract('./User/id', $friendRecs));
        $friendRecs = array_merge($friendRecs, $this->linkedInFriends($user, ($maxRecs - $numRecs), $excludes));
      }

      # if I need more, get friend-of-a-friend recs
      $numRecs = count($friendRecs);
      if ($numRecs < $maxRecs) {
        $excludes = array_merge($excludes, Set::extract('./User/id', $friendRecs));
        $friendRecs = array_merge($friendRecs, $this->foafs($user, ($maxRecs - $numRecs), $excludes));
      }

      # temporarily enable this to hide bugs that introduce duplicates
      #$friendRecs = $this->dedup($friendRecs);

      $this->set('recs', $friendRecs);
      $this->render('/elements/rec/friends');
    } else {
      exit(null);
    }
  }

  private function facebookFriends($user, $limit, $excludes) {
    if ($this->Session->check('facebook.token') && $this->Session->read('facebook.token')) {
      # get Facebook friend facebook_ids
      $this->loadModel('Facebook');
      $fbFriends = $this->Facebook->friends($user['facebook_id'], $this->Session->read('facebook.token'));
      $fbFriendIds = Set::extract('/data/./id', Set::reverse($fbFriends));

      if (count($fbFriendIds) > 0) {
        if (count($excludes) == 1) {
          $excludesSql = array('User.id !=' => $excludes[0]);
        } else {
          $excludesSql = array('User.id NOT' => $excludes);
        }

        $fbFriendRecs = $this->User->find('all', array(
          'contain' => array(
            # CakePHP bug: virtual field doesn't work with User fields specified
            'UserInfo' => array('fields' => array(/*'UserInfo.username',*/ 'UserInfo.first_name', 'UserInfo.last_name', 'UserInfo.avatar', 'UserInfo.photo')),
            'UserPrivateInfo' => array('fields' => array('position')),
          ),
          'fields' => array('id', 'login'),
          'conditions' => array(
            'User.facebook_id' => $fbFriendIds,
            $excludesSql
          ),
          'limit' => $limit,
        ));
        return $fbFriendRecs;
      }
    }
    return array();
  }

  private function foafs($user, $limit, $excludes) {
    # bugfix: sql syntax error for a user with no friends or requests "u2.id NOT IN (1)"
    if (count($excludes) == 1) {
      $excludesSql = array('u2.id !=' => $excludes[0]);
    } else {
      $excludesSql = array('u2.id NOT' => $excludes);
    }

    $foafs = $this->User->find('all', array(
      'alias' => 'u1',
      'contain' => array(),
      'joins' => array(
        # my friends: u1.id => uf1.user_id
        array(
          'table' => 'bs_user_friends',
          'alias' => 'uf1',
          'type' => 'INNER',
          'conditions' => array(
            'uf1.user_id = ' . $user['id'],
            'uf1.approved' => true,
          )
        ),
        # my friends friends: uf1.friend_id = uf2.user_id
        array(
          'table' => 'bs_user_friends',
          'alias' => 'uf2',
          'type' => 'INNER',
          'conditions' => array(
            'uf2.user_id = uf1.friend_id',
            'uf2.approved' => true,
          ),
        ),
        # my friends-friends' info: uf2.friend_id = u2.id
        array(
          'table' => 'bs_users',
          'alias' => 'u2',
          'type' => 'INNER',
          'conditions' => array(
            'uf2.friend_id = u2.id',
            $excludesSql,
            'u2.group_id' => 2,
          ),
        ),
        # friends-friends' user-info: u2.id = ui.user_id
        array(
          'table' => 'bs_user_infos',
          'alias' => 'ui',
          'type' => 'INNER',
          'conditions' => array(
            'u2.id = ui.user_id'
          ),
        ),
        # friends-friends' user-private-info: u2.id = upi.user_id
        array(
          'table' => 'bs_user_private_infos',
          'alias' => 'upi',
          'type' => 'INNER',
          'conditions' => array(
            'u2.id = upi.user_id'
          ),
        ),
      ),
      'fields' => array('DISTINCT u2.id', 'u2.login', 'ui.first_name', 'ui.last_name', 'ui.avatar', 'ui.photo', 'upi.position'),
      'conditions' => array(
        'User.id' => $user['id'],
      ),
      'limit' => $limit,
    ));

    # normalize the result
    foreach ($foafs as &$foaf) {
      $foaf['User'] = $foaf['u2'];
      unset($foaf['u2']);
      $foaf['UserInfo'] = $foaf['ui'];
      unset($foaf['ui']);
      $foaf['UserPrivateInfo'] = $foaf['upi'];
      unset($foaf['upi']);
    }

    return $foafs;
  }

  private function linkedInFriends($user, $limit, $excludes) {
    if ($this->Session->check('linkedin.oauth_access_token') && $this->Session->check('linkedin.oauth_token_secret') 
      && $this->Session->read('linkedin.oauth_access_token') && $this->Session->read('linkedin.oauth_token_secret')) {

      # get LinkedIn friend ids
      $this->loadModel('LinkedIn');
      $liFriends = $this->LinkedIn->friends($this->Session->read('linkedin.oauth_access_token'), $this->Session->read('linkedin.oauth_token_secret'));
      $liFriendIds = Set::extract('/values/./id', Set::reverse($liFriends));
      # filter out 'private' ids
      $liFriendIds = array_filter($liFriendIds, function($liFriendId) { return $liFriendId != 'private'; });

      if (count($liFriendIds) > 0) {
        if (count($excludes) == 1) {
          $excludesSql = array('User.id !=' => $excludes[0]);
        } else {
          $excludesSql = array('User.id NOT' => $excludes);
        }

        $liFriendRecs = $this->User->find('all', array(
          'contain' => array(
            # CakePHP bug: virtual field doesn't work with User fields specified
            'UserInfo' => array('fields' => array(/*'UserInfo.username',*/ 'UserInfo.first_name', 'UserInfo.last_name', 'UserInfo.avatar', 'UserInfo.photo')),
            'UserPrivateInfo' => array('fields' => array('position')),
          ),
          'fields' => array('id', 'login'),
          'conditions' => array(
            'User.linkedin_id' => $liFriendIds, 
            $excludesSql
          ),
          'limit' => $limit,
        ));
        return $liFriendRecs;
      }
    }
    return array();
  }

  /**
   * Dedup an array of User records.
   */
  private function dedup($userRecs) {
    $uniqIds = array_unique(Set::extract('./User/id', $userRecs));
    $dedups = array();
    foreach ($uniqIds as $id) {
      $rec = Set::extract("./User[id=$id]/..", $userRecs);
      $dedups[] = $rec[0];
    }
    return $dedups;
  }
}
?>
