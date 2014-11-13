<?php

/**
 * @throws Exception
 * @property ImageComponent $Image
 */

class ThumbsController extends ThumbsAppController
{
	public $name = 'Thumbs';
	public $uses = array();
	public $autoRender = false;
	public $components = array('Image');

	/**
	 * Directory for cached thumbnails
	 * @var string
	 */
	private $cacheDirectory;

	/**
	 * No caching thumbnail flag
	 * @var bool default:false
	 */
	private $noCaching = false;

	/**
	 * Default image (show if image not exists)
	 * @var string
	 */
	private $defaultImage;

	/**
	 * Format of thumbnail
	 * JPG, GIF, PNG
	 * @var string
	 */
	private $thumbnailFormat = 'PNG';

	/**
	 * Source image for thumbnail
	 * @var string
	 */
	private $source;

	/**
	 * Thumbnail width
	 * @var int
	 */
	private $width;

	/**
	 * Thumbnail height
	 * @var int
	 */
	private $height;

	public function  __construct()
	{
		parent::__construct();

		$this->cacheDirectory = WWW_ROOT . 'thumbs' . DS;
		$this->defaultImage = 'img' . DS . 'no_image' . DS . 'default.png';

		if(!is_dir($this->cacheDirectory)) {
			throw new Exception('Directory is not exists or is not a directory');
		}
                
		if(!file_exists(WWW_ROOT . $this->defaultImage)) {
			throw new Exception('Default image is not exists');
		}
	}

	/**
	 * Get thumbnail mime type
	 * @return string
	 */
	private function getThumbnailMimeType()
	{
		return 'image/' . strtolower($this->thumbnailFormat);
	}

	/**
	 * Get path for cached thumbnail
	 * @return string
	 */
	private function getCachedFilePath()
	{
		$width = ($this->width != 0) ? $this->width : '';
		$height = ($this->height != 0) ? $this->height : '';

		return $this->cacheDirectory . $width . 'x' . $height . DS . $this->source;
	}

	/**
	 * Is cached file exists
	 * @return bool
	 */
	private function isCachedFileExists()
	{
		return file_exists($this->getCachedFilePath());
	}

	/**
	 * Save thumbnail in cache dir
	 * @param GDThumb $img
	 */
	private function saveCachedFile($img)
	{
		$thumbDir = dirname($this->getCachedFilePath());
		if(!file_exists($thumbDir)) {
			$folder = new Folder();
			$folder->create($thumbDir);
		}

		$img->save($this->getCachedFilePath(), $this->thumbnailFormat);
	}

	/**
	 * Parse parameters for thumbnail creating
	 */
	private function parseParams()
	{
		$pattern = '|^thumbs/([0-9]*)?x([0-9]*)?/([^/].*)?$|';
		$matches = array();

		preg_match($pattern, $this->params['url']['url'], $matches);


		if(count($matches) == 4) {
			list(, $width, $height, $src) = $matches;
		} elseif(count($matches) == 3) {
			list(, $width, $height) = $matches;
			$src = $this->defaultImage;
		} else {
			throw new Exception('Thumbnail parameters error');
		}

		if(file_exists(WWW_ROOT . $src)) {
			$this->source = $src;
		} else {
			$this->source = $this->defaultImage;
		}

		$this->width = !empty($width) ? $width : 0;
		$this->height = !empty($height) ? $height : 0;
	}

	/**
	 * Create thumbnail image
	 * @param GDThumb $img
	 */
	private function createThumbnail($img)
	{
		if($this->width != 0 && $this->height !=0)
		{
			$img->adaptiveResize($this->width, $this->height);
		}
		else
		{
			$img->resize($this->width, $this->height);
		}
	}

	function index()
	{
		$this->parseParams();

		if($this->noCaching == false && $this->isCachedFileExists())
		{
			header('Content-Type: ' . $this->getThumbnailMimeType());
			readfile($this->getCachedFilePath());
		}
		else
		{
			$img = $this->Image->create($this->source);

			$this->createThumbnail($img);

			if($this->noCaching == false)
			{
				$this->saveCachedFile($img);
			}

			$img->show();
		}
	}
}