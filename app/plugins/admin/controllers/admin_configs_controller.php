<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 22.12.10
 * Time: 12:43
 * @property User        $User
 * @property UserInfo    $UserInfo
 * @property AdminConfig $Config
 */

class AdminConfigsController extends AdminAppController
{
	public $name = 'AdminConfigs';
    public $uses = array("Config", "User", "UserInfo");

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setLeftMenu('config');

    }

    public function index()
    {
        $this->_setHoverFlag('config');
        if($this->data)
        {
            $this->data = $this->data['Config'];
            $this->Config->saveAll($this->data);
        }
        $this->Config->locale = false;
        $this->data = $this->Config->find('all');
    }

    public function users() {
        $this->_setLeftMenu('users');
        $this->_setHoverFlag('users');
        $this->paginate = array(
                "fields" => array(
                    "User.id", "UserInfo.first_name", "UserInfo.last_name", "UserInfo.id", "Group.name"
                ),
                "order" => "Group.name"
        );
        $this->set('users', $this->paginate("User"));
    }

    public function user_edit($id=null) {
        $this->_setLeftMenu('users');
        $this->_saveData();
        $this->loadModel('Group');
        $userGroups = $this->Group->find("list");
        $this->set('userGroups', $userGroups);
        var_dump($id);
        $this->data = $this->User->read(null, $id);
        var_dump($this->data);
        unset($this->data['User']['password']);
    }

    public function user_add() {
        $this->_setLeftMenu('users');
        $this->_saveData();
        $userGroups = $this->Group->find("list");
        $this->set('userGroups', $userGroups);
        $this->render("user_edit");
    }

    private function _saveData() {
        if ($this->data) {
            if ($this->data['User']['password'] == '537fc7351bbe6c37a8fccf64252f4045c5f5fb55') {
                unset($this->data['User']['password']);
            }

            if ($this->User->saveAll($this->data)) {
                $this->_setFlash("User edited success", "success");
                $this->redirect("users");
            } else {
                $this->_setFlash("Error", "error");
            }
        }
    }


}