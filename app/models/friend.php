<?php

class Friend extends AppModel
{
    public $name = 'Friend';

    public $useTable = 'users';

    public $actsAs = array('Containable');

    public $hasOne = array(
        'UserInfo' => array(
            'foreignKey' => 'user_id'
        )
    );

}

?>