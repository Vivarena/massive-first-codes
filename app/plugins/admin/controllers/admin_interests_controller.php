<?php

/**
 * @property User $User
 * @property Interest $Interest
 * @property UserInterest $UserInterest
 */

class AdminInterestsController extends AdminAppController
{
    public $name = 'AdminInterests';

    public $uses = array('User', 'Interest', 'UserInterest');

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setLeftMenu('community');
        $this->_setHoverFlag('community');
        $this->_setHoverFlag('interests');
    }

    public function index()
    {
        $this->paginate['Interest'] = array(
            'fields' => array('id', 'name', 'description', 'active'),
            'order' => array('created' => 'desc'),
            'limit' => 25
        );
        $this->data = $this->paginate('Interest');
    }

    public function add()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $out = array('status' => false, 'errors' => null);
        if ( $this->RequestHandler->isAjax() ) {
            $this->layout = false;
            $this->autoRender = false;
            if ($this->_saveData($out)) {
                $out = array_merge($out,
                    array('data' =>  $this->Interest->getLastInserted() )
                );
            }
        }
        exit(json_encode($out));
    }

    public function edit()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write('debug', 0);
        $out = array('status' => false, 'errors' => null);
        if ( $this->RequestHandler->isAjax() ) {
            $this->layout = false;
            $this->autoRender = false;
            if ($this->_saveData($out)) {
                $out = array_merge($out,
                    array('data' =>  $this->Interest->getLastModified() )
                );
            }
            if (isset($this->data['id'])) {
                $this->Interest->locale = false;
                $qqq = array();
                foreach ( $this->languages as $key => $lang ) {
                    $this->Interest->locale = $key;
                    $i18n = $this->Interest->read( array('name', 'description'), $this->data['id'] );
                    $qqq['Interest']['name'][$key] = $i18n['Interest']['name'];
                    $qqq['Interest']['description'][$key] = $i18n['Interest']['description'];
                }
                $out = array_merge($out, array('status'=> true), $qqq);
            }
        }
        exit(json_encode($out));
    }

    private function _saveData(&$out)
    {
        if ( isset($this->data['Interest']) ) {
            if ( strlen($this->data['Interest']['id']) ) {
                $this->Interest->id = $this->data['Interest']['id'];
            } else {
                $this->Interest->create();
            }
            unset($this->data['Interest']['id']);

            # Ignore the Translate module. The translations are in the .po files in app/locale/
            $this->data['Interest']['name'] = $this->data['Interest']['name']['eng'];
            $this->data['Interest']['description'] = $this->data['Interest']['description']['eng'];

            if ($this->Interest->save($this->data['Interest'])){
                $out = array_merge($out, array(
                    'status' => true,
                ));
            } else {
                $out = array_merge($out, array(
                    'errors' => $this->Interest->validationErrors
                ));
            }
            return true;
        }
        return false;
    }

}

?>
