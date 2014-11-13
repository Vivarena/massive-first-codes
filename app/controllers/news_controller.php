<?php
/**
 * @property User $User
 */
class NewsController extends AppController
{
    public $name = 'News';

    public $uses = array();

    private $feedGoogleNews = 'https://news.google.com/news/feeds?q=sports&output=rss';

    public function beforeFilter()
    {
        $this->layout = 'community';
    }

    public function google_news()
    {
        App::import('Xml');
        $parsedXml =& new XML($this->feedGoogleNews);
        $rssItem = $parsedXml->toArray();
        foreach ($rssItem['Rss']['Channel']['Item'] as &$item) {
            preg_match('/<img[^>]+>/i', $item['description'], $result);
            $imgSrc = (count($result) > 0) ? (strpos($result[0], 'src')) ? preg_replace('/.*?src="(.*?)".*/i', '$1', $result[0]) : '' : '';
            $item['src_img'] = (!empty($imgSrc)) ? $imgSrc : '';
            $item['description'] = strip_tags($item['description'], '<p><br>');
            $item['description'] = str_replace('and more&nbsp;&raquo;', '', $item['description']);
        }

        $this->set('news', Set::extract('/Rss/Channel/Item/.', $rssItem));

    }

}