<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript">
	
	//funcion para enviar los datos del formulario
	function submitform(){
	 document.params_form.submit();
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
	require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '/'. "checkout_process.php");


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
	if (!strstr($_SERVER['HTTP_REFERER'], FILENAME_CHECKOUT_CONFIRMATION)) {
	      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT,'','SSL'));
	}

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

// load the before_process function from the payment modules
	$payment_modules->before_process();
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_PAYMENT_MODULES_BEFOREPROCESS');

// create the order record
	$insert_id = $order->create($order_totals, 2);
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE');
	$payment_modules->after_order_create($insert_id);
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_PAYMENT_MODULES_AFTER_ORDER_CREATE');

// store the product info to the order
	$order->create_add_products($insert_id);
	$_SESSION['order_number_created'] = $insert_id;
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE_ADD_PRODUCTS');

//send email notifications
	$order->send_order_email($insert_id, 2);
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_SEND_ORDER_EMAIL');


// load the after_process function from the payment modules

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


?>


<form name="params_form" method="post" action= <?= MODULE_PAYMENT_PAYCO_URL; ?> >
	<?php 
	$payco = new payco();
	//$procees = new process_button();
	//$datos = $payco->payco;
	//var_export($order);	

	$pkey = sha1($payco->clave_secreta.$payco->usuario_id);

	if($payco->prueba =="1"){
		$test = "TRUE";
	}else{
		$test = "FALSE";
	}

	?>
	   
	    <!-- Se construye el boton de pago con todos los campos que estan en payco del plugin-->
		<input type="hidden" name="p_cust_id_cliente" value="<?php echo $payco->usuario_id; ?>">
		<input type="hidden" name="p_key" value="<?php echo $pkey; ?>">
		<input type="hidden" name="p_test_request" value="<?php echo $test; ?>">
		<input type="hidden" name="p_email" value="<?php echo $order->customer['email_address']; ?>">
		<input type="hidden" name="p_tax" value="0">
		<input type="hidden" name="p_currency_code" value="<?php echo $order->info['currency']; ?>">
		<input type="hidden" name="p_description" value="<?php echo $payco->description; ?>">
		<input type="hidden" name="p_extra1" value="0">
		<input type="hidden" name="p_extra2" value="0">
		<input type="hidden" name="p_extra3" value="0">
		<input type="hidden" name="p_url_respuesta" value="<?php echo  HTTP_SERVER.DIR_WS_CATALOG."confirmacion.php"; ?>">
		<input type="hidden" name="p_amount" value="<?php echo round($order->info['subtotal'],2); ?>">
		<input type="hidden" name="p_id_factura" value="<?php echo $insert_id; ?>">		
		<input type="hidden" name="p_amount_base" value="0">
	
</form>


<?php 
  //termina el aplicativo
   require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
</body>
</html>