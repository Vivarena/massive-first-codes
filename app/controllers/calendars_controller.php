<?php
/**
 * @property User $User
 * @property CalendarEvent $CalendarEvent
 * @property UserCalendar $UserCalendar
 */
class CalendarsController extends AppController
{
    public $name = 'Calendars';
    private $myId;
    public $uses = array('CalendarEvent', 'UserCalendar','User');
    public $components = array('Email');

    public function beforeFilter()
    {
        $this->layout = 'community';
        $this->myId = $this->Session->read('Auth.User.id');
    }

    public function index()
    {
        $getCalEvents = $this->UserCalendar->getUserEvents($this->myId);
        $allIn = array();

        foreach($getCalEvents as $cal) {

            if(!$cal['CalendarEvent']['subject'])
                $noteTitle = substr($cal['CalendarEvent']['description'], 0, 50);
            else
                $noteTitle = substr($cal['CalendarEvent']['subject'], 0, 50);

            $noteId = $cal['CalendarEvent']['id'];
            $noteDesc =$cal['CalendarEvent']['description'];
            $noteStart = $cal['CalendarEvent']['start_date'];
            $noteSEnd = $cal['CalendarEvent']['end_date'];
            $noteStartMk = strtotime($noteStart);
            $todayMk = strtotime(date("Y-m-d 00:00:00"));
            $allDay = ($cal['CalendarEvent']['all_day'] == 1) ? true : false;
            $noteEdit = ($noteStartMk < $todayMk) ? false : true;

            $allIn[] = array(
                'id' => $noteId,
                'title' => $noteTitle,
                'description' => $noteDesc,
                'start' => $noteStart,
                'end' => $noteSEnd,
                'allDay' => $allDay,
                'editable' => $noteEdit
            );
        }

        Configure::load('google');
        $this->set(array(
            'googleSettings' => Configure::read('GoogleCalendar'),
            'events' => json_encode($allIn),
            'friendsList' => ClassRegistry::init('UserFriend')->listView()->getMyFriends($this->myId)
        ));
    }

    public function editEvent()
    {
        $data = (isset($this->data['CalendarEvent'])) ? $this->data : null;
        $forSave = array();
        if (!empty($data)) {
            $idCal = (int)$data['CalendarEvent']['id'];
            if (!empty($idCal) && $this->CalendarEvent->checkOwner($this->myId, $idCal)) {
                $forSave['CalendarEvent']['id'] = $idCal;
                $forSave['CalendarEvent']['user_id'] = $this->myId;
                $forSave['CalendarEvent']['description'] = strip_tags($data['CalendarEvent']['description']);
                $forSave['CalendarEvent']['subject'] = strip_tags($data['CalendarEvent']['subject']);
                if ($this->CalendarEvent->save($forSave)) {
                    $this->ajaxResponse['shortDesc'] = substr($forSave['CalendarEvent']['subject'], 0, 50);
                    $getFrId = ClassRegistry::init('UserFriend')->getFriendsId($this->myId);
                    $toUserCal = array();
                    $allowedSend = array();
                    if($data['CalendarEvent']['all'] == 1)
                        $data['Calendar']['users'] = $getFrId;
                    foreach ($data['Calendar']['users'] as $i => $uId) {
                        if (in_array($uId, $getFrId)) {
                            $toUserCal[$i]['UserCalendar']['calendar_event_id'] = $idCal;
                            $toUserCal[$i]['UserCalendar']['user_id'] = $uId;
                            $toUserCal[$i]['UserCalendar']['status'] = 0;
                            $allowedSend[] = $uId;
                        }
                    }
                    if (!empty($toUserCal) && $this->UserCalendar->saveAll($toUserCal, array('validate' => false))) {
                        $this->_sendInboxMessage(
                            $allowedSend,
                            array(
                                'subject' => $data['CalendarEvent']['subject'],
                                'desc' => $data['CalendarEvent']['description'],
                                'idBaseCal' => $this->CalendarEvent->id,
                                'event_date' => $this->CalendarEvent->field('start_date')
                            )
                        );
                    }
                } else $this->ajaxResponse['errDesc'] = 'An error occurred when saving!';
            } else $this->ajaxResponse['errDesc'] = 'No owner or ID!';
        }
        $this->afterAjax();
    }

