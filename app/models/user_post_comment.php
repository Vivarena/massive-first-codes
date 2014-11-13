<?php

class UserPostComment extends AppModel
{
    public $name = 'UserPostComment';

    public $belongsTo = array('User', 'UserPost');

    public $actsAs = array(
        'Containable'
    );


    public $validate = array(
        'text' => 'notEmpty',
    );

    public function afterSave($created)
    {

       if ($created) {
           if (isset($this->data['UserPostComment'])) {
               $feed['user_id'] = $this->data['UserPostComment']['user_id'];
               $commentTxt = strip_tags($this->data['UserPostComment']['text']);
               $forLinkedTxt = (strlen($commentTxt) > 165) ? substr($commentTxt, 0, 150).'...' : $commentTxt;
               $commentTxt = substr($commentTxt, 0, 65).'...';
               $serText = array(
                   'text' => 'posted a comment',
                   'comment_text' => $commentTxt
               );
               $feed['activity_text'] = serialize($serText);
               $feed['id_affected_tables'] = $this->data['UserPostComment']['user_post_id'];
               $feed['type_feed'] = 'comment_post';
               $ActivityWall = ClassRegistry::init('ActivityWall');
               $ActivityWall->save($feed);

               $notifyText['facebook'] = 'Posted a comment "'.$commentTxt.'"';
               $notifyText['twitter'] = 'Posted a comment "'.$commentTxt.'"';

               $notifyText['linkedin']['smallTitle'] = 'Did some action on the site';
               $notifyText['linkedin']['boldCaption'] = 'Posted a comment on vivarena.com';
               $notifyText['linkedin']['description'] = $forLinkedTxt;


               $SocialNotification = ClassRegistry::init('SocialNotification');
               $SocialNotification->checkAndSendNotify($feed['user_id'], 'Comments', true, $notifyText);

            }
       }

    }


}
