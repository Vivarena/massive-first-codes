<?php
class NewsletterTemplate extends AppModel {
	var $name = 'NewsletterTemplate';
	var $displayField = 'title';

    public $actsAs = array(
        'Translate' => array(),
    );

    function __construct($id = false, $table = null, $ds = null)
    {
        parent::__construct();

        $this->validate = array(
            'title' => array(
                'rule' => 'notEmpty',
                'message' => __('This field cannot be left blank.', true)
            ),
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
}
?>