<?php
/**
 * Application model for Cake.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       cake
 * @subpackage    cake.app
 */
class AppModel extends Model {

    public $actsAs = array('SavingSorting');

    public function find($conditions = null, $fields = array(), $order = null, $recursive = null, $callback = null) {
        if(is_callable($callback)) {
            return call_user_func($callback, parent::find($conditions, $fields, $order, $recursive));
        }
        return parent::find($conditions, $fields, $order, $recursive);
    }

    /**
     * If model have actAs "Translate"
     * Author Mike S.
     * @param $data - ref. of $this->data from Model
     * @param $languages
     * @param $fields - which fields need to translate
     * @return array
     */
    public function i18dataLoad(&$data, $languages, $fields)
    {

        $tmpNames = array();
        foreach($languages as $key=>$language)
        {
            $this->locale = $key;
            $i18nData = $this->read($fields);
            foreach ($fields as $field)
            {
                $tmpNames[$field][$key] = $i18nData["$this->name"][$field];
            }
        }
        $data["$this->name"] = array_merge($data["$this->name"], $tmpNames);
        return $data;
    }


    function unbindAll($params = array())
    {
        foreach ($this->__associations as $ass) {
            if (!empty($this->{$ass})) {
                $this->__backAssociation[$ass] = $this->{$ass};
                if (isset($params[$ass])) {
                    foreach ($this->{$ass} as $model => $detail) {
                        if (!in_array($model, $params[$ass])) {
                            $this->__backAssociation = array_merge($this->__backAssociation, $this->{$ass});
                            unset($this->{$ass}[$model]);
                        }
                    }
                } else {
                    $this->__backAssociation = array_merge($this->__backAssociation, $this->{$ass});
                    $this->{$ass} = array();
                }

            }
        }
        return true;
    }

    function unbindValidation($type, $fields, $require=false)
    {
        if ($type === 'remove')
        {
            $this->validate = array_diff_key($this->validate, array_flip($fields));
        }
        else
            if ($type === 'keep')
            {
                $this->validate = array_intersect_key($this->validate, array_flip($fields));
            }

        if ($require === true)
        {
            foreach ($this->validate as $field=>$rules)
            {
                if (is_array($rules))
                {
                    $rule = key($rules);

                    $this->validate[$field][$rule]['required'] = true;
                }
                else
                {
                    $ruleName = (ctype_alpha($rules)) ? $rules : 'required';

                    $this->validate[$field] = array($ruleName=>array('rule'=>$rules,'required'=>true));
                }
            }
        }
    }
}
