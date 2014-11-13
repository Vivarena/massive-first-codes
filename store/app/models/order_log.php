<?php
class OrderLog extends AppModel implements PaymentsPlugin_ILoggerEventListener
{
    public $name = 'OrderLog';

    public function loggerEventPerformed($data)
    {
        $this->id = null;
        $data = array(
            'gateway' => $data['Gateway'],
            'order_num' => $data['OrderNum'],
            'order_date' => $data['OrderDate'],
            'payer_name' => $data['PayerName'],
            'payer_email' => $data['PayerEmail'],
            'amount' => $data['OrderAmount'],
            'error_message' => isset($data['ErrorMsg']) ? $data['ErrorMsg'] : null,
            'status' => $data['OrderStatus'],
            'origin_status' => $data['OrderOriginStatus'],
        );
        $this->save($data);
    }

    /**
     * Return $limit last log entries
     * @param int $limit
     * @return array
     */
    public function getLast($limit = 20)
    {
        $data = $this->find('all', array(
            'order' => 'created DESC',
            'limit' => $limit,
        ));

        return Set::extract('/OrderLog/.', $data);
    }

    /**
     * Return log entries included in range
     * @param  $from
     * @param  $to
     * @return array
     */
    public function getByDateRange($from = null, $to = null)
    {
        $from = $from ? date('Y/m/d', strtotime($from)) : date('Y/m/d', strtotime('1970/01/01'));
        $to = $to ? date('Y/m/d', strtotime($to)) : date('Y/m/d', strtotime('+ 1 day'));
        $data = $this->find('all', array(
            'conditions' => array(
                'created BETWEEN ? AND ?' => array($from, $to)
            ),
            'order' => 'created DESC',
        ));

        return Set::extract('/OrderLog/.', $data);
    }
}