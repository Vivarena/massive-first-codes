<?php
App::import('Vendor', 'kcaptcha.kcaptcha');

class KcaptchaController extends AppController
{
    var $uses = array();

	function beforeFilter()
	{
		if (isset($this->Auth)) {
			$this->Auth->allow('index');
		}
	}
	
	function index()
	{
		$captcha = new KCAPTCHA();
		$this->Session->write('captcha_keystring', $captcha->getKeyString());
	}
}

?>