    public function addEvent() {
        $data = (isset($this->data['Calendar'])) ? $this->data : null;
        if (!empty($data)) {
            $allDay = ($data['Calendar']['all_day'] == 'true') ? true : false;
            $data['CalendarEvent']['user_id'] = $this->myId;
            $data['CalendarEvent']['all_day'] = $allDay;//$data['Calendar']['all_day']; //(strtotime($data['Calendar']['start']) == strtotime($data['Calendar']['end'])) ? 1:0;
            $data['CalendarEvent']['start_date'] = date("Y-m-d H:i:s", strtotime($data['Calendar']['start']));
            $time = ($allDay) ? '23:00:00' : 'H:i:s';
            $data['CalendarEvent']['end_date'] = date("Y-m-d $time", strtotime($data['Calendar']['end']));
            $data['CalendarEvent']['description'] = strip_tags($data['CalendarEvent']['description']);
            $data['CalendarEvent']['subject'] = strip_tags($data['CalendarEvent']['subject']);
            if ($this->CalendarEvent->save($data)) {
                $this->ajaxResponse['id'] = $this->CalendarEvent->id;
                $this->ajaxResponse['shortDesc'] = substr($data['CalendarEvent']['subject'], 0, 50);
                $this->ajaxResponse['allDay'] = $data['CalendarEvent']['all_day'];
                $getFrId = ClassRegistry::init('UserFriend')->getFriendsId($this->myId);
                $toUserCal = array();
                $data['Calendar']['users'][] = $this->myId;
                $getFrId[] = $this->myId;
                if(isset($data['CalendarEvent']['all']) && $data['CalendarEvent']['all'] == 1)
                    $data['Calendar']['users'] = $getFrId;
                foreach ($data['Calendar']['users'] as $i => $uId) {
                    if (in_array($uId, $getFrId)) {
                        $toUserCal[$i]['UserCalendar']['calendar_event_id'] = $this->CalendarEvent->id;
                        $toUserCal[$i]['UserCalendar']['user_id'] = $uId;
                        $toUserCal[$i]['UserCalendar']['status'] = ($uId == $this->myId) ? 1 : 0;
                    }
                }
                if (!empty($toUserCal) && $this->UserCalendar->saveAll($toUserCal, array('validate' => false))) {
                    $this->_sendInboxMessage(
                        $data['Calendar']['users'],
                        array(
                            'subject' => $data['CalendarEvent']['subject'],
                            'desc' => $data['CalendarEvent']['description'],
                            'idBaseCal' => $this->CalendarEvent->id,
                            'event_date' => $data['CalendarEvent']['start_date']
                        )
                    );
                }

            } else $this->ajaxResponse['errDesc'] = 'An error occurred when saving!';
        }
        $this->afterAjax();
    }

    public function updateEventDate($idCal = null)
    {
        $idCal = (int)$idCal;
        $dates = (isset($this->data['newDates'])) ? $this->data['newDates'] : null;
        if (!empty($dates) && !empty($idCal)) {
            if ($this->CalendarEvent->checkOwner($this->myId, $idCal)) {
                $forSave = array();
                $forSave['CalendarEvent']['id'] = $idCal;
                $forSave['CalendarEvent']['user_id'] = $this->myId;
                $forSave['CalendarEvent']['all_day'] = (strtotime($dates['start']) == strtotime($dates['end'])) ? 1:0;
                $forSave['CalendarEvent']['start_date'] = date("Y-m-d H:i:s", strtotime($dates['start']));
                $time = ($forSave['CalendarEvent']['all_day'] == 1) ? '23:00:00' : 'H:i:s';
                $forSave['CalendarEvent']['end_date'] = date("Y-m-d $time", strtotime($dates['end']));
                if (!$this->CalendarEvent->save($forSave)) $this->ajaxResponse['errDesc'] = 'An error occurred when saving!';
            } else $this->ajaxResponse['errDesc'] = 'You are not the organizer! You can not make changes!';
        } else $this->ajaxResponse['errDesc'] = 'No dates!';
        $this->afterAjax();
    }

    public function readEvent($id)
    {
        $read = $this->CalendarEvent->setOwner($this->myId)->readEvent($id);
        $cal_owner = $read['CalendarEvent']['user_id'];
        $userStatus = Set::extract('/UserCalendar/status/.', $read);
        $totalInv = count($userStatus);
        if ($read) {
            $getInvitedFiendsId = Set::extract('/UserCalendar/User/id/.', $read);
            $friendsList = ClassRegistry::init('UserFriend')->listView()->butNotId($getInvitedFiendsId)->getMyFriends($this->myId);
            $this->data = $read;
            $this->set('friendsList', $friendsList);
            $this->set('totalInv', $totalInv);
            $this->set('cal_owner', $cal_owner);
            $this->set('totalUserStatus', array_count_values($userStatus));

            $this->ajaxResponse['content'] = 'edit_event_popup';

        }
        $this->ajaxResponse['event'] = $read;
        $this->afterAjax();
    }

