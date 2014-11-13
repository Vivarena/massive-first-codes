<?php
/**
 *
 * @author Alex Bakum
 * @created 24-01-2011
 *
 * @property Event $Event
 * @property EventCategory $EventCategory
 */
class AdminEventsController extends AdminAppController
{
    public $name = 'AdminEvents';

    public $uses = array('EventCategory', 'Event');

    public function beforeFilter()
    {
        parent::beforeFilter();

        $this->_setLeftMenu('event');
    }

    public function index()
    {
        $this->_setHoverFlag('events');
        $this->paginate = array(
            'order' => array('Event.id'=>'DESC')
        );
        $data = $this->paginate('Event');
        $this->set('items', $data);
    }

    public function index_categories() {
        $this->_setHoverFlag('category');

        $this->EventCategory->locale = false;
        $data = $this->EventCategory->find('all');

        $this->set('items', Set::extract('/EventCategory/.', $data));

    }

    public function add_category() {
        if ($this->data){
            if ($this->EventCategory->save($this->data))
                $this->_setFlash('Event category successfully added.', 'success');
            else
                $this->_setFlash('Error occured while adding event category.', 'error');
        }

        $this->redirect($this->referer());
    }

    public function read_category($id = null) {
        $this->layout = null;
        $this->autoRender = false;

        Configure::write('debug', 0);

        $response = array( 'status' => false );

        $data = $this->EventCategory->getItem($id);
        if($data) {
            $response = $data['EventCategory'];
            $response['status'] = true;
        }

        exit(json_encode($response));
    }

    public function edit_category($id = null) {
        if ($this->data && $id) {
            $this->EventCategory->id = $id;
            if ($this->EventCategory->save($this->data))
                $this->_setFlash('Event category successfully edit.', 'success');
            else
                $this->_setFlash('Error occured while updating event category.', 'error');

            $this->redirect('index');
        }
    }

    public function add() {
        if($this->data) {
            $data = $this->data;
            $data['Event']['date_start'] = date('Y/m/d', strtotime($data['Event']['date_start']));
            $data['Event']['date_end'] = date('Y/m/d', strtotime($data['Event']['date_end']));
            $this->Event->create();
            if (!$this->Event->save($data)) {
                $this->_setFlash('Error occured while saving.', 'error');
            } else {
                $this->_setFlash('Event successfully created', 'success');
                $this->redirect('/admin/admin_events/');
            }

        } else {
            $this->_setHoverFlag('add_event');
            $data = $this->EventCategory->find('list');
            $this->set('EventCategory', $data);
        }
    }

    public function edit($id = null) {

        $data = $this->EventCategory->find('list');
        $this->set('EventCategory', $data);

        if ($this->data) {

            $import = false;
            if($this->data['Event']['result_path'] && $this->data['Event']['result_path']['error'] == 0 ){
                $new_name = (time()).$this->data['Event']['result_path']['name'];
                if(move_uploaded_file($this->data['Event']['result_path']['tmp_name'], (WWW_ROOT . '/uploads/results/'.$new_name))){
                   // $result_path = '/uploads/results/' . $this->data['Event']['result_path']['name'];
                    $this->data['Event']['result_path'] = '/uploads/results/' . $new_name;
                    $import = true;

                }
                else{
                    unset($this->data['Event']['result_path']);
                }

            }
            else{
                unset($this->data['Event']['result_path']);
            }


            if (!$this->Event->save($this->data)){
                $this->_setFlash('Error occured while saving.', 'error');
            }
            else {
                if ($import)
                    $this->_setFlash('Results successfully imported.', 'success');
                else
                    $this->_setFlash('Event successfully edited.', 'success');
                $this->redirect($this->referer());
            }
        } else {
            $this->set('edit',true); // need for upload results
            $data = array();
            $this->Event->locale = false;
            $data = $this->Event->read(null, $id);

            $qqq = array();
            foreach($this->languages as $key=>$lang) {
                $this->Event->locale = $key;
                $l18nData = $this->Event->read(array('title', 'details'));
                $qqq['title'][$key] = $l18nData['Event']['title'];
                $qqq['details'][$key] = $l18nData['Event']['details'];
            }
            $data['Event']['title'] = $qqq['title'];
            $data['Event']['details'] = $qqq['details'];
            $this->data = $data;

            $this->render('add');
        }
    }

    function delete($id)
    {
        if($this->Event->delete($id)) {
            $this->_setFlash('Event successfully deleted', 'success');
        } else {
            $this->_setFlash('Event has not been removed', 'error');
        }

        $this->redirect($this->referer());
    }
}

?>
