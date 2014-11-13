<?php
/**
 * Author Mike S.
 *
 */


class GoodAclComponent extends Object {

    public $components = array('Acl', 'Session', 'Auth');

    public $publicActions = array();

    public $controller = null;

    public $groups = array();

    public $admin = '';


    function initialize(&$controller, $settings = array()) {
        $this->controller =& $controller;
    }



    /**
     * Initialize Auth component
     * @return void
     */
    public function initAuth()
    {
        $this->Auth->authorize = 'actions';
        $this->Auth->authenticate = ClassRegistry::init('Page');

        $this->Auth->fields = array(
            'username' => 'email',
            'password' => 'password'
        );
        $this->Auth->loginRedirect = array(
            'controller' => 'community',
            'action' => 'feed'
        );
        $this->Auth->logoutRedirect = array(
            'controller' => 'pages',
            'action' => 'index'
        );
        $this->Auth->loginError = "Error! Invalid email or password";

        $this->Auth->autoRedirect = false;

        // for anonimous users (authorized users used ACL rules)
        if($this->Auth->user() == null) {
            foreach($this->publicActions as $controller => $action)
            {
                $curControllerName = $this->controller->plugin ? "{$this->controller->plugin}.{$this->controller->name}" : $this->controller->name;
                if($controller == $curControllerName) {
                    if($action == '*' || in_array($this->controller->action, $action)) {
                        $this->Auth->allow($this->controller->action);
                    }
                }
            }
        }
    }


    /**
     * Setup ACL permissions
     * @return void
     */
    public function setupAclPermissions($resetAcl = false)
    {
        $groupModel = ClassRegistry::init('Group');
        if ($resetAcl) {
            $aro = new Aro();
            $aro->query('TRUNCATE bs_aros_acos;');
        }


        if (!empty($this->admin)) {
            $group = $groupModel->findByName(strtolower($this->admin));
            $this->Acl->allow($group, 'controllers');
            //$this->Acl->deny($group, 'controllers/' . ucfirst($this->admin));
            unset($group);
        }

        foreach($this->groups as $oneGroup => $oneController)
        {
            $group = $groupModel->findByName(strtolower((!is_array($oneController)) ? $oneController : $oneGroup));
            $this->Acl->deny($group, 'controllers');
            if (is_array($oneController)) {
                foreach($oneController as $controller => $action)
                    {
                        $actionsTxt = (is_array($oneController[$controller])) ? '/'.$controller : '/'.$oneController[$controller];
                        if (is_array($oneController[$controller])) {
                            foreach($oneController[$controller] as $oneAction){
                                $this->Acl->allow($group, 'controllers'.$actionsTxt.'/'.$oneAction);
                            }
                        } else {
                            $this->Acl->allow($group, 'controllers'.$actionsTxt);
                        }

                    }
            }
        }

    }

    /**
     * Only first run for create all needed ACL-records in DB.
     * @param $groups
     * @param bool $reset
     * @param bool $die
     * @return void
     */
    public function firstAroStep($groups, $reset = false, $die = false)
    {
        if ($reset) {
            $aro =& $this->Acl->Aro;
            $aro->query('TRUNCATE bs_aros;');
            unset($aro);
        }
        $this->_createAro($groups);
        $this->_addUsersToAro($groups);
        if ($die) die;
    }


    private function _createAro($g)
    {
        $aro =& $this->Acl->Aro;
        $groups = array();

        foreach($g as $group)
        {
            $groups[]['alias'] = $group;
        }

        foreach($groups as $data)
        {
            $aro->create();
            $aro->save($data);
        }

    }

    private function _addUsersToAro($g)
    {
        $aro = new Aro();
        $users = array();
        $groupModel = ClassRegistry::init('Group');

        $i = 0;
        foreach($g as $group)
        {

            $gID = $groupModel->find('first', array(
                'conditions' => array(
                    'name' => $group
                ),
                'fields' => array('id')
            ));
            $gID = $gID['Group']['id'];
            $users[$i]['alias'] = ucfirst($group);
            $users[$i]['parent_id'] = $gID;
            $users[$i]['model'] = 'Group';
            $users[$i]['foreign_key'] = $gID;
            $i++;
        }

        foreach($users as $data)
        {
            $aro->create();
            $aro->save($data);
        }

    }


}