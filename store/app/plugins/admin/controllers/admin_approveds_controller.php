<?php
/**
 * Created by NikedLab
 * User: nike
 * Date: 23.09.11
 * Time: 15:43
 *
 * @property Approved $Approved
 *
 */ 
class AdminApprovedsController extends AdminAppController {
    public $name = "AdminApproveds";
    public $uses = array("Approved");

    public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_setLeftMenu('product');
	}

    public function index()
    {
        $var = $this->Approved->find('all',
            array(
                "order" => "lft"
            )
        );
        $this->set('items', Set::extract('/Approved/.', $var));
    }

    public function add() {
        $this->_saveData();
    }

    public function edit($id) {
        $this->_saveData();
        if (isset($id) && is_numeric($id)) {
            $this->data = $this->Approved->read(null, $id);
            $this->render("add");
        } else {
            $this->redirect("index");
        }
    }

    private function _saveData()
    {
        if ($this->data) {
            if (substr($this->data['Approved']['link'], 0, 7) != "http://") {
                $this->data['Approved']['link'] = 'http://' . $this->data['Approved']['link'];
            }
            if($this->Approved->save($this->data)) {
                $this->_setFlash('Item successfully edited', 'success');
                $this->redirect("index");
            } else {
                $this->_setFlash('Errors occured, please see below', 'error');
            }

        }
    }

    public function ajaxDelete($id)
    {
        Configure::write("debug", 0);
        if(empty($id) && !is_numeric($id))
        {
            return false;
        }

        if($this->Approved->delete($id))
        {
            exit("okey");
        }

    }
}
