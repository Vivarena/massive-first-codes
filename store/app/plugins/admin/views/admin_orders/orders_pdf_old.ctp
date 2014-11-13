<?php
$empty = "Orders for a certain period of no entries";

$html = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title></title>
</head>
<body>


    <table cellpadding="0" cellspacing="0" border="0" width="780" style="font-family:Arial;">
        <tr>
            <td><img alt="The Situation" src="/app/webroot/img/logo.png" width="190" height="96" hspace="0" vspace="0" /></td>
            <td align="right">
                <h1>FINANCIAL REPORT</h1>
            </td>
        </tr>
        <tr>
            <td colspan="2">';
                if(!empty($dates['from']) && !empty($dates['to'])) {
                $html .= '
                <p align="left">
                    <font size="+1">
                        <strong>
                            PERIOD: FROM ' . date('m/d/Y', strtotime($dates['from'])) . '
                            TO ' . date('m/d/Y', strtotime($dates['to'])) . '
                        </strong>
                    </font>
                </p>';
                } elseif(!empty($dates['from']) && empty($dates['to'])) {
                $html .= '
                <p align="left">
                    <font size="+1">
                        <strong>
                            PERIOD: FROM ' . date('m/d/Y', strtotime($dates['from'])) . '
                        </strong>
                    </font>
                </p>';
                } elseif(empty($dates['from']) && !empty($dates['to'])) {
                $html .= '
                <p align="left">
                    <font size="+1">
                        <strong>
                            PERIOD: TO ' . date('m/d/Y', strtotime($dates['to'])) . '
                        </strong>
                    </font>
                </p>';
                } else {
                $html .= '
                <p align="left">
                    <font size="+1">
                        <strong>
                            &nbsp;
                        </strong>
                    </font>
                </p>';
                }
foreach($result as $item) {
                $html .= '
                <table cellpadding="5" cellspacing="0" border="0" width="780">
                    <tr>
                        <td width="150" bgcolor="#000000" style="color:#FFFFFF; ">
                            <font size="+3">DATE</font>
                        </td>
                        <td width="480" bgcolor="#000000" style="color:#FFFFFF; ">
                            <font size="+3">NAME</font>
                        </td>
                        <td width="150" bgcolor="#000000" style="color:#FFFFFF; ">
                            <font size="+3">PHONE</font>
                        </td>
                    </tr>
                    <tr>
                        <td style=" border-bottom:1px dashed #000000">' . date('m/d/Y', strtotime($item["Order"]["created"])) . '</td>
                        <td style=" border-bottom:1px dashed #000000">' . $item['Order']['shipping_name'] . '</td>
                        <td style=" border-bottom:1px dashed #000000">' . $item['Order']['phone'] . '</td>
                    </tr>
                    <tr>
                        <td colspan="3"></td>
                    </tr>
                </table>
                <h2>PRODUCTS</h2>
                <table cellpadding="5" cellspacing="0" border="0" width="780">
                    <tr>
                        <td width="180" bgcolor="#D26F23" style="color:#FFFFFF;">
                            <font size="+3">PRODUCT NAME</font>
                        </td>
                        <td bgcolor="#D26F23" width="50" style="color:#FFFFFF;">
                            <font size="+3">QTY</font>
                        </td>
                        <td bgcolor="#D26F23" width="100" style="color:#FFFFFF;">
                            <font size="+3">OUR COST</font>
                        </td>
                        <td bgcolor="#D26F23" width="110" style="color:#FFFFFF;">
                            <font size="+3">SALE PRICE</font>
                        </td>
                        <td bgcolor="#D26F23" width="110" style="color:#FFFFFF;">
                            <font size="+3">NET PROFIT</font>
                        </td>
                        <td bgcolor="#D26F23" width="100" style="color:#FFFFFF;">
                            <font size="+3">TOTAL</font>
                        </td>
                        <td bgcolor="#D26F23" width="140" style="color:#FFFFFF; ">
                            <font size="+3">DONATION</font>
                        </td>
                    </tr>';

                    foreach($item['OrderProduct'] as $product) {
                    $html .= '
                    <tr>
                        <td width="180" style="border-bottom:1px dashed #000000">
                            ' . ucfirst(strtolower($product['name'])) . '
                        </td>
                        <td  style="border-bottom:1px dashed #000000">
                            ' . $product['quantity'] . '
                        </td>
                        <td  width="100" style="border-bottom:1px dashed #000000">
                            ' . $product['networth'] . '
                        </td>
                        <td  width="110" style="border-bottom:1px dashed #000000">
                            ' . $product['price'] . '
                        </td>
                        <td width="110" style="border-bottom:1px dashed #000000">
                            ' . $product['net_profit'] . '
                        </td>
                        <td width="100" style="border-bottom:1px dashed #000000">
                            ' . $product['itemPrice'] . '
                        </td>
                        <td width="140" style="border-bottom:1px dashed #000000">
                            ' . $product['charity'] . '
                        </td>
                    </tr>';
                    }
                   
                $html .= '</table>
                <p style="text-align:center;"><br /><br /><font size="+5"> *********</font></p>';

}



                


$html .= '
                <br /><br />
                <table cellpadding="5" cellspacing="0" border="0" width="780">
                    <tr>
                        <td width="680" align="right" bgcolor="#000000" style="color:#FFFFFF; ">
                            <font size="+3">TOTAL:</font>
                        </td>
                        <td width="100" bgcolor="#000000" style="color:#FFFFFF; ">
                            <font size="+3">' . $result[0]['total'] . '</font>
                        </td>
                    </tr>
                </table>
                <br />
                <table cellpadding="5" cellspacing="0" border="0" width="780">
                    <tr>
                        <td width="680" align="right" bgcolor="#000000" style="color:#FFFFFF; ">
                            <font size="+3">NET PROFIT TOTAL:</font>
                        </td>
                        <td width="100" bgcolor="#000000" style="color:#FFFFFF; ">
                            <font size="+3">' . $result[0]['total_profit'] . '</font>
                        </td>
                    </tr>
                </table>
                <br />
                <table cellpadding="5" cellspacing="0" border="0" width="780">
                    <tr>
                        <td width="680" align="right" bgcolor="#000000" style="color:#FFFFFF; ">
                            <font size="+3">DONATION TOTAL:</font>
                        </td>
                        <td width="100" bgcolor="#000000" style="color:#FFFFFF; ">
                            <font size="+3">' . $result[0]['donationSumm'] . '</font>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>


</body>
</html>
';

$mpdf->setPrintHeader(false);
$mpdf->setPrintFooter(false);

$mpdf->AddPage();



$mpdf->WriteHTML($html);

$mpdf->Output();
//$mpdf->Output('orders.pdf','D');
exit;

?>