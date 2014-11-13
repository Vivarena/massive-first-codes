<?php
/**
 * User: Nike
 * Date: 12.07.2011
 * Time: 11:47:14
 */
class Banner extends AppModel
{
    public $name                = 'Banner';
    public $actsAs              = array('Containable', 'Tree',
        'Translate' => array('title')
    );
    public $validate            = array(
        'url' => array(
            'rule'      => 'notEmpty',
            'required'  => true
        ),
    );

    private $_cacheName;

    public function invalidateCache()
    {
        Cache::delete($this->_cacheName);
    }

    public function afterSave($created)
    {
        $this->invalidateCache();
    }

    public function afterDelete()
    {
        $this->invalidateCache();
    }



    public function getActiveBanners($invalidateCache = false)
    {
        $this->locale = Configure::read('Config.locale');
        $this->_cacheName = 'slides_' . $this->locale;

        if($invalidateCache) {
            Cache::delete($this->_cacheName);
        }

        $data = Cache::read($this->_cacheName);
        if($data === false) {
            $data = $this->find("all",
                array(
                    "conditions" => array(
                        'active' => 1,
                    ),
                    "fields" => array(
                        "title", "url", "link", 'areas', 'id'
                    ),
                    'order' => 'lft'
                )
            );
            $data = Set::extract('/Banner/.', $data);
            foreach($data as &$banner)
            {
                if (!empty($banner['areas'])) {
                    $banner['areas'] = unserialize($banner['areas']);
                }
            }
            Cache::write($this->_cacheName, $data);

        }
        return $data;
    }
}
