<?php
class ThumbHelper extends AppHelper
{
	public function show($src, $width = null, $height = null)
	{
		if(empty($src)) {
			$src = 'empty';
		}
		$width = (is_null($width) || $width == 0) ? '' : $width;
     	$height = (is_null($height) || $height == 0) ? '' : $height;

		return "/thumbs/{$width}x{$height}/" . ltrim($src, DS);
	}
}