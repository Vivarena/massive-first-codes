<?php
/**
 * Created by CNR
 * User: nike
 * Date: 19.06.11
 * Time: 0:08
 *
 */

class Faq extends AppModel
{
    public $name   = 'Faq';
    public $actsAs = array('Containable', 'Tree',
        'Translate' => array(
            'quention',
            'answer',
        )
    );
    public $validate = array(
        'quention' => array(
            'rule' => 'notEmpty',
            'message' => "This field could not be empty"
        ),
        'answer' => array(
            'rule' => 'notEmpty'
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

    public function getActiveFaqItems()
    {
        $data = $this->find('all',
            array(
                'conditions' => array(
                    'Faq.active'       => 1,
                ),
                'order' => 'lft'
            )
        );
        return Set::extract('/Faq/.', $data);
    }

    public function getFaqs($frontend=false)
    {
        $conditions = array();
        if ($frontend) {
            $conditions = array_merge($conditions, array("Faq.active" => 1));
        }
        $data = $this->find('all',
            array(
                "conditions" => $conditions,
                'order' => 'lft'
            )
        );
        return $data;
    }
}
