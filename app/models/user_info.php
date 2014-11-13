<?php

class UserInfo extends AppModel
{
    public $name = 'UserInfo';

    public $belongsTo = array('User', 'Country');

    public $actsAs = array(
      'Containable',
    );


    public $validate = array(
        'first_name' => 'notEmpty',
        'last_name' => 'notEmpty'
    );


    function __construct($id = false, $table = null, $ds = null) {    
      parent::__construct($id, $table, $ds);    
      # alt virtual field specification with alias
      $this->virtualFields['username'] = sprintf('CONCAT(%s.first_name, " ", %s.last_name)', $this->alias, $this->alias);
    }

    public function getBasicInfo($id)
    {
        $fields = array(
            'UserInfo.id',
            'UserInfo.user_id',
            'UserInfo.first_name',
            'UserInfo.last_name',
            'UserInfo.photo',
            'UserInfo.country_id',
            'UserInfo.sex',
            'UserInfo.avatar',
            'UserInfo.username'
        );
        $conditions = array('UserInfo.user_id' => $id);

        $data = $this->find('first', array(
            'recursive' => -1,
            'fields' => $fields,
            'conditions' => $conditions
        ));

        return $data[$this->alias];
    }

    public function getIdByUser($idUser)
    {
        $userInfoID = $this->find('first', array(
            'conditions' => array('UserInfo.user_id' => $idUser),
            'fields' => array('UserInfo.id')
        ));
        if ($userInfoID) return $userInfoID['UserInfo']['id'];
        return false;
    }
}

?>
