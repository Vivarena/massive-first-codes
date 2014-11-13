<?php
/**
 * @property GroupFaq $GroupFaq
 */
class AdminAppController extends AppController
{
    public $view = 'cakephp-twig.Twig';
    public $helpers = array('Admin.jGrowl', 'Admin.ExtTree');

    public $bannerPlaces = array(
        "top" => "top",
    );
    /**
     * General settings of pagination
     */
    public $paginate = array(
        'limit' => 10
    );

    /**
     * Current backend language
     * @var string
     */
    protected $curLanguage;

    public function beforeFilter()
    {
//        $this->_getCurrentPermissions();
        parent::beforeFilter();

        Configure::write('Config.language', 'eng');
        $this->_initAvailableLanguages();
    }

    private function _initAvailableLanguages()
    {
        if (empty($this->languages)) {
            $this->languages = array(
                Configure::read('Config.language')
            );
        }

        ksort($this->languages);

        //TODO init languages from DB

        $this->set('languages', $this->languages);
    }

    /**
     * Set menu hover flag
     * @param string $menu_name Name of hover menu
     */
    protected function _setHoverFlag($menu_name)
    {
        if (isset($this->viewVars['_hovers'])) {
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

        if (!in_array($type, $types)) {
            $type = 'message';
        }

        if (empty($key)) {
            $key = 'flash';
        }

        $flash = array(
            'type' => $type,
            'message' => __($msg, true)
        );

        if ($this->Session->check('FlashMessage.' . $key)) {
            $flashData = $this->Session->read('FlashMessage.' . $key);

            array_push($flashData, $flash);
        } else {
            $flashData[] = $flash;
        }

        $this->Session->write('FlashMessage.' . $key, $flashData);
    }

    public function translate()
    {
        Configure::write('debug', 0);

        $text = rawurlencode($this->data['Translate']['text']);
        $text = preg_replace("/\.\.%2F\.\.%2F\.\.%2F/", "", $text);
        $stringArray = explode(". ", $text);

        $translatedArray = array();
        foreach ($stringArray as $t) {
            $data = '&text=' . $t . '&sl=' . $this->data['Translate']['from'] . '&tl=' . $this->data['Translate']['to'];
            $gUrl = 'http://translate.google.com/translate_a/t?multires=1&client=t' . $data;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $gUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, '3');
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Ubuntu; X11; Linux i686; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_POST, 0);
            $res = curl_exec($ch);
            curl_close($ch);

            $res = preg_replace("#[\[\"]{4}#iUsm", '', $res);
            $res = explode('","', $res);

            $translatedArray[] = html_entity_decode(
                                preg_replace("/\\\\u([0-9abcdef]{4})/iUsm", "&#x$1;", array_shift($res)),
                                ENT_QUOTES, 'UTF-8');
        }

        $translatedText = implode(". ", $translatedArray);

        exit(json_encode($translatedText));
    }

    private function _getCurrentPermissions()
    {
        $this->loadModel("Aco");
        $acos = $this->Aco->find("threaded");


        pr($acos);
        die;
    }

    function saveSorting($model)
    {
        Configure::write('debug', 0);
        $this->autoRender = false;
        $err = false;
        $err_desc = '';

        if (isset($this->params['form']['items']) && !empty($this->params['form']['items'])) {
            if (!($this->{$model}->saveSort($this->params['form']['items']))) $err_desc = 'An error occurred when saving items!';
        }


        if (!empty($err_desc)) $err = true;
        $result = array(
            'error' => $err,
            'err_desc' => $err_desc,
        );

        exit(json_encode($result));
    }

    function ajaxActivate($model, $id)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 0);
        if (!$id && !$this->data) {
            return false;
        } else {
            $this->{$model}->id = $id;
        }
        $mode = '';
        $active = $this->{$model}->read('active');
        if ($active["{$model}"]['active'] == 1) {
            $this->{$model}->saveField('active', 0);
            $mode = 'inactive';
        } else {
            $this->{$model}->saveField('active', 1);
            $mode = 'active';
        }
        $result = array(
            'status' => true,
            'mode' => $mode
        );
        exit(json_encode($result));
    }

    function ajaxDelete($model, $id)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 0);
        if (!$id && !$this->data) {
            return false;
        } else {
            $this->{$model}->id = $id;
        }

        if ($this->{$model}->delete($id)) {
            exit(json_encode(array('status'=> true)));
        }

        return false;
    }
}    