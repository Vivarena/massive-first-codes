<?php
/**
 * @property Group $Group
 * @property User $User
 * @property UserInfo $UserInfo
 * @property UserPhoto $UserPhoto
 * @property UserVideo $UserVideo
 * @property PhotoAlbum $PhotoAlbum
 * @property VideoAlbum $VideoAlbum
 * @property ActivityWall $ActivityWall
 *
 * @property SessionComponent $Session
 * @property AuthComponent $Auth
 * @property RequestHandlerComponent $RequestHandler
 */
class AlbumsController extends AppController {
    public $name = 'Albums';

    public $uses = array('PhotoAlbum', 'VideoAlbum', 'UserPhoto', 'UserVideo', 'User', 'UserInfo');
    public $helpers = array('Youtube');

    public $myId;
    public $loginNameNow = null;

    public $noBefore = array('upload_photos', 'getYoutubeCover');

    public function beforeFilter()
    {
        if (!$this->RequestHandler->isAjax() && !in_array($this->action, $this->noBefore)) {
            parent::beforeFilter();
        }
        $this->layout = 'community';
        $this->loginNameNow = (isset($this->params['login'])) ? $this->params['login'] : null;
        if($this->RequestHandler->isAjax()) {
            $this->loginNameNow = $this->Auth->user('login');
        }
        $this->myId = $this->Session->read('Auth.User.id');

    }


    /**
     * @param $type
     * @param bool $ajax
     */
    public function index($type, $ajax = false)
    {
        $login = $this->loginNameNow;
        $getAlbums = false;
        $byID = null;
        if (!empty($login)) {
            $getAuthLogin = $this->Auth->user('login');
            if ($getAuthLogin == $login) {
                $byID = $this->myId;
                $this->set('iOwnerAlbum', true);
            } else {
                $getId = $this->User->getUIDByLogin($login);
                if ($getId) $byID = $getId;
            }
            if ($type == 'photos') { $modelAlbum = 'PhotoAlbum'; }
            elseif($type == 'videos') { $modelAlbum = 'VideoAlbum'; }
            else $modelAlbum = false;
            if ($modelAlbum) {
                $getAlbums = $this->$modelAlbum->getAlbums($byID);
                $this->set('modelAlbum', $modelAlbum);
            }
            $this->set('activeTab', array($type => 'active'));
            $this->set('loginThisPage', $login);
            $this->set('type', $type);
        }


        $this->set('albums', $getAlbums);

        if($ajax) {
            $this->ajaxResponse['content'] = 'ajax_index';
            $this->afterAjax();
        }

    }



    public function view($type = null, $idAlbum = null, $ajax = false)
    {
        if(!$ajax) {
            $type = (isset($this->params['type'])) ? $this->params['type'] : null;
            $idAlbum = (isset($this->params['id'])) ? $this->params['id'] : null;
        }

        $login = $this->loginNameNow;
        if ($type == 'photos') { $modelAlbum = 'PhotoAlbum'; }
        elseif($type == 'videos') { $modelAlbum = 'VideoAlbum'; }
        else $modelAlbum = false;
        if (!empty($idAlbum) && !empty($type) && $modelAlbum) {
            $getAuthLogin = $this->Auth->user('login');
            $getUserId = $this->User->getUIDByLogin($login);
            $album = $this->$modelAlbum->getAlbumByUser($getUserId, $idAlbum);
            $this->set('album', $album);
            $this->set('type', $type);
            if ($getAuthLogin == $login) {
                $this->set('iOwnerAlbum', true);
            }
            $this->set('modelAlbum', $modelAlbum);
            $this->set('activeTab', array($type => 'active'));
            $this->set('type', $type);
            $this->set('loginThisPage', $login);
        }

        if($ajax) {
            $this->ajaxResponse['content'] = 'ajax_view_album';
            $this->afterAjax();
        }else {
            $this->render('view_album');
        }

    }

