<?php 

class AuthorizeNetComponent extends Object {
    function startup(&$controller) { }
    function chargeCard($loginid, $trankey, $ccnum, $ccexpmonth, $ccexpyear, $ccver, $live, $amount, $tax, $shipping, $desc, $billinginfo, $email, $phone, $shippinginfo, $invoice) {
        $ccexp = $ccexpmonth . '/' . $ccexpyear;
        $DEBUGGING					= 0;
        $TESTING					= 0;
        $ERROR_RETRIES				= 2;
        $auth_net_login_id			= $loginid;
        $auth_net_tran_key			= $trankey;
        $auth_net_url				= "https://secure.authorize.net/gateway/transact.dll";
        $authnet_values				= array
        (
            "x_login"				=> $auth_net_login_id,
            "x_version"				=> "3.1",
            "x_delim_char"			=> "|",
            "x_delim_data"			=> "TRUE",
            "x_url"					=> "FALSE",
            "x_type"				=> "AUTH_CAPTURE",
            "x_method"				=> "CC",
            "x_tran_key"			=> $auth_net_tran_key,
            "x_relay_response"		=> "FALSE",
            "x_card_num"			=> str_replace(" ", "", $ccnum),
            "x_card_code"			=> $ccver,
            "x_test_request"		=> $live,
            "x_exp_date"			=> $ccexp,
            "x_description"			=> $desc,
            "x_amount"				=> $amount,
            "x_tax"					=> $tax,
            "x_freight"				=> $shipping,
            "x_first_name"			=> $billinginfo["fname"],
            "x_last_name"			=> $billinginfo["lname"],
            "x_address"				=> $billinginfo["address"],
            "x_city"				=> $billinginfo["city"],
            "x_state"				=> $billinginfo["state"],
            "x_zip"					=> $billinginfo["zip"],
            "x_country"				=> $billinginfo["country"],
            "x_email"				=> $email,
            "x_phone"				=> $phone,
            "x_ship_to_first_name"	=> $shippinginfo["fname"],
            "x_ship_to_last_name"	=> $shippinginfo["lname"],
            "x_ship_to_address"		=> $shippinginfo["address"],
            "x_ship_to_city"		=> $shippinginfo["city"],
            "x_ship_to_state"		=> $shippinginfo["state"],
            "x_ship_to_zip"			=> $shippinginfo["zip"],
            "x_ship_to_country"		=> $shippinginfo["country"],
            "x_invoice_num"         => $invoice
        );
        $fields = "";
        foreach ( $authnet_values as $key => $value ) $fields .= "$key=" . urlencode( $value ) . "&";
        $ch = curl_init("https://secure.authorize.net/gateway/transact.dll");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $resp = curl_exec($ch);
        curl_close ($ch);
        $text = $resp;
        $h = substr_count($text, "|");
        $h++;
        $responsearray = array();
        for($j=1; $j <= $h; $j++){
            $p = strpos($text, "|");
            if ($p === false) {
                $responsearray[$j] = $text;
            }
            else {
                $p++;
                $pstr = substr($text, 0, $p);
                $pstr_trimmed = substr($pstr, 0, -1);
                if($pstr_trimmed==""){
                    $pstr_trimmed="";
                }
                $responsearray[$j] = $pstr_trimmed;
                $text = substr($text, $p);
            }
        }
        return $responsearray;
    }
}