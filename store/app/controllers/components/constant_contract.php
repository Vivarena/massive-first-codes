<?php
/**
 * Created by CNR
 * User: Nike
 * Date: 08.12.11
 * Time: 11:26
 *
 */

class ConstantContractComponent extends Object {

    /**
     * FROM HERE YOU MAY MODIFY YOUR CREDENTIALS
     */

    /**
     * Username for your account
     * @var string
     * @access private
     */
    private $_login = '';

    /**
     * Password for your account
     * @var string
     * @access private
     */
    private $_password = '';

    /**
     * API Key for your account.
     * @var string
     * @access private
     */
    private $_apikey = '';

    /**
     * SUBSCRIBER OPTIONS
     */

    /**
     * Subscriber email address
     * @var
     */
    public $sEmailAddress = 'not specified';

    /**
     * Subscriber first name
     * @var
     */
    public $sFirstName = 'not specified';

    /**
     * Subscriber last name
     * @var
     */
    public $sLastName = 'not specified';

    /**
     * Subscriber company name
     * @var
     */
    public $sCompanyName = 'not specified';

    /**
     * Subscriber job title
     * @var
     */
    public $sJobTitle = 'not specified';

    /**
     * Subscriber home phone number
     * @var
     */
    public $sHomePhoneNumber = 'not specified';

    /**
     * Subscriber address line 1
     * @var
     */
    public $sAddressLine1 = 'not specified';

    /**
     * Subscriber address line 2
     * @var
     */
    public $sAddressLine2 = 'not specified';


    /**
     * Subscriber's city name
     * @var
     */
    public $sCityName = 'not specified';

    /**
     * Subscriber's state code
     * @var
     */
    public $sStateName;

    /**
     * Subscriber's country code
     * @var
     */
    public $sCountryName = '';

    /**
     * Subscriber's postal code
     * @var
     */
    public $sPostalCode = 'not specified';


    /**
     * CONTACT LIST OPTIONS
     * NOTE - Contact Lists will only be hidden if force_lists is set to true. This is to prevent available checkboxes form being hidden.
     */

    /**
     * Define which lists will be available for sign-up
     * @var array
     * @access private
     */
    private $_contact_lists = array(1, 2);

    /**
     * Set this to true to take away the ability for users to select and de-select lists
     * @var bool
     * @access private
     */
    private $_force_lists = false;

    /**
     * Set this to false to hide the list name(s) on the sign-up form.
     * @var bool
     * @access private
     */
    private $_show_contact_lists = true;

    /**
     * FORM OPT IN SOURCE - (Who is performing these actions?)
     */
    /**
     * Values: ACTION_BY_CUSTOMER or ACTION_BY_CONTACT
     *         ACTION_BY_CUSTOMER - Constant Contact Account holder. Used in internal applications.
     *         ACTION_BY_CONTACT - Action by Site visitor. Used in web site sign-up forms.
     * @var string
     * @access private
     */
    private $_actionBy = 'ACTION_BY_CUSTOMER';

    /**
     * DEBUGGING
     * Set this to true to see the response code returned by cURL
     * @var bool
     * @access private
     */
    private $_curl_debug = false;

    /**
     * YOUR BASIC CHANGES SHOULD END HERE
     */
    /**
     * this contains full authentication string.
     * @var string
     * @access private
     */
    private $_requestLogin;

    /**
     * this variable will contain last error message (if any)
     * @var string
     * @access private
     */
    private $_lastError = '';

    /**
     * is used for server calls.
     * @var string
     * @access private
     */
    private $_apiPath = 'https://api.constantcontact.com/ws/customers/';

    /**
     * define which lists shouldn't be returned.
     * @var array
     * @access private
     */
    private $_doNotIncludeLists = array('Removed', 'Do Not Mail', 'Active');



    /**
     * when the object is getting initialized, the login string must be created as API_KEY%LOGIN:PASSWORD
     * @access public
     */
    public function __construct() {
        $this->requestLogin = $this->_apikey."%".$this->_login.":".$this->_password;
        $this->_apiPath = $this->_apiPath.$this->_login;

        try {
            if($this->_login == "username" || $this->_password == "password"
                    || $this->_apikey == "apikey")
            {
                throw new Exception("You need to update your credentials");
            }
        } catch (Exception $e) {
            echo "<strong>" . $e . "</strong>";
        }
    }

    /**
     * Validate an email address
     * @param $email
     * @return  TRUE if address is valid and FALSE if not.
     */
    protected function isValidEmail($email){
        return preg_match('/^[_a-z0-9-][^()<>@,;:"[] ]*@([a-z0-9-]+.)+[a-z]{2,4}$/i', $email);
    }

