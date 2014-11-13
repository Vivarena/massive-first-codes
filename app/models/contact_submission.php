<?php
class ContactSubmission extends AppModel {

	public $name = 'ContactSubmission';
    public $validate = array(
        'first_name'    => array('notempty'),
        'subject'    => array('notempty'),
        /*'last_name'     => array('notempty'),*/
        'comment'       => array('notempty'),
        'email'         => array(
            'notempty',
            'email' => array(
                'rule' => 'email',
                'message' => 'Please enter a valid email address'
            )
        ),
        'kcaptcha' => array(
            'rule'    => 'notempty',
            'message' => 'Incorrect string'
        ),
        'phone'         => array(
            'rule' => array('maxLength', 20),
            'message' => 'Phone number must be no larger than 20 characters long.'
        ),
    );

    //The Associations below have been created with all possible keys, those that are not needed can be removed
    var $hasMany = array(
        'ContactSubmissionComment' => array(
            'className' => 'ContactSubmissionComment',
            'foreignKey' => 'contact_submission_id',
            'dependent' => true,
        )
    );
}