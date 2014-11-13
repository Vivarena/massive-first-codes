<?php

class UserSponsor extends AppModel
{
    public $name = 'UserSponsor';
    public $actsAs = array('Containable');

    public $belongsTo = array(
        'User'
    );

    public $hasMany = array(
        'Rating' =>
             array(
                 'className'   => 'Rating',
                 'foreignKey'  => 'model_id',
                 'conditions' => array('Rating.model' => 'UserSponsor'),
                 'dependent'   => true,
                 'exclusive'   => true
             ),
        'UserSponsorReview'
    );

    /**
     * @var array allowed types
     */
    private $types = array('sponsor', 'gear');

    private $getReview = false;

    public function beforeSave() {
        if(!in_array($this->data['UserSponsor']['type'], $this->types)) {
            return false;
        }
        return true;
    }

    /**
     * After saving sponsor or gear - add him to activity wall
     *
     * @param bool $created
     */
    public function afterSave($created)
    {
        if ($created) {

            $feed['user_id'] = $this->data['UserSponsor']['user_id'];
            $serText = array(
                'text' => 'added a new '.ucfirst((string)$this->data['UserSponsor']['type']),
                'img' => $this->data['UserSponsor']['logo'],
            );
            $feed['id_affected_tables'] = $this->id;
            $feed['activity_text'] = serialize($serText);
            $feed['type_feed'] = 'sponsor';
            $ActivityWall = ClassRegistry::init('ActivityWall');
            $ActivityWall->create();
            $ActivityWall->save($feed);

        }
    }

    public function getAll($uID, $type = 'sponsor')
    {
        $sponsors = $this->find('all', array(
            'conditions' => array(
                'UserSponsor.user_id' => $uID,
                'UserSponsor.type' => $type
            ),
            'recursive' => -1
        ));

        return $sponsors;
    }

    public function withReviews()
    {
        $this->getReview = true;
        return $this;
    }

    public function getSponsor($uID, $id, $type = 'sponsor')
    {
        $reviewContain = array(
            'UserSponsorReview' => array(
                'User' => array(
                    'UserInfo' => array(
                        'fields' => array('UserInfo.avatar', 'UserInfo.photo')
                    ),
                    'Rating' => array(
                        'conditions' => array(
                            'Rating.model_id' => $id,
                            'Rating.model' => $this->alias
                        )
                    ),
                ),
                'order' => 'UserSponsorReview.created DESC'
            )
        );
        $contain = ($this->getReview) ? $reviewContain : array();
        $sponsors = $this->find('first', array(
            'contain' => $contain,
            'conditions' => array(
                'UserSponsor.user_id' => $uID,
                'UserSponsor.type' => $type,
                'UserSponsor.id' => $id
            )
        ));
        return $sponsors;
    }

    public function isReviewOwner($ownId, $id)
    {
        $check = $this->find('count', array(
            'conditions' => array(
                'UserSponsor.user_id' => $ownId,
                'UserSponsor.id' => $id
            ), 'recursive' => -1,
        ));
        return (bool)$check;
    }

    public function invalidateCache($uID, $type = 'sponsor')
    {
        Cache::delete($type.'_'.$uID);
    }


    public function getActive($uID, $type = 'sponsor', $withUser = false)
    {
        $contain = ($withUser) ? array('User' => array('fields' => 'User.login')) : array();
        $cacheName = $type.'_'.$uID;
        $sponsor = Cache::read($cacheName);

        if ($sponsor === false) {
            $sponsor = $this->find('first', array(
                'contain' => $contain,
                'conditions' => array(
                    'UserSponsor.user_id' => $uID,
                    'UserSponsor.active' => 1,
                    'UserSponsor.type' => $type
                )
            ));
            if ($sponsor) Cache::write($cacheName, $sponsor);
        }

        return $sponsor;

    }



}