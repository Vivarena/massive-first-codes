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
            '__'         => new Twig_Filter_Method($this, 'translate_filter'),
            'array_split_into' => new Twig_Filter_Method($this, 'array_split_into_filter'),
            'var_dump'   => new Twig_Filter_Method($this, 'vardump_filter'),
            'url2title'  => new Twig_Filter_Method($this, 'url2title_filter'),
            'inArray'  => new Twig_Filter_Method($this, 'inArray_filter'),
            # Use Text.php version instead. These were buggy.
            #'truncate'   => new Twig_Filter_Function('twig_truncate_filter', array('needs_environment' => true)),
            #'wordwrap'   => new Twig_Filter_Function('twig_wordwrap_filter', array('needs_environment' => true)),
            'avatar' => new Twig_Filter_Method($this, 'avatar_filter'),
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

    public function inArray_filter($need, $arr)
    {
        return in_array($need, $arr);
    }

    function thumb_filter($src, $width = null, $height = null)
    {
        if(empty($src)) {
            $src = 'img' . DS . 'no_image' . DS . 'default.png';
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

    function translate_filter($str, $return=true)
    {
        return __($str, $return);
    }

    function array_split_into_filter($subject, $numparts)
    {
        $res = array();
        $cnt = count($subject);
        $offset = floor($cnt / $numparts);
        $greed = $cnt % $numparts;
        $pos = 0;
        for ($i = 0; $i < $numparts; $i++) {
            if ($i < $greed) {
                $greed_offset = 1;
            } else {
                $greed_offset = 0;
            }
            $res[] = array_slice($subject, $pos, ($offset + $greed_offset), true);
            $pos += ($offset + $greed_offset);
            /*for ($z = 0; $z < ($offset + $greed_offset); $z++) {
                array_shift($subject);
            }*/
        }
        return $res;
    }

    function vardump_filter($subject) {
        echo "<pre>";
        var_dump($subject);
        echo "</pre>";
    }

    function url2title_filter($subject) {
        preg_match_all('#http[s]?://([a-z.-]+.[a-z]{2,3})#is', $subject, $matches);
        return !empty($matches[1]) ? $matches[1][0] : $subject;
    }

    function avatar_filter($subject) {
      if (!empty($subject['avatar'])) {
        return $subject['avatar'];
      } elseif (!empty($subject['photo'])) {
        return $subject['photo'];
      } else {
        return null;
      }
    }

}

// NOTE: the class has ended. if you are writing a new method paste it above
/*if (function_exists('mb_get_info')) {
    function twig_truncate_filter(Twig_Environment $env, $value, $length = 30, $preserve = false, $separator = '...')
    {
        if (mb_strlen($value, $env->getCharset()) > $length) {
            if ($preserve) {
                if (false !== ($breakpoint = mb_strpos($value, ' ', $length, $env->getCharset()))) {
                    $length = $breakpoint;
                }
            }

            return mb_substr($value, 0, $length, $env->getCharset()) . $separator;
        }

        return $value;
    }

    function twig_wordwrap_filter(Twig_Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
    {
        $sentences = array();

        $previous = mb_regex_encoding();
        mb_regex_encoding($env->getCharset());

        $pieces = mb_split($separator, $value);
        mb_regex_encoding($previous);

        foreach ($pieces as $piece) {
            while (!$preserve && mb_strlen($piece, $env->getCharset()) > $length) {
                $sentences[] = mb_substr($piece, 0, $length, $env->getCharset());
                $piece = mb_substr($piece, $length, 2048, $env->getCharset());
            }

            $sentences[] = $piece;
        }

        return implode($separator, $sentences);
    }
} else {
    function twig_truncate_filter(Twig_Environment $env, $value, $length = 30, $preserve = false, $separator = '...')
    {
        if (strlen($value) > $length) {
            if ($preserve) {
                if (false !== ($breakpoint = strpos($value, ' ', $length))) {
                    $length = $breakpoint;
                }
            }

            return substr($value, 0, $length) . $separator;
        }

        return $value;
    }

    function twig_wordwrap_filter(Twig_Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
    {
        return wordwrap($value, $length, $separator, !$preserve);
    }
}*/
