<?php

class User extends AppModel {
    public $name = "User";
    public $belongsTo = array("Group");
    public $hasOne = array(
        "UserInfo" => array(
            'dependent'=> true
        ),
        'UserPrivateInfo' => array(
            'dependent'=> true
        )
    );

    public $hasMany = array(
        'Rating'
    );


    public $actsAs = array(
      'Containable',
    );

    public $validate = array(
      'email' => array(
        'email-rule-1' => array(
          'rule' => 'notEmpty',
          'message' => 'Email address cannot be empty.',
          'last' => true),
        'email-rule-2' => array(
          'rule' => 'email',
          'message' => 'Please supply a valid email address.',
          'last' => true),
        'email-rule-3' => array(
          'rule' => 'isUnique',
          'message' => 'Someone has registered with this email before.',
          'last' => true,
        ),
      ),
      'password' => array(
        'rule'          => 'checkPasswords',
        'required'      => true,
        'allowEmpty'    => false,
        'message'       => 'Passwords do not match'
      ),
      'cpassword' => array(
        'rule' => 'notEmpty',
        'allowEmpty' => false,
      ),
      'login' => array(
        'unique-rule' => array(
          'rule' => 'isUnique',
          'allowEmpty' => false,
          'required' => false,
          'message' => 'This URL is taken.'
        ),
        'reserved-rule' => array(
          'rule' => 'checkReserved',
          'message' => 'This URL is reserved.'
        ),
        'regex-rule' => array(
          'rule' => 'checkCharacters',
        ),
      ),
    );


    public function checkAndSetLogin($user, $uID)
    {
        $fName = ucfirst(str_replace(array(' ', ',', '.'), array('-','',''), trim($user['first_name'])));
        $lName = trim($user['last_name']);
        $lName = (!empty($lName)) ? '-'.ucfirst(str_replace(array(' ', ',', '.'), array('-','',''), trim($user['last_name']))) : '';
        $slugURL = $fName.$lName;
        if ($this->checkLogin($slugURL)) $slugURL .= '-'.$uID;
        return $slugURL;
    }

    public function checkLogin($login)
    {
        $check = $this->find('count', array(
            'conditions' => array(
                'User.login' => $login
            )
        ));
        if ($check > 0) return true;
        return false;
    }

    public function checkAuthEmail($email, $authID)
    {
        $check = $this->find('count', array(
            'conditions' => array(
                'User.email' => $email,
                'User.id <>' => $authID
            )
        ));
        if ($check > 0) return true;
        return false;

    }

    public function getBasicInfo($val, $byLogin = false)
    {
        if ($byLogin) {
            $conditions = array('User.login' => $val);
        } else {
            $conditions = array('User.id' => $val);
        }

        $data = $this->find('first', array(
            'contain' => array(
                'UserInfo' => array( 'fields' => array(
                         'UserInfo.first_name',
                         'UserInfo.last_name',
                         'UserInfo.photo',
                         'UserInfo.sex',
                         'UserInfo.avatar',
                       ),
                'Country' => 'name'
                )
            ),
            'fields' => array('User.id', 'User.login'),
            'conditions' => $conditions
        ));
        $data["UserInfo"]["user_id"]=$data["User"]["id"];
        $data["UserInfo"]["login"]=$data["User"]["login"];
        $data["UserInfo"]["country_name"]= (isset($data["UserInfo"]["Country"]["name"])) ? $data["UserInfo"]["Country"]["name"] : null;
        $data["UserInfo"]["company"]= (isset($data["UserPrivateInfo"]["company"])) ? $data["UserPrivateInfo"]["company"] : null;
        $data["UserInfo"]["position"]= (isset($data["UserPrivateInfo"]["position"])) ? $data["UserPrivateInfo"]["position"] : null;
        unset($data["User"]);
        if (isset($data["UserInfo"]["Country"])) unset($data["UserInfo"]["Country"]);

        return $data["UserInfo"];
    }

