<?php
class LinkedIn extends AppModel  
{  
  var $useTable = false;  

  private $_key = 'zvp18u3uc071';
  private $_secret = '97zovWqEKNRaXZ4a';
  private $_accessTokenUrl = 'https://api.linkedin.com/uas/oauth/accessToken';
  private $_baseUrl = 'http://api.linkedin.com/v1/people/';
  private $_shareApi = '~/shares';
  private $_networkUpdateApi = '~/person-activities';
  private $_connectionsApi = '~/connections';

  /**
   * Validates the LinkedIn credentials. Validates the
   * - signature version
   * - signature order
   * - signature match
   *
   * https://developer.linkedin.com/documents/exchange-jsapi-tokens-rest-api-oauth-tokens
   */
  public function validateCredentials($credentials) {
    if ($credentials->signature_version != 1) {
      $this->log("unknown signature version: ".$credentials->signature_version);
      return false;
    }

    if (!$credentials->signature_order) {
      $this->log("signature order missing");
      return false;
    }

    if (!is_array($credentials->signature_order)) {
      $this->log("signature order invalid: ".$credentials->signature_order);
      return false;
    }

    // build signature based on signature order
    $encrypted_signature = '';
    foreach ($credentials->signature_order as $key) {
      if (isset($credentials->$key)) {
        $encrypted_signature .= $credentials->$key;
      } else {
        $this->log("missing signature piece: ".$key);
        return false;
      }
    }

    // decrypt the signature using our private secret
    $signature = base64_encode(hash_hmac('sha1', $encrypted_signature, $this->_secret, true));

    // validate signature match 
    if ($signature == $credentials->signature) {
      return true;
    } else {
      $this->log("signature validation failed");
      return false;
    }

    // You shouldn't get here.
    return false;
  }

  /**
   * Exchange the oauth2 access_token for an oauth1a token.
   *
   * The LinkedIn JS API uses oauth2. LI sends us a perfectly good oauth2 access_token. 
   * But I can't use it because the REST API only supports oauth1a. So I need to transform the token.
   */
  public function exchangeAccessToken($access_token) {
    $oauth = new OAuth($this->_key, $this->_secret);
    $oauth->fetch($this->_accessTokenUrl, array('xoauth_oauth2_access_token' => $access_token), OAUTH_HTTP_METHOD_POST);
    parse_str($oauth->getLastResponse(), $response);
    return $response;
  }

   /**
   * Get the LinkedIn profile object.
   */
  public function profile($accessToken, $tokenSecret) {
    $oauth = new OAuth($this->_key, $this->_secret);
    $oauth->setToken($accessToken, $tokenSecret);

    $url = $this->_baseUrl . '~';
    $url .= ':(';
    $url .= 'id,first-name,last-name,location,industry,summary,picture-url,public-profile-url,email-address,languages,three-current-positions,date-of-birth,main-address';
    $url .= ')';
    $oauth->fetch($url, array(), OAUTH_HTTP_METHOD_GET, array('x-li-format' => 'json'));
    $profile = json_decode($oauth->getLastResponse());
    return $profile;
  }

  /**
   * Uploads the LinkedIn picture to the filesystem and returns the URL path.
   */
  public function picture($pictureUrl, $liid) {
    if (is_null($pictureUrl)) return null;

    $url_path = DS . 'uploads' . DS . 'userfiles' . DS . 'linkedin_' . $liid;
    $local_dir = rtrim(WWW_ROOT, DS) . $url_path;
    if (!is_dir($local_dir)) {
      mkdir($local_dir, 0777, true);
    }

    $uniq = uniqid();
    $local_file = $local_dir . DS . 'photo_' . $uniq . '.jpg';
    copy($pictureUrl, $local_file);

    $url_file = $url_path . DS . 'photo_' . $uniq . '.jpg';
    return $url_file;
  }

  public function parseLinkedInProfile($profile) {
    $user = array();
    $user['linkedin_id'] = $profile->id;
    $user['first_name'] = $profile->firstName;
    $user['last_name'] = $profile->lastName;
    $user['email'] = $profile->emailAddress;
    if (!empty($profile->summary)) {
      $user['about_me'] = $profile->summary;
    }
    if (isset($profile->industry)) {
      $user['industry'] = $profile->industry;
    }

    if (isset($profile->threeCurrentPositions, $profile->threeCurrentPositions->values)) {
      $position = $profile->threeCurrentPositions->values[0];
      $user['company'] = $position->company->name;
      $user['position'] = $position->title;
    }

    if (!empty($profile->mainAddress) && !empty($profile->location) && !empty($profile->location->country)) {
      $user['address'] = $profile->mainAddress . '\n' . $profile->location->name . '\n' . $profile->location->country->code;
    }

    if (!empty($profile->pictureUrl)) {
      $user['photo'] = $this->picture($profile->pictureUrl, $profile->id);
    }
    if (strpos($profile->publicProfileUrl, 'http://www.linkedin.com/in/') !== false) {
      $user['login'] = str_replace('http://www.linkedin.com/in/', '', $profile->publicProfileUrl);
    } 

    if (!empty($profile->dateOfBirth)) {
      $dob = $profile->dateOfBirth;
      $user['birthday'] = strftime('%Y-%m-%d', strtotime($dob->year . '-' . $dob->month . '-' . $dob->day));
    }

    if (!empty($profile->languages)) {
      $lang = 'Spanish';
      $langs = $profile->languages->values;
      foreach ($langs as $l) {
        if ($l->language->name == 'English') {
          $lang = 'English';
          break;
        } else if ($l->language->name == 'Portuguese') {
          $lang = 'Portugese'; // we misspelled it in the db
          break;
        }
      }
      $user['lang'] = $lang;
    }

    if (!empty($profile->location->country)) {
      $countryCode = $profile->location->country->code;
      $countryCode = strtoupper($countryCode);
      $country = ClassRegistry::init('Country')->find('first', array('conditions' => array('iso' => $countryCode)));
      if ($country) {
        $user['country_id'] = $country['Country']['id'];
      }
      if ($country['Country']['iso3'] == 'USA') {
        $user['usa_residence'] = true;
      }
    }

    return $user;
  }

