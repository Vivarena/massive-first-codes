<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 14.11.11
 * Time: 10:56
 */

class Country extends AppModel {
    public $name = "Country";

    public function getCountryName($countryId) {
        $code = $this->read("name", $countryId);
        return $code['Country']['name'];
    }

    public function getCountryIsoCode($countryId) {
        $code = $this->read("iso", $countryId);
        return $code['Country']['iso'];
    }

    public function getCountryCode($countryId) {
        $code = $this->read("iso", $countryId);
        return $code['Country']['iso'];
    }

    public function getCountries() {
        $data = $this->find("all",
            array(
                "order" => "Country.name"
            )
        );
        return $data;
    }

    public function getCountryTax($param1, $total) {
        $tax = $this->read(null, $param1);
        $percent = $tax['Country']['vat'] / 100;
        $result = $total * $percent;
        return $result;
    }
}
