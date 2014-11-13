<?php
/**
 * @property User $User
 * @property UserInfo $UserInfo
 * @property UserSponsor $UserSponsor
 * @property UserSponsorReview $UserSponsorReview
 *
 * @property SessionComponent $Session
 * @property RequestHandlerComponent $RequestHandler
 */
class SponsorsController extends AppController
{
    public $name = 'Sponsors';
    public $helpers = array('Rating.Rating');
    public $uses = array('UserSponsor');

    public $myId = null;
    public $loginNameNow = null;

    public $noBefore = array();

    public function beforeFilter()
    {
        if (!$this->RequestHandler->isAjax() && !in_array($this->action, $this->noBefore)) {
            parent::beforeFilter();
        }
        $this->layout = 'community';

        $this->myId = $this->Session->read('Auth.User.id');
        $this->loginNameNow = (isset($this->params['login'])) ? $this->params['login'] : null;

    }


    public function index($type)
    {

        $getAuthLogin = $this->Auth->user('login');
        if ($getAuthLogin == $this->loginNameNow) {
            $byID = $this->myId;
        } else {
            $this->loadModel('User');
            $getId = $this->User->getUIDByLogin($this->loginNameNow);
            if ($getId) $byID = $getId;
        }

        $getAllSponsors = $this->UserSponsor->getAll($byID, $type);

        $this->set('allSponsors', $getAllSponsors);

        $this->set('activeTab', array($type.'s' => 'active'));
        $this->set('type', $type);
        $this->set('loginThisPage', $this->loginNameNow);

    }

    public function view($type, $id)
    {
        $id = (int)$id;
        $getAuthLogin = $this->Auth->user('login');
        if ($getAuthLogin == $this->loginNameNow) {
            $byID = $this->myId;
        } else {
            $this->loadModel('User');
            $getId = $this->User->getUIDByLogin($this->loginNameNow);
            if ($getId) $byID = $getId;
        }

        $getSponsor = $this->UserSponsor->withReviews()->getSponsor($byID, $id, $type);

        $this->loadModel('User');
        $owner = $this->User->find('first', array(
            'contain' => array(
                'UserInfo' => array( 'fields' => array(
                    'UserInfo.first_name',
                    'UserInfo.last_name',
                    'UserInfo.photo',
                    'UserInfo.sex',
                    'UserInfo.avatar',
                ),
                    'Country' => 'name'
                ),
                'Rating' => array(
                    'conditions' => array(
                        'Rating.model_id' => $id,
                        'Rating.model' => $this->UserSponsor->alias
                    )
                ),
            ),
            'conditions' => array('User.id' => $getSponsor['UserSponsor']['user_id'])
        ));
        Configure::load('rating.plugin_rating');
        $this->set(array(
            'Sponsor' => $getSponsor,
            'maxRating' => Configure::read('Rating.maxRating'),
            'owner' => $owner,
            'activeTab' => array($type.'s' => 'active'),
            'type' => $type,
            'loginThisPage' => $this->loginNameNow,
            'isOwner' => ($this->myId == $byID)
        ));

    }

    public function save_review($id)
    {
        $id = (int)$id;
        $data = (isset($this->data['UserSponsorReview'])) ? $this->data : false;
        if (!empty($id) && !empty($data)) {
            $this->UserSponsor->id = $id;
            if ($this->UserSponsor->exists()) {
                $this->loadModel('UserSponsorReview');
                if (!$this->UserSponsorReview->checkReviewer($this->myId, $id)) {
                    $data['UserSponsorReview']['user_id'] = $this->myId;
                    $data['UserSponsorReview']['user_sponsor_id'] = $id;
                    $data['UserSponsorReview']['text'] = strip_tags($data['UserSponsorReview']['text']);
                    if ($this->UserSponsorReview->save($data)) {
                        $this->_setFlash('Your review successfully saved', 'success');
                    } else $this->_setFlash('An error occurred!', 'error');
                } else $this->_setFlash('Error! You have already left your review', 'error');
            } else $this->_setFlash('Error! No sponsor/gear!', 'error');
        }
        $this->redirect($this->referer());
    }

