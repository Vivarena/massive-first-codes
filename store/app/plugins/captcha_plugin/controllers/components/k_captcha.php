<?php
/**
 * Created by JetBrains PhpStorm.
 * User: john
 * Date: 2/9/11
 * Time: 6:05 PM
 */
 
class KCaptchaComponent extends Object
{
    public $components = array('Session');

    public function check($key)
    {
        if($key === $this->Session->read('KCaptcha')) {
            return true;
        }

        $this->clearStoredKey();

        return false;
    }

    public function getStoredKey()
    {
        $val = $this->Session->read('KCaptcha');
        return $val;
    }

    public function clearStoredKey()
    {
        $this->Session->delete('KCaptcha');
        return true;
    }
}
