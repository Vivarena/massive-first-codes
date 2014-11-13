<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 22.12.10
 * Time: 12:43
 * @property Config $Config
 */

class AdminConfigsController extends AdminAppController
{
	public $name = 'AdminConfigs';
    public $uses = array("Config");

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setLeftMenu('config');
        $this->_setHoverFlag('config');
    }

    public function index()
    {
        if($this->data)
        {
            $this->data = $this->data['Config'];
            $this->Config->saveAll($this->data);
        }
        $this->data = $this->Config->find('all');
    }

}