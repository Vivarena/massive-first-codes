<?php

class ActivityWall extends AppModel
{
    public $name = 'ActivityWall';

    public $belongsTo = array('User');

    public $hasMany = array('DeletedActivityWall');

    public $actsAs = array(
        'Containable'
    );


    public function getActivity($uID, $feedsInPost = false, $uIDfeed = null, $page = 1, $limit = 10)
    {
        $to = $page*$limit;
        $from = $to - $limit;


        if (!$feedsInPost) {
            $UserFriend = ClassRegistry::init('UserFriend');
            $friends = $UserFriend->find('all', array(
                'conditions' => array('UserFriend.user_id' => $uID, 'UserFriend.approved' => 1),
                'fields' => array('UserFriend.friend_id')
            ));
            $friends = Set::extract('/UserFriend/friend_id/.', $friends);
            $friends[] = $uID;
        } else {
            $friends = $uID;
            $uID = $uIDfeed;
        }
        $inPost = ($feedsInPost) ? '[from=post]' : '[from!=post]';
        $this->unbindAll();
        $feeds = $this->find('all', array(
            'joins' => array(
                array(
                  'table' => 'bs_users',
                  'alias' => 'User',
                  'type'  => 'left',
                  'conditions' => array('ActivityWall.user_id = User.id'),
                ),
                array(
                  'table' => 'bs_user_infos',
                  'alias' => 'UserInfo',
                  'type'  => 'left',
                  'conditions' => array('User.id = UserInfo.user_id'),
                ),
                array(
                  'table' => 'bs_deleted_activity_walls',
                  'alias' => 'DeletedActivityWall',
                  'type'  => 'left',
                  'conditions' => array(
                      //'User.id = DeletedActivityWall.user_id',
                      'ActivityWall.id = DeletedActivityWall.activity_wall_id'
                  ),
                )
            ),
            'conditions' => array('ActivityWall.user_id' => $friends),
            'order' => 'ActivityWall.created DESC',
            'fields' => '*',
            'limit' => $from.', '.$limit
        ));

        $i = 0;
        foreach ($feeds as &$feed)
        {
            $checkDeleted = Set::extract('/DeletedActivityWall[user_id=' . $uID .']'.$inPost.'/.', $feed);
            if ($checkDeleted) {
                unset($feeds[$i]);
                $i++;
                continue;
            }


            $feedText = unserialize($feed['ActivityWall']['activity_text']);
            $usName = $feed['UserInfo']['first_name'].' '.$feed['UserInfo']['last_name'];
            $feed['ActivityWall']['avatar'] = (!empty($feed['UserInfo']['avatar'])) ? $feed['UserInfo']['avatar'] : $feed['UserInfo']['photo'];
            $feed['ActivityWall']['photo'] = $feed['UserInfo']['photo'];
            $feed['ActivityWall']['userID'] = $feed['User']['id'];
            $idAffected = $feed['ActivityWall']['id_affected_tables'];
            $feed['ActivityWall']['userName'] = $usName;
            $feed['ActivityWall']['login'] = $feed['User']['login'];


            switch ($feed['ActivityWall']['type_feed']) {

                case 'friend':
                    $feed['ActivityWall']['text'] = $feedText['text'];
                    $feed['ActivityWall']['friendID'] = $idAffected;
                    $feed['ActivityWall']['friendName'] = $feedText['friend_name'];
                    break;
                case 'post':
                    $feed['ActivityWall']['text'] = $feedText;
                    break;
                case 'comment_post':
                    $feed['ActivityWall']['text'] = $feedText;
                    break;
                case 'video_item':
                    $VideoAlbum = $this->query('SELECT `VideoAlbum`.`name` FROM `'.$this->tablePrefix.'video_albums` AS `VideoAlbum` WHERE `id` = '.$idAffected.' LIMIT 1' );
                    $feed['ActivityWall']['albumName'] = (isset($VideoAlbum[0]['VideoAlbum']['name'])) ? $VideoAlbum[0]['VideoAlbum']['name'] : false;
                    $feed['ActivityWall']['text'] = $feedText['text'];
                    $feed['ActivityWall']['videoUrl'] = $feedText['url_video'];
                    $feed['ActivityWall']['videoCover'] = $feedText['cover_img'];
                    unset($feed['ActivityWall']['activity_text']);
                    break;
                case 'photo_album':
                    $PhotoAlbum = $this->query('SELECT `PhotoAlbum`.`name` FROM `'.$this->tablePrefix.'photo_albums` AS `PhotoAlbum` WHERE `id` = '.$idAffected.' LIMIT 1' );
                    $feed['ActivityWall']['albumName'] = (isset($PhotoAlbum[0]['PhotoAlbum']['name'])) ? $PhotoAlbum[0]['PhotoAlbum']['name'] : false;
                    $feed['ActivityWall']['text'] = $feedText['text'];
                    $feed['ActivityWall']['photos'] = $feedText['photos'];
                    unset($feed['ActivityWall']['activity_text']);
                    break;
                case 'sponsor':
                    $feed['ActivityWall']['text'] = $feedText['text'];
                    $feed['ActivityWall']['img'] = $feedText['img'];
                    break;
                case 'product':
                    $feed['ActivityWall']['text'] = $feedText['text'];
                    $feed['ActivityWall']['img'] = $feedText['img'];
                    $feed['ActivityWall']['link'] = $feedText['link'];
                    break;
                default:
                    break;
            }
            $i++;
        }

        $activity = (!$feedsInPost) ? Set::extract('/ActivityWall/.', $feeds) : $feeds;

        return $activity;
    }


    public function toActivity($type, $data)
    {
        $serText = '';
        switch ($type) {
            case 'photo_album':
                $feed['user_id'] = $data['UserPhoto']['user_id'];
                $serText = array(
                   'text' => 'add new photos to an album',
                );
                if (count($data['UserPhoto']['photos']) > 5) {
                    $serText['photos'] = array_slice($data['UserPhoto']['photos'], 0, 5);
                } else {
                    $serText['photos'] = $data['UserPhoto']['photos'];
                }
                $feed['id_affected_tables'] = $data['UserPhoto']['photo_album_id'];
                break;
            default:
                break;
        }

        $feed['activity_text'] = serialize($serText);
        $feed['type_feed'] = $type;
        $this->save($feed);

    }


}