<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 23.12.10
 * Time: 12:17
 */
 
class AdminSitemapsController extends AdminAppController{
    public $name = "AdminSitemaps";
    public $uses        = array("Sitemap");
    private $home       = "";
    private $map        = array();
    private $links      = array();
    private $sm    = array();
    private $pageTitle = "";

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->home = "http://{$_SERVER["SERVER_NAME"]}/";
        $this->layout='default_1';
    }

    public function index()
    {

        set_time_limit(0);
        $sitemapTree = $this->Sitemap->generatetreelist(
            null, "{n}.Sitemap.url", "{n}.Sitemap.name",
            "&nbsp;&nbsp;|&nbsp;&nbsp;"
        );

        $this->set("sitemapTree", $sitemapTree);
    }

    public function build()
    {
        unlink(WWW_ROOT.'sitemap.xml');
        set_time_limit(0);
        $this->generate();

        ksort($this->map);
        $this->create_xml();

        asort($this->map);
        $this->create_tree();

        $this->Session->setFlash(
            "Sitemap recreated!",
            'default', array(), 'sitemapAdmin'
        );
        $this->redirect("index");
    }

    public function generate($link = null)
    {
        set_time_limit(0);
        $link       = !empty($link) ? $link : $this->home;
        $regTitle   = "/\<title\>([\w\W]+)\</iU";
        $regLinks   = "/\<a[\w\W]+href=['\"]{1}([\w\W]+)['\"]{1}/iU";



        $content = file_get_contents($link);

        preg_match($regTitle, $content, $title);
        preg_match_all($regLinks, $content, $urls);



        if(!empty($urls[1])) {
            foreach($urls[1] as $url) {
                if(
                    $url != $this->home && $url != "/" && $url[0] != "#"
                    && !preg_match("(javascript:|debug_kit|cart|tell-a-friend|free-shipping|shipping-details|images|uploads)", $url)
                ) {


                    if(strstr($url, "http://")) {
                        $tmp = explode("/", $url);
                        if($tmp[2] != $_SERVER["SERVER_NAME"]) {
                            continue;
                        }
                    }

                    if($url[0] == "/") {
                        $url = "http://{$_SERVER["SERVER_NAME"]}$url";
                    } elseif(!strstr($url, "http://")) {
                        $url = "http://{$_SERVER["SERVER_NAME"]}/$url";
                    }

                    if(in_array($url, $this->links)) {
                        continue;
                    }

                    $this->links[]      = $url;

                    $this->map[$url]    = $this->generate($url);



                }
            }
        }

        return $title[1];
    }

    private function create_xml()
    {
        set_time_limit(0);
        $content = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

<url>
    <loc>$this->home</loc>
    <lastmod>
EOF;
        $content .= date("c");
        $content .= <<<EOF
    </lastmod>
    <priority>1.0</priority>
    <changefreq>monthly</changefreq>
</url>
EOF;

        if(!empty($this->map)) {
            foreach($this->map as $url => $page) {
                $titles = explode(" :: ", $page);
                $content .= <<<EOF

<url>
    <loc>$url</loc>
    <lastmod>
EOF;
                $content .= date("c");
                $content .= <<<EOF
    </lastmod>
    <priority>
EOF;
                $content .= count($titles) > 2 ? "0.4" : "0.8";
                $content .= <<<EOF
    </priority>
    <changefreq>monthly</changefreq>
</url>
EOF;
            }
        }

        $content .= <<<EOF

</urlset>
EOF;

        file_put_contents(WWW_ROOT . "sitemap.xml", $content);

        return;
    }

    private function create_tree()
    {
        set_time_limit(0);
        if(!empty($this->map)) {



            foreach($this->map as $url => $page) {
                $titles = explode(" :: ", $page);

                foreach($titles as $key => $title) {
                    if(!array_key_exists($title, $this->sm) && $key != (count($titles) - 1)) {
                        $this->sm[$title] = array(
                            "url"       => false,
                            "parent"    => false
                        );
                    } elseif(!array_key_exists($title, $this->sm) && $key == (count($titles) - 1)) {
                        $this->sm[$title] = array(
                            "url"       => $url,
                            "parent"    => $titles[$key - 1]
                        );
                    } elseif(array_key_exists($title, $this->sm) && $key == (count($titles) - 1)) {
                        $this->sm[$title] = array(
                            "url"       => $url,
                            "parent"    => $titles[$key - 1]
                        );
                    }
                }
            }


            /*$this->sm[$this->pageTitle]["url"] = $this->home;

            $tmp    = $this->Sitemap->find(array("name" => $this->pageTitle), "id");
            $tmpId  = !empty($tmp["Sitemap"]["id"]) ? $tmp["Sitemap"]["id"] : "";

            $this->Sitemap->set(array(
                "id"    => $tmpId,
                "name"  => $this->pageTitle,
                "url"   => $this->sm[$this->pageTitle]["url"]
            ));
            $this->Sitemap->save();
            $this->sm[$this->pageTitle]["id"] = $this->Sitemap->id;

            foreach($this->sm as $key => &$branch) {
                if(!empty($branch["parent"])) {
                    $tmp    = $this->Sitemap->find(array("name" => $key), "id");
                    $tmpId  = !empty($tmp["Sitemap"]["id"]) ? $tmp["Sitemap"]["id"] : "";

                    $this->Sitemap->set(array(
                        "id"        => $tmpId,
                        "name"      => $key,
                        "url"       => $branch["url"],
                        "parent_id"    => $this->sm[$branch["parent"]]["id"]
                    ));
                    $this->Sitemap->save();
                    $branch["id"] = $this->Sitemap->id;
                }
            }

            $this->Sitemap->reorder(array(
                "id" => null, "field" => $this->Sitemap->displayField,
                "order" => "ASC", "verify" => true
            ));*/
        }
    }
}
