<?php

/**
 * @property Group $Group
 * @property User $User
 * @property UserInfo $UserInfo
 * @property UserPrivateInfo $UserPrivateInfo
 * @property UserFinanceInfo $UserFinanceInfo
 */
class AdminUsersController extends AdminAppController
{
	public $name = 'AdminUsers';
    public $uses = array('User');
    public $components = array('RequestHandler');

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_setLeftMenu('community');
		$this->_setHoverFlag('community');
	}

	function index()
	{
		$this->_setHoverFlag('users');
        $this->User->Behaviors->attach('Containable');
		$paginate = array(
            'contain' => array(
                'UserInfo.first_name', 'UserInfo.last_name', 'UserInfo.photo', 'UserInfo.avatar', 'Group.name',
            ),
            /*'conditions' => array('User.group_id <> ' => 1),*/
			'fields'  => array(
				'User.id', 'email', 'group_id', 'created'),
		);
		$this->paginate = array_merge($this->paginate, $paginate);
		$items = $this->paginate('User');
		$this->set('users', $items);
        if ($this->RequestHandler->isAjax() ) {
            exit($this->render('../elements/users/usersAjax'));
        }

	}

    public function all_messages()
    {
        $this->_setHoverFlag('all_msg');

        //$typeMsg = array('1' => 'Inbox', '2' => 'Sent');
        $statusMsg = array('0' => 'Unread', '1' => 'Readed');

        $this->loadModel('Message');

        $this->paginate = array(
            'recursive' => 2
        );

        $messages = $this->paginate('Message');

        $this->set(compact('statusMsg', 'messages'));

        if ($this->RequestHandler->isAjax() ) {
            exit($this->render('../elements/messages/ajaxListMsg'));
        }


    }

    public function view($id)
    {

        $this->loadModel('Country');
        $countries = $this->Country->find('list', array(
            'order' => 'name'
        ));

        $this->loadModel('Group');
        $groups = $this->Group->find('all');
        $this->set('groups',Set::extract('/Group/.',$groups));

        /*$this->set('riskProfile', $this->User->getCustomFields('riskProfile'));
        $this->set('prefLang', $this->User->getCustomFields('prefLang'));
        $this->set('newWorth', $this->User->getCustomFields('newWorth'));
        $this->set('goals', $this->User->getCustomFields('goals'));
        $accountsHeld['Select please'] = $this->User->getCustomFields('accounts');
        $this->set('accountsHeld', $accountsHeld);
        $this->set('roi', $this->User->getCustomFields('roi'));
        $this->set('marital', $this->User->getCustomFields('marital'));
        $this->set('salary', $this->User->getCustomFields('salary'));*/

        $this->set('countries', $countries);

        $this->loadModel('UserPrivateInfo');
//        $this->loadModel('UserFinanceInfo');
//        $this->loadModel('Interest');

        /*$usrIntrsts = $this->Interest->getActiveForUser($id);

        $this->set( 'interests', $this->Interest->getCheckedInterests($usrIntrsts) );*/

        $this->User->Behaviors->attach('Containable');
        /*$this->User->bindModel(array(
            'hasOne' => array('UserPrivateInfo', 'UserFinanceInfo'),
//                'hasMany' => array('UserInterest'),
        ));*/
        $this->User->unbindModel(array('belongsTo' => array('Group')));
//        $this->data = $this->User->findById($id);
        $this->User->Behaviors->detach('Privatizable');
        $this->data = $this->User->read(null,$id);

        /*$accountsTmp = isset($this->data['UserFinanceInfo']['accounts']) ? $this->data['UserFinanceInfo']['accounts'] : null;
        if (!empty($accountsTmp)) {
            $selectedAccounts = explode(', ', $accountsTmp);
            $this->set('selectedAccounts', $selectedAccounts);
        }*/
        unset($this->data['User']['password']);
        foreach($this->data as $model => $fields) {
            unset($this->data[$model]['created']);
            unset($this->data[$model]['modified']);
        }
        if (isset($this->data['UserPrivateInfo']['birthday'])) $this->data['UserPrivateInfo']['birthday'] = date('d M, Y', strtotime($this->data['UserPrivateInfo']['birthday']));


    }

    public function saveInfo($id = null)
    {
/*        Configure::write('debug', 0);
        $this->layout = false;*/
        $out['status'] = false;
        if (!empty($id) && is_numeric($id)) {
            if ($this->data) {
                $data = $this->data;
                foreach ($data as $model => $fields) {
                    //some additional logic to save data to additional models which
                    //belong to User model
                    $sid = $id;
                    if ($model != 'User') {
                        $this->loadModel($model);
                        $cid = $this->{$model}->findByUserId($id, array($model.'.id'));
                        $sid = $cid[$model]['id'];
                        $data[$model]['user_id'] = $id;
                    } else {
                        $emailValidation['email']['email-rule-3'] = array(
                            'rule' => array('isUnique'),
                            'message' => 'This email is already registered. Try other one.'
                        );
                        if (!empty($data['User']['password']) && !empty($data['User']['cpassword'])) {
                                $data['User']['password'] = $this->Auth->password($this->data['User']['password']);
                                $this->User->validate = array_merge($this->User->validate, $emailValidation);
                        } else {
                            unset($this->User->validate['password']);
                            unset($this->data[$model]['password']);
                            unset($data['User']['password']);
                            $this->User->validate = $emailValidation;
                        }

                    }

                    $this->{$model}->Behaviors->detach('Containable');

                    if ( $model != 'UserInterest' ) {
                        if ($model == 'UserPrivateInfo') {
                            if (isset($data[$model]['birthday']) && !empty($data[$model]['birthday'])) $data[$model]['birthday'] = date('Y-m-d', strtotime($data[$model]['birthday']));
                        }
                        if ($model == 'UserFinanceInfo') {
                            if (isset($data[$model]['accounts']) && is_array($data[$model]['accounts'])) $data[$model]['accounts'] = implode(', ', $data[$model]['accounts']);
                        }
                        $data[$model]['id'] = $sid;
                        $this->{$model}->set($data);

                        if ($this->{$model}->validates()) {
                            $this->{$model}->save($data[$model]);
                            $out['status'] = true;
                        } else {
                            $out['status'] = false;
                            $out['errors'] = isset($out['errors']) ?
                                array_merge($out['errors'], $this->{$model}->validationErrors) :
                                $this->{$model}->validationErrors;
                        }
                    } else {
                        unset($data['UserInterest']['user_id']);
                        $this->loadModel($model);
                        if ($this->UserInterest->updateInterest($id, $data['UserInterest'])) {
                            $out['status'] = true;
                        } else {
                            $out['status'] = false;
                            $out['errors'] = isset($out['errors']) ?
                               array_merge($out['errors'], $this->{$model}->validationErrors) :
                               $this->{$model}->validationErrors;
                        }
                    }
                }
            } else {
                $out['status'] = false;
            }
        }
        if (!isset($out['errors'])) {
            $this->_setFlash('User info successfully saved.', 'success');
        } else {
            $errorText = '';
            foreach ($out['errors'] as $field => $errTxt)
            {
                $errorText .= '<br/>' . $field . ': ' .$errTxt;
            }
            $this->_setFlash('An error occurred when saved. ' . $errorText, 'error');
        }
        $this->redirect('index');
    }


    function delete($id)
    {
      # TODO: this should be a cascading delete
      $this->loadModel('UserFriend');
      $friends = $this->UserFriend->find('all', array(
        'contain' => array(),
        'conditions' => array(
          "OR" => array (
            'UserFriend.friend_id' => $id,
            'UserFriend.user_id' => $id
          )
        )
      ));
      if ($friends) {
        $delID = Set::extract('/UserFriend/id/.', $friends);
        $this->UserFriend->deleteAll(array('UserFriend.id' => $delID));
      }

      $this->User->Behaviors->detach('Privatizable');

      /*$this->loadModel('UserPrivacy');
      # find 'first' ignores the default privacy
      $privacy = $this->UserPrivacy->find('first', array(
        'conditions' => array('user_id' => $id)
      ));
      if ($privacy) {
        # only cascade delete if there is a user_privacy
        $this->User->bindModel(array(
          'hasOne' => array(
            'UserPrivacy' => array(
              'dependent' => true
            )
          )
        ), false);
      }*/
      # cascade delete to user poll answers
      $this->User->bindModel(array(
        'hasMany' => array(
          'UserPolls' => array(
            'dependent' => true,
          )
        ),
      ), false);
      if($this->User->delete($id)) {
        $this->_setFlash('User successfully deleted', 'success');
      } else {
        $this->_setFlash('User has not been removed', 'error');
      }

      $this->redirect($this->referer());
    }
}
