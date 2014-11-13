<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 22.12.10
 * Time: 12:43
 * @property Banner $Banner
 */

class AdminBannersController extends AdminAppController
{
	public $name = 'AdminBanners';
    public $uses = array('Banner');

    public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_setLeftMenu('content');
        $this->_setHoverFlag('content');
		$this->_setHoverFlag('banner');
	}

    public function index()
    {
        if ($this->data) {
            $this->_saveData();
        }
        $var = $this->Banner->find('all',
            array(
                "order" => "lft"
            )
        );
        $this->set('delaySlides', SiteConfig::read('Slides.delay'));
        $this->set('items', Set::extract('/Banner/.', $var));
    }

    private function _saveData()
    {
        $imageTypes = array(
            'image/jpeg',
            'image/gif',
            'image/png'
        );
        if (isset($this->data['Banner']['url']['tmp_name'])
            && !empty($this->data['Banner']['url']['tmp_name'])
            && isset($this->data['Banner']['title'])
            && !empty($this->data['Banner']['title'])
        ) {
            if (in_array($this->data['Banner']['url']['type'], $imageTypes)) {
                $fileExt = end(explode('.', $this->data['Banner']['url']['name']));
                $fileName = md5(mktime()) . '.' . $fileExt;
                if (move_uploaded_file($this->data['Banner']['url']['tmp_name'], WWW_ROOT . 'uploads' . DS . 'banners' . DS . $fileName)) {
                    $this->data['Banner']['url'] = '/uploads' . DS . 'banners' . DS . $fileName;
                    $this->data['Banner']['active'] = 1;
                    if ($this->Banner->save($this->data)) {
                        $this->_setFlash('Upload success', 'success');
                        $this->redirect($this->referer());
                    }
                }
            } else {
                $this->_setFlash('It is not supported Image format', 'error');
            }
        }
        $this->_setFlash('Unknown error. May be you enter incorrect data', 'error');
    }

    public function add()
    {
        if(!empty($this->data))
        {
            $this->_saveData();
                $this->_setFlash('Banner successfully added', 'success');
                $this->redirect("index");
        }
        $this->set('bannerPlaces', $this->bannerPlaces);
    }

    public function edit()
    {
        if($this->data)
        {
            if ($this->data['Banner']['url']['error'] != 0 &&
                isset($this->data['Banner']['url_old']) &&
                !empty($this->data['Banner']['url_old'])
            ) {
                $this->data['Banner']['url'] =  $this->data['Banner']['url_old'];
                unset($this->data['Banner']['url_old']);
                $this->data['Banner']['active'] = 1;
                if ($this->Banner->save($this->data)) {
                    $this->_setFlash('Upload success', 'success');
                    $this->redirect($this->referer());
                } else {
                    $this->_setFlash('Unknown error. May be you enter incorrect data', 'error');
                }
            } else {
                $this->_saveData();
            }
        }
        $this->redirect("/admin/admin_banners");
    }

    public function editBanner($id = null)
    {
        $this->layout = "default_1";


        if ($this->data) {
            if (isset($this->data['Banner']['areas']) && !empty($this->data['Banner']['areas'])) {
                $this->data['Banner']['areas'] = serialize($this->data['Banner']['areas']);
            }
            $this->data['Banner']['active'] = 1;
            if ($this->Banner->save($this->data)) {
                $this->_setFlash('Saving successfully', 'success');
                $this->redirect('/admin/admin_banners');
            } else {
                $this->_setFlash('Unknown error. May be you enter incorrect data', 'error');
            }
        }

        $this->data = $this->Banner->read(null, $id);
        if( $this->data) {
            $titleLang = $this->Banner->i18dataLoad(& $this->data, $this->languages, array('title'));
            $this->data['Banner']['title'] = $titleLang['Banner']['title'];
            if (!empty($this->data['Banner']['areas'])) {
                $this->data['Banner']['areas'] = unserialize($this->data['Banner']['areas']);
            }
        }

        $this->set('action', 'Edit');

    }

    function updateDelay()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';

        if (isset($this->data['delay']) && !empty($this->data['delay'])) {
            SiteConfig::invalidate();
            $this->loadModel('Config');
            $getID = $this->Config->find('first', array(
                'conditions' => array('Config.group' => 'Slides', 'Config.key' => 'delay'),
                'fields' => array('Config.id')
            ));
            $getID = ($getID) ? $getID['Config']['id'] : false;
            if ($getID) {
                $this->Config->id = $getID;
                if (!$this->Config->saveField('value', $this->data['delay'])) {
                    $err_desc = 'An error occurred when saving data!';
                }
            }
        } else {
            $err_desc = 'No delay!';
        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    public function ajaxDelete($id)
    {

        if(empty($id) && !is_numeric($id))
        {
            return false;
        }

        if($this->Banner->delete($id))
        {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            Configure::write("debug", 0);
            exit(json_encode(array('status'=> true)));
        }

        return false;

    }

    public function ajaxRead($id)
    {
        $this->layout = null;
        $this->autoRender = false;

        Configure::write('debug', 0);

        $response = array( 'status' => false );

        $data = $this->Banner->read(null, $id);

        if($data) {
            $titleLang = $this->Banner->i18dataLoad(&$data, $this->languages, array('title'));
            $data['Banner']['title'] = $titleLang['Banner']['title'];
            $response = $data['Banner'];
            $response['status'] = true;
        }

        exit(json_encode($response));
    }

    public function moveUp($id)
    {
        $this->Banner->moveUp($id, 1);
        $this->redirect($this->referer());
    }

    public function moveDown($id)
    {
        $this->Banner->moveDown($id, 1);
        $this->redirect($this->referer());
    }
}