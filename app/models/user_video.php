<?php

class UserVideo extends AppModel
{
    public $name = 'UserVideo';

    public $belongsTo = array(
        'User', 'VideoAlbum'
    );


    public function afterSave($created)
    {
        if ($created) {

            $feed['user_id'] = $this->data['UserVideo']['user_id'];
            $serText = array(
               'text' => 'add new video to an album',
               'url_video' => $this->data['UserVideo']['url_video'],
               'cover_img' => $this->data['UserVideo']['cover_img'],
            );
            $feed['id_affected_tables'] = $this->data['UserVideo']['video_album_id'];
            $feed['activity_text'] = serialize($serText);
            $feed['type_feed'] = 'video_item';
            $ActivityWall = ClassRegistry::init('ActivityWall');
            $ActivityWall->create();
            $ActivityWall->save($feed);

        }
    }

    public function getLastVideo($uID, $limit = 4)
    {
        $this->unbindModel(array(
            'belongsTo' => array('User', 'VideoAlbum')
        ));
        $getPhotos = $this->find('all', array(
            'conditions' => array('UserVideo.user_id' => $uID),
            'limit' => $limit,
            'fields' => array('UserVideo.cover_img', 'UserVideo.video_album_id', 'UserVideo.url_video'),
            'order' => 'UserVideo.created DESC'
        ));

        return $getPhotos;

    }

}