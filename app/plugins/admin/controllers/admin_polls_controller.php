<?php
/**
 * Used models
 * @property User $User
 * @property UserPoll $UserPoll
 * @property PollQuestion $PollQuestion
 * @property PollAnswer $PollAnswer
 *
 * Used components
 * @property RequestHandlerComponent $RequestHandler
 */
class AdminPollsController extends AdminAppController
{
    public $name = 'AdminPolls';
    public $uses = array('User', 'Poll', 'UserPoll', 'PollQuestion', 'PollAnswer');
    public $components = array('RequestHandler');

    public function beforeFilter()
    {
        parent::beforeFilter();

        $this->_setLeftMenu('community');
        $this->_setHoverFlag('community');
    }

    public function index()
    {
        $this->_setHoverFlag('manage');
//        $this->PollQuestion->Behaviors->detach(array('Containable'));
        $data = $this->PollQuestion->find('all');
        $this->set('pollQuestions', $data);

    }

    private function _saveData(&$id = null) {
        $status = false;
        if ($this->data) {
            $data = $this->data;
            $status = true;
            foreach ($data as $model => &$fields) {
                if (empty($data[$model]['id'])) {
                    $this->{$model}->create(array($model => $fields));
                } else {
                    $this->{$model}->id = $fields['id'];
                    unset($fields['id']);
                    $this->{$model}->set(array($model => $fields));
                }

                if ($this->{$model}->save()) {
                    $id = $this->{$model}->id;
                    $this->_setFlash($model. ' successfully saved.', 'success');
                } else {
                    $status[] = $this->{model}->validationsErrors();
                    $this->_setFlash($model . ' save errors', 'error');
                }
            }
        }
        return $status;
    }

    public function add_question() {
        if ($this->RequestHandler->isAjax()) {
            Configure::write('debug', 0);
            $this->layout = false;
            $this->autoRender = false;
            $id = null;
            $status = $this->_saveData($id);
            if (!is_array($status) && $status) {
                exit(json_encode(array('status'=> $status, 'id' => $id)));
            } else {
                exit(json_encode($status));
            }
        }
    }

    public function edit_question($id = null) {
        if ($this->RequestHandler->isAjax()) {
            Configure::write('debug', 0);
            $this->layout = false;
            $this->autoRender = false;
            if ($this->RequestHandler->isGet()) {
                if (!is_null($id)) {
                    $out = array();
                    foreach($this->languages as $key => $lang) {
                        $this->PollQuestion->locale = $key;
                        $data = $this->PollQuestion->read(array('q_text'), $id);
                        $out[$key] = $data['PollQuestion']['q_text'];
                    }
                    exit(json_encode($out));
                }
            } elseif ($this->RequestHandler->isPost()) {
                $status = $this->_saveData();
                if (!is_array($status) && $status) {
                    exit(json_encode(array('status'=> $status, 'id' => $id)));
                } else {
                    exit(json_encode($status));
                }
            }
        }
    }

    public function questions($id = null) {
        if (is_null($id)) {
            $this->redirect($this->referer);
        } else {
            $data = $this->PollQuestion->find('first', array('conditions' => array('PollQuestion.id' => $id)));
            $this->set('pollAnswers', $data);
        }
    }

    public function add_answer() {
        if ($this->RequestHandler->isAjax()) {
            Configure::write('debug', 0);
            $this->layout = false;
            $this->autoRender = false;
            $id = null;
            $status = $this->_saveData($id);
            if (!is_array($status) && $status) {
                exit(json_encode(array('status'=> $status, 'id' => $id)));
            } else {
                exit(json_encode($status));
            }
        }
    }

    public function edit_answer($id = null) {
        if ($this->RequestHandler->isAjax()) {
            Configure::write('debug', 0);
            $this->layout = false;
            $this->autoRender = false;
            if ($this->RequestHandler->isGet()) {
                if (!is_null($id)) {
                    $out = array();
                    foreach($this->languages as $key => $lang) {
                        $this->PollAnswer->locale = $key;
                        $data = $this->PollAnswer->read(array('a_text'), $id);
                        $out[$key] = $data['PollAnswer']['a_text'];
                    }
                    exit(json_encode($out));
                }
            } elseif ($this->RequestHandler->isPost()) {
                $id = null;
                $status = $this->_saveData($id);
                if (!is_array($status) && $status) {
                    exit(json_encode(array('status'=> $status, 'id' => $id)));
                } else {
                    exit(json_encode($status));
                }
            }
        }
    }


}

?>