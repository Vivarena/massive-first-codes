<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 18.06.11
 * Time: 23:58
 *
 * @property Faq          $Faq
 * @property GroupFaq $GroupFaq
 *
 */
 
class AdminFaqsController extends AdminAppController{
    public $name = "AdminFaqs";
    public $uses = array("Faq");


    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setLeftMenu('content');
        $this->_setHoverFlag('faq');

    }

    public function beforeRender() {
        parent::beforeRender();
    }

    function index()
    {
        $data = $this->Faq->getFaqs();
        $this->set('items', $data);

    }

    function add()
    {

        $this->_setHoverFlag('add_faq');
        if($this->data) {
            foreach ($this->languages as $key=>$lang) {
                $this->data['Faq']['answer'][$key] = trim($this->data['Faq']['answer'][$key]);
                if (strlen($this->data['Faq']['answer'][$key]) < 10) {
                    $this->data['Faq']['answer'][$key] = '';
                }
            }

            foreach ($this->languages as $key=>$lang) {
                if ($this->data['Faq']['quention'][$key] == '') {
                    $this->data['Faq']['quention'][$key] = $this->data['Faq']['quention']['eng'];
                }
                if ($this->data['Faq']['answer'][$key] == '') {
                    $this->data['Faq']['answer'][$key] = $this->data['Faq']['answer']['eng'];
                }
            }

            if($this->Faq->save($this->data)) {
                $this->_setFlash('News successfully added', 'success');
                $this->data = null;
                $this->redirect("index");
            }
            $this->_setFlash('Errors occured, please see below', 'error');
        }


    }

    function edit($id = null)
    {
        if(!$id && !$this->data) {
            $this->_setFlash('Invalid Faq ID', 'error');
            $this->render('empty');
        } else {
            $this->Faq->id = $id;
        }

        if($this->data) {
            foreach ($this->languages as $key=>$lang) {
                $this->data['Faq']['answer'][$key] = trim($this->data['Faq']['answer'][$key]);
                if (strlen($this->data['Faq']['answer'][$key]) < 10) {
                    $this->data['Faq']['answer'][$key] = '';
                }
            }

            foreach ($this->languages as $key=>$lang) {
                if ($this->data['Faq']['quention'][$key] == '') {
                    $this->data['Faq']['quention'][$key] = $this->data['Faq']['quention']['eng'];
                }
                if ($this->data['Faq']['answer'][$key] == '') {
                    $this->data['Faq']['answer'][$key] = $this->data['Faq']['answer']['eng'];
                }
            }
            if($this->Faq->save($this->data)) {
                $this->_setFlash('Faq successfully added', 'success');
                $this->data = null;
                $this->redirect("index");

            } else {
                $this->_setFlash('Errors occured, please see below', 'error');
            }
        }
        if(!$this->data)
        {
            $data = array();
            $qqq = array();
            $this->Faq->locale = false;
            $data = $this->Faq->read(null, $id);
            $this->Faq->i18dataLoad(&$data, $this->languages, array('quention', 'answer'));

            $this->data = $data;

        }
        $this->render('add');
    }

    function delete($id)
    {
        if($this->Faq->delete($id)) {
            $this->_setFlash('Faq successfully deleted', 'success');
        } else {
            $this->_setFlash('Faq has not been removed', 'error');
        }

        $this->redirect('index');
    }

}
