<?php
/**
 * Simple Authorization by roles
 * Author Mike S. <misilakov@gmail.com>
 *
 * @property AuthComponent $Auth
 */
class SimpleAuthComponent extends Object {

    public $components = array('Session', 'Auth');

    public $publicActions = array();

    public $controller = null;

    public $groups = array();

    private $config = array();


    function initialize(Controller $controller, $settings = array()) {
        $this->controller =& $controller;
    }

    /**
     * Initialize Auth component
     * @return void
     */
    public function initAuth()
    {
        Configure::load('permissions');
        $this->config = Configure::read('AuthPermissions');

        if (Configure::read("debug") == 0) session_start();
        $this->Auth->authorize = 'controller';
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

        // for anonimous users (authorized users used aka-ACL rules)
        if($this->Auth->user() == null) {
            $this->checkPublicAuth();
        }

    }


    private function checkPublicAuth()
    {
        $curControllerName = $this->controller->plugin ? "{$this->controller->plugin}.{$this->controller->name}" : $this->controller->name;
        foreach($this->config['publicActions'] as $controller => $action) {
            if($controller == $curControllerName) {
                if($action == '*' || in_array($this->controller->action, $action)) {
                    $this->Auth->allow($this->controller->action);
                }
            }
        }
    }

    public function isAuth($user)
    {
        // Admin can access every action
        if (isset($user['group_id']) && $user['group_id'] == 1) return true;

        $this->publicActions = $this->config['publicActions'];
        $currentGroupName = '';
        foreach ($this->config['authGroups'] as $group => $options) {
            if ($options['group_id'] == $user['group_id']) {
                $currentGroupName = $group;
                break;
            }
        }

        $this->config['authGroups'][$currentGroupName]['accesses'] = array_merge($this->config['commonAuthAccess'], $this->config['authGroups'][$currentGroupName]['accesses']);

        $accesses = $this->config['authGroups'][$currentGroupName]['accesses'];

        $curControllerName = $this->controller->plugin ? "{$this->controller->plugin}.{$this->controller->name}" : $this->controller->name;
        if ($curControllerName == 'Users' && $this->controller->action == 'denied') return true;
        foreach($accesses as $controller => $action) {
            if($controller == $curControllerName) {
                if($action == '*' || in_array($this->controller->action, $action)) {
                    return true;
                }
            }
        }

        // Default deny
        $this->Session->setFlash('Access not allowed!', 'flash_custom', array('title' => ' Denied!  ', 'type' => 'error' ));
        $this->controller->redirect(array('controller' => 'Users', 'action' => 'denied', 'plugin' => null));

        return false;
    }


}