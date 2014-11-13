<?php
/**
  * Facebook.Graph component
  *
  * @author Nike - CNR Miami
  * @version 1.0
  * @link http://marinka.never
  */
App::import('Lib', 'Facebook.FB');
class GraphComponent extends Object {

    /**
     * Load the API into a class property and allow access to it.
     * @param $controller
     * @return void
     */
    function initialize($controller){
        $this->_FB = new FB();
    }

    /**
     * Getter for User albums from Facebook
     * @return mixed
     */
    function getUserAlbums() {
        $query = "SELECT aid, name, cover_object_id, photo_count FROM album WHERE owner=me() AND photo_count>0";
        $result = $this->_submitQuery($query);
        $result = $this->_appendAlbumCovers($result);
        return $result;
    }

    /**
     * Getter for User albums from Facebook by album Id
     * @param $albumId
     * @return array
     */
    public function getPhotosByAlbum($albumId) {
        $query = 'SELECT src, caption, src_big FROM photo WHERE aid="' .$albumId . '"';
        $result = $this->_submitQuery($query);
        return $result;
    }

    /**
     * Getter for Facebook User Avatar
     * @return String
     */
    public function getUserAvatar() {
        $query = "SELECT pic_big FROM user WHERE uid=me()";
        $avatar = $this->_submitQuery($query);
        return $avatar[0]['pic_big'];
    }

    /**
     * Publish message on wall
     * @param $facebookMessage
     * @return void
     */
    public function postOnWall($facebookMessage) {
        $attachment = array(
            'message' => $facebookMessage,
//            'name' => 'This is my demo Facebook application!',
//            'caption' => "Caption of the Post",
            'link' => 'http://' . env("SERVER_NAME"),
//            'description' => 'this is a description',
            'picture' => 'http://' . env('SERVER_NAME') . '/img/logo.png',
            'actions' => array(
                array(
                    'name' => 'Go to WingBuddy.com',
                    'link' => 'http://' . env("SERVER_NAME")
                )
            )
        );


        $result = $this->_FB->api('/me/feed/', 'post', $attachment);
    }

    /**
     * Create Facebook checkins
     * @param $trips
     * @return void
     */
    public function createCheckins($trips) {
        foreach ($trips as $trip) {
            if (!empty($trip['longitude'])) {
                $attachment = array(
                    'access_token' => $this->_FB->getAccessToken(),
                    'place' => "145768288146",
                    'message' =>'I went to placename today',
                    'picture' => 'http://www.place.com/logo.jpg',
                    'coordinates' => json_encode(array(
                            'latitude'  => $trip['latitude'],
                            'longitude' => $trip['longitude'],
                        )
                    )
                );
//                pr($attachment);
                $result = $this->_FB->api('/me/checkins/', 'POST', $attachment);
            }
        }
    }

    /**
     * Private section
     */

    /**
     * Submit prepared query to Facebook
     * @param $queryString
     * @return mixed
     */
    private function _submitQuery($queryString) {
        $fqlQuery = array(
            "method" => "fql.query",
            "query"  => $queryString
        );
        $responce = $this->_FB->api($fqlQuery);
        return $responce;
    }

    /**
     * Method for adding covers to album
     * @param $result
     * @return mixed
     */
    private function _appendAlbumCovers($result) {
        $query = "SELECT src FROM photo WHERE object_id=";

        foreach ($result as &$album) {
            $res = $this->_submitQuery($query . $album['cover_object_id']);
            if ($album['photo_count'] > 0) {
                @$album['cover'] = $res[0]['src'];
            }
        }
        unset($res);
        unset($album);
        return $result;
    }



    /**
     * Variables section
     */

    /**
     * Allow direct access to the facebook API
     * @link http://wiki.developers.facebook.com/index.php/Main_Page
     */
    private $_FB = null;

}
?>