    public function delete_review($id)
    {
        $this->loadModel('UserSponsorReview');
        $getSponsorId = $this->UserSponsorReview->getSponsorId($id);
        if ($getSponsorId && $this->UserSponsor->isReviewOwner($this->myId, $getSponsorId)) {
            if (!$this->UserSponsorReview->delete($id, false)) $this->ajaxResponse['errDesc'] = 'An error occurred when deleting!';
        } else $this->ajaxResponse['errDesc'] = 'Error! You Do not Own!';
        $this->afterAjax();
    }

    public function edit($type, $id)
    {
        $id = (int)$id;
        $getAuthLogin = $this->Auth->user('login');
        if ($getAuthLogin == $this->loginNameNow) {
            $byID = $this->myId;
        } else {
            $this->loadModel('User');
            $getId = $this->User->getUIDByLogin($this->loginNameNow);
            if ($getId) $byID = $getId;
        }

        $getSponsor = $this->UserSponsor->getSponsor($byID, $id, $type);

        $this->loadModel('User');
        $owner = $this->User->find('first', array(
            'contain' => array(
               'Rating' => array(
                    'conditions' => array(
                        'Rating.model_id' => $id,
                        'Rating.model' => $this->UserSponsor->alias
                    )
                ),
            ),
            'conditions' => array('User.id' => $getSponsor['UserSponsor']['user_id'])
        ));

       // $this->set('Sponsor', $getSponsor);
        $this->data = $getSponsor;
/*        $this->set('Sponsor', $getSponsor);*/
        $this->set('activeTab', array($type.'s' => 'active'));
        $this->set('type', $type);
        @$this->set('rating', $owner['Rating'][0]['rating']);
        @$this->set('rating_id', $owner['Rating'][0]['id']);
        $this->set('loginThisPage', $this->loginNameNow);

    }


