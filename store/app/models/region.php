<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 03.08.11
 * Time: 15:39
 */
 
class Region extends AppModel{
    public $name = "Region";

    public function getRegionName($id) {
        $data = $this->find("first",
            array(
                "conditions" => array(
                    "id" => $id
                )
            )
        );
        return $data['Region']['name'];
    }

    public function getRegionList() {
        $data = $this->find("list",
            array(
                "conditions" => array(
                    "country_id" => 153
                )
            )
        );
        return $data;
    }

    public function getStates()
    {
        $data = $this->find("all",
            array(
                "conditions" => array(
                    "country_id" => 153
                )
            )
        );
        return $data;
    }

    public function getStateTax($param1, $total) {
        $tax = $this->read(null, $param1);
        $percent = $tax['Region']['tax'] / 100;
        $result = $total * $percent;
        return $result;
    }

    public function getIsoCode($param1) {
        $iso = $this->read("iso", $param1);
        return $iso['Region']['iso'];
    }


}