    public function getUIDByLogin($login)
    {
        $id = $this->find('first', array(
                'conditions' => array($this->alias.'.login' => $login), 'recursive' => -1, 'fields' => array('User.id')
            )
        );
        if ($id) $id = $id['User']['id'];
        return $id;
    }

    public function getCustomFields($fieldsName)
    {
        $fields = $this->fieldSet[$fieldsName];
        $trues = array();
        $trues = array_pad($trues, count($fields), true);
        return array_combine($fields, array_map('__', $fields, $trues));
    }

    function checkPasswords()
    {
      return (strcmp($this->data["$this->name"]['password'], Security::hash($this->data["$this->name"]['cpassword'], null, true)) == 0);
    }


    function checkCharacters($check) {
      $slug = $check['login'];
      if (preg_match('/^[a-z0-9-_]+$/', $slug)) {
        return true;
      } else {
        $goodslug = strtolower($slug);
        $goodslug = preg_replace('/[^a-z0-9-_]/', '-', $goodslug);
        return __('How about this instead?', true) . ' ' . $goodslug;
      }
    }

    public function parentNode() {
        if (!$this->id && empty($this->data)) {
            return null;
        }

        if (isset($this->data['User']['group_id'])) {
            $groupId = $this->data['User']['group_id'];
        } else {
            $groupId = $this->field("group_id");
        }

        if (!$groupId) {
            return null;
        } else {
            return array('Group' => array('id' => $groupId));
        }

    }

    public function bindNode($user) {
        return array('model' => 'Group', 'foreign_key' => $user['User']['group_id']);
    }

    public function confirm($data, $field) {
        $first = $this->data[$this->alias][$field];
        $second = $data['c'.$field];
        return $first == AuthComponent::password($second) ? true : false;
    }



    /*
     * Static methods that can be used to retrieve the logged in user
     * from anywhere
     *
     * Copyright (c) 2008 Matt Curry
     * www.PseudoCoder.com
     * http://github.com/mcurry/cakephp/tree/master/snippets/static_user
     * http://www.pseudocoder.com/archives/2008/10/06/accessing-user-sessions-from-models-or-anywhere-in-cakephp-revealed/
     *
     * @author      Matt Curry <matt@pseudocoder.com>
     * @license     MIT
     *
     */

    function &getInstance($user=null) {
      static $instance = array();

      if ($user) {
        $instance[0] =& $user;
      }

      if (!$instance) {
        #trigger_error(__("User not set.", true), E_USER_WARNING);
        return $instance;
      }

      return $instance[0];
    }

    function store($user) {
      if (empty($user)) {
        return false;
      }

      User::getInstance($user);
    }

    function get($path) {
      $_user =& User::getInstance();

      $path = str_replace('.', '/', $path);
      if (strpos($path, 'User') !== 0) {
        $path = sprintf('User/%s', $path);
      }

      if (strpos($path, '/') !== 0) {
        $path = sprintf('/%s', $path);
      }

      $value = Set::extract($path, $_user);

      if (!$value) {
        return false;
      }

      return $value[0];
    }

    public function GetEmailById($id) {
        $data = $this->find('first', array(
            'conditions' => array(
                'User.id' => (int) $id
            ),
            'fields' => 'User.email'
        ));
        if(!$data) {
            return false;
        }
        return $data['User']['email'];
    }

    public function is_private($id){
        $this->recursive = -1;
        $options = array(
            'conditions'=> array(
                'id' => $id
            ),
            'fields' => 'private'
        );
        $data = $this->find('first', $options);

        return $data['User']['private']? true:false;
    }

    ####
    ## end cakephp_static_user
    ####

    public function getUserByEmail($email)
    {
        if(!Validation::getInstance()->email($email)) { return false; }
        $data = $this->find('first',array('conditions'=>array('email'=>$email)));

        return $data? $data :false;
    }

}
