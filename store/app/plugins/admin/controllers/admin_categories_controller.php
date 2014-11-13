<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 12.08.11
 * Time: 12:04
 *
 * @property Category $Category
 *
 */
 
class AdminCategoriesController extends AdminAppController{
    public $name = "AdminCategories";
    public $uses = "Category";

    /**
     * Called before the controller action.
     *
     * @access public
     * @link http://book.cakephp.org/view/984/Callbacks
     */
    public function beforeFilter() {
        parent::beforeFilter();
        $this->_setLeftMenu('product');

    }

    /**
     * Start page
     * @return void
     */
    public function index() {
        $this->_setHoverFlag("category");
        $categories = $this->getCategories();
        $temp = array();
        foreach ($categories as $key=>&$tmp) {
            $activeStatus = $this->Category->read('active', $key);
            $temp[$key] = array(
                'active' => $activeStatus['Category']['active'],
                'title'  => $tmp
            );
        }
        $this->set('categories', $temp);
    }

    /**
     * Add new category
     * @return void
     */
    public function add() {
        $this->_setHoverFlag("category_add");
        $this->_saveData();


        $this->set('categories', $this->getCategories());
    }



    /**
     * Saving data from $this->data
     * @access private
     * @return void
     */
    private function _saveData() {
        if ($this->data) {
            if($this->Category->save($this->data)) {
                $this->_setFlash('Item successfully edited', 'success');
                $this->redirect("index");
            } else {
                $this->_setFlash('Errors occured, please see below', 'error');
            }

        }
    }

    /**
     * Ajax method for getting products from categories
     * Input parameter $id_cat - category ID
     * @param $id_cat
     * @return void
     */
    public function ajaxGetSsubCategories($id_cat){

        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $this->loadModel("Product");

        $sub_categories = $this->Category->find('first', array('contain' => array(
                           'Product' => array(
                               'fields' => array(
                                   'title','id'
                               ), 'conditions' => array('active' => 1)
                           )
                        ),'conditions'=>array('Category.id' => $id_cat)));
        $new_sub_cat = array();
        if ($sub_categories){
            $key = 0;

            foreach ($sub_categories['Product'] as $sub_cat) {
                $new_sub_cat[$key]['title'] = $sub_cat['title'];
                $new_sub_cat[$key]['id'] = $sub_cat['id'];
                $key++;
            }
        } else $err = true;

        $result = array(
            'error' => $err,
            'sub_categor' => $new_sub_cat
        );

        exit(json_encode($result));

    }

    /**
     * Ajax method for category deleting
     * @param $id
     * @return bool
     */
    public function ajaxDelete($id)
    {
        Configure::write("debug", 0);
        if(empty($id) && !is_numeric($id))
        {
            return false;
        }

        if($this->Category->delete($id))
        {
            exit("okey");
        }

        return false;
    }

    public function edit($id=null) {
        $this->_saveData();
        if (isset($id) && is_numeric($id) ) {
            $this->data = $this->Category->read(null, $id);
            $this->set('categories', $this->getCategories());
            $this->render("add");
        }
    }


}
