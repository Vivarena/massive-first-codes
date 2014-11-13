<?php
/**
 * Load phpthumb library (http://phpthumb.gxdlabs.com)
 */

/**
 * Image component class
 * @author Dmitry
 * @uses phpthumb library (http://phpthumb.gxdlabs.com)
 */
class ImageComponent extends Object
{
	/**
	 * Return phpthumb object
	 * @param string $filename The path of file to load
	 * @param array $options
	 * @param bool $isDataStream
	 * @return object
	 */
    public $controller = null;

    public $components = array('Session', 'Thumbs.ThumbsControl');

	public function create($filename, $options = array(), $isDataStream = false)
	{
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        App::import('Vendor', 'ThumbLib/ThumbLib');
		return PhpThumbFactory::create($filename, $options, $isDataStream);
	}

    function initialize(&$controller, $settings = array()) {
        $this->controller =& $controller;
        foreach ($this->controller->uses as $modelClass){
            $controller->loadModel($modelClass);
            $this->$modelClass = $controller->$modelClass;
        }
    }


    public function cropPhoto($dataImage, $uID)
    {

        $result = array();
        $x1 = $dataImage['crop']['x1'];
        $x2 = $dataImage['crop']['x2'];
        $y1 = $dataImage['crop']['y1'];
        $y2 = $dataImage['crop']['y2'];
        if ($dataImage['crop']['flag'] == 'album') {
            $pathDetails = pathinfo($dataImage['crop']['src']);
            $dirToPhotos = rtrim(WWW_ROOT, DS) . $pathDetails['dirname'];
            $nameFile = $pathDetails['basename'];
            $ava_file = rtrim(WWW_ROOT, DS) . DS . 'uploads' . DS . 'userfiles' . DS . 'user_' . $uID . DS .'ava_' . $nameFile;
        } else {
            $dirToPhotos = rtrim(WWW_ROOT, DS) . DS . 'uploads' . DS . 'userfiles' . DS . 'user_' . $uID;
            $nameFile = end(explode('/', $dataImage['crop']['src']));
            $ava_file = $dirToPhotos . DS .'ava_' . $nameFile;
        }
        $avaFileName = DS . 'uploads' . DS . 'userfiles' . DS . 'user_' . $uID . DS . 'ava_' . $nameFile;

        if ($this->crop($dirToPhotos . DS . $nameFile, $ava_file, array($x1, $y1, $x2, $y2))) {
            $userInfoID = $this->UserInfo->getIdByUser($uID);
            $this->UserInfo->id = $userInfoID;
            $data['UserInfo']['avatar'] =  $avaFileName;
            $this->UserInfo->save($data);
            $this->Session->write('Auth.User.info.avatar', $avaFileName);
            $result['avaFileName'] = $avaFileName;
            $this->ThumbsControl->removeCachedThumbs($avaFileName);
        } else {
            $result['err_desc'] = "An error occurred when saving coordinates!";
        }

        return $result;
    }

    private function crop($file_input, $file_output, $crop, $size = 390) {
        list($wOrig, $hOrig, $type) = getimagesize($file_input);
        if (!$wOrig || !$hOrig) {
            return false;
        }

        $types = array('','gif','jpeg','png');
        $ext = $types[$type];
        if ($ext) {
                $func = 'imagecreatefrom'.$ext;
                $img = $func($file_input);
        } else {
            return false;
        }

        list($x1, $y1, $x2, $y2) = $crop;
        $w = $x2 - $x1;
        $h = $y2 - $y1;

        $imgNewSize = ($wOrig > $size) ? $this->resizeImg($img, $wOrig, $hOrig, $size) : array('imgNewSize' => $img);
        $img_o = imagecreatetruecolor($w, $h);

        imagecopy($img_o, $imgNewSize['imgNewSize'], 0, 0, $x1, $y1, $w, $h);
        $img_o = $this->resizeImg($img_o, $w, $h, 120);
        if ($type == 2) {
            return imagejpeg($img_o['imgNewSize'],$file_output,100);
        } else {
            $func = 'image'.$ext;
            return $func($img_o['imgNewSize'],$file_output);
        }
    }

    private function resizeImg($img, $wOrig, $hOrig, $size) {

        $result['hDist'] = round($size/($wOrig / $hOrig));
        $result['wDist'] = $size;
        $result['imgNewSize'] = imagecreatetruecolor($result['wDist'], $result['hDist']);
        imagecopyresampled($result['imgNewSize'], $img, 0, 0, 0, 0, $result['wDist'], $result['hDist'], $wOrig, $hOrig);

        return $result;
    }
}