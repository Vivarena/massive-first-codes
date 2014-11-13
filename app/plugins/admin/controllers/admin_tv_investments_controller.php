<?php
/**
 *
 * @author Mike S.
 * @created 18-05-2012
 *
 * @property TvInvestment $TvInvestment
 * @property TvInvestmentCategory $TvInvestmentCategory
 */
class AdminTvInvestmentsController extends AdminAppController
{
    public $name = 'AdminTvInvestments';
    public $helpers = array('Youtube');

    public $uses = array('TvInvestmentCategory', 'TvInvestment');

    public function beforeFilter()
    {
        parent::beforeFilter();

        $this->_setLeftMenu('tv');
    }

    public function index()
    {
        $this->_setHoverFlag('tv_investment');
        $data = $this->paginate('TvInvestment');
        $this->set('items', $data);
    }

    public function index_categories() {
        $this->_setHoverFlag('category');

        $this->TvInvestmentCategory->locale = false;
        $data = $this->TvInvestmentCategory->find('all');

        $this->set('items', Set::extract('/TvInvestmentCategory/.', $data));

    }

    public function add_category() {
        if ($this->data){
            if ($this->TvInvestmentCategory->save($this->data))
                $this->_setFlash('TV category successfully added.', 'success');
            else
                $this->_setFlash('Error occured while adding TV category.', 'error');
        }

        $this->redirect($this->referer());
    }

    public function read_category($id = null) {
        $this->layout = null;
        $this->autoRender = false;

        Configure::write('debug', 0);

        $response = array( 'status' => false );

        $data = $this->TvInvestmentCategory->getItem($id);
        if($data) {
            $response = $data['TvInvestmentCategory'];
            $response['status'] = true;
        }

        exit(json_encode($response));
    }

    public function edit_category($id = null) {
        if ($this->data && $id) {
            $this->TvInvestmentCategory->id = $id;
            if ($this->TvInvestmentCategory->save($this->data))
                $this->_setFlash('TV category successfully edit.', 'success');
            else
                $this->_setFlash('Error occured while updating TV category.', 'error');

            $this->redirect('index');
        }
    }

    public function add() {
        if($this->data) {
            $data = $this->data;
            $data['TvInvestment']['date_release'] = date('Y/m/d', strtotime($data['TvInvestment']['date_release']));
            $this->TvInvestment->create();
            if (!$this->TvInvestment->save($data)) {
                $this->_setFlash('Error occured while saving.', 'error');
            } else {
                $this->_setFlash('TV successfully created', 'success');
                $this->redirect('/admin/admin_tv_investments/');
            }

        } else {
            $this->_setHoverFlag('add_tv');
            $data = $this->TvInvestmentCategory->find('list');
            $this->set('TvInvestmentCategory', $data);
        }
    }

    public function edit($id = null) {

        $data = $this->TvInvestmentCategory->find('list');
        $this->set('TvInvestmentCategory', $data);

        if ($this->data) {
            //$this->Event->id = $id;
            if (isset($this->data['TvInvestment']['date_release'])) $this->data['TvInvestment']['date_release'] = date('Y/m/d', strtotime($this->data['TvInvestment']['date_release']));
            if (!$this->TvInvestment->save($this->data))
                $this->_setFlash('Error occured while saving.', 'error');
            else {
                $this->_setFlash('TV successfully edited.', 'success');
                $this->redirect('index');
            }
        } else {
            $data = array();
            $this->TvInvestment->locale = false;
            $data = $this->TvInvestment->read(null, $id);

            $this->TvInvestment->i18dataLoad(&$data, $this->languages, array('title', 'details', 'video_transcripts'));

            $this->data = $data;

            $this->render('add');
        }
    }


    function getYoutubeShot($size = 'small')
    {
        if($this->RequestHandler->isAjax()) {
            $videoURL = $this->params['form']['videoURL'];
            $this->set('sizeThumb', $size);
            $this->set('videoURL', $videoURL);
            exit($this->render('../elements/youtubeAjaxShot', 'ajax'));
        }
    }


    function delete($id)
    {
        if($this->TvInvestment->delete($id)) {
            $this->_setFlash('TV successfully deleted', 'success');
        } else {
            $this->_setFlash('TV has not been removed', 'error');
        }

        $this->redirect($this->referer());
    }
}

?>
