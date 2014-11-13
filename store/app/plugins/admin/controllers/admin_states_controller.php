<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 11.11.11
 * Time: 11:12
 *
 * @property Region $Region
 *
 */

class AdminStatesController extends AdminAppController {
    public $uses = array("Region");

    public function beforeFilter() {
        parent::beforeFilter();
        $this->_setHoverFlag("states");
        $this->_setLeftMenu("product");
    }

    public function index() {
        $this->_saveData();
        $states = $this->Region->getStates();
        $this->set('states', $states);
    }

    private function _saveData() {
        if ($this->data) {
            $erorr = false;
            $data = $this->data['Region'];
            foreach ($data as $state) {
                if (!$this->Region->save($state)) {
                    $erorr = true;
                }
            }

            if ($erorr) {
                $this->_setFlash("States updated, but with errors", "success");
            } else {
                $this->_setFlash("States updated successfully", "success");
            }

        }
    }
}
