<?php
/**
 *
 * @author Alex Bakum
 * @created 25-01-2011
 *
 * @property Event $Event
 * @property EventCategory $EventCategory
 */


class EventsController extends AppController
{
    public $name = 'Events';
    public $uses = array('EventCategory', 'Event');

    public function beforeFilter()
    {

        parent::beforeFilter();

        $this->myId = $this->Session->read('Auth.User.id');
    }

    public function afterFilter() {
        parent::afterFilter();
    }

    private function _setCategoryNav() {

        $this->EventCategory->locale = SiteConfig::read('Config.language');

        $data = $this->EventCategory->find('list', array('fields'=>array('title')));
        $this->set('event_cats', $data);
    }

    public function index()
    {
        $this->layout = 'community';

        $this->Event->locale = false;
        $data = $this->Event->find('all', array('order'=>array('Event.id'=>'DESC')));
        $this->set('events', $data);

    }
     //TODO: This need fixed, data need show from DB !!!
    public function view($id)
    {
        App::import('Vendor', 'php-excel-reader/excel_reader2');
        $this->layout = 'community';
        $this->Event->locale = false;
        $data = $this->Event->findById($id);
        if(file_exists((WWW_ROOT .$data['Event']['result_path'])) && is_file(WWW_ROOT .$data['Event']['result_path']))
        {
            $exelData = new Spreadsheet_Excel_Reader();
            $exelData->read((WWW_ROOT .$data['Event']['result_path']));
            if(isset($exelData->sheets[0]['cells']))
            {
                $excel = $exelData->sheets[0]['cells'];
            }
            else
            {
                $excel = false;

            }
            $this->set('data', $excel );
        }

        $this->set('event', $data);
    }

}

?>