<?php
/**
 * User: Etoro
 */
class PrivatizableBehavior extends ModelBehavior {

  function setup(&$model, $config = array()) {
    # default owner_id is 'id' (i.e. for the User model)
    $owner_id = isset($config['owner_id']) ? $config['owner_id'] : 'id';

    # setups don't immediately precede their associated finds
    # e.g. setup UserInfo, then setup User, then find UserInfo
    # so we need to bloat the settings so they don't get overwritten
    $this->settings[$model->alias]['owner_id'] = $owner_id;

    return true;
  }

  /**
   * Hide private model fields.
   */
  public function afterFind(&$model, $results, $primary) {
    # empty results
    if (sizeof($results) === 0) return $results;

    # probably a find('count')
    if (!isset($results[0][$model->alias])) return $results;

    # get the owner_id value specified in the config settings
    # owner_id MUST be in the results. Otherwise don't privatize.
    $id = isset($results[0][$model->alias][$this->settings[$model->alias]['owner_id']]) ? $results[0][$model->alias][$this->settings[$model->alias]['owner_id']] : null;
    if ($id === null) return $results;

    # get the user's privacy settings
    $user_privacy = ClassRegistry::init('UserPrivacy');
    $user_privacy->cacheQueries = true;
    $privacy = $user_privacy->find('all', array('contain' => false, 'conditions' => array('user_id' => $id)));
    $user_privacy->cacheQueries = false;
    $privacy = Set::extract('/UserPrivacy/.', $privacy);
    $privacy = isset($privacy[0]) ? $privacy[0] : $privacy;

    $privacy_level = $this::get_privacy_level($id);

    $results = $this->privatize($results, $privacy_level, $privacy);
    return $results;
  }

  /**
   * Remove fields from the results that are not visible according to the privacy.
   */
  private function privatize($results, $privacy_level, $privacy) {
    # If it's your own data, don't bother to privatize.
    if ($privacy_level === 1) return $results;

    foreach ($results as $key => $value) {
      if ($this->is_excluded_field($key)) {
        continue;
      }

      if (is_array($value)) {
        # assumes association name matches privacy column name
        # e.g. UserSuggestion association controlled by Privacy['UserSuggestion']
        if (isset($privacy[$key]) && !is_int($key) && $privacy_level > $privacy[$key]) {
          $results[$key] = null;
        } else {
          # climb the tree, because CakePHP finders return trees
          $results[$key] = $this->privatize($value, $privacy_level, $privacy);
        }
      } else {
        if (isset($privacy[$key]) && $privacy_level > $privacy[$key]) {
          # privacy level is not strong enough to view this field
          # clobber this field in the results
          $results[$key] = '';
        }
      }
    }
    return $results;
  }

  # fields to skip when privatizing
  # these are always public
  static $excluded_fields = array('id', 'created', 'modified');

  /**
   * Certain important fields are always public.
   */
  private function is_excluded_field($field) {
    # 0 (array index) is considered "in" the array (because PHP sucks)
    if (in_array($field, self::$excluded_fields) && !is_int($field)) {
      # literal exclusions
      return true;
    } elseif (stripos(strrev($field), 'di_') === 0) {
      # exclude foreign keys: fields that end with '_id'
      return true;
    } else {
      return false;
    }
  }

  static function detach(&$model) {
    $model.detach('Privatizable');
  }

  static function get_privacy_level($owner_id) {
    # use cakephp_static_user
    $logged_in_user = User::get('id');

    $privacy_level = 4; #default to show public only

    if ($logged_in_user) {
      if ($owner_id == $logged_in_user) {
        $privacy_level = 1; # my own stuff -> private
      } else {
        $user_friend = ClassRegistry::init('UserFriend');
        # use the UserFriend association
        $friends = $user_friend->find('all', array(
          'contain' => false,
          'conditions' => array(
            'user_id' => $owner_id,
            'approved' => true
          )
        ));
        $friends = Set::extract('/UserFriend/friend_id', $friends);
        if (in_array($logged_in_user, $friends)) {
          $privacy_level = 2; # friends-only
        } else {
          $privacy_level = 3; # network-only
        }
      }
    }

    return $privacy_level;
  }
}
