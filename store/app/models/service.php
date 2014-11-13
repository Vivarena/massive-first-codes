<?php
/**
 * User: Nike
 * Date: 24.02.2011
 * Time: 15:47:14
 */
class Service extends AppModel
{
    public $name   = 'Service';
    public $actsAs = array('Containable', 'Tree');


    public $validate            = array(
        'name'              => array(
            'rule'      => 'notEmpty',
        ),
        'description' => array(
            'rule'      => 'notEmpty',
        ),
        'key' => array(
            'minLengthRule' => array(
                'rule' => array('minLength', 3)
            ),
            'maxLengthRule' => array(
                'rule' => array('maxLength', 128)
            ),
            'formatRule' => array(
                'rule' => '/^[-_a-zA-Z0-9]+$/i'
            ),
            'uniqueRule' => array(
                'rule' => 'isUnique'
            )
        )
    );

    function __construct($id = false, $table = null, $ds = null)
    {
        parent::__construct($id, $table, $ds);

        $this->validate['key']['minLengthRule']['message'] = __('This field cannot be less then 3 symbols', true);
        $this->validate['key']['maxLengthRule']['message'] = __('This field cannot be more then 128 symbols', true);
        $this->validate['key']['formatRule']['message'] = __('This field must contain only letters or integers, separated by "_" or "-" symbols', true);
        //TODO isUnique don't work?
        $this->validate['key']['uniqueRule']['message'] = __('This field must be unique', true);
    }


    public function getActiveServices($limit=null) {
        $this->locale = Configure::read('Config.language');
        $data = $this->find("all",
            array(
                "conditions" => array(
                    "Service.active" => 1
                ),
                "limit" => $limit
            )
        );
        return Set::extract("/Service/.", $data);
    }

    public function readPost($id) {
        $data = $this->read(null, $id);
        return $data["Service"];
    }

    public function getLastAddedService() {
        $this->locale = Configure::read('Config.language');
        $data = $this->find('first');
        return $data["Service"];
    }

}
