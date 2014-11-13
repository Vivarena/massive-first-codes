<?php
/**
* Created by Slava Basko
* Email: basko.slava@gmail.com
* Date: 5/8/13
* Time: 12:32 PM
*/

class MegaCurlComponent extends Object {

    private $controller;

    public $response;

    private $log_file = 'MegaCurlDebug';

    private $cookie_file;

    private $ch = null;

    private $options = array();

    private $headers = array();

    public $err_code;

    public $err_string;

    public $info;

    const DEBUG_MODE = false;

    const XDEBUG = 1;

    /**
     * @param $controller Controller
     */
    function initialize(&$controller) {
        $this->controller =& $controller;
        $this->cookie_file = TMP.'cookie.txt';
    }

    /**
     * @param $name
     * @return $this
     */
    public function SetDebugFileName($name) {
        $this->log_file = (string) $name;
        return $this;
    }

    /**
     * @param $url
     * @return $this
     */
    public function SetRequestUrl($url) {
        if(filter_var($url, FILTER_VALIDATE_URL)) {
            $this->ch = curl_init($url);
            return $this;
        }
        $this->Close();
        die('It\'s joke? '.$url.' not URL.');
    }

    /**
     * @param $cookieFile
     * @return bool
     */
    private function CreateCookieFile($cookieFile) {
        try {
            $file = new SplFileObject($cookieFile, "w");
            $file->fwrite("");
            chmod($cookieFile, 0777);
            return true;
        }catch (Exception $e) {
            echo $e->getCode();
            echo $e->getMessage();
        }
        return false;
    }

    /**
     * Call this method before Execute() for save session. e.g login, shopping cart or somewhere else.
     * If you need start session for each users separate, put file name for every user. File name must
     * be unique;
     * <code>
     * $this->MegaCurl->OneSession($path_to_file);
     * </code>
     *
     * @param null $c_file Unique file name
     * @return $this Self
     */
    public function OneSession($c_file = null) {
        if($c_file !== null) {$this->cookie_file = $c_file;}
        if(!file_exists($this->cookie_file)) {
            $this->CreateCookieFile($this->cookie_file);
        }
        $this->SetOptions(array(
            'COOKIEFILE' => $this->cookie_file,
            'COOKIEJAR' => $this->cookie_file,
            'USERAGENT' => env('HTTP_USER_AGENT')
        ));
        return $this;
    }

    /**
     * If you need start session for each users separate, put file name for every user. File name must
     * be unique;
     * <code>
     * $this->MegaCurl->RenewSession($path_to_file);
     * </code>
     *
     * @param null $c_file
     * @return bool
     */
    public function RenewSession($c_file = null) {
        if($c_file !== null) {$this->cookie_file = $c_file;}
        unlink($this->cookie_file);
        return $this->CreateCookieFile($this->cookie_file);
    }

    /**
     * @param $method
     * @return $this
     */
    public function SetHttpMethod($method) {
        if(in_array($method, array('post', 'get', 'put', 'delete', 'head', 'options', 'connect'))) {
            $this->options[CURLOPT_CUSTOMREQUEST] = strtoupper((string) $method);
            return $this;
        }
        $this->Close();
        die('Are you kidding me? The are no HTTP method like - '.$method);
    }

    /**
     * @param array $options
     * @return $this
     */
    public function SetOptions(array $options)
    {
        foreach($options as $option_code => $option_value)
        {
            if (is_string($option_code) && !is_numeric($option_code)) {
                $option_code = constant('CURLOPT_' . strtoupper($option_code));
            }
            $this->options[$option_code] = $option_value;
        }
        curl_setopt_array($this->ch, $this->options);
        return $this;
    }

    /**
     * @param $header
     * @param null $content
     * @return $this
     */
    public function SetHttpHeader($header, $content = null)
    {
        $this->headers[] = $content ? (string) $header.': '.(string) $content : (string) $header;
        return $this;
    }

    /**
     * @param bool $debug
     * @return bool|mixed
     */
    public function Execute($debug = self::DEBUG_MODE)
    {
        // Set default options if not exist
        if (!isset($this->options[CURLOPT_TIMEOUT])) $this->options[CURLOPT_TIMEOUT] = 60;
        if (!isset($this->options[CURLOPT_RETURNTRANSFER])) $this->options[CURLOPT_RETURNTRANSFER] = TRUE;
        if (!isset($this->options[CURLOPT_FAILONERROR])) $this->options[CURLOPT_FAILONERROR] = TRUE;

        // if debug
        if ($debug) {
            // set xdebug cookie
            if (!isset($this->options[CURLOPT_COOKIE])) $this->options[CURLOPT_COOKIE] = 'XDEBUG_SESSION=1';
            // increase timeout
            if (!isset($this->options[CURLOPT_TIMEOUT])) $this->options[CURLOPT_TIMEOUT] = 3600;
        }

        if (!empty($this->headers)) $this->options[CURLOPT_HTTPHEADER] = $this->headers;

        // set options
        curl_setopt_array($this->ch, $this->options);

        // execute
        $this->response = curl_exec($this->ch);

        // fail
        if ($this->response === FALSE) {
            $this->err_code = curl_errno($this->ch);
            $this->err_string = curl_error($this->ch);
            $this->log($this->err_string, $this->log_file);
            $this->log($this->err_code, $this->log_file);
            $this->Close();
            return false;
        }
        // successful
        else {
            $this->info = curl_getinfo($this->ch);
            $this->Close();
            $this->ResetAllParams();
            if(true) {
                $this->log($this->info, $this->log_file);
            }
            return $this->response;
        }
    }

    /**
     * @param array $data
     * @param bool $debug
     * @return bool|mixed
     */
    public function ExecutePost(array $data, $debug = self::DEBUG_MODE) {
        $this->SetOptions(array(
            'POST' => true,
            'POSTFIELDS' => http_build_query($data)
        ));
        $this->SetHttpMethod('post');
        return $this->Execute($debug);
    }

    /**
     * @return bool
     */
    public function Close() {
        curl_close($this->ch);
        $this->ch = null;
        return true;
    }

    /**
     * @return bool
     */
    public function ResetAllParams() {
        $this->info = array();
        $this->options = array();
        $this->headers = array();
        $this->err_code = 0;
        $this->err_string = '';
        return true;
    }

}