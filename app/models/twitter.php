<?php
class Twitter extends AppModel
{  
  var $useTable = false;

  // VizualTech local
  //private $_key = '5U5dF1ALyrZppvc2uzhBw';
  //private $_secret = 'aYX4HSn5pex0YLEVAHpnmHGvOOj7aDtvCA9yUQIlo';
  // This shouldn't have to change because we override the oauth_callback.
  private $_key = 'nnyoBMpYAzqoTJRGJUf3A';
  private $_secret = '9bfmWa3mBSojtzqeURSP81a7H8RsM5Wy1RXGkll1M';

  private $_api = "https://api.twitter.com/1/";
  private $_requestTokenUrl = "https://api.twitter.com/oauth/request_token";
  private $_accessTokenUrl = "https://api.twitter.com/oauth/access_token";
  private $_userUrl = "https://api.twitter.com/1.1/account/verify_credentials.json";
  private $_updateUrl = "https://api.twitter.com/1.1/statuses/update.json";

  /**
   * Generate a request token. Return the redirect URL, token, and secret.
   */
  public function authorize()
  {
    $oauth = new OAuth($this->_key, $this->_secret);
    # Overrides the callback in the app settings.
    $url = 'https://'.$_SERVER['HTTP_HOST'].'/twitter/callback';
    $response = $oauth->getRequestToken($this->_requestTokenUrl, $url);
    #$this->log($response);
    # (
    #     [oauth_token] => ...
    #     [oauth_token_secret] => ...
    #     [oauth_callback_confirmed] => true
    # )
    return array_merge($response, array('redirect' => "https://api.twitter.com/oauth/authenticate?oauth_token=" . $response['oauth_token']));
  }

  /**
   * Generate an access token.
   */
  public function authenticate($oauth_verifier, $oauth_token, $oauth_token_secret) {
    $oauth = new OAuth($this->_key, $this->_secret);
    $oauth->setToken($oauth_token, $oauth_token_secret);
    $response = $oauth->getAccessToken($this->_accessTokenUrl, null, $oauth_verifier);
    # (
    #     [oauth_token] => ...
    #     [oauth_token_secret] => ...
    #     [user_id] => user's id
    #     [screen_name] => user's twitter name
    # )
    return $response;
  }

  public function user($oauth_token, $oauth_token_secret) {
    $oauth = new OAuth($this->_key, $this->_secret);
    $oauth->setToken($oauth_token, $oauth_token_secret);
    $oauth->fetch($this->_userUrl, array('include_entities' => false, 'skip_status' => true), OAUTH_HTTP_METHOD_GET);
    $response = json_decode($oauth->getLastResponse());
    return $response;
  }

  private function picture($pictureUrl, $twiID)
  {
    $url_path = DS . 'uploads' . DS . 'userfiles' . DS . 'twitter_' . $twiID;
    $local_dir = rtrim(WWW_ROOT, DS) . $url_path;
    if (!is_dir($local_dir)) {
      mkdir($local_dir, 0777, true);
    }
    $uniq = uniqid();
    $local_file = $local_dir . DS . 'photo_' . $uniq . '.jpg';
    # v1 API is deprecated: https://dev.twitter.com/docs/api/1/get/users/profile_image/%3Ascreen_name
    # No replacement for this picture API in v1.1: https://dev.twitter.com/docs/api/1.1.
    #copy("http://api.twitter.com/1/users/profile_image?screen_name=$screenName&size=original", $local_file);

    # Use the pictureUrl in the user resource.
    # http://a0.twimg.com/profile_images/1015050883/eddroid_normal.jpg =>
    # http://a0.twimg.com/profile_images/1015050883/eddroid.jpg (original size image)
    $pictureUrl = str_replace('_normal', '', $pictureUrl);
    copy($pictureUrl, $local_file);
    $url_file = $url_path . DS . 'photo_' . $uniq . '.jpg';

    return $url_file;
  }

  public function parseTwitterUser($profile)
  {
    $user = array();
    $user['twitter_id'] = $profile->id;
    $tmpUserName = explode(' ', $profile->name);
    $user['first_name'] = (isset($tmpUserName[0])) ? $tmpUserName[0] : '';
    $user['last_name'] = (isset($tmpUserName[1])) ? $tmpUserName[1] : '';

    // Email addresses are not available through the Twitter API. 
    // https://dev.twitter.com/discussions/1737
    //$user['email'] 

    if (!empty($profile->description)) {
      $user['about_me'] = $profile->description;
    }

    if (!empty($profile->location)) {
      $user['address'] = $profile->location;
    }

    if (!empty($profile->profile_image_url)) {
      $user['photo'] = $this->picture($profile->profile_image_url, $user['twitter_id']);
    }
    $user['login'] = $profile->screen_name;

    if (!empty($profile->lang)) {
      $lang = $profile->lang;
      if ($lang == 'en') {
        $lang = 'English';
      } else if ($lang == 'pt') {
        $lang = 'Portugese'; // misspelled in the db
      } else {
        $lang = 'Spanish';
      }
      $user['lang'] = $lang;
    }

    return $user;
  }

  /**
   * Tweets. Posts a status update.
   */
  public function update($oauth_token, $oauth_token_secret, $status) {
    $oauth = new OAuth($this->_key, $this->_secret);
    $oauth->setToken($oauth_token, $oauth_token_secret);
    $oauth->fetch($this->_updateUrl, array('status' => $status), OAUTH_HTTP_METHOD_POST);
    $response = json_decode($oauth->getLastResponse());
    return $response;
  }

  /**
   * Bit.ly URL shortener.
   *
   * This should probably be located somewhere else.
   */
  public function getBitLyUrl($url) {
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch,CURLOPT_URL,'http://api.bitly.com/v3/shorten?login=mikesilakov&apiKey=R_49727bfd8e60935ef119aec50ec6960c&longUrl='.urlencode($url).'&format=json');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }
}
