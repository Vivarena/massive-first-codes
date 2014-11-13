<?php
/**
 * Created by CNR
 * User: Nike
 * Date: 08.12.11
 * Time: 11:26
 *
 * @property Country             $Country
 * @property CartComponent       $Cart
 * @property SessionComponent    $Session
 */

class UspsComponent extends Object {

    public $components = array("Cart", "Session");

    /**
     * USPS API User id
     * @var string
     */
    private $_userId = "202MPSEN4608";

    /**
     * USPS API User id
     * @var string
     */
    private $_password = "462IY67NR264";

    /**
     * @var string
     * Seller location Postal Code
     */
    private $_originZip = '07728';

    /**
     * Byer location Postal Code
     * @var
     */
    private $_destinationZip;

    /**
     * Byer location Destination Country
     * @var
     */
    private $_destinationCountry;

    /**
     * Test API Url
     * @var string
     */
    private $_testUrl = "http://testing.shippingapis.com/ShippingAPITest.dll";

    /**
     * Production API Url
     * @var string
     */
    private $_productionUrl = "http://production.shippingapis.com/ShippingAPI.dll";

    /**
     * Product summary weight
     * @var int
     */
    private $_weight = 3;

    /**
     * Debug On/Off
     * @var bool
     */
    private $_debug = false;

    /**
     * API Request Type. 4 - last API version
     * @var int
     */
    private $_requestType = 4;

    /**
     * Products quantity
     * @var int
     */
    private $_productQty;

    /**
     * Setter for Debug Mode
     * @param $debug
     */
    public function setDebug($debug) {
        $this->_debug = $debug;
    }

    /**
     * Setter for destination postal code
     */
    private function _setDestinationZip() {
        $address = $this->Session->read("BillingShipping.Shipping");
        $this->_destinationZip = $address['zip'];
    }

    /**
     * Setter for destination country
     */
    private function _setDestinationCountry() {
        $address = $this->Session->read("BillingShipping.Shipping");
        $this->_destinationCountry = $this->Country->getCountryIsoCode($address['country']);
    }

    /**
     * Setter for summary product weight
     * @param $weith
     */
    public function setWeight($weith) {
        $this->_weight = $weith;
    }

    /**
     * Set product quantity
     */
    private function _setProductQty() {
        $productQty = $this->Cart->in_cart();
        $qty = 0;
        foreach ($productQty['Products'] as $product) {
            $qty += $product['qty'];
        }
        unset($product);
        $this->_productQty = $qty;
    }

    /**
     * called before Controller::beforeFilter()
     * @param  $controller
     * @param array $settings
     * @return void
     */
    function initialize(&$controller, $settings = array())
    {
        $this->Country = ClassRegistry::init('Country');
    }

    /**
     * Getter Domestic USPS Shipping Rate
     * @return array
     */
    function uspsDomestic() {

        $this->_setProductQty();
        $this->_setDestinationZip();

        $ch = curl_init();

        $url = $this->_debug ? $this->_testUrl : $this->_productionUrl;

        // set the target url
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

        // parameters to post
        curl_setopt($ch, CURLOPT_POST, 1);

        $data = "API=RateV" . $this->_requestType . "&XML=";
        $data .= '
        <RateV' . $this->_requestType . 'Request USERID="' . $this->_userId . '" PASSWORD="' . $this->_password . '">
            <Revision></Revision>
            <Package ID="1ST">
                <Service>All</Service>
                <ZipOrigination>' . $this->_originZip . '</ZipOrigination>
                <ZipDestination>' . $this->_destinationZip . '</ZipDestination>
                <Pounds>' . $this->_weight * $this->_productQty . '</Pounds>
                <Ounces>0</Ounces>
                <Container></Container>
                <Size>REGULAR</Size>
                <Machinable>true</Machinable>
            </Package>
        </RateV' . $this->_requestType . 'Request>
        ';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 1);
        // send the POST values to USPS
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);

        $result=curl_exec ($ch);
        curl_close($ch);

        $result = str_replace('&amp;lt;sup&amp;gt;&amp;amp;reg;&amp;lt;/sup&amp;gt;', '', $result);
        $result = str_replace('&amp;lt;sup&amp;gt;&amp;amp;trade;&amp;lt;/sup&amp;gt;', '', $result);

