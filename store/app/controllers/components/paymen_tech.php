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

class PaymenTechComponent extends Object {

    /**
     * {@inheritdoc}
     */
    public $components = array("Cart", "Session");

    /**
     * Paymentech User Name
     * @var string
     */
    private $_userName = "OS654888";

    /**
     * Paymentech User Password
     * @var string
     */
    private $_userPassword = "SO929353";

    /**
     * Paymentech Bin
     * @var
     */
    private $_modulepaymentechBin = "000002";

    /**
     * Paymentech Merchant ID
     * @var
     */
    private $_modulepaymentechMerch = "720000141486";

    /**
     * Paymentech Terminal ID
     * @var string
     */
    private $_modulepaymentechTerm = "001";

    /**
     * Payment Debug Mode
     * @var bool
     */
    private $_debugMode = false;

    /**
     * Curl URL for debug mode
     * @var string
     */
    private $_debugUrl = "https://orbitalvar2.paymentech.net";

    /**
     * Curl URL for live mode
     * @var string
     */
    private $_liveUrl = "https://orbital1.paymentech.net";

    /**
     * Curl request URL
     * @var
     */
    private $_requestUrl;

    /**
     * Page for curl request
     * @var string
     */
    private $_page = "xml.php";

    /**
     * Setter for Payment Debug Mode
     * @param $debugMode
     */
    public function setDebugMode($debugMode) {
        $this->_debugMode = $debugMode;
    }


    function Checkoutpaymentech($orderid, $cc){
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Configure::write("debug", 0);
        $this->_requestUrl = $this->_debugMode ? $this->_debugUrl : $this->_liveUrl ;
        $total = $this->Cart->getTotal();
        $billing = $this->Session->read("BillingShipping.Billing");

        $exp   = $cc["date"]['month'] . substr($cc['date']["year"], 2); // get the expiry date with only the last two chars of the years
        //$total = str_replace('.', '', $totals); //implied decimal, so take it out

        $post_string="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <Request>
                    <NewOrder>
                            <OrbitalConnectionUsername>$this->_userName</OrbitalConnectionUsername>
                            <OrbitalConnectionPassword>$this->_userPassword</OrbitalConnectionPassword>
                            <IndustryType>EC</IndustryType>
                            <MessageType>AC</MessageType>
                            <BIN>$this->_modulepaymentechBin</BIN>
                            <MerchantID>$this->_modulepaymentechMerch</MerchantID>
                            <TerminalID>$this->_modulepaymentechTerm</TerminalID>
                            <CardBrand>$cc[type]</CardBrand>
                            <AccountNum>$cc[number]</AccountNum>
                            <Exp>$exp</Exp>
                            <CardSecValInd>1</CardSecValInd>
                            <CardSecVal>$cc[code]</CardSecVal>
                            <AVSzip>$billing[zip]</AVSzip>
                            <AVSaddress1>$billing[address1]</AVSaddress1>
                            <AVScity>$billing[city]</AVScity>
                            <AVSstate>$billing[state]</AVSstate>
                            <AVSname>$billing[first_name] $billing[name]</AVSname>
                            <OrderID>$orderid</OrderID>
                            <Amount>$total</Amount>
                            <Comments>Email: $billing[email]</Comments>
                            <ShippingRef></ShippingRef>
                    </NewOrder>
            </Request>
            ";

        //die(str_replace("\n", "<br />", str_replace("<", "&lt;",  $post_string)));

        $header= "POST /authorize/ HTTP/1.0\r\n";        // HTTP/1.1 should work fine also
        $header.= "MIME-Version: 1.0\r\n";
        $header.= "Content-type: application/PTI40\r\n";
        $header.= "Content-length: "  .strlen($post_string) . "\r\n";
        $header.= "Content-transfer-encoding: text\r\n";
        $header.= "Request-number: 1\r\n";
        $header.= "Document-type: Request\r\n";
        $header.= "Interface-Version: Test 1.4\r\n";
        $header.= "Connection: close \r\n\r\n";                // Must have two CR/LF's here
        $header.= $post_string;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->_requestUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HEADER, false);                // You are providing a header manually so turn off auto header generation
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $header);
        // The following two options are necessary to properly set up SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);

        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        } else {
            curl_close($ch);
        }

        // use XML Parser on $data, and your set!

        $xmlParser = xml_parser_create();
        xml_parser_set_option($xmlParser,XML_OPTION_CASE_FOLDING,0);
        xml_parser_set_option($xmlParser,XML_OPTION_SKIP_WHITE,1);
        xml_parse_into_struct($xmlParser, $data, $vals, $index);
        xml_parser_free($xmlParser);

        print ($data);
        die;
        // $vals = array of XML tags.  Go get em!
    }



}