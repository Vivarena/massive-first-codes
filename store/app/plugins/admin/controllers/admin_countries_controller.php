<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 11.11.11
 * Time: 11:12
 *
 * @property Country $Country
 *
 */

class AdminCountriesController extends AdminAppController {
    public $uses = array("Country");

    public function beforeFilter() {
        parent::beforeFilter();
        $this->_setHoverFlag("country");
        $this->_setLeftMenu("product");
    }

    public function index() {
        $this->_saveData();
        $countries = $this->Country->getCountries();
        $this->set('countries', $countries);
    }

    private function _saveData() {
        if ($this->data) {
            $erorr = false;
            $data = $this->data['Country'];
            foreach ($data as $state) {
                if (!$this->Country->save($state)) {
                    $erorr = true;
                }
            }

            if ($erorr) {
                $this->_setFlash("Countries updated, but with errors", "success");
            } else {
                $this->_setFlash("Countries updated successfully", "success");
            }

        }
    }
}
