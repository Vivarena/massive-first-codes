<?php
abstract class PaymentsPlugin_Gateway
{
    /**
     * @var array
     */
    private $_paymentEventListeners = array();
    /**
     * @var array
     */
    private $_loggerEventListeners = array();
    /**
     * @var null|\PaymentsPlugin_IRequest
     */
    protected $request = null;
    /**
     * @var bool
     */
    private $_debugMode = false;

    /**
     * Base result fields
     */
    protected $_result_OrderNum;
    protected $_result_OrderDate;
    protected $_result_OrderAmount;
    protected $_result_OrderStatus;
    protected $_result_OrderOriginStatus;
    protected $_result_PayerName;
    protected $_result_PayerEmail;

    /**
     * @var array
     */
    protected $_defaultStatuses = array();

    /**
     * @var array
     */
    private $_resultData = array();

    /**
     * @throws Exception
     * @param PaymentsPlugin_IRequest $request
     */
    public function __construct(PaymentsPlugin_IRequest $request)
    {
        if(!is_null($request)) {
            $this->request = $request;
        } else {
            throw new Exception('Uh-oh, unsupported request type');
        }

        $statuses = array(
            'UNPROCESSED', 'PROCESSED',
            'IN_PROGRESS',
            'DELIVERED', 'COMPLETED',
            'CANCELED', 'EXPIRED',
            'DENIED', 'FAILED',
            'REFUNDED', 'PARTIALLY_REFUNDED',
            'PENDING',
            'CUSTOM',
            'UNKNOWN',
        );
        $this->_defaultStatuses = array_combine($statuses, $statuses);
    }

    /**
     * @param PaymentsPlugin_IPaymentEventListener $listener
     * @return void
     */
    final public function addPaymentEventListener(PaymentsPlugin_IPaymentEventListener $listener)
    {
        if(!is_null($listener)) {
            $this->_paymentEventListeners[] = $listener;
        }
    }

    /**
     * @param PaymentsPlugin_ILoggerEventListener $listener
     * @return void
     */
    final public function addLoggerEventListener(PaymentsPlugin_ILoggerEventListener $listener)
    {
        if(!is_null($listener)) {
            $this->_loggerEventListeners[] = $listener;
        }
    }

    /**
     * @return void
     */
    final protected function _sendMessages()
    {
        $this->_createResultData();
        
        foreach($this->_loggerEventListeners as $loggerEventListener) {
            $loggerEventListener->loggerEventPerformed($this->_resultData);
        }
        foreach($this->_paymentEventListeners as $paymentEventListener) {
            $paymentEventListener->paymentEventPerformed($this->_resultData);
        }
    }

    /**
     * @param bool $debugMode
     * @return void
     */
    final public function enableDebugMode($debugMode = true)
    {
        $this->_debugMode = $debugMode;
    }

    /**
     * @return bool
     */
    final public function isDebugModeEnabled()
    {
        return $this->_debugMode;
    }

    /**
     * @return void
     */
    private function _createResultData()
    {
        $reflect = new ReflectionClass($this);

        $this->_resultData['Gateway'] = str_replace('PaymentsPlugin_Gw', '', $reflect->getName());
        
        $properties = $reflect->getProperties(ReflectionProperty::IS_PROTECTED);
        foreach($properties as $property) {
            $propName = $property->getName();
            if(strpos($propName, '_result_') !== false) {
                $this->_resultData[str_replace('_result_', '', $propName)] = $this->{$propName};
            }
        }
    }
}