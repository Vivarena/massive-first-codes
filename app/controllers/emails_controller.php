<?php

/**
 * @property Message $Message
 * @property MessengerComponent $Messenger
 * @property EmailComponent $Email
 * @property User $User
 * @property UserInfo $UserInfo
 *
 * @property RequestHandlerComponent $RequestHandler
 */

App::import('Sanitize');
class EmailsController extends AppController {

    public $name = "Emails";
    public $components = array('SwiftMailer');
    public $uses = array('Message');
    public $helpers = array("Text");

    public $myId;

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->myId = $this->Session->read('Auth.User.id');

        $this->set('unread', $this->Message->getUnreadMessages($this->myId));
    }

    function  beforeRender() {
      parent::beforeRender();
      $this->loadModel('UserInfo');
      if (!$this->RequestHandler->isAjax() ) {
        $userData = $this->UserInfo->getBasicInfo($this->myId);
        $this->data = array_merge((array)$this->data, $userData);
      }
    }

    public function index()
    {
        $this->inbox();
    }

    function inbox() {
        $this->layout = 'community';
        if ($this->RequestHandler->isAjax()) {
            $this->layout = false;
        }
        $this->paginate['Message'] = array(
            'contain' => array('From', 'From.UserInfo'),
            'conditions' => array(
                'Message.to_id' => $this->myId,
                'Message.to_deleted' => 0
            ),
            'order' => array('Message.modified' => 'DESC'),
            'limit' => 20,
            'recursive' => 2
        );

        $messages = $this->paginate('Message');

        if ($this->Session->check('ShareThis')) {
            $this->set('shareThis', true);
        }

        //$this->set( 'unread', $this->Message->getUnreadMessages($this->myId) );
        $this->set('messages', $messages);
        $this->set('activeSection', array('inbox' => 'activeInbox'));
    }

    function outbox() {
        $this->layout = 'community';
        $this->paginate['Message'] = array(
            'contain' => array('To', 'To.UserInfo'),
            'conditions' => array(
                'Message.from_id' => $this->myId,
                'Message.from_deleted' => 0,
                'Message.type !=' => 'system'
            ),
            'order' => array('Message.created' => 'DESC'),
            'limit' => 20,
            'recursive' => 2
        );
        $messages = $this->paginate('Message');
        $this->set( 'unread', $this->Message->getUnreadMessages($this->myId) );
        $this->set('messages', $messages);
        $this->set('outbox', true);
        $this->set('activeSection', array('sent' => 'activeSent'));
        $this->render('inbox');
    }

    function junk() {
        Configure::write('debug', 0);
        if ($this->RequestHandler->isAjax()) {
            $this->layout = false;
            $this->autoRender = false;
            $this->paginate['Message'] = array(
                'contain' => array('To.UserInfo.username', 'From.UserInfo.username'),
                'conditions' => $this->Message->getTrashConditions($this->myId),
                'order' => array('Message.created' => 'DESC'),
                'limit' => 20,
                'recursive' => 2
            );
            $this->data = $this->paginate('Message');

            $this->render('/elements/messaging/messages');
        }
    }

    function ajaxSendMessage() {
      /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      Configure::write('debug', 0);
      $this->layout = 'ajax';
      if (isset($this->params['form'])) {
        $data = $this->params['form'];

        $content = '';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $message = Sanitize::html($data['message'], array('remove' => true));
        $date = date('M. jS, g:iA');
        if ($data['content']) {
          # Add reply to the top of the thread.
          $content = <<<CONTENT
{$message}
{$date} from "{$data['to']}"
\r\n
CONTENT;
        } else {
          $content = $data['message'];
        }

        #$fromID = $this->myId;

        # no forwarding in this design yet
        #if (isset($this->data['Message']['forward'])) {
        #  $this->data['Message']['forward'][] = $this->data['Message']['to_id'];
        #  foreach ($this->data['Message']['forward'] as $forward)
        #  {
        #    $data[]['Message'] = array(
        #      'to_id' => $forward,
        #      'from_id' => $fromID,
        #      'subject' => $this->data['Message']['subject'],
        #      'content' => $this->data['Message']['content']
        #    );
        #  }
        #  unset($this->data['Message']);
        #  $this->Message->set($data);
        #} else {

        /*$this->Message->set(array(
          'to_id' => $data['toId'],
          'from_id' => $this->myId,
          'subject' => $data['subject'],
          'content' => $content,
          'status' => false
        ));*/
        # this is used by sendNotificationEmail... ugh
        $this->data = $this->Message->data;

        #}

          $msgRes = $this->Messenger->To($data['toId'])
              ->Subject($data['subject'])
              ->Content($content)
              ->Save();

        if(/*$this->Message->saveAll()*/$msgRes) {
          if (isset($data['replyTo'])) {
            // TODO: this value could be useful for tracking message threads
            $this->Message->touch($data['replyTo']);
          }
          $d['to_id'] = $data['told'];
            $this->NotifyUser(array('user_id' => $data['toId'], 'message' => $content, 'subject'=> $data['subject']));
//          $this->_sendNotificationEmail($d);
          exit(json_encode(array('complete' => 1)));
        } else {
          #exit(json_encode(array('errors' => $this->Message->validationErrors)));
          return false;
        }
      }
      exit();
    }

    function ajaxSendMessageTo()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);

        $this->layout = 'ajax';
        $data = array();
        if($this->data) {
            $fromID = $this->Auth->user('id');

            if(in_array('all', $this->data['Message']['writeTo'])) {
                $this->loadModel('UserFriend');
                $all_friends_id = $this->UserFriend->getFriendsId($fromID);
                $this->data['Message']['writeTo'] = $all_friends_id;
            }

            if (isset($this->data['Message']['writeTo'])) {
                foreach ($this->data['Message']['writeTo'] as $key => $writeTo)
                {
                    $data[]['Message'] = array(
                        'to_id' => $writeTo,
                        'from_id' => $fromID,
                        'subject' => $this->data['Message']['subject'],
                        'content' => $this->data['Message']['content']
                    );
                }
                $this->Message->set($data);
                if($this->Message->saveAll()) {
                    $tmp_arr = array(
                        'message' => $this->data['Message']['content'],
                        'subject'=> $this->data['Message']['subject']
                    );
                    if(isset($data['toId'])) {
                        $tmp_arr['user_id'] = $data['toId'];
                        $this->NotifyUser($tmp_arr);
                    }
                    if(isset($data[0])) {
                        foreach ($data as $node) {
                            $tmp_arr['user_id'] = $node['Message']['to_id'];
                            $this->NotifyUser($tmp_arr);
                        }
                    }
                  //$this->_sendNotificationEmail($data);
                  unset($this->data['Message']);
                  exit(json_encode(array('complete' => 1)));
                } else {
                    exit(json_encode(array('errors' => $this->Message->validationErrors[0])));
                }
            } else {
                exit(json_encode(array('errors' => array('Send to' => 'This field cannot be left blank'))));
            }

        }
        exit();
    }


    function view($msgId = null)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        //Configure::write('debug', 0);
            $this->layout = 'community';

            $this->Message->contain(array('To.UserInfo', 'From.UserInfo' => array('fields' => array('username', 'avatar', 'photo') )));
            $messageInfo = $this->Message->find('first', array(
                'conditions' => array('Message.id' => $msgId, 'Message.to_id' => $this->Auth->user('id'))
            ));
            if ($messageInfo) {

                $allMsgID = $this->Message->find('all', array(
                    'order' => 'Message.created DESC',
                    'fields' => array('Message.id'),
                    'conditions' => array('Message.to_id' => $this->myId)
                ));

                $allMsgID = Set::extract('/Message/id/.', $allMsgID);

                $prevNext = array();
                $i = 0;
                foreach ($allMsgID as $oneMsg)
                {
                    if ($oneMsg == $msgId) {
                        $prevNext['prev']['Message']['id'] = (isset($allMsgID[$i-1])) ? $allMsgID[$i-1] : null;
                        $prevNext['next']['Message']['id'] = (isset($allMsgID[$i+1])) ? $allMsgID[$i+1] : null;
                    }
                    $i++;
                }


                $this->_setMessageAsReaded($messageInfo['Message']['id']);

                if ($messageInfo['Message']['from_id'] == $this->myId && $messageInfo['Message']['from_destroyed'] == 1) {
                    $this->redirect(array('action' => 'outbox'));
                } elseif ($messageInfo['Message']['to_id'] == $this->myId && $messageInfo['Message']['to_destroyed'] == 1) {
                    $this->redirect(array('action' => 'inbox'));
                }
                $neighborsConditions = array();
                if ($messageInfo['Message']['from_id'] == $this->myId) {
                    $messageInfo['User'] = $messageInfo['To'];

                    $neighborsConditions = array(
                        'Message.from_id' => $messageInfo['Message']['from_id'],
                    );

                    if ($messageInfo['Message']['from_deleted'] == 1) {
                        $neighborsConditions['Message.from_destroyed'] = 0;

                        $this->set('deleted', true);
                    } else {
                        $neighborsConditions['Message.from_deleted'] = 0;
                    }
                } elseif ($messageInfo['Message']['to_id'] == $this->myId) {
                    $messageInfo['User'] = $messageInfo['From'];

                    $neighborsConditions = array(
                        'Message.to_id' => $messageInfo['Message']['to_id'],
                    );

                    if ($messageInfo['Message']['to_deleted'] == 1) {
                        $neighborsConditions['Message.to_destroyed'] = 0;

                        $this->set('deleted', true);
                    } else {
                        $neighborsConditions['Message.to_deleted'] = 0;
                    }
                } else {
                    $this->redirect(array('action' => 'inbox'));
                }
                $this->loadModel('UserInfo');
                $userPhoto = $this->UserInfo->getBasicInfo($messageInfo['User']['id']);
                $this->set('userPhoto', $userPhoto);
//                $this->set(compact('messageInfo'));
                $this->data = $messageInfo;


                $this->Message->contain();
                $this->data['Message'] = array_merge($this->data['Message'],
                    $this->Message->find('neighbors', array(
                        'fields' => array('id', 'created'),
                        'field' => 'created',
                        'value' => $messageInfo['Message']['created'],
                        'conditions' => $neighborsConditions
                )));

                $this->data['Message'] = array_merge($this->data['Message'], $prevNext);
            } else {
                $this->redirect(array('action' => 'inbox'));
            }
            //$this->render('/elements/messaging/message');
        //}
    }

    public function ajaxDeleteMessage($id=null, $type=null) {
      Configure::write('debug', 0);
      #if ( !empty($this->data) ) {
      #    exit( json_encode($this->_deleteMessages()) );
      #} elseif (!empty($id)) {
      if (!empty($id)) {
        exit( json_encode($this->_deleteMessage($id, $type)) );
      }
      return false;
    }

    public function ajaxDeleteMessages() {
      Configure::write('debug', 0);
      $ids = $this->params['pass'];
      if (!empty($ids)) {
        exit( json_encode($this->_deleteMessage($ids, null)) );
      }
      return false;
    }

    private function _deleteMessage($id, $type) {
      #if($this->data['id'] != $this->myId) {

      # $id can be 1 id or an array of ids 
      $result = $this->Message->updateAll(
        array('to_deleted' => 1),
        array(
          'Message.id' => $id,
          'Message.to_id' => $this->myId
        )
      );
      #} else {
      #        $result = $this->Message->updateAll(
      #        array('from_deleted' => 1),
      #        array(
      #            'Message.id' => $id,
      #            'Message.from_id' => $this->myId
      #        )
      #    );
      #}

      if ($result) {
        return "ok";
      }
      return false;
    }

    private function _deleteMessages(){
        $data = $this->data;
        $out = array();
        foreach ($data as $msgData) {
            if ( isset($msgData['Message']['i   d']) ) {
                $fieldName = ($msgData['Message']['from_id'] == $this->myId) ?
                    'from_deleted' : 'to_deleted';
                $fieldName = $msgData['Message']['to_deleted'] ? 'to_destroyed' : $fieldName;
                $this->Message->id = $msgData['Message']['id'];
                if ( $this->Message->saveField($fieldName, 1) ) {
                    $out = array_merge($out, array('status'=> true));
                } else {
                    $out = array_merge($out, array(
                        'status' =>  false,
                        'errors' => array_merge_recursive((array)$out['errors'], $this->Message->validationErrors)
                    ));
                }
            }
            continue;
        }
        return $out;
    }

    function destroy($id=null) {
        $baseCondition = array();
        if(!empty($this->params['form']['ids'])) {
            $ids = $this->params['form']['ids'];
            $ids = explode(",", $ids);
            $baseCondition = array('Message.id' => $ids);
        } elseif(!empty($id)) {
            $baseCondition = array('Message.id' => $id);
        } elseif($this->RequestHandler->isAjax()) {
            $baseCondition = array();
        } else {
            $this->redirect(array('action' => 'trash'));
        }

        $this->Message->updateAll(
            array('to_destroyed' => 1),
            array_merge($baseCondition, array(
                'Message.to_id' => $this->Auth->user('id'),
                'Message.to_deleted' => 1,
            ))
        );

        $this->Message->updateAll(
            array('from_destroyed' => 1),
            array_merge($baseCondition, array(
                'Message.from_id' => $this->Auth->user('id'),
                'Message.from_deleted' => 1,
            ))
        );

        if(!$this->RequestHandler->isAjax()) {
            $this->redirect(array('action' => 'trash'));
        } else {
            exit("okey");
        }
    }

    private function _setMessageAsReaded($messageId) {
        $result = $this->Message->updateAll(
            array('status' => 1),
            array(
                'Message.id' => $messageId,
            )
        );
        return $result;
    }

    public function write()
    {
        $this->layout = 'community';
      if (isset($this->params['pass'][0])) {
        $userId = $this->params['pass'][0];
        $this->loadModel('UserInfo');
        $this->loadModel('User');
        //$this->data = $this->UserInfo->findByUserId($userId, array('user_id', 'username', 'avatar', 'photo', 'sex') );
        $this->data = $this->User->find('first',array(
            'conditions' => array('User.id' => $userId),
            'contain' => array(
                'UserInfo' => array(
                        'fields' => array('UserInfo.user_id', 'UserInfo.avatar', 'UserInfo.photo', 'UserInfo.sex', 'UserInfo.first_name', 'UserInfo.last_name')
                    )
                ),
            'fields' => array('User.login', 'User.id')
        ));
      } else {
        $this->loadModel('UserFriend');
        $friends = $this->UserFriend->find('all',array(
          'contain' => array(
            'Friend' => array(
              'fields' => array('id', 'login'), # can't just get id here
              'UserInfo' => array(
                'fields' => array('username', 'avatar'),
              ),
            ),
          ),
          'conditions' => array(
            'UserFriend.user_id' => $this->myId,
            'UserFriend.approved' => 1
          ),
        ));
        $friends = Set::extract('/Friend/.', $friends);
        $friends = Set::sort($friends, '{n}.UserInfo.username', 'asc');
        $this->set('friends', $friends);
      }

      if ($this->RequestHandler->isAjax()) {
        Configure::write('debug', 0);
        $this->layout = false;
        $this->autoRender = false;
      } else {
        $this->layout = 'community';
      }

    if ($this->Session->check('ShareThis')) {
        $this->set('shareThisURL', $this->Session->read('ShareThis.location'));
        $this->set('shareType', $this->Session->read('ShareThis.shareType'));
        $this->Session->delete('ShareThis');
    }

      $this->render('/elements/messaging/new');
    }

    public function writeTo()
    {
        //Configure::write('debug', 0);
        $this->layout = 'community';

        $uid = $this->Session->read('Auth.User.id');
        $query = array(
            'contain' => true,
            'conditions' => array(
                'UserFriend.user_id' => $uid,
                'UserFriend.approved' => 1
            ),
            'recursive' => 2
        );
        $this->loadModel('UserFriend');
        $data = $this->UserFriend->find('all', $query);
        if ($data) {
            foreach ($data as &$oneUser)
            {
                $oneUser['User'] = $this->_setFriendshipStatus($oneUser['UserFriend']['friend_id']);
                $oneUser['UserInfo'] = $oneUser['Friend']['UserInfo'];
            }
            $this->set('friends', $data);
        }
        if ($this->Session->check('ShareThis')) {
            $this->set('shareThisURL', $this->Session->read('ShareThis.location'));
            $this->Session->delete('ShareThis');
        }

    }

    private function _setFriendshipStatus($friendId)
    {
        $myId = $this->Session->read('Auth.User.id');

        $isMyFriend = $this->UserFriend->find( 'count', array(
            'conditions' => array(
                'user_id' => $myId,
                'friend_id' => $friendId,
            )
        ) );

        $iAmFriend = $this->UserFriend->find( 'count', array(
            'conditions' => array(
                'user_id' => $friendId,
                'friend_id' => $myId,
            )
        ) );

        $result['isMyFriend'] = $isMyFriend;
        $result['iAmFriend'] = $iAmFriend;
        return $result;
    }

    public function countUnread() {
        Configure::write('debug', 0);
        if ($this->RequestHandler->isAjax()) {
            $this->layout = false;
            $this->autoRender = false;

            echo $this->Message->getUnreadMessages($this->myId);

            die;
        }
    }

    /**
     * array(user_id, email, subject, message) needed
     *
     * @param array $data
     */
    private function NotifyUser(array $data) {
        $this->set('server', 'http://'.$_SERVER['SERVER_NAME']);
        $this->set('sender', $this->Session->read('Auth.User.info.username'));
        $this->set('subject', $data['subject']);
        $this->set('message', $data['message']);
        $this->loadModel('User');
        if(!is_array($data['user_id'])) {
            $user = $this->User->find('first', array(
                'fields' => array('User.id', 'User.email'),
                'conditions' => array('User.id' => $data['user_id']),
                'recursive' => -1
            ));
            $this->SwiftMailer->to = $user['User']['email'];
            $this->SwiftMailer->sendAs = 'html';
            try {
                $this->SwiftMailer->send('message', __('Someone wrote you a message!', true));
            } catch (Exception $e) {
                $this->log($e->getMessage());
                $this->log($e->getTraceAsString());
            }
        }elseif(is_array($data['user_id'])) {
            $user = $this->User->find('first', array(
                'fields' => array('User.id', 'User.email'),
                'conditions' => array('User.id IN' => $data['user_id']),
                'recursive' => -1
            ));
            $this->SwiftMailer->sendAs = 'html';
            foreach ($user['User'] as $node) {
                try {
                    $this->SwiftMailer->to = $node['email'];
                    $this->SwiftMailer->send('message', __('Someone wrote you a message!', true));
                } catch (Exception $e) {
                    $this->log($e->getMessage());
                    $this->log($e->getTraceAsString());
                }
            }
        }
    }

    private function _sendNotificationEmail($forwardData = null)
    {
      $this->set('server', 'http://'.$_SERVER['SERVER_NAME']);

      $this->set('sender', $this->Session->read('Auth.User.info.username'));

      $this->loadModel('User');
      $sendTo = array();
      if (!empty($forwardData)) {
        foreach ($forwardData as $to)
        {
          $sendTo[] = $to['Message']['to_id'];
        }
        $sendTo = $this->User->find('all', array(
          'conditions' => array('User.id' => $sendTo),
          'fields' => array('User.email')
        ));
        $sendTo = Set::extract('/User/email/.', $sendTo);
      } else {
        $this->User->Behaviors->detach('Privatizable');
        $data = $this->User->find('first', array(
          'fields' => array('User.id', 'User.email'),
          'conditions' => array('User.id' => $this->data['Message']['to_id']),
          'recursive' => -1
        ));
        $sendTo = array($data['User']['email']);
      }

      $this->set('subject', $this->data['Message']['subject']);
      $this->set('message', $this->data['Message']['content']);
      /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      $this->SwiftMailer->to = $sendTo;
      $this->SwiftMailer->sendAs = 'html';

      try {
        $this->SwiftMailer->send('message', __('Someone wrote you a message!', true));
      } catch (Exception $e) {
        # ignore email problems because the db message has been created already
        $this->log($e->getMessage());
        $this->log($e->getTraceAsString());
      }
    }

    public function sendInvite($toName = null, $toEmail = null)
    {
        Configure::write('debug', 0);
        $out = array('status' => false);
        $this->layout = false;
        if ($this->RequestHandler->isAjax() ) {
            $this->SwiftMailer->sendAs = 'html';

            foreach ($this->data['contacts'] as $key => $contact) {
                $temp=explode('|',$contact);
                $contacts[$key]['name']=$toName=$temp[0];
                $contacts[$key]['email']=$toEmail=$temp[1];

                $this->SwiftMailer->to = $toEmail;
                $this->set('server',$_SERVER['SERVER_NAME']);
                $subject=$this->Session->read('Auth.User.info.username')." invited you to ".$_SERVER['SERVER_NAME'];
                try {
                  $contacts[$key]['sended']=$this->SwiftMailer->send('invite', $subject);
                } catch (Exception $e) {
                  $this->log($e->getMessage());
                  $this->log($e->getTraceAsString());
                  $contacts[$key]['sended']= false;
                }
            }
            $out = array('status' => true,'contacts' => $contacts );
        }
        exit(json_encode($out));
    }
}

