<?php
/**
 * Class PostsController
 *
 *
 * @property UserPost $UserPost
 *
 */
class PostsController extends AppController
{

    var $name = 'Posts';

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->layout = 'community';
        $this->myId = $this->Session->read('Auth.User.id');
    }

    public function addPost()
    {
        if ($this->RequestHandler->isAjax() ) {
            $this->loadModel('UserPost');
            $data = (isset($this->params['url']['data'])) ? $this->params['url']['data'] : null;
            if (!empty($data)) {
                $data['UserPost']['user_id'] = $this->Auth->user('id');
                if ($data['UserPost']['type'] == 'video') {
                    $youtubeCover = (isset($data['Youtube']['cover_img'])) ? $data['Youtube']['cover_img'] : null;
                    $checkURL = preg_match('/youtube.com\/embed\/([\w-]{11})/', $data['UserVideo']['url_video']);
                    if (!empty($checkURL)) {
                        $file_name = 'video_post_' . uniqid() . '.jpg';
                        $dirToPhotos = DS . 'uploads' . DS . 'userfiles' . DS . 'user_'.$this->Auth->user('id');
                        if (empty($err_desc) && !is_dir(rtrim(WWW_ROOT, DS) . $dirToPhotos)) {
                            mkdir(rtrim(WWW_ROOT, DS) . $dirToPhotos, 0777, true);
                        }
                        $pathToFile = $dirToPhotos . DS . $file_name;
                        $savedFile = file_put_contents(rtrim(WWW_ROOT, DS).$pathToFile, file_get_contents($youtubeCover));
                        if ($savedFile) {
                            $data['UserPost']['cover_video'] = $pathToFile;
                            $data['UserPost']['attached_video'] = $data['UserVideo']['url_video'];
                        } else $this->set('errorMsg', 'An error occurred while saving the image cover');
                    }
                $this->UserPost->unbindValidation('remove', array('text'));
                } elseif ($data['UserPost']['type'] == 'image') {
                    if (isset($data['AttachImage']) && !empty($data['AttachImage'])) {
                        $dirToAttach = DS . 'uploads' . DS . 'attach';
                        if (!is_dir(rtrim(WWW_ROOT, DS) . $dirToAttach)) {
                            mkdir(rtrim(WWW_ROOT, DS) . $dirToAttach, 0777, true);
                        }
                        $fullPathTmp = rtrim(WWW_ROOT, DS) . $data['AttachImage'];
                        $pInfo = pathinfo($fullPathTmp);
                        $file_name = $pInfo['basename'];
                        $pathToFile = $dirToAttach . DS . $file_name;
                        $newFile = (file_exists($fullPathTmp)) ? rename($fullPathTmp, rtrim(WWW_ROOT, DS) . $pathToFile) : false;
                            $data['UserPost']['attached_image'] = $pathToFile;
                    }
                    $this->UserPost->unbindValidation('remove', array('text'));
                }
                if ($this->UserPost->save($data)) {
                    $toSession['postId'] = $this->UserPost->id;
                    $toSession['postText'] = $this->data['UserPost']['text'];
                    $toSession['userId'] = $this->Auth->user('id');
                    $this->Session->write('userStatusText', $toSession);
                } else {
                    $this->set('errorMsg', 'An error occurred when adding the post');
                }
            } else $this->set('errorMsg', 'An error occurred when adding the post');
            if (isset($this->params['url']['fromProfile']) && $this->params['url']['fromProfile'] == 1){
                Configure::write('debug', 0);
                exit(json_encode(array('error' => false)));
            }
            $this->loadModel('ActivityWall');
            $this->set('aWall', $this->ActivityWall->getActivity($this->myId));
            $this->render('/elements/community/activityList');
        } else {
            $this->redirect('/');
        }
    }

    public function attachImage()
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';
        $pathToFile = '';
        $idPhoto = null;

        $file = (isset($this->params['form']['file'])) ? $this->params['form']['file'] : null;

        if (!empty($file)) {
            $ext = '';
            $extList = array('image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/png' => 'png');
            $size = getimagesize($file['tmp_name']);

            $getType = (isset($size['mime'])) ? $size['mime'] : null;  //image_type_to_mime_type(exif_imagetype($file['tmp_name']));

            if (array_key_exists($getType, $extList)) {
                $ext = $extList[$getType];
            } else $err_desc .= "Invalid mime type: " . $getType . "\n";

            $file_name = uniqid() . '.' . $ext;
            $dirToPhotos = DS . 'uploads' . DS . 'tmp';
            if (empty($err_desc) && !is_dir(rtrim(WWW_ROOT, DS) . $dirToPhotos)) {
                mkdir(rtrim(WWW_ROOT, DS) . $dirToPhotos, 0777, true);
            }
            $pathToFile = $dirToPhotos . DS . $file_name;
            if(empty($err_desc) && move_uploaded_file($file['tmp_name'], rtrim(WWW_ROOT, DS) . $pathToFile)) {

            } else $err_desc .= 'An error occurred when uploading file!';
        }

        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'name' => $pathToFile,
            'err_desc' => $err_desc,
        );
        $this->log($result, 'result');
        exit(json_encode($result));

    }

    public function delPost($id) {}

}
?>