    function getYoutubeCover($size = 'small', $ajax = false)
    {
        if($this->RequestHandler->isAjax()) {
            if($ajax) {
                $videoURL = $this->data['videoURL'];
                $videoURL = explode('/', $videoURL);
                $videoURL = 'http://youtu.be/'.end($videoURL);
            }else {
                $videoURL = $this->params['form']['videoURL'];
            }
            $this->set('sizeThumb', $size);
            $this->set('videoURL', $videoURL);
            exit($this->render('../elements/youtubeAjaxShot', 'ajax'));
        }
    }


    function addItemVideo()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $pathToFile = '';
        $userVideo = (isset($this->data['UserVideo'])) ? $this->data : null;
        $youtubeCover = (isset($this->data['Youtube']['cover_img'])) ? $this->data['Youtube']['cover_img'] : null;
        $checkURL = preg_match('/youtube.com\/embed\/([\w-]{11})/', $userVideo['UserVideo']['url_video']);
        if (!empty($userVideo) && !empty($youtubeCover) && !empty($checkURL)) {
            $file_name = 'cover_' . uniqid() . '.jpg';
            $dirToPhotos = DS . 'uploads' . DS . 'albums' . DS . 'v_'.md5($this->Auth->user('email'));
            if (empty($err_desc) && !is_dir(rtrim(WWW_ROOT, DS) . $dirToPhotos)) {
                mkdir(rtrim(WWW_ROOT, DS) . $dirToPhotos, 0777, true);
            }
            $pathToFile = $dirToPhotos . DS . $file_name;
            $savedFile = file_put_contents(rtrim(WWW_ROOT, DS).$pathToFile, file_get_contents($youtubeCover));

            if ($savedFile) {
                $userVideo['UserVideo']['cover_img'] = $pathToFile;
                $userVideo['UserVideo']['user_id'] = $this->myId;
                if ($this->UserVideo->save($userVideo)){
                    $userVideo['UserVideo']['id'] = $this->UserVideo->id;
                } else $err_desc = 'An error occurred while saving Youtube link';
            } else $err_desc = 'An error occurred while saving the image cover';

        } else {
            $err_desc = 'No data!';
            $userVideo['UserVideo'] = null;
        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'coverImg' => $pathToFile,
            'itemVideo' => $userVideo['UserVideo'],
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    public function create($type)
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $uInfo = $this->Auth->user();
        $login = (!empty($uInfo['User']['login'])) ? $uInfo['User']['login'] : 'profile-'.$uInfo['User']['id'];

        $redirect = '';
        if ($type == 'photos') { $modelAlbum = 'PhotoAlbum'; }
        elseif($type == 'videos') { $modelAlbum = 'VideoAlbum'; }
        else $err_desc = 'No type of album!';
        if (empty($err_desc)) {
            $album = (isset($this->data[$modelAlbum]['name'])) ? $this->data : null;
            if (!empty($album)) {
                $album[$modelAlbum]['user_id'] = $this->myId;
                if ($this->$modelAlbum->save($album)) {
                    $redirect = '/'.$login.'/albums/'.$type.'/'.$this->$modelAlbum->id;
                } else {
                    $err_desc = 'No data for new album!';
                }
            }
        }

        if (!empty($err_desc)) $err = true;

        $result = array(
            'redirectURL' => $redirect,
            'error' => $err,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    public function upload_photos()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $pathToFile = '';
        $idPhoto = null;

        $albumID = (isset($this->data['PhotoAlbum']['id']) && !empty($this->data['PhotoAlbum']['id'])) ? $this->data['PhotoAlbum']['id'] : null;
        if (!empty($albumID)) {
            $file = (isset($this->params['form']['file'])) ? $this->params['form']['file'] : null;
            if (!empty($file)) {
                $pInfo = pathinfo($file['name']);
                $ext = strtolower($pInfo['extension']);
                if ($ext != 'jpg' && $ext != 'jpeg' && $ext != 'gif' && $ext != 'png') {
                    $err_desc .= "Invalid file type: " . $ext . "\n";
                }
                /*$fInfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);

                if ($fInfo != 'image/jpeg' && $fInfo != 'image/gif' && $fInfo != 'image/png') {
                    $err_desc .= "Invalid mime type: " . $fInfo . "\n";
                }*/
                $extList = array('image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/png' => 'png');
                $size = getimagesize($file['tmp_name']);

                $getType = (isset($size['mime'])) ? $size['mime'] : null;

                if (array_key_exists($getType, $extList)) {
                    $ext = $extList[$getType];
                } else $err_desc .= "Invalid mime type: " . $getType . "\n";

                $file_name = 'photo_' . uniqid() . '.' . $ext;
                $dirToPhotos = DS . 'uploads' . DS . 'albums' . DS . md5($this->Auth->user('email'));
                if (empty($err_desc) && !is_dir(rtrim(WWW_ROOT, DS) . $dirToPhotos)) {
                    mkdir(rtrim(WWW_ROOT, DS) . $dirToPhotos, 0777, true);
                }
                $pathToFile = $dirToPhotos . DS . $file_name;
                if(empty($err_desc) && move_uploaded_file($file['tmp_name'], rtrim(WWW_ROOT, DS) . $pathToFile)) {
                    $toPhotoTable['UserPhoto']['user_id'] = $this->myId;
                    $toPhotoTable['UserPhoto']['photo_album_id'] = $albumID;
                    $toPhotoTable['UserPhoto']['image'] = $pathToFile;
                    if ($this->UserPhoto->save($toPhotoTable)) {
                        $idPhoto = $this->UserPhoto->id;
                    } else $err_desc .= 'An error occurred when saving photo!';
                }


            }
        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'id' => $idPhoto,
            'name' => $pathToFile,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }


    public function completeUpload()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';

        $photos = (isset($this->data['UserPhoto']['photos'])) ? $this->data : null;
        if (!empty($photos)) {
            $photos['UserPhoto']['user_id'] = $this->myId;
            $this->loadModel('ActivityWall');
            $this->ActivityWall->toActivity('photo_album', $photos);
        } else $err_desc = 'No photos!';

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    public function setCover()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $type = (isset($this->data['Type'])) ? $this->data['Type'] : null;

        if ($type == 'photos') { $modelAlbum = 'PhotoAlbum'; }
        elseif($type == 'videos') { $modelAlbum = 'VideoAlbum'; }
        else $err_desc = 'No type of album!';
        if (empty($err_desc)){
            $cover = (isset($this->data[$modelAlbum]['cover'])) ? $this->data : null;
            if (!empty($cover)) {
                if (!$this->$modelAlbum->save($cover)) $err_desc = 'An error occurred when saving the cover for this album!';
            }
        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    public function deleteItemAlbum()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $type = (isset($this->data['Type'])) ? $this->data['Type'] : null;
        if ($type == 'photos') { $modelAlbum = 'UserPhoto'; }
        elseif($type == 'videos') { $modelAlbum = 'UserVideo'; }
        else $err_desc = 'No type of album!';

        $itemId = (isset($this->data['id'])) ? $this->data['id'] : null;
        if (!empty($itemId)) {
            $deletedPhoto = $this->$modelAlbum->deleteAll(
                array(
                    $modelAlbum.'.user_id' => $this->myId,
                    $modelAlbum.'.id' => $itemId
                ), false
            );
            if (!$deletedPhoto) $err_desc = 'An error occurred when deleting the photo!';
        }


        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }


    public function deleteAlbum()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $type = (isset($this->data['Type'])) ? $this->data['Type'] : null;
        if ($type == 'photos') { $modelAlbum = 'PhotoAlbum'; }
        elseif($type == 'videos') { $modelAlbum = 'VideoAlbum'; }
        else $err_desc = 'No type of album!';

        $itemId = (isset($this->data['id'])) ? $this->data['id'] : null;
        if (!empty($itemId)) {
            $deletedPhoto = $this->$modelAlbum->delete($itemId);
            if (!$deletedPhoto) $err_desc = 'An error occurred when deleting the photo!';
        }


        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }


}



