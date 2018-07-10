<?php
/* vim: set ts=4 sw=4 sts=4 et: */
/*****************************************************************************\
+-----------------------------------------------------------------------------+
| X-Cart Software license agreement                                           |
| Copyright (c) 2001-present Qualiteam software Ltd <info@x-cart.com>         |
| All rights reserved.                                                        |
+-----------------------------------------------------------------------------+
| PLEASE READ  THE FULL TEXT OF SOFTWARE LICENSE AGREEMENT IN THE "COPYRIGHT" |
| FILE PROVIDED WITH THIS DISTRIBUTION. THE AGREEMENT TEXT IS ALSO AVAILABLE  |
| AT THE FOLLOWING URL: https://www.x-cart.com/license-agreement-classic.html |
|                                                                             |
| THIS AGREEMENT EXPRESSES THE TERMS AND CONDITIONS ON WHICH YOU MAY USE THIS |
| SOFTWARE PROGRAM AND ASSOCIATED DOCUMENTATION THAT QUALITEAM SOFTWARE LTD   |
| (hereinafter referred to as "THE AUTHOR") OF REPUBLIC OF CYPRUS IS          |
| FURNISHING OR MAKING AVAILABLE TO YOU WITH THIS AGREEMENT (COLLECTIVELY,    |
| THE "SOFTWARE"). PLEASE REVIEW THE FOLLOWING TERMS AND CONDITIONS OF THIS   |
| LICENSE AGREEMENT CAREFULLY BEFORE INSTALLING OR USING THE SOFTWARE. BY     |
| INSTALLING, COPYING OR OTHERWISE USING THE SOFTWARE, YOU AND YOUR COMPANY   |
| (COLLECTIVELY, "YOU") ARE ACCEPTING AND AGREEING TO THE TERMS OF THIS       |
| LICENSE AGREEMENT. IF YOU ARE NOT WILLING TO BE BOUND BY THIS AGREEMENT, DO |
| NOT INSTALL OR USE THE SOFTWARE. VARIOUS COPYRIGHTS AND OTHER INTELLECTUAL  |
| PROPERTY RIGHTS PROTECT THE SOFTWARE. THIS AGREEMENT IS A LICENSE AGREEMENT |
| THAT GIVES YOU LIMITED RIGHTS TO USE THE SOFTWARE AND NOT AN AGREEMENT FOR  |
| SALE OR FOR TRANSFER OF TITLE. THE AUTHOR RETAINS ALL RIGHTS NOT EXPRESSLY  |
| GRANTED BY THIS AGREEMENT.                                                  |
+-----------------------------------------------------------------------------+
\*****************************************************************************/

