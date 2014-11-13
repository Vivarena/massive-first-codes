<?php
/**
* Created by Slava Basko
* Email: basko.slava@gmail.com
* Date: 5/27/13
* Time: 8:07 PM
*/

class CrypterComponent extends Object {

    /**
     * @var Controller
     */
    public $controller;

    /**
     * @var null|string
     */
    private $api_key = 'jhdo26ssd23sl40kljsd1987slk29lknf01';

    /**
     * @param null $api_key
     */
    public function __construct($api_key = null) {
        if(!is_null($api_key)) {
            $this->api_key = $api_key;
        }
        return $this;
    }

    /**
     * @param $controller
     */
    function initialize(&$controller) {
        $this->controller =& $controller;
    }

    /**
     * @param $params
     * @return string
     */
    public function Crypt($params) {
        $encode = base64_encode(rtrim(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($this->api_key), json_encode($params), MCRYPT_MODE_CBC, md5(md5($this->api_key))), "\0"));
        return $encode;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function Decrypt($data) {
        $decode_data = json_decode(rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($this->api_key), base64_decode($data), MCRYPT_MODE_CBC, md5(md5($this->api_key))), "\0"), true);
        return $decode_data;
    }

}