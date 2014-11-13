<?php

/**
 * Created by CNR.
 * User: nike
 * Date: 17.06.11
 * Time: 13:03
 *
 * @property Page $Page
 *
 */

class AdminPagesController extends AdminAppController
{
	public $name = 'AdminPages';
    public $uses = array('Admin.Page');

	public function beforeFilter()
	{
		parent::beforeFilter();

		$this->_setLeftMenu('content');
		$this->_setHoverFlag('content');
	}

	function index()
	{
        $this->manage_static();
	}

	function add()
	{
		$this->_setHoverFlag('add');

		if($this->data) {

            /*foreach ($this->languages as $key=>$lang) {
                $this->data['Page']['content'][$key] = trim($this->data['Page']['content'][$key]);
                if (strlen($this->data['Page']['content'][$key]) < 10) {
                    $this->data['Page']['content'][$key] = '';
                }
            }

            foreach ($this->languages as $key=>$lang) {
                if ($this->data['Page']['content'][$key] == '') {
                    $this->data['Page']['content'][$key] = $this->data['Page']['content']['eng'];
                }
                if ($this->data['Page']['title'][$key] == '') {
                    $this->data['Page']['title'][$key] = $this->data['Page']['title']['eng'];
                }
            }*/

			$this->Page->create($this->data);

			$this->data['Page']['type'] = 'static';
			if($this->Page->save($this->data)) {
				$this->_setFlash('Page successfully added', 'success');
				$this->data = null;
                $this->redirect("index");
			} else {
				$this->_setFlash('Errors occured, please see below', 'error');
			}
		}
	}

	function edit($id = null)
	{
		if(!$id && !$this->data) {
			$this->_setFlash('Invalid Page', 'error');
			// TODO show error ?
			$this->render('empty');
		} else {
			$this->Page->id = $id;
		}

		if($this->data) {

			if($this->Page->save($this->data)) {
				$this->_setFlash('Page successfully edit', 'success');
                $this->redirect("index");
			} else {
				$this->_setFlash('Errors occured, please see below', 'error');
			}
		} else {
			$data = array();

			$this->Page->locale = false;
			$data = $this->Page->read(null, $id);

            $this->data = $data;
		}
        $this->render("add");
	}

	function delete($id)
	{
		if($this->Page->delete($id)) {
			$this->_setFlash('Page successfully deleted', 'success');
		} else {
			$this->_setFlash('Page has not been removed', 'error');
		}

		$this->redirect($this->referer());
	}

	function activate($id = null)
	{
		if(!$id && !$this->data) {
			$this->_setFlash('Invalid Page', 'error');
			// TODO show error ?
			$this->render('empty');
		} else {
			$this->Page->id = $id;
		}

		$active = $this->Page->read('active');
		if($active['Page']['active'] == 1) {
			$this->Page->saveField('active', 0);
		} else {
			$this->Page->saveField('active', 1);
		}

		$this->redirect($this->referer());
	}

	function manage_static()
	{
		$this->_manage_pages('static');
	}

	function manage_hidden()
	{
		$this->_manage_pages('hidden');
	}

	function _manage_pages($type)
	{
		$this->_setHoverFlag('manage_' . $type);

		if($type == 'static') {
			$type = array('static', 'service');
		}

		$paginate = array(
			'conditions' => array(
				'type' => $type
			),
			'fields'  => array(
				'id', 'title', 'key', 'active', 'modified'
			),
			'order' => 'created'
		);
		$this->paginate = array_merge($this->paginate, $paginate);
        $this->paginate['limit'] = 20;
		$items = $this->paginate('Page');

		$this->set('type', $type);
		$this->set('items', Set::extract('/Page/.', $items));

		$this->render('manage');
	}

    public function files() {

    }

    public function duplicate($id = null) {
        $this->Page->locale = false;
        extract($this->Page->read(null, $id));

        foreach($this->languages as $key=>$language)
        {
            $this->Page->locale = $key;
            $l18nData = $this->Page->read(array('title', 'content'));
            $Page['title'][$key] = $l18nData['Page']['title'];
            $Page['content'][$key] = $l18nData['Page']['content'];
        }
        unset ($Page['created']); unset ($Page['modified']); unset ($Page['id']);

        $Page['key'] .= '-copy';

        $this->Page->create();
        if ($this->Page->save($Page)) {
            $this->_setFlash('Page has been duplicated', 'success');
        } else {
            $this->_setFlash('Error occured while duplicating', 'error');
        }

        $this->redirect($this->referer());
    }
}
