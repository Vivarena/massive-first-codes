<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 12.08.11
 * Time: 12:06
 */
 
class Category extends AppModel{
    public $name = "Category";
    public $actsAs = array("Containable", "Tree", );
    public $hasMany = array("Product");


    private $_cacheName = 'DynamicMenu';

    /**
     * Return menu
     * @param bool $invalidateCache
     *
     * @internal param $id
     * @return array
     */
    public function getProductMenu($invalidateCache = true)
    {
        if($invalidateCache) {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            Cache::delete($this->_cacheName);
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $data = Cache::read($this->_cacheName);
        if($data === false) {
            $data = $this->find('all', array(
                'conditions' => array(
                    'Category.active' => 1
                ),
                'fields' => array(
                    'id', 'parent_id',
                    'title', 'active', 'image'
                ),
                'order' => 'lft'
            ));

            $data = $this->_createTree($data);
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            Cache::write($this->_cacheName, $data);
        }

//        pr($data);
//        die;

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
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $ids = Set::extract("/{$this->name}/id", $data);

        foreach($data as $item) {
            $item['children'] = array();
            $id = $item[$this->name]['id'];
            $parentId = $item[$this->name]['parent_id'];

            $idMap[$id] = array_merge($item[$this->name], array('children' => array()));

            if(!$parentId) { // || !in_array($parentId, $ids)
                $idMap[$id]['level'] = 0;
                $results[] =& $idMap[$id];
            } else {
                @$idMap[$id]['level'] = $idMap[$parentId]['level'] + 1;
                $idMap[$parentId]['children'][] =& $idMap[$id];
            }

            $idMap[$id]['hasProducts'] = count($item['Product']) ? true : false;
        }
        return $results;
    }

    public function getName($id)
    {
        $data = $this->read('title', $id);
        return $data['Category']['title'];
    }

    public function fuckEmptyCategories($clicks)
    {
        foreach ($clicks as $key=>$category) {
            if (count($category['Product']['Category']) == 0) {
                unset($clicks[$key]);
            }
        }
        unset($category);
        return $clicks;
    }

    public function getSearchResult($data)
    {
        $this->bindModel(array('hasMany' => array('CategoriesProduct')));
        $categories = $this->find('all', 
            array(
                'contain' => array(
                    'CategoriesProduct'
                ), 
                'conditions' => array(
                    'Category.active' => 1, 
                    'Category.title LIKE' => "%{$data['search']}%",
                ), 
                'fields' => array(
                    'Category.id', 'Category.title',
                )
            )
        );
        
        $results = array();
        if ($categories) {
            foreach ($categories as $category) {
                if (!empty($category['CategoriesProduct'])) {
                    $results[] = $category['Category'];
                }
            }
        }

        return $results;
    }

    public function getCategoriesId($ident) {
        $this->contain();
        $data = $this->find("all",
            array(
                "conditions" => array(
                    "Category.title" => $ident
                ),
                "fields" => "Category.id"
            )
        );
        return Set::extract('/Category/id', $data);

    }
}
