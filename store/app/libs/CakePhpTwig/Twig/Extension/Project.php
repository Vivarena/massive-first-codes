<?php
/**
 * This class - an extension that implements a Twig filters specific to your site
 * Please add your Twig filters here
 */
class Twig_Extension_Project extends Twig_Extension
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        return array(
            'thumb'      => new Twig_Filter_Method($this, 'thumb_filter'),
            'toUrl'      => new Twig_Filter_Method($this, 'toUrl_filter'),
            'nl2br'      => new Twig_Filter_Method($this, 'nl2br_filter'),
            'nicetime'   => new Twig_Filter_Method($this, 'nicetime_filter'),
            'substr'     => new Twig_Filter_Method($this, 'substr_filter'),
            'char'       => new Twig_Filter_Method($this, 'char_filter'),
            'slug'       => new Twig_Filter_Method($this, 'slug_filter'),
            'pr'         => new Twig_Filter_Method($this, 'pr_filter'),
            'realId'     => new Twig_Filter_Method($this, 'realId_filter'),
            'percent'    => new Twig_Filter_Method($this, 'percent_filter'),
            'normalize'  => new Twig_Filter_Method($this, 'normalize_filter'),
            'cutTag'     => new Twig_Filter_Method($this, 'cutTag_filter'),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'specific_to_project';
    }

    function thumb_filter($src, $width = null, $height = null)
    {
        if(empty($src)) {
            $src = 'empty';
        }
        $width = (is_null($width) || $width == 0) ? '' : $width;
        $height = (is_null($height) || $height == 0) ? '' : $height;

        return "/thumbs/{$width}x{$height}/" . ltrim($src, DS);
    }

    function toUrl_filter($baseUrl, $id, $slug)
    {
        $baseUrl = trim(trim($baseUrl), '/');
        $id = trim($id);
        $slug = str_replace('.html', '', Inflector::slug(strtolower($slug), '-'));
        return "/{$baseUrl}/{$id}-{$slug}.html";


    }
    function nl2br_filter($str)
    {
        return nl2br($str);
    }

    function substr_filter($str, $start, $end)
    {
        return substr($str, $start, $end);
    }

    function realId_filter($str)
    {
        $id = explode('.', $str);
        return $id[0];
    }

    function char_filter($str)
    {
        return strtoupper(substr($str,0,1));
    }

    function slug_filter($str)
    {
        return Inflector::slug($str, "_");
    }

    function percent_filter($str)
    {
        $id = explode('-', $str);
        return trim(end($id));
    }

    function normalize_filter($str)
    {
        return ucfirst(strtolower(Inflector::humanize($str)));
    }

    function cutTag_filter($str)
    {
        return preg_replace("/<sup>/", "", $str);
    }


    function pr_filter($str)
    {

        return "<pre>" . print_r($str) . "</pre>";
    }

    function nicetime_filter($date)
    {
        if(empty($date)) {
            return "No date provided";
        }

        $periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
        $lengths         = array("60","60","24","7","4.35","12","10");

        $now             = time();
        $unix_date         = strtotime($date);

        // check validity of date
        if(empty($unix_date)) {
            return "Bad date";
        }

        // is it future date or past date
        if($now > $unix_date) {
            $difference     = $now - $unix_date;
            $tense         = "ago";

        } else {
            $difference     = $unix_date - $now;
            $tense         = "from now";
        }

        for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
            $difference /= $lengths[$j];
        }

        $difference = round($difference);

        if($difference != 1) {
            $periods[$j].= "s";
        }

        return "$difference $periods[$j] {$tense}";

    }

}