<?php

class UserCalendar extends AppModel
{
    public $name = 'UserCalendar';

    public $belongsTo = array('User', 'CalendarEvent');

    public $actsAs = array('Containable');


    public function getUserEvents($uId)
    {
        $get = $this->find('all', array(
            'conditions' => array(
                'UserCalendar.user_id' => $uId,
                'UserCalendar.status' => 1
            )
        ));
        return $get;
    }

    public function updateStatus($idCal, $uId, $status)
    {
        $get = $this->find('first', array(
            'recursive' => -1,
            'conditions' => array(
                'UserCalendar.calendar_event_id' => $idCal,
                'UserCalendar.user_id' => $uId
            )
        ));
        if ($get) {
            $this->id = $get['UserCalendar']['id'];
            return $this->saveField('status', $status, false);
        }
        return false;
    }

}
