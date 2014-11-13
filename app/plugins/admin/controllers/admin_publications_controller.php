<?php
/**
 * @property Publication  $Publication
 * @property PublicationCategory  $PublicationCategory
 *
 */

class AdminPublicationsController extends AdminAppController
{
    public $name = 'AdminPublications';
    public $uses = array("Publication", "PublicationCategory");
    public $helpers = array('Admin.ExtTree');
    public $components = array('RequestHandler');

    private $_langs = array(
        'eng' => 'English',
        'por' => 'Portuguese',
        'spa' => 'Spanish',
    );

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setLeftMenu('publications');


    }

    public function index()
    {

        $this->_setHoverFlag('publications');
        $this->set('categories', $this->PublicationCategory->getAllCategories());
        if($this->RequestHandler->isAjax()) {
            $this->render('../elements/publications/catTree');
        }

    }


    public function readPublication($id)
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $publication = $this->Publication->readPublication($id);
        if ($publication) {
            $publication = $publication['Publication'];
        } else {
            $err_desc = 'An error occurred when loading category info!';

        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'publication' => $publication,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    public function readPubsInCategory($idCat, $type = '')
    {

        $pubs = $this->Publication->readPubsByCategory($idCat);
        $this->set('idCat', $idCat);
        $this->set('pubs', $pubs);
        $this->render('../elements/publications/pubList'.$type);

    }

    public function savePublication($id = null)
    {
        if($this->params['url']['data']) {
            if ($this->_savePubs($this->params['url']['data'], $id)) {
                $idCat = $this->params['url']['data']['Publication']['publication_category_id'];
                $this->set('pubs', $this->Publication->readPubsByCategory($idCat));
                $this->set('idCat', $idCat);
            } else {
                $this->set('pubs', 'An error occurred!');
            }
        }
        $this->render('../elements/publications/pubList');

    }

    private function _savePubs($data, $id = null)
    {
        if (!empty($id) && is_numeric($id)) {
            $this->Publication->id = $id;
        }
        $this->_checkLangFields(&$data['Publication'], array('name', 'content'));
        return $this->Publication->save($data);
    }

    private function _checkLangFields(&$data, $fieldsNames)
    {
        array_shift($this->_langs);
        foreach($this->_langs as $key=>$language)
        {
            foreach($fieldsNames as $field){
                if (empty($data[$field][$key])) $data[$field][$key] = $data[$field]['eng'];
            }
        }
        return $data;
    }

    public function readCategory($id)
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $category = $this->PublicationCategory->readCategory($id);
        if ($category) {
            $category = $category['PublicationCategory'];
        } else {
            $err_desc = 'An error occurred when loading category info!';

        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'category' => $category,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    public function saveCategory($id = null)
    {
        if($this->params['url']['data']) {
            if ($this->_saveCategory($this->params['url']['data'], $id)) {
                $this->set('categories', $this->PublicationCategory->getAllCategories());
            } else {
                $this->set('categories', 'An error occurred!');
            }
        }
        if($this->RequestHandler->isAjax()) {
            $this->render('../elements/publications/catTree');
        }
    }

    private function _saveCategory($data, $id = null)
    {
        if (!empty($id) && is_numeric($id)) {
            $this->PublicationCategory->id = $id;
        }
        return $this->PublicationCategory->save($data);
    }

    public function editCategories()
    {
        $this->set('categories', $this->PublicationCategory->getAllCategories());
        $this->render('../elements/publications/catTreeEdit');
    }

    public function saveSortingCat()
    {

        if ($this->params['form']['items'] && !empty($this->params['form']['items'])){
            if ($this->PublicationCategory->saveSort($this->params['form']['items'])) {
                $this->set('categories', $this->PublicationCategory->getAllCategories());
            } else {
                $this->set('categories', 'An error occurred!');
            }
        }

        $this->render('../elements/publications/catTree');


    }

    public function deletePublication($id, $idCat)
    {
        $this->Publication->delete($id);
        $pubs = $this->Publication->readPubsByCategory($idCat);
        $this->set('idCat', $idCat);
        $this->set('pubs', $pubs);
        $this->render('../elements/publications/pubList');
    }

    public function deleteCategoryPub($id)
    {
        $this->PublicationCategory->delete($id);
        $this->Publication->deleteAll(array('Publication.publication_category_id' => $id), false);
        $this->set('categories', $this->PublicationCategory->getAllCategories());
        $this->render('../elements/publications/catTree');

    }

    function saveSorting($model)
        {
            Configure::write('debug', 0);
            $this->autoRender = false;
            $err = false;
            $err_desc = '';

            if (isset($this->params['form']['items']) && !empty($this->params['form']['items'])) {
                if (!($this->{$model}->saveSort($this->params['form']['items']))) $err_desc = 'An error occurred when saving items!';
            }


            if (!empty($err_desc)) $err = true;
            $result = array(
                'error' => $err,
                'err_desc' => $err_desc,
            );

            exit(json_encode($result));
        }

}

