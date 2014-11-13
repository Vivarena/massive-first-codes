<?php
/**
* Created by Slava Basko
* Email: basko.slava@gmail.com
* Date: 7/29/13
* Time: 5:05 PM
*/

class MessengerComponent extends Object {

    /**
     * @var Controller
     */
    private $controller;

    /**
     * @var Message
     */
    private $Message;

    /**
     * @var null Sender ID
     */
    private $currentUserId = null;

    /**
     * @var null Receiver ID
     */
    private $userId = null;

    /**
     * @var string Message subject
     */
    private $subject = '';

    /**
     * @var string Message content
     */
    private $content = '';

    /**
     * @var bool Message status
     */
    private $status = false;

    /**
     * @param object $controller Like __construct method
     */
    function initialize(&$controller) {
        $this->controller =& $controller;
        $this->controller->loadModel('Message');
        $this->Message = $this->controller->Message;
        $this->currentUserId = $this->controller->Auth->user('id');
    }

    /**
     * Set receiver ID
     *
     * @param null $userId
     * @return $this
     */
    public function To($userId = null) {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Set subkect
     *
     * @param string $subject
     * @return $this
     */
    public function Subject($subject = '') {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return $this
     */
    public function Content($content = '') {
        $this->content = $content;
        return $this;
    }

    public function Status($status) {
        if(!is_bool($status)) {
            throw new Exception('Message status must be TRUE or FALSE. '.strtoupper(gettype($status)).' given.');
        }
        $this->status = $status;
        return $this;
    }

    /**
     * Save message for user
     *
     * @return bool
     */
    public function Save() {
        $this->Message->set(array(
            'to_id' => $this->userId,
            'from_id' => $this->currentUserId,
            'subject' => $this->subject,
            'content' => $this->content,
            'status' => $this->status
        ));
        if(!$this->Message->save()) {
            return false;
        }
        return true;
    }

}