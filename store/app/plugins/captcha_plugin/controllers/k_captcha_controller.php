<?php
/**
 * Created by JetBrains PhpStorm.
 * User: john
 * Date: 2/9/11
 * Time: 6:15 PM
 */

App::import('vendor', 'CaptchaPlugin.kcaptcha/kcaptcha');

class KCaptchaController extends CaptchaPluginAppController
{
    public $uses = array();
    public $components = array('Session', 'Auth');

    public function beforeFilter()
    {
        Configure::write('debug', 0);

        $this->Auth->allow('*');
    }

    public function index()
    {
        $kCaptcha = new KCAPTCHA();
        $this->Session->write('KCaptcha', $kCaptcha->getKeyString());
    }
}
?>