<?php
/**
 * @author Vitaliy Kh.
 *
 * @property Aro $Aro
 *
 */

class User extends AppModel
{
    public $name        = 'User';
    public $actsAs      = array('Acl' => 'requester');
    public $validate    = array(
        'username'  => array('rule' => 'notEmpty'),
        'email'     => array(
            1 => array('rule' => 'email'),
            2 => array('rule' => 'isUnique'),
            0 => array('rule' => 'notEmpty')
        ),
        'password'  => array('rule' => 'notEmpty'),
    );

    public function parentNode()
    {
        if (!$this->id && empty($this->data)) {
            return null;
        }

        if (empty($this->data)) {
            $data = $this->read();
        } else {
            $data = $this->data;
        }
        
        if (!isset($data['User']['group_id'])) {
            return null;
        } else {
            return array('Group' => array('id' => $data['User']['group_id']));
        }
    }

    /**
     * After save callback
     *
     * Update the aro for the user.
     *
     * @access public
     * @param $created
     * @return void
     */
    public function afterSave($created) {
        if(!$created) {
            $parent                     = $this->parentNode();
            $parent                     = $this->node($parent);
            $node                       = $this->node();
            $aro                        = $node[0];
            $aro['Aro']['parent_id']    = $parent[0]['Aro']['id'];

            $this->Aro->save($aro);
        }
    }

    public function confirm($data, $field)
    {
        return $this->data[$this->alias][$field] != $data["c$field"] ?
            false : true;
    }

    public function hashPasswords($data, $enforce = false) {
        if($enforce && isset($this->data[$this->alias]['password'])) {
            if(!empty($this->data[$this->alias]['password'])) {
                $this->data[$this->alias]['password'] = Security::hash(
                    $this->data[$this->alias]['password'], null, true
                );
            }
        }

        return $data;
    }

//    public function beforeSave() {
//        $this->hashPasswords(null, true);
//
//        return true;
//    }

}