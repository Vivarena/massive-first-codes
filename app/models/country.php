<?php
/**
 * Created by CNR.
 * User: nike
 * Date: 14.11.11
 * Time: 10:56
 */

class Country extends AppModel {
    public $name = "Country";

    public $actsAs = array(
        'Containable'
    );


    public function getCountryName($countryId) {
        $code = $this->read("name", $countryId);
        return $code['Country']['name'];
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

    /**
     * Author Mike S.
     * Sorting for USA and LatAm countries
     * @return array
     */
    public function getSortedCountries()
    {

        $countries = $this->find('all', array(
            'fields' => array('id','name','sort'),
            'order' => 'sort DESC'
        ));

        $getFirstCountries = Set::extract('/Country[sort>0]', $countries);
        $countries = array_diff_assoc($countries, $getFirstCountries);
        $countries = Set::sort($countries, '{n}.Country.name', 'asc');
        $countries = array_merge($getFirstCountries, $countries);

        self::recursive_unset($countries, 'sort');

        $countries = Set::extract('/Country/.', $countries);

        $newCountry = array();
        foreach ($countries as $country)
            $newCountry[$country['id']] = $country['name'];

        return $newCountry;

    }


    public function recursive_unset(&$array, $unwanted_key)
    {
        unset($array[$unwanted_key]);
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::recursive_unset($value, $unwanted_key);
            }
        }
    }

}