  /**
   * @param $oauthToken
   * @param $oauthTokenSecret
   * @param $smallTitle
   * @param $boldCaption
   * @param $description
   * @param string $url
   * @return array|null
   */
  public function shareContent($oauthToken, $oauthTokenSecret, $smallTitle, $boldCaption, $description, $url = 'http://vivarena.com') {
    $body = new stdClass();
    $body->comment = strip_tags($smallTitle);

    $body->content = new stdClass();
    $body->content->title = strip_tags($boldCaption);
    $body->content->description = strip_tags($description);
    $body->content->{'submitted-url'} = strip_tags($url);
    $body->content->{'submitted-image-url'} = 'https://vivarena.com/img/logo.jpg';

    $body->visibility = new stdClass();
    $body->visibility->code = 'anyone';

    $body_json = json_encode($body);

    $url = $this->_baseUrl.$this->_shareApi;

    return $this->send($url, $body_json, $oauthToken, $oauthTokenSecret);

    /*
     * Regular network update.
    $body = new stdClass();
    $body->{'content-type'} = 'linkedin-html';
    $body->body = htmlspecialchars($description);

    $body_json = json_encode($body);

    $url = $this->_baseUrl.$this->_networkUpdateApi;
    return $this->send($url, $body_json, $oauthToken, $oauthTokenSecret);
     */
  }

  /**
   * API call to POST data
   *
   * @param $path
   * @param $data  array/object for json or an string for xml/json
   * @param $oauthToken
   * @param $oauthTokenSecret
   * @param string $type  "json" or "xml"
   * @throws Exception
   * @return array|null response
   */
  public function send($path, $data, $oauthToken, $oauthTokenSecret, $type = 'json') {
    switch ($type) {

    case 'json':
      $contentType = array("Content-Type" => "application/json", "x-li-format" => "json");
      if (!is_string($data)) {
        $data = json_encode($data);
      }
      break;

    case 'xml':
      $contentType = array("Content-Type" => 'text/xml');
      break;

    default:
      throw new Exception('Type: "'.$type.'" not supported');
    }
    try {
      $oauth = new OAuth($this->_key, $this->_secret);
      $oauth->setToken($oauthToken, $oauthTokenSecret);
      $oauth->fetch($path, $data, OAUTH_HTTP_METHOD_POST, $contentType);
      parse_str($oauth->getLastResponse(), $responseText);
    } catch (Exception $e) {
      return array('error' => $e->getMessage());
    }

    $response = $this->_decode($responseText);
    # usually returns nothing at all when it's working correctly
    # errors are OAuthExceptions
    if (isset($response['error'])) {
      return array('error' => $response['error']['message']);
    }
    return $response;
  }

  /**
   * Decodes the response based on the content type
   *
   * @param string $response
   * @param string $contentType
   * @return void
   * @author Dean Sofer
   */
  private function _decode($response, $contentType = 'application/xml') {
    // Extract content type from content type header
    if (preg_match('/^([a-z0-9\/\+]+);\s*charset=([a-z0-9\-]+)/i', $contentType, $matches)) {
      $contentType = $matches[1];
      $charset = $matches[2];
    }

    // Decode response according to content type
    switch ($contentType) {
    case 'application/xml':
    case 'application/atom+xml':
    case 'application/rss+xml':
      App::import('Core', 'Xml');
      $Xml = new Xml($response);
      $response = $Xml->toArray(false); // Send false to get separate elements
      $Xml->__destruct();
      $Xml = null;
      unset($Xml);
      break;
    case 'application/json':
    case 'text/javascript':
      $response = json_decode($response, true);
      break;
    }
    return $response;
  }

  public function friends($oauthToken, $oauthTokenSecret) {
    $oauth = new OAuth($this->_key, $this->_secret);
    $oauth->setToken($oauthToken, $oauthTokenSecret);
    $url = $this->_baseUrl . $this->_connectionsApi;
    $oauth->fetch($url, array(), OAUTH_HTTP_METHOD_GET, array('x-li-format' => 'json'));
    $friends = json_decode($oauth->getLastResponse());
    return $friends;
  }
}
?>
