<?php

class UserFriend extends AppModel
{
    public $name = 'UserFriend';


    public $actsAs = array('Containable');

    public $belongsTo = array(
        'Friend'/*=> array(
            'className' => 'Friend',
            'joinTable' => 'users',
            'foreignKey' => 'id',
            'associationForeignKey' => 'friend_id',
        )*/
    );

    public $validate = array(
        'user_id' => array(
            'uid_rule_1' => array(
                'rule' => 'notEmpty',
                'message' => 'This field cannot be empty.'
            ),
            'uid_rule_2' => array(
                'rule' => 'numeric',
                'message' => 'Only numbers are allowed.'
            ),
        ),
        'friend_id' => array(
            'fid_rule_1' => array(
                'rule' => 'notEmpty',
                'message' => 'This field cannot be empty.'
            ),
            'fid_rule_2' => array(
                'rule' => 'numeric',
                'message' => 'Only numbers are allowed.'
            ),
            'fid_rule_3' => array(
                'rule' => 'notEqToUID',
                'message' => 'You can`t add yourself to network.'
            ),
        ),
        'approved' => array(
            'rule' => 'boolean',
            'message' => 'Only boolean allowed.'
        )
    );

    private $typeFind = 'all';

    private $expIDs;
    private $withExpection = false;

    public function getFriendsId($user_id = null){
        $options = array(
            'recursive' => -1,
            'conditions' => array(
                'user_id' => $user_id,
                'approved' => true
            ),
            'fields' => 'friend_id'
        );
        $friendsId = Set::extract('/UserFriend/friend_id',$this->find('all',$options));

        return $friendsId;
    }

    public function notEqToUID($check){
        return $check['friend_id'] != $this->data[$this->alias]['user_id'];
    }

    public function GetCount($uId)
    {
        $cacheName = 'countFriends_'.$uId;
        $friends = Cache::read($cacheName);

        if ($friends === false) {
            $friends = $this->find('count', array(
                'conditions' => array(
                    'UserFriend.user_id' => $uId,
                    'UserFriend.approved' => 1,
//                    'Friend.id <>' => null,
                )
            ));
            if ($friends) Cache::write($cacheName, $friends);
        }
        return $friends;
    }

    public function countRequests($uId)
    {
        $this->Behaviors->detach('Containable');
        $count = $this->find('count', array(
            'conditions' => array(
                'user_id' => $uId,
                'approved' => 0
            ),
        ));

        return $count ? $count : 0;
    }


    public function afterSave($created)
    {
        $approved = (isset($this->data['UserFriend']['approved'])) ? $this->data['UserFriend']['approved'] : 0;

        if ($approved == 1 && isset($this->data['UserFriend']['user_id'])) {
            $ActivityWall = ClassRegistry::init('ActivityWall');
            $UserInfo = ClassRegistry::init('UserInfo');

            $userID = $this->data['UserFriend']['user_id'];
            $friendID = $this->data['UserFriend']['friend_id'];
            $names = $UserInfo->find('all', array(
                'conditions' => array('UserInfo.user_id' => array($userID, $friendID)),
                'fields' => array('UserInfo.username', 'User.id')
            ));

            $name = Set::extract('/User[id='.$userID.']/../UserInfo/username', $names);
            $serText = array(
                'text' => 'added a new friend in his network',
                'friend_name' => $name[0]
            );
            $feed['activity_text'] = serialize($serText);
            $feed['type_feed'] = 'friend';

            $feed['user_id'] = $this->data['UserFriend']['friend_id'];
            $feed['id_affected_tables'] = $this->data['UserFriend']['user_id'];
            $ActivityWall->create();
            $ActivityWall->save($feed);

            $notifyText['facebook'] = 'Added a new friend on vivarena.com';
            $notifyText['twitter'] = 'Added a new friend on vivarena.com';

            $notifyText['linkedin']['smallTitle'] = 'Did some action on the site';
            $notifyText['linkedin']['boldCaption'] = 'Added a new friend on vivarena.com';
            $notifyText['linkedin']['description'] = 'Added a new friend on vivarena.com. Interesting?';

            $SocialNotification = ClassRegistry::init('SocialNotification');
            $SocialNotification->checkAndSendNotify($feed['user_id'], 'Friends', true, $notifyText);

            $name = Set::extract('/User[id='.$friendID.']/../UserInfo/username', $names);
            $serText = array(
                'text' => 'added a new friend in his network',
                'friend_name' => $name[0]
            );
            $feed['activity_text'] = serialize($serText);
            $feed['user_id'] = $this->data['UserFriend']['user_id'];
            $feed['id_affected_tables'] = $this->data['UserFriend']['friend_id'];
            $ActivityWall->create();
            $ActivityWall->save($feed);

        }

    }

