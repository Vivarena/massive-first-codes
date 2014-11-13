<?php
/**
 * @property NewsletterTemplate $NewsletterTemplate
 */
class AdminEmailTemplatesController extends AdminAppController
{
    public $name = 'AdminEmailTemplates';
    public $uses = array('NewsletterTemplate');

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_setLeftMenu('config');
    }

    public function index()
    {
        $this->_setHoverFlag('manage');
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $this->data = Set::extract( '/NewsletterTemplate/.', $this->paginate('NewsletterTemplate') );
//        $this->data = $this->NewsletterTemplate->find('all');
    }

    public function view($id = null)
    {

    }

    public function add($id = null)
    {
        $this->_setHoverFlag('add');

        $this->render('view');
    }

    public function edit($id = null)
    {
        $this->render('view');
    }

    private function _saveData()
    {

    }

}

?>