<?php
class SiteConfig
{
	private static $instance;

	private $_cacheConfigName = 'SiteConfig';
	private $_cacheName = 'SiteConfig';
	private $_modelName = 'Config';
	private $_params;

	private function __construct()
	{
	}

	public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

	public function getInstance()
	{
		if(!self::$instance) {
			$classname = __CLASS__;
			self::$instance = new $classname;
		}

		return self::$instance;
	}

	public function invalidate()
	{
		$self = self::getInstance();

		Cache::delete($self->_cacheName, $self->_cacheConfigName);

		$self->initialize();
	}

	public function initialize()
	{
		$self = self::getInstance();

		$defaultCache = Cache::config('default');
		if(Configure::read('debug') >= 1) {
			$duration = '+10 seconds';
		} else {
			$duration = '+999 days';
		}

		$cacheSettings = $defaultCache['settings'];
		$cacheSettings['duration'] = $duration;

		Cache::config($self->_cacheConfigName, $cacheSettings);

		$cachedData = Cache::read($self->_cacheName, $self->_cacheConfigName);
		if(!$cachedData) {
			$model = ClassRegistry::init($self->_modelName);

			$data = $model->find('all', array(
				'order' => 'group'
			));

			$self->_params = Set::combine($data, "/{$self->_modelName}/key", "/{$self->_modelName}/value", "/{$self->_modelName}/group");

			Cache::write($self->_cacheName, $self->_params, $self->_cacheConfigName);
		} else {
			$self->_params = $cachedData;
		}
	}

	public function read($key)
	{
		$self = self::getInstance();

		if(strpos($key, '.') === false) {
			$key = 'General.' . $key;
		} else {
			$key = ucfirst($key);
		}

		return Set::extract($key, $self->_params);
	}

	public function write($key, $value)
	{
		$self = self::getInstance();

		if(strpos($key, '.') === false) {
			$group = 'General';
		} else {
			$parts = explode('.', $key, 2);
			$group = ucfirst($parts[0]);
			$key = $parts[1];
		}

		if(Set::extract("{$group}.{$key}", $self->_params)) {
			$self->_params[$group][$key] = $value;
			
			return true;
		} else {
			return false;
		}
	}
}