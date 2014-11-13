<?php
/**
 * @author Nike
 * @property Subscriber           $Subscriber
 * @property Subgroup             $Subgroup
 * @property EmailComponent  $Email
  */
class AdminSubscribersController extends AdminAppController
{
    public $name = 'AdminSubscribers';
    public $uses = array('Subscriber', 'Subgroup');
    public $components = array('Email');

    public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_setLeftMenu('subscribers');
		$this->_setHoverFlag('subscribers');
	}

    function index()
    {
        
        $this->paginate["conditions"] = array();
        if($this->data)
        {
            if(!empty($this->data["Subscriber"]["email"])) {
                $this->paginate["conditions"] = array_merge(
                    $this->paginate["conditions"],
                    array("Subscriber.email LIKE '%{$this->data["Subscriber"]["email"]}%'")
                );
            }

            if(!empty($this->data["Subscriber"]["subgroup_id"])) {
                $this->paginate["conditions"] = array_merge(
                    $this->paginate["conditions"],
                    array("Subscriber.subgroup_id" => $this->data["Subscriber"]["subgroup_id"])
                );
            }
        }

        $this->paginate['order'] = 'Subscriber.id DESC';
        $this->set('items', $this->paginate('Subscriber'));
        $this->set('groups', $this->Subgroup->find('list'));
    }

    public function add() {
        if ($this->data) {
            if (
                isset($this->data['Subscriber']['name']) &&
                !empty($this->data['Subscriber']['name']) &&
                isset($this->data['Subscriber']['email']) &&
                !empty($this->data['Subscriber']['email']) &&
                isset($this->data['Subscriber']['subgroup_id']) &&
                !empty($this->data['Subscriber']['subgroup_id'])
            ) {
                if ($this->Subscriber->save($this->data)) {
                    $this->_setFlash('Subscriber successfully added', 'success');
                } else {
                    $this->_setFlash('Database error. Please try later', 'error');
                }
            } else {
                $this->_setFlash('Unknown error. May be you enter incorrect data', 'error');
            }
        }
        $this->redirect('index');
    }

    public function edit($id = null)
    {
        if($this->data) {
            if (
                isset($this->data['Subscriber']['name']) &&
                !empty($this->data['Subscriber']['name']) &&
                isset($this->data['Subscriber']['email']) &&
                !empty($this->data['Subscriber']['email']) &&
                isset($this->data['Subscriber']['subgroup_id']) &&
                !empty($this->data['Subscriber']['subgroup_id'])
            ) {
                if ($this->Subscriber->save($this->data)) {
                    $this->_setFlash('Subscriber successfully added', 'success');
                } else {
                    $this->_setFlash('Database error. Please try later', 'error');
                }
            } else {
                $this->_setFlash('Unknown error. May be you enter incorrect data', 'error');
            }
            $this->redirect("/admin/admin_subscribers");
        }

    }

    function groups()
    {
        $this->paginate['order']    = 'Subgroup.title ASC';
        $this->paginate['fields']   = array('id', 'title');
        $this->set('items', $this->paginate('Subgroup'));
    }

    function groupadd()
    {
        if($this->data)
        {
            if($this->Subgroup->save($this->data)) {
                $this->_setFlash("Category successfully added!", "success");

                $this->redirect('groups');
            }
        }
    }

    function groupedit($id=null)
    {
        if($this->data)
        {
            if($this->Subgroup->save($this->data)) {
                $this->_setFlash("Category successfully added!", "success");

                $this->redirect('groups');
            }
        }
        $this->Subgroup->id = $id;
        $this->data = $this->Subgroup->read(array('Subgroup.id', 'Subgroup.title'));
        $this->render("groupadd");


    }

    function sendgroups()
    {
        if($this->data)
        {
            $subject = $this->data['Subgpoup']['subject'];
            $message = $this->data['Subgpoup']['message'];
            $users = array();
            foreach($this->data['Subgpoup']['id'] as $key=>$data)
            {
                if($data != 0)
                {
                    $tmp = $this->Subscriber->find('all', array(
                        'conditions' => array(
                            'Subscriber.subgroup_id' => $key
                        )
                    ));
                    $tmp = Set::extract('/Subscriber/email', $tmp);
                    foreach($tmp as $t)
                    {
                        $users[] = $t;
                    }
                }
            }
            $result = $this->send($users, $subject, $message);
            if(!$result) {
                $this->_setFlash('Message send', 'success');
            } else {
                $this->_setFlash('Error', 'error');
            }
        }

            $datas = $this->Subgroup->find('all');
            $arr = array();
            foreach($datas as $data)
            {
                if($this->Subscriber->find('first', array('conditions' => array('subgroup_id'=>$data['Subgroup']['id']))))
                {
                    $arr[] = $data;
                }

            }
            $this->set('items', $arr);

    }

    function sendusers()
    {
        if($this->data)
        {
            $subject = $this->data['Subgpoup']['subject'];
            $message = $this->data['Subgpoup']['message'];
            $users = array();
            foreach($this->data['Subgpoup']['id'] as $key=>$data)
            {
                if($data != 0)
                {
                    $tmp = $this->Subscriber->find('all', array(
                        'conditions' => array(
                            'Subscriber.id' => $key
                        )
                    ));
                    $tmp = Set::extract('/Subscriber/email', $tmp);
                    foreach($tmp as $t)
                    {
                        $users[] = $t;
                    }
                }
            }

            $result = $this->send($users, $subject, $message);
            if(!$result) {
                $this->_setFlash('Message send', 'success');
            } else {
                $this->_setFlash('Error', 'error');
            }
        }
            $datas = $this->Subscriber->find('all');
            $this->set('items', $datas);
    }

    function send($array, $subject="", $message="")
    {
        $fail = array();
        foreach($array as $user)
        {
            $this->Email->to = $user;
            $this->Email->from = SUPPORTEMAIL;
            $this->Email->subject = $subject;
            $this->Email->sendAs = 'html';

            if(!$this->Email->send($message))
            {
                $fail[] = $user;
            }
        }
        return $fail;
    }



        function export_csv()
        {
            $data = $this->Subscriber->find('all');
            $data = Set::extract('/Subscriber/email', $data);
            $buffer =  fopen("php://output", 'w');
            fputcsv($buffer, $data);
            fclose($buffer);
            header("Content-type:application/vnd.ms-excel");
            header("Content-disposition:attachment;filename=Subscriber.csv");
            die;
        }

        function import_csv()
        {
            
            if(!empty($this->data["Subscriber"]["file"]["tmp_name"])) {

                if($this->data["Subscriber"]["file"]["type"] == 'text/csv')
                {
                    $error              = false;
                    $data               = array();
                    $data["name"]       = $this->data["Subscriber"]["file"]["name"];

                    $dir = WWW_ROOT . "uploads" . DS . "csv";
                    if(!is_dir($dir)) {
                        mkdir($dir, 0777);
                    }

                    $path = $dir . DS . $data["name"];

                    if(move_uploaded_file($this->data["Subscriber"]["file"]["tmp_name"], $dir . DS . $data["name"])) {
                        $csv = fopen($path, 'r');
                        $datas = fgetcsv($csv, 1000, ",");
                        fclose($csv);
                        foreach($datas as $data)
                        {
                            if(!$this->Subscriber->findByEmail($data))
                            {
                                $this->Subscriber->create();
                                $this->Subscriber->save(array('email' => $data));
                            }
                        }
                    }
                    $this->Session->setFlash('Import OK');
                    $this->redirect($this->referer());
                }
                $this->Session->setFlash('Error! It is not a CSV file');
                $this->redirect($this->referer());
            }

            $this->redirect($this->referer());
            
        }


    public function ajaxRead($id) {
        $this->layout = null;
        $this->autoRender = false;

        Configure::write('debug', 0);

        $response = array( 'status' => false );

        $data = $this->Subscriber->read(null, $id);
        if($data) {
            $response = $data['Subscriber'];
            $response['status'] = true;
        }

        exit(json_encode($response));
    }

    public function ajaxReadGroup($id) {
        $this->layout = null;
        $this->autoRender = false;

        Configure::write('debug', 0);

        $response = array( 'status' => false );

        $data = $this->Subgroup->read(null, $id);
        if($data) {
            $response = $data['Subgroup'];
            $response['status'] = true;
        }

        exit(json_encode($response));
    }

    public function ajaxDelSubscriber($id) {
        $this->layout = null;
        $this->autoRender = false;

        Configure::write('debug', 0);

        if (!empty($id)) {
            if($this->Subscriber->delete($id))
            {
                exit('okey');
            }
        }
        return false;
    }

    function ajaxDelGroup($id) {
        $this->layout = null;
        $this->autoRender = false;

        Configure::write('debug', 0);

        if(isset($id) && is_numeric($id))
        {
            if($this->Subgroup->delete($id))
            {
                exit('okey');
            }
        }
        return false;
    }

        

}