/**
 * NOCHEX
 *
 * @category   X-Cart
 * @package    X-Cart
 * @subpackage Payment interface
 * @author     Ruslan R. Fazlyev <rrf@x-cart.com>
 * @copyright  Copyright (c) 2001-present Qualiteam software Ltd <info@x-cart.com>
 * @license    https://www.x-cart.com/license-agreement-classic.html X-Cart license agreement
 * @version    cf9e608d41c40f761c6416f642a1d0094a6af214, v54 (xcart_4_7_7), 2017-01-24 09:29:34, ps_nochex.php, aim
 * @link       http://www.x-cart.com/
 * @see        ____file_see____
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['mode']) && $_GET['mode'] == 'responder' && $_POST && isset($_GET['orderids']) && $_GET['orderids']) {
    require __DIR__.'/auth.php';

    x_load('http');

    $bill_output['sessid'] = func_query_first_cell("SELECT sessid FROM $sql_tbl[cc_pp3_data] WHERE ref='".$orderids."'");

    // APC system responder
    foreach ($_POST as $k => $v) {
        $advinfo[] = "$k: $v";
    }

    $to_email = trim($to_email);
	$postvars = http_build_query($_POST);
	ini_set("SMTP","mail.nochex.com" ); 
	$header = "From: apc@nochex.com";
 
	if(isset($_POST["optional_2"]) == "ENABLED"){
		
		$url = "https://secure.nochex.com/callback/callback.aspx";
		// Curl code to post variables back
		$ch = curl_init(); // Initialise the curl tranfer
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars); // Set POST fields
		curl_setopt($ch, CURLOPT_HTTPHEADER, "Host: secure.nochex.com");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
		curl_setopt ($ch, CURLOPT_SSLVERSION, 6); 
		$output = curl_exec($ch); // Post back
		curl_close($ch);
		
		if($_POST["transaction_status"] == "100"){
			$Callback_status = "Test";
		}else{
			$Callback_status = "Live"; 
		}
		

		if (!strstr($output, "AUTHORISED")) {
            $bill_output['code'] = 2;
            $bill_output['billmes'] = "Callback Declined! This was a " . $Callback_status . " transaction";
		}else{
            $bill_output['code'] = 1;
            $bill_output['billmes'] = "Callback Authorised! This was a " . $Callback_status . " transaction";
		}
	
	}else{
	
		// Set parameters for the email
		$url = "https://www.nochex.com/apcnet/apc.aspx";
		// Curl code to post variables back
		$ch = curl_init(); // Initialise the curl tranfer
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars); // Set POST fields
		curl_setopt($ch, CURLOPT_HTTPHEADER, "Host: www.nochex.com");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
		curl_setopt ($ch, CURLOPT_SSLVERSION, 6); 
		$output = curl_exec($ch); // Post back
		curl_close($ch);

		if (!strstr($output, "AUTHORISED")) {
            $bill_output['code'] = 2;
            $bill_output['billmes'] = "APC Declined! This was a " . $_POST["status"] . " transaction";
		}else{
            $bill_output['code'] = 1;
            $bill_output['billmes'] = "APC Authorised! This was a " . $_POST["status"] . " transaction";
		}
	}

    if (isset($amount)) {
        $payment_return = array(
            'total' => $amount
        );
    }

    $skey = $_GET['orderids'];
    require $xcart_dir.'/payment/payment_ccmid.php';
    require $xcart_dir.'/payment/payment_ccwebset.php';

} elseif (isset($_GET['mode']) && $_GET['mode'] == 'complete' && isset($_GET['orderids'])) {
 
    require __DIR__.'/auth.php';

    $weblink = 2;
    $skey = $_GET['orderids'];
    require($xcart_dir.'/payment/payment_ccview.php');

} elseif (isset($_GET['mode']) && $_GET['mode'] == 'cancel' && isset($_GET["orderids"])) {
  
    require __DIR__.'/auth.php';

    $bill_output['sessid'] = func_query_first_cell("SELECT sessid FROM $sql_tbl[cc_pp3_data] WHERE ref='".$_GET["orderids"]."'");
    $bill_output['code'] = 2;
    $bill_output['billmes'] = "Cancelled by customer";

    $skey = $_GET['orderids'];
    require($xcart_dir.'/payment/payment_ccend.php');

} else {

    if (!defined('XCART_START')) { header("Location: ../"); die("Access denied"); }

    $_orderids = func_addslashes($module_params['param04'].join("-",$secure_oid));
    if (!$duplicate)
        db_query("REPLACE INTO $sql_tbl[cc_pp3_data] (ref,sessid,trstat) VALUES ('".$_orderids."','".$XCARTSESSID."','GO|".implode('|',$secure_oid)."')");

$totalProducts = count($cart["products"]);

$description = "";
$xmlCollection = "<items>";
for($i=0, $totalProducts; $i < $totalProducts; $i++){
  
$description .= " Product: " . $cart["products"][$i]["product"] . ", Qty " . $cart["products"][$i]["amount"]. " x Price " . $cart["products"][$i]["price"];
$xmlCollection .= "<item><id>" .$cart["products"][$i]["productid"]. "</id><name>" .$cart["products"][$i]["product"]. "</name><description> Product Code: " .$cart["products"][$i]["productcode"]. ", Product Weight: " . $cart["products"][$i]["weight"]."</description><quantity>" .$cart["products"][$i]["amount"]. "</quantity><price>" .$cart["products"][$i]["price"]. "</price></item>";

}
$xmlCollection .= "</items>";
 
 	/* XML */
	if ($module_params['param02'] == 'Y') {
		$description = "Order created for: " . $_orderids;
	}else{
		$xmlCollection = "";	
	}

	/* Postage */	
	if ($module_params['param03'] == 'Y') {
		$postage = $cart["shipping_cost"];
		$totalamount = $cart["subtotal"];
	}else{
		$postage = "";
		$totalamount = $cart["total_cost"];
	}

	/* Callback */	
	if ($module_params['param05'] == 'Y') { 
		$callOpt = "ENABLED";
	}else{
		$callOpt = "DISABLED";
	}
  
    $fields = array(
        'merchant_id' => trim($module_params['param01']),
        'amount' => $totalamount,
        'postage' => $postage,
        'order_id' => $_orderids,
        'billing_fullname' => $userinfo["b_firstname"] . ' ' . $userinfo["b_lastname"],
        'billing_address' => $userinfo["b_address"],
        'billing_city' => $userinfo["b_city"],
        'billing_country' => $userinfo["b_country"],
        'billing_postcode' => $userinfo["b_zipcode"],
        'delivery_fullname' => $userinfo["s_firstname"] . ' ' . $userinfo["s_lastname"],
        'delivery_address' => $userinfo["s_address"],
        'delivery_city' => $userinfo["s_city"],
        'delivery_country' => $userinfo["s_country"],
        'delivery_postcode' => $userinfo["s_zipcode"],
        'email_address' => $userinfo['email'],
        'customer_phone_number' => $userinfo["phone"],
        'description' => $description,
        'xml_item_collection' => $xmlCollection,
        'optional_2' => $callOpt,
        'cancel_url' => $current_location . '/payment/ps_nochex.php?mode=cancel&orderids=' . $_orderids,
        'callback_url' => $current_location . '/payment/ps_nochex.php?mode=responder&orderids=' . $_orderids,
        'success_url' => $current_location . '/payment/ps_nochex.php?mode=complete&orderids=' . $_orderids,
        'test_success_url' => $current_location . '/payment/ps_nochex.php?mode=complete&orderids=' . $_orderids
    );
	 
    if ($module_params['testmode'] == 'Y') {
        $fields['test_transaction'] = 100;
    }

    func_create_payment_form('https://secure.nochex.com/default.aspx', $fields, 'NOCHEX');
}

exit;

?>
