<?php

class Event extends AppModel
{
    public $name = 'Event';

    public $belongsTo = array('EventCategory');

    public $actsAs = array(
        'Translate' => array('title', 'details'),
        'Containable'
    );

    public $validate = array(
        'title' => 'notEmpty',
        'details' => 'notEmpty',
        'date_start' => array(
            'rule' => array('date', 'ymd'),
            'message' => 'Please enter date in MM-DD-YYYY format.',
            'allowEmpty' => 'true'),
        'date_end' => array(
            'rule' => array('date', 'ymd'),
            'message' => 'Please enter date in MM-DD-YYYY format.',
            'allowEmpty' => 'true'),
    );

}

?>