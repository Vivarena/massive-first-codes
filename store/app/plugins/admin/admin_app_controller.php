<?php
/**
 *@property Category $Category
 *@property SiteMenu $SiteMenu
 */
class AdminAppController extends AppController
{
	public $helpers = array('Admin.jGrowl');
    public $bannerPlaces = array(
        "top"=>"top",
    );
	/**
	 * General settings of pagination
	 */
	public $paginate = array(
		'limit' => 10
	);


    public $_statuses = array(
        1 => 'Order placed',
        'Unprocessed payment',
        'Canceled',
        'Failed',
        'Pending',
        'Shipping',
        'Order complete',
    );

    /*public $_statuses = array(
        1 => 'Order paid',
        'Unprocessed payment',
        'Payment canceled',
        'Payment failed',
        'Payment pending',
        'Payment in process',
        'Shipping',
    );*/

    /**
	 * Current backend language
	 * @var string
	 */
	protected $curLanguage;

	public function beforeFilter()
	{
        parent::beforeFilter();

		$this->_initBackendLanguage();
		$this->_initAvailableLanguages();
        $this->set('menuList', $this->_getMenuList());
	}



	private function _initBackendLanguage()
	{
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $current = SiteConfig::read('Language.backendLang');
		if($current !== null) {
			$this->curLanguage = $current;
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            Configure::write('Config.language', $current);
		} else {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $this->curLanguage = Configure::read('Config.language');
		}
	}

	private function _initAvailableLanguages()
	{
		if(empty($this->languages)) {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $this->languages = array(
				Configure::read('Config.language')
			);
		}

		//TODO init languages from DB

		$this->set('languages', $this->languages);
	}

	/**
	 * Set menu hover flag
	 * @param string $menu_name Name of hover menu
	 */
	protected function _setHoverFlag($menu_name)
	{
		if(isset($this->viewVars['_hovers'])) {
			$hovers = array_merge(
					$this->viewVars['_hovers'],
					array($menu_name => true)
				);
		} else {
			$hovers = array($menu_name => true);
		}

		$this->set('_hovers', $hovers);
	}

	/**
	 * Set left menu name
	 * @param string $menu_name
	 */
	protected function _setLeftMenu($menu_name)
	{
		$this->set('_left_menu_name', $menu_name);
	}

	/**
	 * Set jGrowl message (for use jGrowl helper)
	 * @param string $msg Message to be flashed
	 * @param string $type Type of message (error, warning, success, message). Default is 'message'
	 * @param string $key Message key, default is 'flash'
	 */
	public function _setFlash($msg, $type = 'message', $key = 'flash')
	{
		$types = array('error', 'warning', 'message', 'success');

		if(!in_array($type, $types)) {
			$type = 'message';
		}

		if(empty($key)) {
			$key = 'flash';
		}

		$flash = array(
			'type' => $type,
			'message' => __($msg, true)
		);

		if($this->Session->check('FlashMessage.' . $key)) {
			$flashData = $this->Session->read('FlashMessage.' . $key);

			array_push($flashData, $flash);
		} else {
			$flashData[] = $flash;
		}

		$this->Session->write('FlashMessage.' . $key, $flashData);
	}

    function activate($model, $id)
    {
        $this->loadModel("{$model}");
        if(!$id && !$this->data) {
            $this->_setFlash('Invalid Page', 'error');
            // TODO show error ?
            $this->render('empty');
        } else {
            $this->$model->id = $id;
        }

        $active = $this->$model->read('active', $id);
        if($active["{$model}"]['active'] == 1) {
            $this->$model->saveField('active', 0);
        } else {
            $this->$model->saveField('active', 1);
        }

        $this->redirect($this->referer());
    }

    public function moveUp($model, $id)
    {
        $this->loadModel("{$model}");
        $this->$model->moveUp($id, 1);
        $this->redirect($this->referer());
    }

    public function moveDown($model, $id)
    {
        $this->loadModel("{$model}");
        $this->$model->moveDown($id, 1);
        $this->redirect($this->referer());
    }

    public function ajaxDelete($model, $id)
    {
        Configure::write("debug", 0);
        $this->loadModel("{$model}");
        if(empty($id) && !is_numeric($id))
        {
            return false;
        }

        if($this->$model->delete($id))
        {
            exit("okey");
        }
        return false;

    }

    private function _getMenuList()
    {
        $this->loadModel("SiteMenu");
        $menus = $this->SiteMenu->find('all');
        return Set::extract('/SiteMenu/.', $menus);
    }

    /**
     * Get all categories in format: [id] => [name]
     * @access public
     * @return array
     */
    public function getCategories()
    {
        return $this->Category->generatetreelist(null, null, null, " > ");
    }
}    