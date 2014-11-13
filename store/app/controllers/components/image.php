<?php
/**
 * Load phpthumb library (http://phpthumb.gxdlabs.com)
 */

/**
 * Image component class
 * @author Dmitry
 * @uses phpthumb library (http://phpthumb.gxdlabs.com)
 */
class ImageComponent extends Object
{
	/**
	 * Return phpthumb object
	 * @param string $filename The path of file to load
	 * @param array $options
	 * @param bool $isDataStream
	 * @return object
	 */
	public function create($filename, $options = array(), $isDataStream = false)
	{
        App::import('Vendor', 'ThumbLib/ThumbLib');
		return PhpThumbFactory::create($filename, $options, $isDataStream);
	}
}