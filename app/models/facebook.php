<?php
class Facebook extends AppModel  
{  
  var $useTable = false;  

  private $_app_id = '691338290876178';
  private $_secret = '58ee0d1b16d3f9b312b91ed5181231f7';
  //private $_app_id = '559801934073207';
  //private $_secret = '0787ed428052e4e8844200f89ef4e66b';

  private $_base_url = "https://graph.facebook.com/";
  private $_fql_url = "https://graph.facebook.com/fql?q=";
  private $_oauth_url = "https://graph.facebook.com/oauth/";

  /**
   * Get the fb user object.
   *
   * @param $uid Facebook user id (aka facebook_id).
   * @param $access_token Facebook access token.
   */
  public function user($uid, $access_token) {
    $url = $this->_base_url . $uid . '?access_token=' . $access_token;
    $fbuser = json_decode(file_get_contents($url));

    $location = $this->location($uid, $access_token);
    if (isset($location->data[0]->current_location)) {
      $fbuser->current_location = $location->data[0]->current_location;
    }

    return $fbuser;
  }


    /**
     * Post the message on the wall of Facebook.
     * @param $token
     * @param $msg
     * @param string $uid
     * @return array|mixed
     */
    public function postWall($token, $msg, $uid = 'me')
    {
        $url = $this->_base_url . $uid . '/feed';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);

        $data = array(
            'access_token' => $token,
            'message' => strip_tags($msg),
            'picture' => 'https://vivarena.com/img/logo.jpg',
            'link' => $_SERVER['SERVER_NAME'],
            'name' => 'vivarena.com - Largest Community of Athletes and Sports Enthusiastic',
            'caption' => 'Claim your personal domain today, before someone else gets ahead of you!'
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $fbResult = curl_exec($ch);
        curl_close($ch);

        return json_decode($fbResult);
    }

  /**
   * Uploads the Facebook picture to the filesystem and returns the URL path.
   *
   * @param $fbid Facebook user id
   */
  public function picture($fbid) {
    if (is_null($fbid)) return null;

    $url = $this->_base_url . $fbid . '/picture?type=large';

    $url_path = DS . 'uploads' . DS . 'userfiles' . DS . 'facebook_' . $fbid;
    $local_dir = rtrim(WWW_ROOT, DS) . $url_path;
    if (!is_dir($local_dir)) {
      mkdir($local_dir, 0777, true);
    }

    $uniq = uniqid();
    $local_file = $local_dir . DS . 'photo_' . $uniq . '.jpg';
    copy($url, $local_file);

    $url_file = $url_path . DS . 'photo_' . $uniq . '.jpg';
    return $url_file;
  }

  /**
   * Gets user's location data from the Facebook FQL API.
   */
  public function location($fbid, $access_token) {
    return $this->fql($access_token, "SELECT current_location FROM user WHERE uid = ".$fbid);
  }

  /**
   * Public unauthenticated api data: graph.facebook.com/$id
   */
  public function graph($fbid) {
    $url = $this->_base_url . $fbid;
    $fbobj = json_decode(file_get_contents($url));
    return $fbobj;
  }

  /**
   * FQL API.
   */
  public function fql($access_token, $fql) {
    $url = $this->_fql_url . urlencode($fql) . '&access_token=' . $access_token;
    $result = json_decode(file_get_contents($url));
    return $result;
  }

  /**
   * Parse the fb object into a user.
   *
   * @param $fbuser The return result from user. This is an object, not an array.
   */
  public function parseFacebookUser($fbuser) {
    $user = array();
    $user['facebook_id'] = $fbuser->id;
    $user['first_name'] = $fbuser->first_name;
    $user['last_name'] = $fbuser->last_name;
    if (isset($fbuser->email)) {
      $user['email'] = $fbuser->email;
    }
    if (isset($fbuser->work)) {
      $user['company'] = $fbuser->work[0]->employer->name;
    }
    if (isset($fbuser->location)) { 
      $user['address'] = $fbuser->location->name;
    }
    $user['photo'] = $this->picture($fbuser->id);
    if (isset($fbuser->username)) {
      $user['login'] = $fbuser->username;
    }

    $user['sex'] = $fbuser->gender === 'male' ? 'M' : 'F';
    if (isset($user['birthday'])) {
      $user['birthday'] = strftime('%Y-%m-%d', strtotime($fbuser->birthday));
    }
    if (isset($fbuser->relationship_status)) {
      $user['marital_status'] = $fbuser->relationship_status === 'Married' ? 'Married' : 'Single';
    }


    $locale = $fbuser->locale;
    # Portuguese is spelled incorrectly in the db
    $langs = array('en' => 'English', 'es' => 'Spanish', 'pt' => 'Portugese');
    foreach ($langs as $code => $lang) {
      if (strpos($locale, $code, 0) === 0) {
        $user['lang'] = $lang;
      }
    }
    if (!isset($user['lang'])) $user['lang'] = 'Other';


    if (isset($fbuser->current_location)) {
      $fbcountry = $fbuser->current_location->country;
      $country = ClassRegistry::init('Country')->find('first', array('conditions' => array('name' => $fbcountry)));
      if ($country) {
        $user['country_id'] = $country['Country']['id'];
        if ($country['Country']['iso3'] == 'USA') {
          $user['usa_residence'] = true;
        }
      }
    }

    return $user;
  }

  public function friends($fbid, $access_token) {
    if (is_null($fbid) || is_null($access_token)) {
      return new stdClass();
    }
    $url = $this->_base_url . $fbid . '/friends/?access_token=' . $access_token;
    $fbobj = json_decode(file_get_contents($url));
    return $fbobj;
  }

  public function exchangeToken($access_token) {
    $url = $this->_oauth_url . "access_token?grant_type=fb_exchange_token&client_id=$this->_app_id&client_secret=$this->_secret&fb_exchange_token=$access_token";
    $response = parse_str(file_get_contents($url));
    return $access_token;
  }
}
?>
