<?php

/**
 * @property User $User
 */

class AdminUsersController extends AdminAppController
{
	public $name = 'AdminUsers';
    public $uses = array('User');

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_setLeftMenu('users');
		$this->_setHoverFlag('users');
	}

	function index()
	{
		$this->_setHoverFlag('manage');
		$paginate = array(
			'fields'  => array(
				'id', 'username'
			)
		);
		$this->paginate = array_merge($this->paginate, $paginate);
		$items = $this->paginate();

		$this->set('items', Set::extract('/User/.', $items));

	}

	function edit($id = null)
	{
		if(!$id && !$this->data) {
			$this->_setFlash('Invalid User ID', 'error');
			$this->render('empty');
		} else {
			$this->User->id = $id;
		}

		if($this->data) {

			// to prevent empty password saving  4d96f0dd6e8947d6e55e7f116093828bb831b69a
			if (empty($this->data['User']['password'])) {
			    unset($this->data['User']['password']);
			}

			if($this->User->save($this->data)) {
				$this->_setFlash('User data successfully edit', 'success');
                $this->redirect("index");
			} else {
				$this->_setFlash('Errors occured, please see below', 'error');
			}

			$this->data['User']['password'] = '';
		} else {
			$data = array();

			$data = $this->User->read(array(
					'id', 'username')
				);

			$this->data = $data;
		}
	}

	function delete($id)
	{
		if($this->User->delete($id)) {
			$this->_setFlash('User successfully deleted', 'success');
		} else {
			$this->_setFlash('User has not been removed', 'error');
		}

		$this->redirect($this->referer());
	}
}