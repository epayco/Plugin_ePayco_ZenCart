<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript">
	//funcion para enviar los datos del formulario
	function submitform(){
	 //document.params_form.submit();
	}
</script>
</head>


<body onLoad="submitform()">

<?php

/**
 * module to process a completed checkout
 *
 * @package procedureCheckout
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: checkout_process_pol.php 4865 2006-10-31 07:44:40Z drbyte $
 * @version Plugin 1.0 del modulo de pagos de Payco
 */
 
 
 
	include('includes/application_top.php');
	require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/lang.'. "checkout_process.php");


// This should be first line of the script:
	$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_PROCESS');
	
	if (!defined('IS_ADMIN_FLAG')) {
	  die('Illegal Access');
	}

//notifica el comienzo del checkout
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEGIN');

//incluye los lenguajes
	require(DIR_WS_MODULES . zen_get_module_directory('require_languages.php'));

// if the customer is not logged on, redirect them to the time out page
	  if (!$_SESSION['customer_id']) {
			zen_redirect(zen_href_link(FILENAME_TIME_OUT));
	  } else {
		// validate customer
			if (zen_get_customer_validate_session($_SESSION['customer_id']) == false) {
				  $_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_SHIPPING));
				  zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
			}
	  }

// confirm where link came from
	/*if (!strstr($_SERVER['HTTP_REFERER'], FILENAME_CHECKOUT_CONFIRMATION)) {
	      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT,'','SSL'));
	}*/

// load selected payment module
	require(DIR_WS_CLASSES . 'payment.php');
	$payment_modules = new payment($_SESSION['payment']);

// load the selected shipping module
	require(DIR_WS_CLASSES . 'shipping.php');
	$shipping_modules = new shipping($_SESSION['shipping']);
	
	require(DIR_WS_CLASSES . 'order.php');
	$order = new order;

// prevent 0-entry orders from being generated/spoofed
	if (sizeof($order->products) < 1) {
	 	zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
	}

	
	require(DIR_WS_CLASSES . 'order_total.php');
	$order_total_modules = new order_total;
	
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_ORDER_TOTALS_PRE_CONFIRMATION_CHECK');
	
	$order_totals = $order_total_modules->pre_confirmation_check();
	
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_ORDER_TOTALS_PROCESS');
	$order_totals = $order_total_modules->process();
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS');

	if (!isset($_SESSION['payment']) && !$credit_covers) {
	  	zen_redirect(zen_href_link(FILENAME_DEFAULT));
	}

// create the order record
	$insert_id = $order->create($order_totals, 2);
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE');
	$payment_modules->after_order_create($insert_id);
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_PAYMENT_MODULES_AFTER_ORDER_CREATE');

	// load the before_process function from the payment modules
	$_POST['insert_id'] = $insert_id;
	$sessionId = $payment_modules->before_process();
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_PAYMENT_MODULES_BEFOREPROCESS');

// store the product info to the order
	$order->create_add_products($insert_id);
	$_SESSION['order_number_created'] = $insert_id;
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE_ADD_PRODUCTS');

//send email notifications
	//$order->send_order_email($insert_id, 2);
	//$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_SEND_ORDER_EMAIL');
	if($sessionId['success']){
		$sessionId = $sessionId['data'];
	}else{
		zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT,'','SSL'));
		die();
	}

// load the after_process function from the payment modules
	$payment_modules->after_process();
	$_SESSION['cart']->reset(true);
	
// unregister session variables used during checkout
	unset($_SESSION['sendto']);
	unset($_SESSION['billto']);
	unset($_SESSION['shipping']);
	unset($_SESSION['payment']);
	unset($_SESSION['comments']);
	$order_total_modules->clear_posts();//ICW ADDED FOR CREDIT CLASS SYSTEM


// This should be before the zen_redirect:
	$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_PROCESS');

	$payco = new epayco();

	if($payco->prueba =="1"){
		$test = true;
	}else{
		$test = false;
	}
echo sprintf(
			'<script
				src="https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod-v2.js">
			</script>
			<script>
			let session = "%s";
			let testMode = "%s" == "1" ? true : false;
				const checkout = ePayco.checkout.configure({
					sessionId: session,
					type: "standard",
					test: testMode
				});
				checkout.open();
			</script>
        ',
            $sessionId,
			$test
        );
  //termina el aplicativo
   require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
</body>
</html>