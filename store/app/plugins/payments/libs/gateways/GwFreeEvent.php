<?php
class PaymentsPlugin_GwFreeEvent extends PaymentsPlugin_Gateway implements PaymentsPlugin_IGateway
{
    private $_additionalData = array();

    /**
     * Result variables
     */
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

    public function process()
    {
        foreach ($this->_additionalData as $paramName => $paramValue) {
            $resultParamName = '_result_' . Inflector::camelize($paramName);
            $this->{$resultParamName} = $paramValue;
        }

        $this->_result_OrderNum = '-';
        $this->_result_OrderDate = date('r');
        $this->_result_OrderAmount = '0.00';
        $this->_result_OrderStatus = 'COMPLETED';
        $this->_result_OrderOriginStatus = null;
        $this->_result_PayerName = isset($this->_additionalData['UserBadgeName']) ? $this->_additionalData['UserBadgeName'] : null;
        $this->_result_PayerEmail = isset($this->_additionalData['UserEmail']) ? $this->_additionalData['UserEmail'] : null;

        $this->_sendMessages();
    }
}
