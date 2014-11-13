<?php
class JGrowlHelper extends AppHelper
{
	public $helpers = array('Session', 'Html');

	private $_view;

	public function beforeRender()
	{
		parent::beforeRender();

		$this->_view = ClassRegistry::getObject('view');

		$jGrowlMsgCss = $this->Html->css('/admin/js/jquery/plugins/jgrowl/message_types.css');
		$jGrowlCss = $this->Html->css('/admin/js/jquery/plugins/jgrowl/jquery.jgrowl.css');
		$jGrowlJs = $this->Html->script('/admin/js/jquery/plugins/jgrowl/jquery.jgrowl_minimized.js');

		$this->_view->addScript($jGrowlMsgCss);
		$this->_view->addScript($jGrowlCss);
		$this->_view->addScript($jGrowlJs);
	}

	public function flash($key = 'flash')
	{
		if($this->Session->check('FlashMessage.' . $key)) {
			$flash = $this->Session->read('FlashMessage.' . $key);

			$this->Html->scriptStart();
			foreach($flash as $flashData)
			{
				$params = array(
					'life' => 5000,
					'theme' => 'jGrowl-type-' . $flashData['type']
				);

				if($flashData['type'] == 'error') {
					$params['sticky'] = true;
				}
				
				echo '$.jGrowl("' . $flashData['message'] . '", ' . json_encode($params) . ');';

			}
			$out = $this->Html->scriptEnd();

			$this->Session->delete('FlashMessage.' . $key);

			return $out;
		}
	}
}