        $data = strstr($result, '<?');
        //echo '<!-- '. $data. ' -->'; // Uncomment to show XML in comments
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $data, $vals, $index);
        xml_parser_free($xml_parser);
        $params = array();
        $level = array();
        foreach ($vals as $xml_elem) {
            if ($xml_elem['type'] == 'open') {
                if (array_key_exists('attributes',$xml_elem)) {
                    @list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
                } else {
                    $level[$xml_elem['level']] = $xml_elem['tag'];
                }
            }
            if ($xml_elem['type'] == 'complete') {
                $start_level = 1;
                $php_stmt = '$params';
                while($start_level < $xml_elem['level']) {
                    $php_stmt .= '[$level['.$start_level.']]';
                    $start_level++;
                }
                $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                eval($php_stmt);
            }
        }

        $result = Set::extract($params['RATEV4RESPONSE']['1ST'], "{n}");

        $expessMail = array();
        $priorityMail = array();

        foreach ($result as $key=>$tmp) {
            if (substr($tmp['MAILSERVICE'], 0, 7) == "Express") {
                $expessMail[] = $result[$key];
            } elseif (substr($tmp['MAILSERVICE'], 0, 8) == "Priority") {
                $priorityMail[] = $result[$key];
            }

        }
        unset($tmp);
        @usort($expessMail, create_function('$a,$b','if ($a["RATE"]==$b["RATE"]) return 0; return $a["RATE"]>$b["RATE"] ? -1 : 1;'));
        @usort($priorityMail, create_function('$a,$b','if ($a["RATE"]==$b["RATE"]) return 0; return $a["RATE"]>$b["RATE"] ? -1 : 1;'));
        //echo '<pre>'; print_r($params); echo'</pre>'; // Uncomment to see xml tags
        return array(
            //"expess_mail"    => $expessMail,
            "priority_mail"  => $priorityMail
        );
    }

    /**
     * Getter International USPS Shipping Rate
     * @return array
     */
    function uspsInternational() {

        $this->_setProductQty();
        $this->_setDestinationCountry();

        $ch = curl_init();

        $url = $this->_debug ? $this->_testUrl : $this->_productionUrl;

        // set the target url
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

        // parameters to post
        curl_setopt($ch, CURLOPT_POST, 1);

        $data = "API=IntlRateV2&XML=";
        $data .= '
        <IntlRateV2Request USERID="' . $this->_userId . '" PASSWORD="' . $this->_password . '">
            <Revision></Revision>
            <Package ID="1ST">
                <Pounds>15</Pounds>
                <Ounces>0</Ounces>
                <Machinable>True</Machinable>
                <MailType>Package</MailType>
                <GXG>
                    <POBoxFlag>Y</POBoxFlag>
                    <GiftFlag>Y</GiftFlag>
                </GXG>
                <ValueOfContents>0.0</ValueOfContents>
                <Country>Ukraine</Country>
                <Container>RECTANGULAR</Container>
                <Size>LARGE</Size>
                <Width>10</Width>
                <Length>15</Length>
                <Height>10</Height>
                <Girth>0</Girth>
                <CommercialFlag>N</CommercialFlag>
            </Package>
        </IntlRateV2Request>
        ';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 1);
        // send the POST values to USPS
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);

        $result=curl_exec ($ch);

        curl_close($ch);

        $result = str_replace('&amp;lt;sup&amp;gt;&amp;amp;reg;&amp;lt;/sup&amp;gt;', '', $result);
        $result = str_replace('&amp;lt;sup&amp;gt;&amp;amp;trade;&amp;lt;/sup&amp;gt;', '', $result);

        $data = strstr($result, '<?');
//        echo '<!-- '. $data. ' -->'; // Uncomment to show XML in comments
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $data, $vals, $index);
        xml_parser_free($xml_parser);
        $params = array();
        $level = array();
        foreach ($vals as $xml_elem) {
            if ($xml_elem['type'] == 'open') {
                if (array_key_exists('attributes',$xml_elem)) {
                    @list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
                } else {
                    $level[$xml_elem['level']] = $xml_elem['tag'];
                }
            }
            if ($xml_elem['type'] == 'complete') {
                $start_level = 1;
                $php_stmt = '$params';
                while($start_level < $xml_elem['level']) {
                    $php_stmt .= '[$level['.$start_level.']]';
                    $start_level++;
                }
                $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                eval($php_stmt);
            }
        }



        $result = Set::extract($params['INTLRATEV2RESPONSE']['1ST'], "{n}");
        $resArray = array();
        foreach ($result as $tmp) {
            $resArray[] = array(
                "MAILSERVICE" => $tmp['SVCDESCRIPTION'],
                "RATE"        => $tmp['POSTAGE']
            );
        }
        unset($tmp);

        @usort($resArray, create_function('$a,$b','if ($a["RATE"]==$b["RATE"]) return 0; return $a["RATE"]>$b["RATE"] ? -1 : 1;'));
        //echo '<pre>'; print_r($params); echo'</pre>'; // Uncomment to see xml tags
        return array(
            "international"    => $resArray,
        );
    }




}