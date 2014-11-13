<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 31.05.11
 * Time: 14:57
 */
 
class Menu extends AppModel {
    public $name = "Menu";
    public $actsAs = array('Tree', 'Translate' => array('name'), "Containable");
    private $_cacheName = 'DynamicMenu';


    function __construct($id = false, $table = null, $ds = null) {
   		parent::__construct();
        $this->locale = Configure::read('Config.language');

    }

    /**
     * Invalidate cache
     * @return void
     */
    public function invalidateCache()
    {
        Cache::delete($this->_cacheName);
    }

    /**
     * After save callback
     * @param  $created
     * @return void
     */
    public function afterSave($created)
    {
        $this->invalidateCache();
    }

    /**
     * After delete callback
     * @return void
     */
    public function afterDelete()
    {
        $this->invalidateCache();
    }


    /**
     * Return menu
     * @param $id
     * @param bool $active
     * @param bool $invalidateCache
     * @return array
     */
    public function get($id, $active=true, $invalidateCache = false)
    {
        $this->_cacheName = 'DynamicMenu_' . $this->locale . '_' . $id;
        if($invalidateCache) {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            Cache::delete($this->_cacheName);
        }
        $conditions = array('site_menu_id' => $id);
        if ($active) {
            $conditions = array_merge($conditions, array('active' => true));
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Cache::read($this->_cacheName);
        if($data === false) {

            $this->contain();
            $data = $this->find('threaded',
                array(
                    'conditions' => $conditions,
                    'fields' => array('id', 'parent_id', 'lft', 'rght', 'name', 'active', "url"),
                    'order' => "lft"
                )
            );
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            Cache::write($this->_cacheName, $data);
        }

        return $data;
    }

    /**
     * Make data as tree
     * @param  $data
     * @return array
     */
    private function _createTree($data)
    {
        $results = $idMap = array();
        $ids = Set::extract("/{$this->name}/id", $data);

        foreach($data as $item) {
            $item['children'] = array();
            $id = $item[$this->name]['id'];
            $parentId = $item[$this->name]['parent_id'];

            $idMap[$id] = array_merge($item[$this->name], array('children' => array()));

            if(!$parentId || !in_array($parentId, $ids)) {
                $idMap[$id]['level'] = 0;
                $results[] =& $idMap[$id];
            } else {
                $idMap[$id]['level'] = $idMap[$parentId]['level'] + 1;
                $idMap[$parentId]['children'][] =& $idMap[$id];
            }
        }

        return $results;
    }

    /**
     * Return menu item data
     * @param  $id
     * @return array | false
     */
    public function getItem($id)
    {
        $this->locale = Configure::read('Config.language');

        $data = $this->read(array('id', 'name', 'url', 'parent_id'), $id);

        $this->i18dataLoad(&$data, $this->languages, array('name'));

        return isset($data) ? $data : false;
    }

    public function getChildMenu($id)
    {
        $data = $this->find('first',
            array(
                'conditions' => array(
                    'url'    => $id,
                    'active' => 1
                ),
                'fields' => array(
                    'parent_id', 'id'
                )
            )
        );

        if (!empty($data['Menu']['parent_id'])) {
            $allChildren = $this->children($data['Menu']['parent_id'], true);
        } else {
            $allChildren = $this->children($data['Menu']['id'], true);
        }
        $result = Set::extract('/Menu/.', $allChildren);

        foreach ($result as $key=>&$tmp) {
            if ($tmp['active'] == 0) {
                unset($result[$key]);
            }
        }

        return $result;
    }

    public function getAddData($id)
    {
        $data = $this->find('first',
            array(
                 'conditions' => array(
                     'url' => $id
                 ),
//                 'fields' => array(
//                     'parent_id', 'id'
//                 )
            )
        );

        if (!empty($data['Menu']['parent_id'])) {
            $tmp = $this->getpath($data['Menu']['parent_id']);
            $info = array(
                'parent_name' => $tmp[0]['Menu']['name'],
                'self_name'   => $data['Menu']['name']
            );
        } else {
            $info = array(
                'parent_name' => $data['Menu']['name']
            );
        }
        return $info;

    }

    public $languages = array(
        'eng' => 'English',
        'por' => 'Portuguese',
        'spa' => 'Spanish',
    );

}
