<?php

class UserPhoto extends AppModel
{
    public $name = 'UserPhoto';

    public $belongsTo = array(
        'User', 'PhotoAlbum'
    );


    public function getLastPhoto($uID, $limit = 4)
    {
        $this->unbindModel(array(
            'belongsTo' => array('User', 'PhotoAlbum')
        ));
        $getPhotos = $this->find('all', array(
            'conditions' => array('UserPhoto.user_id' => $uID),
            'limit' => $limit,
            'fields' => array('UserPhoto.image', 'UserPhoto.photo_album_id'),
            'order' => 'UserPhoto.created DESC'
        ));

        return $getPhotos;

    }

}