<?php
class PaymentsPlugin_CurlRequest implements PaymentsPlugin_IRequest
{
    /**
     * cURL handler
     * @var Resource
     */
    private $_ch = null;
    /**
     * cURL error
     * @var int
     */
    private $_error = null;
    /**
     * URL value
     * @var string
     */
    private $_url = null;

    public function __construct()
    {
        $this->_ch = curl_init();
    }
    
    public function __destruct()
    {
        if(!is_null($this->_ch)) {
            curl_close($this->_ch);
        }
    }

    public function doPost($data)
    {
        return $this->_doRequest($data, 'post');
    }

    public function getError()
    {
        return $this->_error;
    }

    public function setUrl($url)
    {
        $this->_url = $url;
    }

    private function _doRequest($data, $type)
    {
        if(empty($this->_url)) {
            throw new Exception('empty URL');
        } else {
            $this->_setOption(CURLOPT_URL, $this->_url);
        }

        if($type == 'post') {
            $this->_setOption(CURLOPT_POST, 1);
            $this->_setOption(CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            throw new Exception('not implemented yet');
        }

        $additionalOptions = array(
            CURLOPT_HEADER => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 1,
        );
        $this->_setOptions($additionalOptions);
        
        $response = curl_exec($this->_ch);
        if($response === false) {
            $this->_error = curl_error($this->_ch);

            return false;
        } else {
            return $response;
        }
    }

    private function _setOption($name, $value)
    {
        curl_setopt($this->_ch, $name, $value);
    }

    private function _setOptions($options)
    {
        curl_setopt_array($this->_ch, $options);
    }
}