<?php

class PhotoAlbum extends AppModel
{
    public $name = 'PhotoAlbum';

    public $belongsTo = array('User');
    public $hasMany = array('UserPhoto');

    public function getAlbums($uID)
    {

        $this->unbindModel(array(
            'belongsTo' => array('User'),
            'hasMany' => array('UserPhoto'),
        ));

        $albums = $this->find('all', array(
            'joins' => array(
                array(
                    'table' => $this->tablePrefix.'user_photos',
                    'alias' => 'UserPhoto',
                    'type' => 'left',
                    'conditions' => 'PhotoAlbum.id = UserPhoto.photo_album_id'
                )
            ),
            'conditions' => array('PhotoAlbum.user_id' => $uID),
            'fields' => array('PhotoAlbum.*', 'COUNT(UserPhoto.id) AS Count'),
            'group' => 'PhotoAlbum.id'
        ));

        return $albums;

    }

    public function getAlbumByUser($uID, $albID)
    {

        $photos = $this->find('first', array(
            'conditions' => array('PhotoAlbum.user_id' => $uID, 'PhotoAlbum.id' => $albID)
        ));

        return $photos;
    }

}