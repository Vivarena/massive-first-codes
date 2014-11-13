<?php

class UserPost extends AppModel
{
    public $name = 'UserPost';

    public $belongsTo = array('User');
    public $hasMany = array('UserPostComment');

    public $actsAs = array(
        'Containable'
    );


    public $validate = array(
        'text' => 'notEmpty',
    );

    public function getStatus($uid)
    {
      $conditions = array('user_id' => $uid);

      $data = $this->find('first', array(
        'fields' => array('id','text'),
        'recursive' => -1,
        'conditions' => $conditions,
        'order' => 'UserPost.created DESC'
       ));

     if ($data) {
       $data["postId"]=$data["UserPost"]["id"];
       $data["postText"]=strip_tags($data["UserPost"]["text"]);
       $data["userId"]=$uid;
       unset($data["UserPost"]);
     }
     return $data;
    }

    public function afterSave($created)
    {

       if ($created) {
           if (isset($this->data['UserPost'])) {

               $feed['user_id'] = $this->data['UserPost']['user_id'];
               $postTxt = strip_tags($this->data['UserPost']['text']);
               $forLinkedTxt = (strlen($postTxt) > 200) ? substr($postTxt, 0, 200).'...' : $postTxt;
               $postTxt = substr($postTxt, 0, 75).'...';
               $serText = array(
                   'text' => 'added a new post',
                   'post_text' => $postTxt
               );
               if ($this->data['UserPost']['type'] == 'video' && isset($this->data['UserPost']['cover_video']) && isset($this->data['UserPost']['attached_video'])) {
                   $serText['type'] = 'video';
                   $serText['cover_video'] = $this->data['UserPost']['cover_video'];
                   $serText['attached_video'] = $this->data['UserPost']['attached_video'];
               }
               if ($this->data['UserPost']['type'] == 'image' && isset($this->data['UserPost']['attached_image'])) {
                   $serText['type'] = 'image';
                   $serText['attached_image'] = $this->data['UserPost']['attached_image'];
               }
               $feed['activity_text'] = serialize($serText);
               $feed['id_affected_tables'] = $this->id;
               $feed['type_feed'] = 'post';
               $ActivityWall = ClassRegistry::init('ActivityWall');
               $ActivityWall->save($feed);

               $notifyText['facebook'] = 'Added a new post "'.$postTxt.'"';
               $notifyText['twitter'] = 'Added a new post "'.$postTxt.'"';

               $notifyText['linkedin']['smallTitle'] = 'Did some action on the site';
               $notifyText['linkedin']['boldCaption'] = 'Added a new post on vivarena.com';
               $notifyText['linkedin']['description'] = $forLinkedTxt;

               //$SocialNotification = ClassRegistry::init('SocialNotification');
               //$SocialNotification->checkAndSendNotify($feed['user_id'], 'Posts', true, $notifyText);

           }
       }

    }


}
