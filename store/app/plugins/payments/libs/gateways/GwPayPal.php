<?php
class PaymentsPlugin_GwPayPal extends PaymentsPlugin_Gateway implements PaymentsPlugin_IGateway
{
    private $_sandboxUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    private $_productionUrl = 'https://www.paypal.com/cgi-bin/webscr';

    private $_merchantEmail = null;
    private $_ipnData = array();

    /**
     * Result variables
     */
    protected $_result_MerchantEmail;
    protected $_result_EventOrderId;

    public function setIpnData($ipnData)
    {
        $this->_ipnData = $ipnData;
    }

    public function setMerchantEmail($email)
    {
        $this->_merchantEmail = $email;
    }

    public function process()
    {
        //TODO check if $_ipnData is not empty
        //TODO check if $merchantEmail is not empty

        $this->_ipnData['cmd'] = '_notify-validate';
        $url = $this->isDebugModeEnabled() ? $this->_sandboxUrl : $this->_productionUrl;

        $this->request->setUrl($url);
        $result = $this->request->doPost($this->_ipnData);

        $this->_setupBaseResultVariables($this->_ipnData);

        if($result == 'VERIFIED') {
            if($this->_merchantEmail != $this->_ipnData['business']) {
                $this->_result_OrderStatus = $this->_defaultStatuses['DENIED'];
            }

            $this->_result_MerchantEmail = $this->_ipnData['business'];
            $this->_result_EventOrderId = $this->_ipnData['custom'];
        }

        $this->_sendMessages();
    }

    private function _getStatus($originStatus)
    {
        /* PayPal statuses:
            Canceled_Reversal, Completed, Denied,
            Expired, Failed, In-Progress,
            Partially_Refunded, Pending,
            Processed, Refunded, Reversed,
            Voided
        */
        $statuses = array(
            'Canceled_Reversal' => $this->_defaultStatuses['CANCELED'],
            'Completed' => $this->_defaultStatuses['COMPLETED'],
            'Denied' => $this->_defaultStatuses['DENIED'],
            'Expired' => $this->_defaultStatuses['EXPIRED'],
            'Failed' => $this->_defaultStatuses['FAILED'],
            'In-Progress' => $this->_defaultStatuses['IN_PROGRESS'],
            'Partially_Refunded' => $this->_defaultStatuses['PARTIALLY_REFUNDED'],
            'Pending' => $this->_defaultStatuses['PENDING'],
            'Processed' => $this->_defaultStatuses['PROCESSED'],
            'Refunded' => $this->_defaultStatuses['REFUNDED'],
            'Reversed' => $this->_defaultStatuses['CUSTOM'],
            'Voided' => $this->_defaultStatuses['CUSTOM'],
        );

        return isset($statuses[$originStatus]) ? $statuses[$originStatus] : $this->_defaultStatuses['UNKNOWN'];
    }

    private function _setupBaseResultVariables($data)
    {
        $this->_result_OrderNum = $data['txn_id'];
        $this->_result_OrderDate = date('r', strtotime($data['payment_date']));
        $this->_result_OrderAmount = $data['mc_gross'];
        $this->_result_OrderStatus = $this->_getStatus($data['payment_status']);
        $this->_result_OrderOriginStatus = $data['payment_status'];
        $this->_result_PayerName = $data['first_name'] . ' ' . $data['first_name'];
        $this->_result_PayerEmail = $data['payer_email'];
    }
}
