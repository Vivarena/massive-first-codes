<?php

class CalendarEvent extends AppModel
{
    public $name = 'CalendarEvent';

    public $belongsTo = array('User');
    public $hasMany = array('UserCalendar' => array('dependent'=> true));

    public $actsAs = array('Containable');

    public $validate = array(
        'description' => 'notEmpty',
    );

    private $ownerId;
    private $checkOwner = false;

    public function setOwner($ownId)
    {
        $this->ownerId = $ownId;
        $this->checkOwner = true;
        return $this;
    }

    public function afterFind($results, $primary = false)
    {
        if ($this->checkOwner && isset($results[0])) {
            $results[0][$this->name]['iAmOwner'] = ($results[0][$this->name]['user_id'] == $this->ownerId) ? true : false;
        }
        return $results;
    }

    public function readEvent($id, $onlyCal = false)
    {

        $contain = ($onlyCal) ? array() : array('UserCalendar' => array('User' => array('UserInfo' => array('order' => 'first_name ASC'))));
        $event = $this->find('first', array(
            'contain' => $contain,
            'conditions' => array(
                'CalendarEvent.id' => $id
            )
        ));
        return $event;
    }

    public function checkOwner($uId, $calId)
    {
        $check = $this->find('count', array(
            'conditions' => array(
                'CalendarEvent.id' => $calId,
                'CalendarEvent.user_id' => $uId
            ), 'recursive' => -1
        ));
        return (bool)$check;
    }
}
