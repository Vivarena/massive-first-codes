<?php

class UserSponsorReview extends AppModel
{
    public $name = 'UserSponsorReview';
    public $actsAs = array('Containable');
    public $recursive = -1;

    public $belongsTo = array(
        'User', 'UserSponsor'
    );

    public $validate = array(
        'text' => array(
            'rule' => 'notEmpty',
            'message' => 'Please fill text field!'
        )
    );

    public function checkReviewer($uId, $revId)
    {
        $check = $this->find('count', array(
            'conditions' => array(
                'UserSponsorReview.user_id' => $uId,
                'UserSponsorReview.user_sponsor_id' => $revId
            ),
            'recursive' => -1,
        ));
        return (bool)$check;
    }
    
    public function getSponsorId($id)
    {
        $get = $this->find('first', array(
            'conditions' => array(
                'UserSponsorReview.id' => $id
            ), 'recursive' => -1,
            'fields' => 'UserSponsorReview.user_sponsor_id'
        ));

        return ($get) ? $get['UserSponsorReview']['user_sponsor_id'] : false;
    }

}