    public function deleteEvent($calId = null)
    {
        $calId = (int)$calId;
        if (!empty($calId) && $this->CalendarEvent->checkOwner($this->myId, $calId)) {
            if (!$this->CalendarEvent->delete($calId)) $this->ajaxResponse['errDesc'] = 'An error occurred when deleting!';
        } else $this->ajaxResponse['errDesc'] = 'No owner or ID!';
        $this->afterAjax();
    }

    public function invitations($idCal, $type) {
        $allowedType = array('accept' => 1, 'reject' => 2);
        $this->CalendarEvent->id = $idCal;
        if ($this->CalendarEvent->exists()) {
            if (array_key_exists($type, $allowedType)) {
                if (!$this->UserCalendar->updateStatus($idCal, $this->myId, $allowedType[$type])) $this->ajaxResponse['errDesc'] = 'An error occurred when saving!';
            }
        } else $this->ajaxResponse['errDesc'] = 'Sorry, but the event is not found';
        $this->afterAjax();
    }

    /**
     *  For autocomplete, but now is not used.
     */
    public function getFriends()
    {
        Configure::write("debug", 0);
        $this->autoRender = false;
        $byName = (isset($this->data['q'])) ? $this->data['q'] : null;
        $byName = (!empty($byName)) ? strip_tags($byName) : null;
        $get = ClassRegistry::init('UserFriend')->listView()->getMyFriends($this->myId, $byName);
        foreach($get as $id => $name) {
            $results['results'][] = array('id' => $id, 'text' => $name);
        }
        $results['q'] = $byName;
        exit(json_encode($results));
    }

    private function _sendInboxMessage($IDs, $data)
    {
        $this->loadModel('Message');
        $subject = 'invite you to an event ' . $data['subject'];
        //$description = $data['desc'];
        $this->set('dataCal', $data);

        $content = $this->render('inbox_msg', 'ajax');
        $this->output = '';

        $toMsgCenter = array();
        foreach ($IDs as $i => $id) {
            if ($id != $this->myId) {
                $toMsgCenter[$i]['Message']['to_id'] = $id;
                $toMsgCenter[$i]['Message']['from_id'] = $this->myId;
                $toMsgCenter[$i]['Message']['subject'] = $subject;
                $toMsgCenter[$i]['Message']['content'] = $content;
                $toMsgCenter[$i]['Message']['event_date'] = $data['event_date'];
                $toMsgCenter[$i]['Message']['status'] = false;
                $this->_sendInboxEmail($id, $data);
            }
        }

        if ($this->Message->saveAll($toMsgCenter, array('validate' => false))) {

        } else {
            $this->log('error saving message');
            $this->log($this->Message->validationErrors);
        }
    }

   private function _sendInboxEmail($toID, $dataMess)
   {
       $this->set('server', 'http://' . $_SERVER['SERVER_NAME']);

       $myInfo = $this->Session->read('Auth.User.info');
       $poss = (strcmp($myInfo['sex'], 'M') == 0) ? 'his' : 'her';

       $this->set('subject', $myInfo['username'] . ' ' . 'wants to invite you to ' . $poss . ' event "' . $dataMess['subject'] . '"');
       $this->set('subj', $dataMess['subject']);
       //$this->set('subject', $myInfo['username'] . ' ' . __('wants to invite you to ' . $poss . ' event '.$dataMess['subject']));
       $this->set('event_date', $dataMess['event_date']);
       $this->set('event_desc', $dataMess['desc']);

       /** @noinspection PhpDynamicAsStaticMethodCallInspection */
       $data = $this->User->read('email', $toID);

       $this->Email->smtpOptions = array(
           'port'=> '465',
           'timeout'=> '30',
           'host' => 'ssl://smtp.gmail.com',
           'username'=> 'vivarena.site@gmail.com',
           'password'=> 'vivaQaz987'
       );
       $this->Email->delivery = 'smtp';

       $this->Email->to = $data['User']['email'];
       //$this->Email->to = 'alex.l@vizualtech.com';
       $this->Email->from = 'noreply@' . $_SERVER['SERVER_NAME'];
       $this->Email->subject = 'Someone wants to add you to ' . $poss . ' event!';
       $this->Email->template = 'request_event';
       $this->Email->layout = 'default';
       $this->Email->sendAs = 'html';
       $this->Email->send();
       return true;
   }
}