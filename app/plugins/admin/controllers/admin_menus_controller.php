<?php
/**
 * @property Menu         $Menu
 * @property Page         $Page
 * @property SiteMenu     $SiteMenu
 */
class AdminMenusController extends AdminAppController
{
    public $name = 'AdminMenus';
    public $uses = array('Menu', 'SiteMenu', 'Page');
    public $imageTypes = array(
                'image/jpeg',
                'image/gif',
                'image/png'
           );

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setLeftMenu('menus');
        $this->_setHoverFlag('menu');
    }


    public function index() {
        $menus = $this->SiteMenu->find('all'/*,
            array(
                "conditions" => array(
                    "SiteMenu.active" => 1
                )
            )*/
        );
        $this->set('menus', Set::extract('/SiteMenu/.', $menus));
    }

    public function menu($id)
    {
        $menuName = $this->SiteMenu->read("name", $id);

        $this->set('menuType', $id);
        $this->set("menuName", $menuName['SiteMenu']['name']);
        $this->set('static_pages', $this->Page->getAllActivePages());
        $menus = $this->Menu->get($id, false, true);
        $this->set('menu', $menus);
    }

    public function delete($id)
    {
        if($this->Menu->delete($id)) {
            $this->_setFlash('Item successfully deleted', 'success');
        } else {
            $this->_setFlash('Item has not been removed', 'error');
        }

        $this->redirect($this->referer());
    }

    public function add()
    {

        if($this->data /*&& !empty($this->data['DynamicMenu']['parent_id'])*/) {

            $this->data['Menu']['parent_id'] = null;
            $this->Menu->save($this->data);

            $this->_setFlash('Menu item successfully added', 'success');
        }

        $this->redirect($this->referer());
    }

    public function edit($id = null)
    {
        if($this->data && $id) {
            unset($this->data['Menu']['parent_id']);

            $this->Menu->id = $id;
            if($this->Menu->save($this->data)) {
                $this->_setFlash('Menu item successfully edited', 'success');
            } else {
                $this->_setFlash('Errors occurred', 'error');
            }
        }

        $this->redirect($this->referer());
    }

    public function read($id)
    {
        $this->layout = null;
        $this->autoRender = false;

        Configure::write('debug', 0);

        $response = array( 'status' => false );

        $data = $this->Menu->getItem($id);
        if($data) {
            $response = $data['Menu'];
            $response['status'] = true;
        }

        exit(json_encode($response));
    }

    public function menu_add($name) {
        if (isset($name)) {
            $this->SiteMenu->save(
                array(
                     "id"   => '',
                     'name' => $name
                )
            );
            $this->redirect($this->referer());
        }

    }

    public function menu_del($id) {
        if (isset($id) && is_numeric($id) ) {
            if ($this->SiteMenu->delete($id)) {
                $this->Menu->deleteAll(array("site_menu_id" => $id));
                $this->_setFlash('Menu successfully deleted', 'success');
            } else {
                $this->_setFlash('Errors occurred', 'error');
            }

            $this->redirect("index");
        }

    }

    function activate($model, $id)
    {
        if(!$id && !$this->data) {
            $this->_setFlash('Invalid Page', 'error');
            // TODO show error ?
            $this->render('empty');
        } else {
            $this->$model->id = $id;
        }

        $active = $this->$model->read('active');
        if($active["{$model}"]['active'] == 1) {
            $this->$model->saveField('active', 0);
        } else {
            $this->$model->saveField('active', 1);
        }

        $this->redirect($this->referer());
    }

}