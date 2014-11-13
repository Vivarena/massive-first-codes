<?php
/**
 * @property SessionComponent $Session 
 * @property CartComponent    $Cart
 * @property Region           $Region
 * @property Country          $Country
 * @property Product          $Product
 */
class QnetComponent extends Object
{

    /**
     * {@inheritdoc}
     */
    public $components = array("Cart", "Session");

    public $Controller = null;

    function initialize($controller)
    {
        $this->Controller = $controller;
        $this->Region = ClassRegistry::init('Region');
        $this->Country = ClassRegistry::init('Country');
    }

    public function sendQuery($sku=null) {


        $billingShipping = $this->Session->read("BillingShipping");    
        $products = $this->Cart->in_cart();

        $billingShipping['Billing']['state']   = $this->Region->getRegionName($billingShipping['Billing']['state']);
        $billingShipping['Billing']['country'] = $this->Country->getCountryName($billingShipping['Billing']['country']);

        $billingShipping['Shipping']['state']   = $this->Region->getRegionName($billingShipping['Shipping']['state']);
        $billingShipping['Shipping']['country'] = $this->Country->getCountryName($billingShipping['Shipping']['country']);


        $xml = "<?xml version='1.0' encoding='iso-8859-1' standalone='yes'?>
<!DOCTYPE QNET>
<QNET Version='0206' Type='Download'>
<ORDER Reqnum='123456' BillShipTo='Y' RlsPrefix='X'>
 	<CLIENT>SITCH</CLIENT>
 	<USER FullName='Frank Sorrentino'>MRSORRENTINO</USER>
 	<DATE>" . date('Ymd', mktime()) . "</DATE>
 	<LOGIN_CC>01</LOGIN_CC>
 	<CLIENT_PO>PO1234567890-abcde</CLIENT_PO>
 	<ATTN>ATTN: Receiving</ATTN>
 	<VIA>U2</VIA>
 	<EMAIL>" . $billingShipping['Billing']['email'] . "</EMAIL>
 	<TRACK Company='U'>1Z12345670012345</TRACK>
 	<BILLING>
 	 	<ADDR_LINE1>" . $billingShipping['Billing']['address1'] . "</ADDR_LINE1>
 	    <ADDR_LINE2>" . $billingShipping['Billing']['address2'] . "</ADDR_LINE2>
 	    <ADDR_CITY>" . $billingShipping['Billing']['city'] . "</ADDR_CITY>
 	    <ADDR_STATE>" . $billingShipping['Billing']['state'] . "</ADDR_STATE>
 	    <ADDR_ZIP>" . $billingShipping['Billing']['zip'] . "</ADDR_ZIP>
 	</BILLING>
 	<SHIP_LOCS>
        <CC Type='C' Id='SITCH' Ref='1'>
            <ATTN>Bill U. Later</ATTN>
            <ADDR_LINE1>S" . $billingShipping['Shipping']['address1'] . "</ADDR_LINE1>
            <ADDR_LINE2>" . $billingShipping['Shipping']['address2'] . "</ADDR_LINE2>
            <ADDR_CITY>" . $billingShipping['Shipping']['city'] . "</ADDR_CITY>
            <ADDR_STATE>" . $billingShipping['Shipping']['state'] . "</ADDR_STATE>
            <ADDR_ZIP>" . $billingShipping['Shipping']['zip'] . "</ADDR_ZIP>
            <ADDR_PHONE>" . $billingShipping['Shipping']['phone'] . "</ADDR_PHONE>
        </CC>
 	</SHIP_LOCS>
 	<REMARKS>Please ship this order as soon as possible.</REMARKS>
 	<LINE_ITEMS>";
        foreach ($products['Products'] as $key=>$product) {
            $xml .= "<LINE Number='$key' Catalog='" . $sku . "'>
            <ITEM Client='STFRUS'>$product[title]</ITEM>
            <DESC>OfficialSituation.com</DESC>
            <COST>$product[price]</COST>
            <PRICE>" . $product['price'] * $product['qty'] . "</PRICE>
            <REQUIRED_DATE>" . date("Ymd", mktime()) . "</REQUIRED_DATE>
            <QTY ShipRef='1' ChargeTo='ADMIN-CC1'>$product[qty]</QTY>
            <REMARKS>Remember to ship the new ones</REMARKS>
            <ARTWORK>" . env("SERVER_NAME") . $product['image'] . "</ARTWORK>
        </LINE>";
        }
        unset($product);
        
 	$xml .= "</LINE_ITEMS>
                </ORDER>
                </QNET>";

        if (isset($sku) && !empty($sku)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://qnet.e-quantum2k.com/~suncoast/cgi-bin/external-order.cgi');
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$xml);

            // The following two options are necessary to properly set up SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);

            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            Configure::write("debug", 0);

            $data = curl_exec($ch);



            if (curl_errno($ch)) {
                print curl_error($ch);
            } else {
                curl_close($ch);
            }
        }

    }

    public function sendQueryPayPal($orderId) {
        //TODO ...
    }
}