    /**
     * Private function used to send requests to ConstantContact server
     * @param string $request - is the URL where the request will be made
     * @param string $parameter - if it is not empty then this parameter will be sent using POST method
     * @param string $type - GET/POST/PUT/DELETE
     * @return a string containing server output/response
     */
    protected function doServerCall($request, $parameter = '', $type = "GET") {
        $request = str_replace('http://', 'https://', $request);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->requestLogin);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/atom+xml"));
        curl_setopt($ch, CURLOPT_HEADER, false); // Do not return headers
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // If you set this to 0, it will take you to a page with the http response
            switch ($type) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                break;
        }
        $emessage = curl_exec($ch);
        //if ($this->_curl_debug) {   echo $error = curl_error($ch);   }
        curl_close($ch);
        return $emessage;
    }

    /**
     * Recursive Method that retrieves all the Email Lists from ConstantContact.
     * @param string $path [default is empty]
     * @return array of lists
     */
    public function getLists($path = '') {
        $mailLists = array();

        if ( empty($path)) {
            $call = $this->_apiPath.'/lists';
        } else {
            $call = $path;
        }

        $return = $this->doServerCall($call);
        $parsedReturn = simplexml_load_string($return);
        $call2 = '';

        foreach ($parsedReturn->link as $item) {
            $tmp = $item->Attributes();
            $nextUrl = '';
            if ((string) $tmp->rel == 'next') {
                $nextUrl = (string) $tmp->href;
                $arrTmp = explode($this->_login, $nextUrl);
                $nextUrl = $arrTmp[1];
                $call2 = $this->_apiPath.$nextUrl;
                break;
            }
        }

        foreach ($parsedReturn->entry as $item) {
            if ($this->_contact_lists ){
                if (in_array((string) $item->title, $this->_contact_lists)) {
                    $tmp = array();
                    $tmp['id'] = (string) $item->id;
                    $tmp['title'] = (string) $item->title;
                    $mailLists[] = $tmp;
                }
            } else if (!in_array((string) $item->title, $this->_doNotIncludeLists)) {
                $tmp = array();
                $tmp['id'] = (string) $item->id;
                $tmp['title'] = (string) $item->title;
                $mailLists[] = $tmp;
            }
        }

        if ( empty($call2)) {
            return $mailLists;
        } else {
            return array_merge($mailLists, $this->getLists($call2));
        }

    }

    /**
     * Method that retrieves  all Registered Email Addresses.
     * @param string $email_id [default is empty]
     * @return array of lists
     */
    public function getAccountLists($email_id = '') {
        $mailAccountList = array();

        if ( empty($email_id)) {
            $call = $this->_apiPath.'/settings/emailaddresses';
        } else {
            $call = $this->_apiPath.'/settings/emailaddresses/'.$email_id;
        }

        $return = $this->doServerCall($call);
        $parsedReturn = simplexml_load_string($return);

        foreach ($parsedReturn->entry as $item) {
            $nextStatus = $item->content->Email->Status;
            $nextEmail = (string) $item->title;
            $nextId = $item->id;
            $nextAccountList = array('Email'=>$nextEmail, 'Id'=>$nextId);
            if($nextStatus == 'Verified'){
                $mailAccountList[] = $nextAccountList;
            }
        }
        return $mailAccountList;
    }




    /**
     * Method that checks if a subscriber already exist
     * @param string $email
     * @return subscriber`s id if it exists or false if it doesn't
     */
    public	function subscriberExists($email = '') {
        $call = $this->_apiPath.'/contacts?email='.$email;
        $return = $this->doServerCall($call);
        $xml = simplexml_load_string($return);
        $id = $xml->entry->id;
        if($id){ return $id; }
        else { return false; }
    }

    /**
     * Method that retrieves from Constant Contact a collection with all the Subscribers
     * If email parameter is mentioned then only mentioned contact is retrieved.
     * @param string $email
     * @param string $page
     * @return Bi-Dimenstional array with information about contacts.
     */
    public	function getSubscribers($email = '', $page = '') {
        $contacts = array();
        $contacts['items'] = array();

        if (! empty($email)) {
            $call = $this->_apiPath.'/contacts?email='.$email;
        } else {
            if (! empty($page)) {
                $call = $this->_apiPath.$page;
            } else {
                $call = $this->_apiPath.'/contacts';
            }
        }

        $return = $this->doServerCall($call);
        $parsedReturn = simplexml_load_string($return);
        // We parse here the link array to establish which are the next page and previous page
        foreach ($parsedReturn->link as $item) {
            $attributes = $item->Attributes();

            if (! empty($attributes['rel']) && $attributes['rel'] == 'next') {
                $tmp = explode($this->_login, $attributes['href']);
                $contacts['next'] = $tmp[1];
            }
            if (! empty($attributes['rel']) && $attributes['rel'] == 'first') {
                $tmp = explode($this->_login, $attributes['href']);
                $contacts['first'] = $tmp[1];
            }
            if (! empty($attributes['rel']) && $attributes['rel'] == 'current') {
                $tmp = explode($this->_login, $attributes['href']);
                $contacts['current'] = $tmp[1];
            }
        }

        foreach ($parsedReturn->entry as $item) {
            $tmp = array();
            $tmp['id'] = (string) $item->id;
            $tmp['title'] = (string) $item->title;
            $tmp['status'] = (string) $item->content->Contact->Status;
            $tmp['EmailAddress'] = (string) $item->content->Contact->EmailAddress;
            $tmp['EmailType'] = (string) $item->content->Contact->EmailType;
            $tmp['Name'] = (string) $item->content->Contact->Name;
            $contacts['items'][] = $tmp;
        }

        return $contacts;
    }

    /**
     * Retrieves all the details for a specific contact identified by $email.
     * @param string $email
     * @return array with all information about the contact.
     */
    public	function getSubscriberDetails($email) {
        $contact = $this->getSubscribers($email);
        $fullContact = array();
        $call = str_replace('http://', 'https://', $contact['items'][0]['id']);
        // Convert id URI to BASIC compliant
        $return = $this->doServerCall($call);
        $parsedReturn = simplexml_load_string($return);
        $fullContact['id'] = $parsedReturn->id;
        $fullContact['email_address'] = $parsedReturn->content->Contact->EmailAddress;
        $fullContact['first_name'] = $parsedReturn->content->Contact->FirstName;
        $fullContact['last_name'] = $parsedReturn->content->Contact->LastName;
        $fullContact['middle_name'] = $parsedReturn->content->Contact->MiddleName;
        $fullContact['company_name'] = $parsedReturn->content->Contact->CompanyName;
        $fullContact['job_title'] = $parsedReturn->content->Contact->JobTitle;
        $fullContact['home_number'] = $parsedReturn->content->Contact->HomePhone;
        $fullContact['work_number'] = $parsedReturn->content->Contact->WorkPhone;
        $fullContact['address_line_1'] = $parsedReturn->content->Contact->Addr1;
        $fullContact['address_line_2'] = $parsedReturn->content->Contact->Addr2;
        $fullContact['address_line_3'] = $parsedReturn->content->Contact->Addr3;
        $fullContact['city_name'] = (string) $parsedReturn->content->Contact->City;
        $fullContact['state_code'] = (string) $parsedReturn->content->Contact->StateCode;
        $fullContact['state_name'] = (string) $parsedReturn->content->Contact->StateName;
        $fullContact['country_code'] = $parsedReturn->content->Contact->CountryCode;
        $fullContact['zip_code'] = $parsedReturn->content->Contact->PostalCode;
        $fullContact['sub_zip_code'] = $parsedReturn->content->Contact->SubPostalCode;
        $fullContact['custom_field_1'] = $parsedReturn->content->Contact->CustomField1;
        $fullContact['custom_field_2'] = $parsedReturn->content->Contact->CustomField2;
        $fullContact['custom_field_3'] = $parsedReturn->content->Contact->CustomField3;
        $fullContact['custom_field_4'] = $parsedReturn->content->Contact->CustomField4;
        $fullContact['custom_field_5'] = $parsedReturn->content->Contact->CustomField5;
        $fullContact['custom_field_6'] = $parsedReturn->content->Contact->CustomField6;
        $fullContact['custom_field_7'] = $parsedReturn->content->Contact->CustomField7;
        $fullContact['custom_field_8'] = $parsedReturn->content->Contact->CustomField8;
        $fullContact['custom_field_9'] = $parsedReturn->content->Contact->CustomField9;
        $fullContact['custom_field_10'] = $parsedReturn->content->Contact->CustomField10;
        $fullContact['custom_field_11'] = $parsedReturn->content->Contact->CustomField11;
        $fullContact['custom_field_12'] = $parsedReturn->content->Contact->CustomField12;
        $fullContact['custom_field_13'] = $parsedReturn->content->Contact->CustomField13;
        $fullContact['custom_field_14'] = $parsedReturn->content->Contact->CustomField14;
        $fullContact['custom_field_15'] = $parsedReturn->content->Contact->CustomField15;
        $fullContact['notes'] = $parsedReturn->content->Contact->Note;
        $fullContact['mail_type'] = $parsedReturn->content->Contact->EmailType;
        $fullContact['status'] = $parsedReturn->content->Contact->Status;
        $fullContact['lists'] = array();

        if ($parsedReturn->content->Contact->ContactLists->ContactList) {
            foreach ($parsedReturn->content->Contact->ContactLists->ContactList as $item) {
                $fullContact['lists'][] = trim((string) $item->Attributes());
            }
        }

        return $fullContact;
    }

    /**
     * Method that modifies a contact State to DO NOT MAIL
     * @param string $email - contact email address
     * @return TRUE in case of success or FALSE otherwise
     */
    public	function deleteSubscriber($email) {
        if ( empty($email)) {  return false;   }
        $contact = $this->getSubscribers($email);
        $id = $contact['items'][0]['id'];
        $return = $this->doServerCall($id, '', 'DELETE');
        if (! empty($return)) {  return false;  }
        return true;
    }

    /**
     * Method that modifies a contact State to REMOVED
     * @param string $email - contact email address
     * @return TRUE in case of success or FALSE otherwise
     */
    public	function removeSubscriber($email) {
        $contact = $this->getSubscriberDetails($email);
        $contact['lists'] = array();
        $xml = $this->createContactXML($contact['id'], $contact);

        if ($this->editSubscriber($contact['id'], $xml)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Upload a new contact to Constant Contact server
     * @return TRUE in case of success or FALSE otherwise
     */
    public	function addSubscriber() {
        $contactXML = $this->createContactXML();
        $call = $this->_apiPath.'/contacts';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 0);
        $this->doServerCall($call, $contactXML, 'POST');
    }

    /**
     * Modifies a contact
     * @param string $contactUrl - identifies the url for the modified contact
     * @param string $contactXML - formed XML with contact information
     * @return TRUE in case of success or FALSE otherwise
     */
    public	function editSubscriber($contactUrl, $contactXML) {
        $return = $this->doServerCall($contactUrl, $contactXML, 'PUT');

        if (! empty($return)) {
            $parsedReturn = null;
            if (strpos($return, '<') !== false) {
                $parsedReturn = simplexml_load_string($return);
                if (! empty($parsedReturn->message)) {
                    $this->_lastError = $parsedReturn->message;
                }
            } else {
                $this->_lastError = $parsedReturn->message;
            }
            return false;
        }
        return true;
    }

    /**
     * Method that compose the needed XML format for a contact
     * @param string $id
     * @return Formed XML
     */
    public	function createContactXML($id = null) {
        if ( empty($id)) {
            $id = "urn:uuid:E8553C09F4xcvxCCC53F481214230867087";
        }

        $update_date = date("Y-m-d").'T'.date("H:i:s").'+01:00';

        $post = new SimpleXMLElement('<entry></entry>');
        $post->addAttribute('xmlns', 'http://www.w3.org/2005/Atom');

        $title = $post->addChild('title', "");
        $title->addAttribute('type', 'text');

        $post->addChild('updated', date('c'));
        $post->addChild('author', "");
        $post->addChild('id', 'data:,none');

        $summary = $post->addChild('summary', 'Contact');
        $summary->addAttribute('type', 'text');

        $content = $post->addChild('content');
        $content->addAttribute('type', 'application/vnd.ctct+xml');

        $contact = $content->addChild('Contact');
        $contact->addAttribute('xmlns', 'http://ws.constantcontact.com/ns/1.0/');

        $contact->addChild('EmailAddress',  htmlspecialchars(($this->sEmailAddress), ENT_QUOTES, 'UTF-8'));
        $contact->addChild('FirstName',     urldecode(htmlspecialchars($this->sFirstName, ENT_QUOTES, 'UTF-8')));
        $contact->addChild('LastName',      urldecode(htmlspecialchars($this->sLastName, ENT_QUOTES, 'UTF-8')));
        $contact->addChild('OptInSource', 'ACTION_BY_CUSTOMER');

        $contact->addChild("HomePhone",   htmlspecialchars($this->sHomePhoneNumber, ENT_QUOTES, 'UTF-8'));
        $contact->addChild("Addr1",       htmlspecialchars($this->sAddressLine1, ENT_QUOTES, 'UTF-8'));
        $contact->addChild("Addr2",       htmlspecialchars($this->sAddressLine2, ENT_QUOTES, 'UTF-8'));
        $contact->addChild("City",        htmlspecialchars($this->sCityName, ENT_QUOTES, 'UTF-8'));
        if (isset($this->sStateName)) {
            $contact->addChild("StateName",   htmlspecialchars($this->sStateName, ENT_QUOTES, 'UTF-8'));
        }
        $contact->addChild("CountryName", htmlspecialchars($this->sCountryName, ENT_QUOTES, 'UTF-8'));
        $contact->addChild("PostalCode",  htmlspecialchars($this->sPostalCode, ENT_QUOTES, 'UTF-8'));
        $contact->addChild("EmailType",   htmlspecialchars("HTML", ENT_QUOTES, 'UTF-8'));

        $contactList = $contact->addChild("ContactLists");

        if (is_array($this->_contact_lists)) {
            foreach ($this->_contact_lists as $tmp) {
                $contactListAttr = $contactList->addChild("ContactList");
                $contactListAttr->addAttribute("id", "http://api.constantcontact.com/ws/customers/{$this->_login}/lists/" . $tmp);
            }
        }

        $entry = $post->asXML();
        return $entry;
    }

    /**
     * Method that returns a html sample for email campaign
     * @param string $sample [default is EmailContent]: EmailContent, EmailTextContent or
     * PermissionReminder
     * @param string $type [default is html]: html or text
     * @return a default content for email content or permission reminder
     */
    public function getEmailIntro($sample = 'EmailContent', $type = 'html') {
        switch($sample){
            case 'EmailContent':
                $file = 'EmailContent.txt';
                break;
            case 'EmailTextContent':
                $file = 'EmailContent.txt';
                $type = 'text';
                break;
            case 'PermissionReminder':
                $file = 'PermissionReminder.txt';
                break;
            default:
                $file = 'EmailContent.txt';
        }

        $handle = fopen("txt/$file", "rb");
        $contents = '';
        while (!feof($handle)) {
            $contents .= fread($handle, 8192);
        }
        $contents = ($type == 'html') ? ($contents) : (trim(strip_tags($contents)));
        fclose($handle);
        return $contents;
    }


    /**
     * Method that retrieves campaingn collections from ConstantCampaign
     * If campaign_id is mentioned then only mentioned campaign is retrieved.
     * If campaign_id represents a status [SENT, DRAFT, RUNNING, SCHEDULED]
     * only the campaigns with that status will be retrieved
     * @param string $campaign_id [default is empty]
     * @param string $page
     * @return Bi-Dimenstional array with information about campaigns.
     */
    public function getCampaigns($campaign_id = '', $page = '') {
        $campaigns = array();
        $campaigns['items'] = array();

        switch($campaign_id){
            case 'SENT':
            case 'DRAFT':
            case 'RUNNING':
            case 'SCHEDULED':
                $call = $this->_apiPath.'/campaigns?status='.$campaign_id;
                break;
            case 'ALL':
                $call = (!empty($page)) ? ($this->_apiPath.$page) : ($this->_apiPath.'/campaigns');
                break;
            default:
                $call = $this->_apiPath.'/campaigns/'.$campaign_id;
        }

        $return = $this->doServerCall($call);
        $parsedReturn = simplexml_load_string($return);
        //we parse here the link array to establish which are the next page and previous page
        if($parsedReturn != false){

            foreach ($parsedReturn->link as $item) {
                $attributes = $item->Attributes();
                if (! empty($attributes['rel']) && $attributes['rel'] == 'next') {
                    $tmp = explode($this->_login, $attributes['href']);
                    $campaigns['next'] = $tmp[1];
                }
                if (! empty($attributes['rel']) && $attributes['rel'] == 'first') {
                    $tmp = explode($this->_login, $attributes['href']);
                    $campaigns['first'] = $tmp[1];
                }
                if (! empty($attributes['rel']) && $attributes['rel'] == 'current') {
                    $tmp = explode($this->_login, $attributes['href']);
                    $campaigns['current'] = $tmp[1];
                }
            }

            foreach ($parsedReturn->entry as $item) {
                $tmp = array();
                $tmp['id'] = (string) $item->id;
                $tmp['title'] = (string) $item->title;
                $tmp['name'] = (string) $item->content->Campaign->Name;
                $tmp['status'] = (string) $item->content->Campaign->Status;
                $timestamp = strtotime($item->content->Campaign->Date);
                $campaig_date = date("F j, Y, g:i a", $timestamp);
                $tmp['date'] = (string) $campaig_date;
                $campaigns['items'][] = $tmp;
            }

        }
        return $campaigns;
    }


    /**
     * Retrieves all the details for a specific campaign identified by $id.
     * @param string $id
     * @return array with all information about the campaign.
     */
    public function getCampaignDetails($id) {
        if (!empty($id)){
            $fullContact = array();
            $call = str_replace('http://', 'https://', $id);
            // Convert id URI to BASIC compliant
            $return = $this->doServerCall($call);
            $parsedReturn = simplexml_load_string($return);
            $fullCampaign['campaignId'] = $parsedReturn->id;
            $cmp_vars = get_object_vars($parsedReturn->content->Campaign);

            foreach ($cmp_vars as $var_name=>$cmp_item){
                $fullCampaign[$var_name] = $cmp_item;
            }

            $cmp_from_email = $parsedReturn->content->Campaign->FromEmail->EmailAddress;
            $fullCampaign['FromEmail'] = (string) $cmp_from_email;
            $cmp_reply_email = $parsedReturn->content->Campaign->ReplyToEmail->EmailAddress;
            $fullCampaign['ReplyToEmail'] = (string) $cmp_reply_email;
            $fullCampaign['lists'] = array();

            if ($parsedReturn->content->Campaign->ContactLists->ContactList) {
                foreach ($parsedReturn->content->Campaign->ContactLists->ContactList as $item) {
                    $fullCampaign['lists'][] = trim((string) $item->Attributes());
                }
            }
            return $fullCampaign;
        }  else {
            return false;
        }
    }

    /**
     * Check if a specific campaign exist already
     * @param string $id
     * @param string $new_name
     * @return a boolean value.
     */
    public function campaignExists($id = '', $new_name) {
        if(!empty($id)) {
            $call = $this->_apiPath.'/campaigns/'.$id;
            $return = $this->doServerCall($call);
            $xml = simplexml_load_string($return);
            if ($xml !== false) {
                $id = $xml->content->Campaign->Attributes();
                $id = $id['id'];
                $name = $xml->content->Campaign->Name;
            } else {
                $id = null;
                $name = null;
            }
            $all_campaigns = $this->getCampaigns('ALL');
            $all_campaigns = $all_campaigns['items'];
            foreach ($all_campaigns as $key=>$item) {
                if ($item['name'] == $new_name)  {
                    return 1;  // 1 - the new campaign has a similar name with an old one
                    break;
                }
            }
            /**
             * 2 - this campaign already exist
             * 0 - this is a new campaign
             */
            return ($id != null) ? (2) : (0);
        }
        return false;

    }


    /**
     * Method that delete a camaign; this will exclude
     * the removed campaign from overall statistics
     * @param string $id - campaign id
     * @return TRUE in case of success or FALSE otherwise
     */
    public function deleteCampaign($id) {
        if ( empty($id)) {  return false;  }
        $return = $this->doServerCall($id, '', 'DELETE');
        if (! empty($return) || $return === false) {  return false;  }
        return true;
    }

    /**
     * Upload a new campaign to ConstantContact server
     * @param string $campaignXML - formatted XML with campaign information
     * @return TRUE in case of success or FALSE otherwise
     */
    public function addCampaign($campaignXML) {
        $call = $this->_apiPath.'/campaigns';
        $return = $this->doServerCall($call, $campaignXML, 'POST');
        $parsedReturn = simplexml_load_string($return);
        if ($return) {
            return true;
        } else {
            $xml = simplexml_load_string($campaignXML);
            $cmp_id = $xml->content->Campaign->Attributes();
            $cmp_id = $cmp_id['id'];
            $cmp_name = $xml->content->Campaign->Name;
            if(!empty($cmp_id)) {
                $search_status = $this->campaignExists($cmp_id, $cmp_name);
                switch($search_status){
                    case 0:
                        $error = 'An Error Occurred. The campaign could not be added.';
                        break;
                    case 1:
                        $error = 'The name of the campaign already exist. Each campaign must have a distinct name.';
                        break;
                    case 2:
                        $error = 'This campaign already exists.';
                        break;
                    default:
                        $error = 'An Error Occurred. The campaign could not be added.';
                }
                $this->_lastError = $error;
            }  else {
                $this->_lastError = 'An Error Occurred. The campaign could not be added.';
            }
            return false;
        }

    }

    /**
     * Modifies a campaign
     * @param string $campaignId - identifies the id for the modified campaign
     * @param string $campaignXML - formed XML with campaign information
     * @return TRUE in case of success or FALSE otherwise
     */
    public function editCampaign($campaignId, $campaignXML) {
        $return = $this->doServerCall($campaignId, $campaignXML, 'PUT');
        if ($return === false) {
            $this->_lastError = 'An Error Occurred. The campaign could not be edited.';
            return false;
        } else {
            if (! empty($return)) {
                $parsedReturn = null;
                if (strpos($return, '<') !== false) {
                    $parsedReturn = simplexml_load_string($return);
                    if (! empty($parsedReturn->message)) {
                        $this->_lastError = $parsedReturn->message;
                    }
                } else {
                    $this->_lastError = $parsedReturn->message;
                }
                return false;
            }
            return true;
        }
    }

    /**
     * Method that validate the current campaign before sending it to server
     * @param string $id
     * @param array $params
     * @return an error message or true
     */
    public function validateCampaign( $id, $params = array() ) {
        if( trim($params['cmp_name'])== '' ) {
            $this->_lastError = '<i>Campaign Name</i> is mandatory.';
            return true;
        } elseif( trim($params['cmp_subject'])== '' ) {
            $this->_lastError = '<i>Subject</i> is mandatory.';
            return true;
        } elseif( trim($params['cmp_from_name'])== '' ) {
            $this->_lastError = '<i>From Name</i> is mandatory.';
            return true;
        } elseif( trim($params['cmp_from_email'])== '' ) {
            $this->_lastError = '<i>From Email Address</i> is mandatory.';
            return true;
        } elseif( trim($params['cmp_reply_email'])== '' ) {
            $this->_lastError = '<i>Reply Email Address</i> is mandatory.';
            return true;
        } elseif( trim($params['cmp_grt_name'])== '' ) {
            $this->_lastError = '<i>Greeting Name</i> is mandatory.';
            return true;
        } elseif( trim($params['cmp_org_name'])== '' ) {
            $this->_lastError = '<i>Organization Name</i> is mandatory.';
            return true;
        } elseif( trim($params['cmp_org_addr1'])== '' ) {
            $this->_lastError = '<i>Address 1</i> is mandatory.';
            return true;
        } elseif( trim($params['cmp_org_city'])== '' ) {
            $this->_lastError = '<i>City</i> is mandatory.';
            return true;
        } elseif( trim($params['org_zip'])== '' ) {
            $this->_lastError = '<i>Zip/Postal Code</i> is mandatory.';
            return true;
        } elseif( trim($params['org_country'])== '' ) {
            $this->_lastError = '<i>Country</i> is mandatory.';
            return true;
        } elseif( trim($params['cmp_html_body'])== '' ) {
            $this->_lastError = '<i>HTML Body</i> is mandatory.';
            return true;
        } elseif ( $params["lists"] == NULL ) {
            $this->_lastError = 'Choose at least <i>one Campaign</i> from the list.';
            return true;
        } else {
            if( trim($params['cmp_perm_reminder'])== 'YES') {
                $reminder_text =  $params['cmp_txt_reminder'];
                if(trim($reminder_text)== ''){
                    $this->_lastError = '<i>Permission Reminder</i> is required.';
                    return true;
                }
            }
            if(trim($params['org_country']) != '') {
                if( trim($params['org_country'])== 'us' ) {
                    if(trim($params['org_state_us']) == '' ){
                        $this->_lastError = '<i>State</i> is mandatory.';
                        return true;
                    }
                    if ( in_array($params['org_state_us'], $this->caStates) ) {
                        $this->_lastError = '<i>US State</i> is required.';
                        return true;
                    }
                } elseif( trim($params['org_country'])== 'ca' ) {
                    if(trim($params['org_state_us']) == '' ){
                        $this->_lastError = '<i>State</i> is mandatory.';
                        return true;
                    }
                    if ( in_array($params['org_state_us'], $this->usStates) ) {
                        $this->_lastError = '<i>CA State</i> is required.';
                        return true;
                    }
                }
            }
            if( trim($params['cmp_as_webpage'])== 'YES' ) {
                if(trim($params['cmp_as_webtxt'])== ''){
                    $this->_lastError = '<i>Webpage Text</i> is required.';
                    return true;
                }
                if(trim($params['cmp_as_weblink'])== ''){
                    $this->_lastError = '<i>Webpage Link Text</i> is required.';
                    return true;
                }
            }
            if( trim($params['cmp_forward'])== 'YES') {
                $fwd_email =  $params['cmp_fwd_email'];
                if(trim($params['cmp_fwd_email'])== ''){
                    $this->_lastError = '<i>Forward email</i> is required.';
                    return true;
                }
            }
            if( trim($params['cmp_subscribe'])== 'YES') {
                if(trim($params['cmp_sub_link'])== ''){
                    $this->_lastError = '<i>Subscribe me</i> is required.';
                    return true;
                }
            }
            else
            {
                return false;
            }
        }
        return false;
    }


    /**
     * Method that compose the needed XML format for a campaign
     * @param string $id
     * @param array $params
     * @return Formed XML
     */
    public function createCampaignXML( $id, $params = array() ) {
        if (empty($id)) {  // Add a new Campaign
            $id = str_replace('https://', 'http://', $this->_apiPath."/campaigns/1100546096289");
            $standard_id = str_replace('https://', 'http://', $this->_apiPath."/campaigns");
        } else {
            $standard_id = $id;
        }
        $href = str_replace("http://api.constantcontact.com", "", $id);
        $standard_href = str_replace("https://api.constantcontact.com", "", $this->_apiPath."/campaigns");               $update_date = date("Y-m-d").'T'.date("H:i:s").'+01:00';
        $xml_string = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><entry xmlns='http://www.w3.org/2005/Atom'></entry>";
        $xml_object = simplexml_load_string($xml_string);
        $link_node = $xml_object->addChild("link");
        $link_node->addAttribute("href", $standard_href); //[1st *href]
        $link_node->addAttribute("rel", "edit");
        $id_node = $xml_object->addChild("id", $standard_id);  //[1st *id]
        $title_node = $xml_object->addChild("title", htmlspecialchars($params['cmp_name'], ENT_QUOTES, 'UTF-8'));
        $title_node->addAttribute("type", "text");
        $updated_node = $xml_object->addChild("updated", htmlentities($update_date));
        $author_node = $xml_object->addChild("author");
        $author_name = $author_node->addChild("name", htmlentities("Constant Contact"));
        $content_node = $xml_object->addChild("content");
        $content_node->addAttribute("type", "application/vnd.ctct+xml");
        $campaign_node = $content_node->addChild("Campaign");
        $campaign_node->addAttribute("xmlns", "http://ws.constantcontact.com/ns/1.0/");
        $campaign_node->addAttribute("id", $id);  //[2nd *id]
        $name_node = $campaign_node->addChild("Name", urldecode(htmlspecialchars($params['cmp_name'], ENT_QUOTES, 'UTF-8')));
        $campaign_status =  !empty($params['cmp_status']) ? ($params['cmp_status']) : ('Draft');
        $status_node = $campaign_node->addChild("Status", urldecode(htmlentities($campaign_status)));
        $campaign_date = !empty($params['cmp_date']) ? ($params['cmp_date']) : ($update_date);
        $date_node = $campaign_node->addChild("Date", urldecode(htmlentities($campaign_date)));
        $subj_node = $campaign_node->addChild("Subject", urldecode(htmlspecialchars($params['cmp_subject'], ENT_QUOTES, 'UTF-8')));
        $from_name_node = $campaign_node->addChild("FromName", urldecode(htmlspecialchars($params['cmp_from_name'], ENT_QUOTES, 'UTF-8')));
        $view_as_webpage = (!empty($params['cmp_as_webpage']) &&  $params['cmp_as_webpage'] == 'YES') ? ('YES') : ('NO');
        $as_webpage_node = $campaign_node->addChild("ViewAsWebpage", urldecode(htmlentities($view_as_webpage)));
        #$as_web_lnk_txt = ($view_as_webpage == 'YES') ? ($params['cmp_as_weblink']) : ('');
        $as_web_lnk_txt = $params['cmp_as_weblink'];
        $as_weblink_node = $campaign_node->addChild("ViewAsWebpageLinkText", urldecode(htmlspecialchars(($as_web_lnk_txt), ENT_QUOTES, 'UTF-8')));
        #$as_web_txt = ($view_as_webpage == 'YES') ? ($params['cmp_as_webtxt']) : ('');
        $as_web_txt = $params['cmp_as_webtxt'];
        $as_webtxt_node = $campaign_node->addChild("ViewAsWebpageText", urldecode(htmlspecialchars(($as_web_txt), ENT_QUOTES, 'UTF-8')));
        $perm_reminder_node = $campaign_node->addChild("PermissionReminder", urldecode(htmlentities($params['cmp_perm_reminder'])));
        $permission_reminder_text = ($params['cmp_perm_reminder'] == 'YES') ? ($params['cmp_txt_reminder']) : ('');
        $text_reminder_node = $campaign_node->addChild("PermissionReminderText", urldecode(htmlspecialchars(($permission_reminder_text), ENT_QUOTES, 'UTF-8')));
        $grt_sal_node = $campaign_node->addChild("GreetingSalutation", htmlspecialchars(($params['cmp_grt_sal']), ENT_QUOTES, 'UTF-8'));
        $grt_name_node = $campaign_node->addChild("GreetingName", htmlentities($params['cmp_grt_name']));
        $grt_str_node = $campaign_node->addChild("GreetingString", htmlspecialchars($params['cmp_grt_str'], ENT_QUOTES, 'UTF-8'));
        $org_name_node = $campaign_node->addChild("OrganizationName", htmlspecialchars($params['cmp_org_name'], ENT_QUOTES, 'UTF-8'));
        $org_addr1_node = $campaign_node->addChild("OrganizationAddress1", htmlspecialchars($params['cmp_org_addr1'], ENT_QUOTES, 'UTF-8'));
        $org_addr2_node = $campaign_node->addChild("OrganizationAddress2", htmlspecialchars($params['cmp_org_addr2'], ENT_QUOTES, 'UTF-8'));
        $org_addr3_node = $campaign_node->addChild("OrganizationAddress3", htmlspecialchars($params['cmp_org_addr3'], ENT_QUOTES, 'UTF-8'));
        $org_city_node = $campaign_node->addChild("OrganizationCity", htmlspecialchars($params['cmp_org_city'], ENT_QUOTES, 'UTF-8'));
        switch($params['org_country']){
            case 'us':
                $us_state = $params['org_state_us'];
                break;
            case 'ca':
                $us_state = $params['org_state_us'];
                break;
            default:
                $us_state = '';
        }
        $org_state_us_node = $campaign_node->addChild("OrganizationState", htmlentities($us_state));
        switch($params['org_country']){
            case 'us':
                $international_state = '';
                break;
            case 'ca':
                $international_state = '';
                break;
            default:
                $international_state = htmlspecialchars($params['org_state'], ENT_QUOTES, 'UTF-8');
        }
        $org_state_name = $campaign_node->addChild("OrganizationInternationalState", htmlentities($international_state));
        $org_country_node = $campaign_node->addChild("OrganizationCountry", htmlentities($params['org_country']));
        $org_zip_node = $campaign_node->addChild("OrganizationPostalCode", htmlspecialchars($params['org_zip'], ENT_QUOTES, 'UTF-8'));
        $include_fwd_email = (!empty($params['cmp_forward']) && $params['cmp_forward'] == 'YES') ? ('YES') : ('NO');
        #$fwd_txt = ($include_fwd_email == 'YES') ? ($params['cmp_fwd_email']) :('');
        $fwd_txt = $params['cmp_fwd_email'];
        $fwd_node = $campaign_node->addChild("IncludeForwardEmail", htmlentities($include_fwd_email));
        $fwd_email_node = $campaign_node->addChild("ForwardEmailLinkText", htmlspecialchars(($fwd_txt), ENT_QUOTES, 'UTF-8'));
        $include_sub_link = (!empty($params['cmp_subscribe']) && $params['cmp_subscribe'] == 'YES') ? ('YES') : ('NO');
        $sub_node = $campaign_node->addChild("IncludeSubscribeLink", htmlentities($include_sub_link));
        #$sub_txt = ($include_sub_link == 'YES') ? ($params['cmp_sub_link']) : ('');
        $sub_txt = $params['cmp_sub_link'];
        $sub_link_node = $campaign_node->addChild("SubscribeLinkText", htmlspecialchars(($sub_txt), ENT_QUOTES, 'UTF-8'));
        $email_format_node = $campaign_node->addChild("EmailContentFormat", $params['cmp_mail_type']);
        if($params['cmp_type'] != 'STOCK'){
            $html_body_node = $campaign_node->addChild("EmailContent", htmlspecialchars($params['cmp_html_body'], ENT_QUOTES, 'UTF-8'));
            $text_body_node = $campaign_node->addChild("EmailTextContent", "<Text>".htmlspecialchars(strip_tags($params['cmp_text_body']), ENT_QUOTES, 'UTF-8')."</Text>");
            $campaign_style_sheet = ($params['cmp_mail_type'] == 'XHTML') ? ($params['cmp_style_sheet']) : ('');
            $style_sheet_node = $campaign_node->addChild("StyleSheet", htmlspecialchars($campaign_style_sheet, ENT_QUOTES, 'UTF-8'));
        }
        $campaignlists_node = $campaign_node->addChild("ContactLists");

        if ($params['lists']) {
            foreach ($params['lists'] as $list) {
                $campaignlist_node = $campaignlists_node->addChild("ContactList");
                $campaignlist_node->addAttribute("id", $list);
                $campaignlink_node = $campaignlist_node->addChild("link");
                $campaignlink_node->addAttribute("xmlns", "http://www.w3.org/2005/Atom");
                $campaignlink_node->addAttribute("href", str_replace("http://api.constantcontact.com", "", $list));
                $campaignlink_node->addAttribute("rel", "self");
            }
        }

        $cmp_from_email = explode('|',$params['cmp_from_email']);
        $fromemail_node = $campaign_node->addChild("FromEmail");
        $femail_node = $fromemail_node->addChild("Email");
        $femail_node->addAttribute("id", $cmp_from_email[1]);
        $femail_node_link = $femail_node->addChild("link");
        $femail_node_link->addAttribute("xmlns", "http://www.w3.org/2005/Atom");
        $femail_node_link->addAttribute("href", str_replace("http://api.constantcontact.com", "", $cmp_from_email[1]));
        $femail_node_link->addAttribute("rel", "self");
        $femail_addrs_node = $fromemail_node->addChild("EmailAddress", htmlentities($cmp_from_email[0]));               $cmp_reply_email = explode('|',$params['cmp_reply_email']);
        $replyemail_node = $campaign_node->addChild("ReplyToEmail");
        $remail_node = $replyemail_node->addChild("Email");
        $remail_node->addAttribute("id", $cmp_reply_email[1]);
        $remail_node_link = $remail_node->addChild("link");
        $remail_node_link->addAttribute("xmlns", "http://www.w3.org/2005/Atom");
        $remail_node_link->addAttribute("href", str_replace("http://api.constantcontact.com", "", $cmp_reply_email[1]));
        $remail_node_link->addAttribute("rel", "self");
        $remail_addrs_node = $replyemail_node->addChild("EmailAddress", htmlentities($cmp_reply_email[0]));             $source_node = $xml_object->addChild("source");
        $sourceid_node = $source_node->addChild("id", $standard_id);  //[3th *id]
        $sourcetitle_node = $source_node->addChild("title", "Campaigns for customer: ".$this->_login);
        $sourcetitle_node->addAttribute("type", "text");
        $sourcelink1_node = $source_node->addChild("link");
        $sourcelink1_node->addAttribute("href", "campaigns");  //[2nd *href]
        $sourcelink2_node = $source_node->addChild("link");
        $sourcelink2_node->addAttribute("href", "campaigns");  //[3th *href]
        $sourcelink2_node->addAttribute("rel", "self");
        $sourceauthor_node = $source_node->addChild("author");
        $sauthor_name = $sourceauthor_node->addChild("name", $this->_login);
        $sourceupdate_node = $source_node->addChild("updated", htmlentities($update_date));

        $entry = $xml_object->asXML();
        // $search  = array('&gt;', '\"', '&#13;', '&#10;&#13;', '"/>', '&', '&amp;lt;', '�', '�');
        // $replace = array('>', '"', '', '', '" />', '&amp;', '&lt;', '&amp;Yuml;', '&amp;yuml;');
        // $entry = str_replace($search, $replace, $entry);
        return $entry;
    }


    /**
     * @param  $sAddressLine1
     */
    public function setSAddressLine1($sAddressLine1)
    {
        $this->sAddressLine1 = $sAddressLine1;
    }

    /**
     * @param  $sAddressLine2
     */
    public function setSAddressLine2($sAddressLine2)
    {
        $this->sAddressLine2 = $sAddressLine2;
    }

    /**
     * @param  $sCityName
     */
    public function setSCityName($sCityName)
    {
        $this->sCityName = $sCityName;
    }

    /**
     * @param  $sCompanyName
     */
    public function setSCompanyName($sCompanyName)
    {
        $this->sCompanyName = $sCompanyName;
    }

    /**
     * @param  $sCountryName
     */
    public function setSCountryName($sCountryName)
    {
        $this->sCountryName = $sCountryName;
    }

    /**
     * @param  $sEmailAddress
     */
    public function setSEmailAddress($sEmailAddress)
    {
        $this->sEmailAddress = $sEmailAddress;
    }

    /**
     * @param  $sFirstName
     */
    public function setSFirstName($sFirstName)
    {
        $this->sFirstName = $sFirstName;
    }

    /**
     * @param  $sHomePhoneNumber
     */
    public function setSHomePhoneNumber($sHomePhoneNumber)
    {
        $this->sHomePhoneNumber = $sHomePhoneNumber;
    }

    /**
     * @param  $sJobTitle
     */
    public function setSJobTitle($sJobTitle)
    {
        $this->sJobTitle = $sJobTitle;
    }

    /**
     * @param  $sLastName
     */
    public function setSLastName($sLastName)
    {
        $this->sLastName = $sLastName;
    }

    /**
     * @param  $sPostalCode
     */
    public function setSPostalCode($sPostalCode)
    {
        $this->sPostalCode = $sPostalCode;
    }

    /**
     * @param  $sStateName
     */
    public function setSStateName($sStateName)
    {
        $this->sStateName = $sStateName;
    }


}