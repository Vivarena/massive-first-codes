<?php
class PaymentsPlugin_GwAuthorizeNet extends PaymentsPlugin_Gateway implements PaymentsPlugin_IGateway
{
    private $_sandboxUrl = 'https://test.authorize.net/gateway/transact.dll';
    private $_productionUrl = 'https://secure.authorize.net/gateway/transact.dll';

    private $_delimChar = '|';

    private $_paymentData = array();
    private $_additionalData = array();

    /**
     * Result variables
     */
    protected $_result_ErrorMsg;
    protected $_result_MembershipType;
    protected $_result_EventId;
    protected $_result_UserBadgeName;
    protected $_result_UserEmail;
    protected $_result_UserFirstName;
    protected $_result_UserLastName;
    protected $_result_UserCompany;
    protected $_result_UserPhone;

    public function setAdditionalData($data)
    {
        $this->_additionalData = $data;
    }

    public function setData($data)
    {
        $this->_paymentData = $data;

        $this->_paymentData['x_test_request'] = $this->isDebugModeEnabled() ? 'YES' : 'NO';

        $this->_paymentData['x_version'] = '3.1';
        $this->_paymentData['x_delim_data'] = 'TRUE';
        $this->_paymentData['x_delim_char'] = $this->_delimChar;
        $this->_paymentData['x_relay_response'] = 'FALSE';

        $this->_paymentData['x_type'] = 'AUTH_CAPTURE';
        $this->_paymentData['x_method'] = 'CC';
    }

    public function process()
    {
        $url = $this->isDebugModeEnabled() ? $this->_sandboxUrl : $this->_productionUrl;

        $this->request->setUrl($url);
        $results = explode($this->_delimChar, $this->request->doPost($this->_paymentData));

        $this->_setupResultVariables($results);

        $this->_sendMessages();
    }

    private function _getStatus($originStatus)
    {
        /* Authorize.net statuses:
            1 = Approved,
            2 = Declined,
            3 = Error,
            4 = Held for Review
        */
        $statuses = array(
            1 => $this->_defaultStatuses['COMPLETED'],
            2 => $this->_defaultStatuses['DENIED'],
            3 => $this->_defaultStatuses['FAILED'],
            4 => $this->_defaultStatuses['PENDING'],
        );

        return isset($statuses[$originStatus]) ? $statuses[$originStatus] : $this->_defaultStatuses['UNKNOWN'];
    }

    private function _setupResultVariables($data)
    {
        /**
            $response[0] = Response Code (1 = Approved, 2 = Declined, 3 = Error, 4 = Held for Review)
            $response[1] = Response Subcode (Code used for Internal Transaction Details)
            $response[2] = Response Reason Code (Code detailing response code)
            $response[3] = Response Reason Text (Text detailing response code and response reason code)
            $response[8] = Description
            $response[9] = Amount
            $response[37] = MD5 Hash (Gateway generated MD5 has used to authenticate transaction response)
            $response[39] = Card Code Response (CCV Card Code Verification response code - M = Match, N = No Match, P = No Processed, S = Should have been present, U = Issuer unable to process request)
        */

        $this->_result_OrderNum = $data[37];
        $this->_result_OrderDate = date('r');
        $this->_result_OrderAmount = $data[9];
        $this->_result_OrderStatus = $this->_getStatus($data[0]);
        $this->_result_OrderOriginStatus = $data[0];
        $this->_result_PayerName = '';
        $this->_result_PayerEmail = '';
        $this->_result_ErrorMsg = $data[3];

        // todo refactor this
        foreach ($this->_additionalData as $paramName => $paramValue) {
            $resultParamName = '_result_' . Inflector::camelize($paramName);
            $this->{$resultParamName} = $paramValue;
        }
    }
}
