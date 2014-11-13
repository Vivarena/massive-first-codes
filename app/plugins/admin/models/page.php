<?php
class Page extends AdminAppModel
{
	public $name = 'Page';

	public $validate = array(
			'key' => array(
				'minLengthRule' => array(
					'rule' => array('minLength', 3)
				),
				'maxLengthRule' => array(
					'rule' => array('maxLength', 128)
				),
				'formatRule' => array(
					'rule' => '/^[-_a-zA-Z0-9]+$/i'
				),
				'uniqueRule' => array(
					'rule' => 'isUnique'
				)
			)
		);
    public $locale;

	function __construct($id = false, $table = null, $ds = null)
	{
		parent::__construct($id, $table, $ds);

		$this->validate['key']['minLengthRule']['message'] = __('This field cannot be less then 3 symbols', true);
		$this->validate['key']['maxLengthRule']['message'] = __('This field cannot be more then 128 symbols', true);
		$this->validate['key']['formatRule']['message'] = __('This field must contain only letters or integers, separated by "_" or "-" symbols', true);
		//TODO isUnique don't work?
		$this->validate['key']['uniqueRule']['message'] = __('This field must be unique', true);
	}

	/*public function save($data = null, $validate = true, $fieldList = array())
	{
		// if isset translation field from l18n table - validate it
		if(isset($data['Page']['title'])) {
			foreach($data['Page']['title'] as $lang => $title)
			{
				if(empty($title)) {
					$this->invalidate('title_' . $lang, __('This field cannot be empty', true));
				}
			}
		}

		return parent::save($data, $validate, $fieldList);
	}*/
}