    public function addToNetwork($fromID, $toID, $allowed = false)
        {

            $result = array('status' => false);

            $check = $this->find('count', array(
                'conditions' => array('UserFriend.user_id' => $toID, 'UserFriend.friend_id' => $fromID)
            ));
            if (empty($check)) {
                $data = array(
                    'UserFriend' => array(
                        'friend_id' => $fromID,
                        'user_id' => $toID,
                        'approved' => $allowed
                    )
                );
                $this->create($data);
                if ($this->validates()) {
                    if($this->save()){
                        if($allowed){
                            $data = array(
                                'UserFriend' => array(
                                    'friend_id' => $toID,
                                    'user_id' => $fromID,
                                    'approved' => true
                                )
                            );
                            $this->create($data);
                            if ($this->validates()) {
                                $this->save();
                            }
                            $result = array_merge($result, array('status'=> true, 'message'=> __('Successfully added.', true)));
                        }else{
                            $result = array_merge($result, array('status'=> true, 'message'=> __('Pending', true)));

                        }
                    }else{
                        $result = array_merge($result, array('status'=> false, 'message'=> __('Error. Please try again.', true)));
                    }
                }
            } else {
                $result = array_merge($result, array('status'=> false, 'message'=> __('Already pending', true)));
            }

            return $result;

        }

    public function isFriend($userId1, $userId2) {
      $friends = $this->find('all', array(
        'contain' => false,
        'conditions' => array(
          'user_id' => $userId1,
          'approved' => true
        )
      ));
      $friends = Set::extract('/UserFriend/friend_id', $friends);
      return in_array($userId2, $friends);
    }

    public function getRequestCount($userId) {
      $data = $this->find("count",
        array(
          "conditions" => array(
            "user_id"         => $userId,
            "approved"        => 0,
          )
        )
      );
      return $data;
    }

    public function setFriendshipStatus($friendId, $authUserID)
    {
        $isMyFriend = $this->find( 'count', array(
            'conditions' => array(
                'user_id' => $authUserID,
                'friend_id' => $friendId,
            )
        ) );

        $iAmFriend = $this->find( 'count', array(
            'conditions' => array(
                'user_id' => $friendId,
                'friend_id' => $authUserID,
            )
        ) );

        $result['isMyFriend'] = $isMyFriend;
        $result['iAmFriend'] = $iAmFriend;
        return $result;
    }

    public function listView() {
        $this->typeFind = 'list';
        return $this;
    }
    
    public function butNotId($expIds) {
        $this->expIDs = $expIds;
        $this->withExpection = true;
        return $this;
    }

    public function getMyFriends($myId, $filterName = null)
    {

        $joins = array(
            array(
                'table' => $this->tablePrefix.'users',
                'alias' => 'User',
                'type' => 'LEFT',
                'conditions' => array(
                    'User.id = UserFriend.friend_id',
                )
            ),
            array(
                'table' => $this->tablePrefix.'user_infos',
                'alias' => 'UserInfo',
                'type' => 'LEFT',
                'conditions' => array(
                    'User.id = UserInfo.user_id',
                )
            )
        );
        $conditions = array(
            'UserFriend.user_id' => $myId,
            'UserFriend.approved' => 1
        );
        if ($this->withExpection && !empty($this->expIDs) && (is_array($this->expIDs) && count($this->expIDs) > 1)) {
            $conditions['UserFriend.friend_id NOT'] = $this->expIDs;
        }
        if (!empty($filterName)) {
            $conditions['OR'] = array(
                "UserInfo.last_name LIKE"  => "{$filterName}%",
                "UserInfo.first_name LIKE" => "{$filterName}%",
                "CONCAT(UserInfo.last_name, ' ', UserInfo.first_name)" => "{$filterName}",
                "CONCAT(UserInfo.first_name, ' ', UserInfo.last_name)" => "{$filterName}"
            );
        }
        $fields = ($this->typeFind == 'list') ? array('UserInfo.first_name', 'UserInfo.last_name', 'User.id') : '*';
        $get = $this->find('all', array(
            'recursive' => -1,
            'order' => 'UserInfo.first_name ASC',
            'joins' => $joins,
            'fields' => $fields,
            'conditions' => $conditions
        ));
        if ($this->typeFind == 'list') {
            $get = Set::combine($get, '{n}.User.id', array('{0} {1}', '{n}.UserInfo.first_name', '{n}.UserInfo.last_name'));
        }

        return $get;

    }
}
