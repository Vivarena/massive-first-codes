<?php
class Message extends AppModel {
    var $name = 'Message';
    var $actsAs = array('Containable');

    var $belongsTo = array(
        'From' => array(
            'className'  => 'User',
            'foreignKey' => 'from_id',
            'fields'     => array(
                "email", 'login', 'id'
            )
        ),
        'To' => array(
            'className'  => 'User',
            'foreignKey' => 'to_id',
            'fields'     => array(
                "email", 'login', 'id'
            )
        )
    );

    function __construct($id = false, $table = null, $ds = null)
    {
        parent::__construct();

        $this->validate = array(
            'subject' => array(
                'rule' => 'notEmpty',
                'message' => __('This field cannot be left blank.', true)
            ),
            'content' => array(
                'rule' => 'notEmpty',
                'message' => __('This field cannot be left blank.', true)
            )
        );
    }

    function getTrashCount($userId)
    {
        return $this->find('count', array(
            'conditions' => $this->getTrashConditions($userId)
        ));
    }

    function getTrashConditions($userId)
    {
        return array(
            'OR' => array(
                array(
                    'Message.to_id !=' => $userId,
                    'Message.from_id' => $userId,
                    'Message.from_deleted' => 1,
                    'Message.from_destroyed' => 0
                ),
                array(
                    'Message.from_id !=' => $userId,
                    'Message.to_id' => $userId,
                    'Message.to_deleted' => 1,
                    'Message.to_destroyed' => 0
                ),
            )
        );
    }

    public function getUnreadMessages($userId) {
        $data = $this->find("count",
            array(
                "conditions" => array(
                    "Message.to_id"         => $userId,
                    "Message.status"        => 0,
                    "Message.to_deleted"    => 0
                )
            )
        );
        return $data;
    }

    public function touch($id = null) {
      if (!is_null($id)) {
        $this->id = $id;
      }

      if (is_null($this->id)) return false;

      return $this->saveField('modified', DboSource::expression('NOW()'));
    }
}
