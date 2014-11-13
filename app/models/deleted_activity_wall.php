<?php

class DeletedActivityWall extends AppModel
{
    public $name = 'DeletedActivityWall';

    public $belongsTo = array('User', 'ActivityWall');

    public $actsAs = array(
        'Containable'
    );




}