    public function add()
    {
        //Configure::write('debug', 0);
        //$this->autoRender = false;
        $err = false;
        $resultMsg = '';

        $sponsorData = (isset($this->data['UserSponsor'])) ? $this->data : null;

        if (!empty($sponsorData) && !empty($this->myId)) {
//            if($this->referer()){
//                $this->redirect($this->referer());
//            }
            $file = (isset($this->data['UserSponsor']['logoFile'])) ? $this->data['UserSponsor']['logoFile'] : null;
            if ($file['error'] < 1)  {

                $pInfo = pathinfo($file['name']);
                $ext = strtolower($pInfo['extension']);
                if ($ext != 'jpg' && $ext != 'jpeg' && $ext != 'gif' && $ext != 'png') {
                    $resultMsg .= "Invalid file type: " . $ext . "\n";
                }
                /*$fInfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);

                if ($fInfo != 'image/jpeg' && $fInfo != 'image/gif' && $fInfo != 'image/png') {
                    $resultMsg .= "Invalid mime type: " . $fInfo . "\n";
                }*/

                $extList = array('image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/png' => 'png');
                $size = getimagesize($file['tmp_name']);

                $getType = (isset($size['mime'])) ? $size['mime'] : null;

                if (array_key_exists($getType, $extList)) {
                    $ext = $extList[$getType];
                } else $resultMsg .= "Invalid mime type: " . $getType . "\n";

                $file_name = $this->data['UserSponsor']['type'].'_' . uniqid() . '.'.$ext;
                $dirToPhotos = DS . 'uploads' . DS . 'userfiles' . DS . 'user_'.$this->myId;
                if (empty($resultMsg) && !is_dir(rtrim(WWW_ROOT, DS) . $dirToPhotos)) {
                    mkdir(rtrim(WWW_ROOT, DS) . $dirToPhotos, 0777, true);
                }
                $pathToFile = $dirToPhotos . DS . $file_name;
                if(empty($resultMsg) && move_uploaded_file($file['tmp_name'], rtrim(WWW_ROOT, DS) . $pathToFile)) {
                    $this->data['UserSponsor']['user_id'] = $this->myId;
                    $this->data['UserSponsor']['logo'] = $pathToFile;
                    if ($this->UserSponsor->save($this->data)) {
                        $this->UserSponsor->invalidateCache($this->myId);
                    } else $resultMsg = 'An error occurred when saving sponsor!';
                }
            } else{
                //edit sponsors
                if($this->data['UserSponsor']['logo']){
                    $this->data['UserSponsor']['user_id'] = $this->myId;
                    if ($this->UserSponsor->save($this->data)) {
                        $this->UserSponsor->invalidateCache($this->myId);
                    } else $resultMsg = 'An error occurred when saving sponsor!';
                }
                else{
                    $resultMsg = 'No logotype for sponsors';
                }

            }
        }

        if (!empty($resultMsg)) {
            $err = true; $this->data['UserSponsor'] = null;
        } else {
            $resultMsg = ucfirst($this->data['UserSponsor']['type']).' successfully save';

            //Save rating
            $rating = ClassRegistry::init('rating.Rating');
            //Prepare data


            //Edit
            if (isset($this->data['UserSponsor']['rating']['value']) && isset($this->data['UserSponsor']['rating']['id'])){
                $ratingData['Rating']['id'] = $this->data['UserSponsor']['rating']['id'];
                $ratingData['Rating']['rating'] = $this->data['UserSponsor']['rating']['value'];
                $ratingData['Rating']['model'] = 'UserSponsor';
                $ratingData['Rating']['model_id'] = $this->UserSponsor->id;
                $ratingData['Rating']['user_id'] = $this->myId;
                $ratingData['Rating']['name'] = $this->data['UserSponsor']['rating']['name'];
                if (!$rating->save($ratingData)) {
                    $err = true;
                    $resultMsg = 'Error occurred while saving rating';
                }

            //New
            }elseif ( isset($this->data['UserSponsor']['rating']['value'] )){
            $ratingData['Rating']['rating'] = $this->data['UserSponsor']['rating']['value'];
            $ratingData['Rating']['model'] = 'UserSponsor';
            $ratingData['Rating']['model_id'] = $this->UserSponsor->id;
            $ratingData['Rating']['user_id'] = $this->myId;
            $ratingData['Rating']['name'] = $this->data['UserSponsor']['rating']['name'];
                if (!$rating->save($ratingData)) {
                    $err = true;
                    $resultMsg = 'Error occurred while saving rating';
                }
            }




        }
        $typeMsg = ($err) ? 'error' : 'message';

        $this->_setFlashMsg($resultMsg, $typeMsg);
        $this->redirect($this->referer());

    }

    public function setStatus($status = 'inactive')
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';

        $idSponsor = (isset($this->data['idSponsor']) && !empty($this->data['idSponsor'])) ? $this->data['idSponsor'] : null;
        $type = (isset($this->data['type']) && !empty($this->data['type'])) ? $this->data['type'] : 'sponsor';
        if (($status == 'active' || $status == 'inactive') && !empty($idSponsor)) {
            $status = ($status == 'active') ? 1 : 0;
            $forSponsor['UserSponsor']['id'] = $idSponsor;
            $forSponsor['UserSponsor']['user_id'] = $this->myId;
            $forSponsor['UserSponsor']['active'] = $status;
            $this->UserSponsor->updateAll(
                array('UserSponsor.active' => 0),
                array('UserSponsor.user_id' => $this->myId, 'UserSponsor.type' => $type)
            );
            $updateToActive = $this->UserSponsor->updateAll(
                array('UserSponsor.active' => $status),
                array('UserSponsor.user_id' => $this->myId, 'UserSponsor.id' => $idSponsor, 'UserSponsor.type' => $type)
            );
            if ($updateToActive) {
                $this->UserSponsor->invalidateCache($this->myId, $this->data['type']);
            } else $err_desc = 'An error occurred';
        } else $err_desc = 'No data!';

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'status' => $status,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    public function delete($id = null) {
        if(is_null($id) || !ctype_digit($id)) {
            throw new Exception('param ID null or incorrect type');
        }
        if($this->UserSponsor->delete((int)$id)) {
            $this->redirect($this->referer());
        }else {
            $this->_setFlashMsg('Error. No item with ID: '.$id, 'error');
            $this->redirect($this->referer());
        }
    }


}