<?php
class ThumbsControlComponent extends Object
{
	/**
	 * Directory for cached thumbnails
	 * @var string
	 */
	private $cacheDirectory;
	/**
	 * Default image (show if image not exists)
	 * @var string
	 */
	private $defaultImage;

	public function __construct()
	{
		$this->cacheDirectory = WWW_ROOT . 'thumbs' . DS;
		$this->defaultImage = 'img' . DS . 'no_image' . DS . 'default.png';
	}

	public function removeCachedThumbs($path = null)
	{
		if($path) {
			$this->_clearThumbsByPath($path);
		} else {
			$this->_clearAllThumbs();
		}
	}

	public function removeNoImageThumbs()
	{
		$this->_clearThumbsByPath($this->defaultImage);
	}

	private function _clearThumbsByPath($path)
	{
		$folder = new Folder($this->cacheDirectory);

		$filename = basename($path);

		$result = $folder->findRecursive($filename);
		foreach($result as $thumb)
		{
			$folder->delete(dirname($thumb));
		}
	}

	private function _clearAllThumbs()
	{
		$folder = new Folder($this->cacheDirectory);

		list($thumbsFolders) = $folder->read();
		foreach($thumbsFolders as $item) 
		{
			$folder->delete($this->cacheDirectory . DS . $item);
		}
	}

	public function gc()
	{
		throw new Exception('This feature yet not implemented');
	}
}