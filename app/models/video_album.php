<?php

class VideoAlbum extends AppModel
{
    public $name = 'VideoAlbum';

    public $belongsTo = array('User');
    public $hasMany = array('UserVideo');

    public function getAlbums($uID)
    {

        $this->unbindModel(array(
            'belongsTo' => array('User'),
            'hasMany' => array('UserVideo'),
        ));

        $albums = $this->find('all', array(
            'joins' => array(
                array(
                    'table' => $this->tablePrefix.'user_videos',
                    'alias' => 'UserVideo',
                    'type' => 'left',
                    'conditions' => 'VideoAlbum.id = UserVideo.video_album_id'
                )
            ),
            'conditions' => array('VideoAlbum.user_id' => $uID),
            'fields' => array('VideoAlbum.*', 'COUNT(UserVideo.id) AS Count'),
            'group' => 'VideoAlbum.id'
        ));

        return $albums;

    }

    public function getAlbumByUser($uID, $albID)
    {

        $photos = $this->find('first', array(
            'conditions' => array('VideoAlbum.user_id' => $uID, 'VideoAlbum.id' => $albID)
        ));

        return $photos;
    }

}