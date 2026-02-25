<?php
/**
 * Epayco Payment Module
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: piloujp 2025 Oct 10 Modified in v2.2.0 $
 */

/**
 * @since ZC v1.0.3
 */
class epayco extends base {

//declaracion de variables
    public $code;
    public $title; 
    public $description;
    public $enabled;
    public $sort_order; 
    public $order_status;
    protected $_check;
    public $pcustId;
    public $prueba;
    public $pKey;
    public $publickKey;
    public $privateKey;
    public $form_action_url;



// class constructor
	function __construct()
	{
      global $order;

//asignacion de valores
      $this->code = 'epayco';
      $this->title = MODULE_PAYMENT_PAYCO_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_PAYCO_TEXT_DESCRIPTION;
      $this->enabled = (defined('MODULE_PAYMENT_PAYCO_STATUS') && MODULE_PAYMENT_PAYCO_STATUS == 'True');  
      $this->sort_order = defined('MODULE_PAYMENT_PAYCO_SORT_ORDER') ? MODULE_PAYMENT_PAYCO_SORT_ORDER : null;

      if (null === $this->sort_order) return false;
      if (defined('MODULE_PAYMENT_PAYCO_ORDER_STATUS_ID') && (int)MODULE_PAYMENT_PAYCO_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_PAYCO_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();
	  
	  /* a que direccion se redirecciona despues de dar click en el boton de pago, se direcciona al checkout el cual crea la orden y envia los datos al sistema */
    //  $this->form_action_url = DIR_WS_CATALOG . 'checkout_process_pol.php';
	  
	  /* Numero de usuario en Payco   */
      $this->pcustId = MODULE_PAYMENT_PAYCO_PCUSTID;
	  
	  /* Indica si la transaccion es de prueba (1) o no (0)*/
	  $this->prueba = MODULE_PAYMENT_PAYCO_CHECKOUT_MODE == 'Yes' ? 1 : 0;

    $this->pKey = MODULE_PAYMENT_PAYCO_PKEY;
    $this->publickKey = MODULE_PAYMENT_PAYCO_PUBLICK_KEY;
    $this->privateKey = MODULE_PAYMENT_PAYCO_PRIVATE_KEY;
	  //$this->form_action_url = zen_href_link('ipn_main_handler.php', 'type=ec&markflow=1&clearSess=1&stage=final', 'SSL', true, true, true);
	  $this->form_action_url = DIR_WS_CATALOG . 'checkout_process_pol.php';
	        
    }

// class methods

    //funcion para actualizar el estado del plugin
  function update_status() {
    global $order, $db;

    if ($this->enabled && (int)MODULE_PAYMENT_PAYCO_ZONE > 0 && isset($order->billing['country']['id'])) {
      $check_flag = false;
      $check_query = $db->Execute("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . MODULE_PAYMENT_PAYCO_ZONE . "' AND zone_country_id = '" . (int)$order->billing['country']['id'] . "' ORDER BY zone_id");
      while (!$check_query->EOF) {
        if ($check_query->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check_query->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check_query->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }
  }


    function javascript_validation() {
      return false;
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->title,
                    'icon' => '<img src="'. MODULE_PAYMENT_PAYCO_MARK_BUTTON_IMG .'" alt="'. MODULE_PAYMENT_PAYCO_TEXT_BUTTON_ALTTEXT .'" title="'. MODULE_PAYMENT_PAYCO_TEXT_BUTTON_ALTTEXT .'">'
                   );
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      //return false;
      $token = $this->generateToken();

      $_SESSION['epayco_token'] = $token;

      return array(
        'title' => $this->title
      );
    }
    

    /**
   * Prepare and submit the final authorization to epayco via the appropriate means as configured
   * @since ZC v1.3.7
   */
  function before_process() {
    global $order, $db;

    // The order has already been created at this point, so we can grab the order ID and update the order with the transaction ID returned from epayco
    $descripcion = $_POST['description'];
    $currency = $_POST['currency'];
    $amount = $_POST['amount'];
    $iva = $_POST['iva'];
    $ico = $_POST['ico'];
    $base_tax = $amount - $iva;
    $lang = $_POST['lang'];
    $url_respuesta = $_POST['url_respuesta'];
    $confirm_url = $_POST['url_confirmacion'];
    $name_billing = $_POST['name_billing'];
    $email_billing = $_POST['email_billing'];
    $testMode = $_POST['test'] == "0" ? false : true;
    $token = $_POST['token'];
    $insert_id = $_POST['insert_id'];
    $payload  = array(
        "name"=>substr($descripcion, 0, 30),
        "description"=>substr($descripcion, 0, 240),
        "invoice"=>(string)$insert_id."_zc",
        "currency"=>$currency,
        "amount"=>floatval($amount),
        "taxBase"=>floatval($base_tax),
        "tax"=>floatval($iva),
        "taxIco"=>floatval($ico),
        "lang"=>$lang,
        "confirmation"=>$confirm_url,
        "response"=>$url_respuesta,
        "billing" => [
            "name" => $name_billing,
            "email" => $email_billing,
        ],
        "autoclick"=> true,
        //"ip"=>$myIp,
        "test"=>$testMode,
          "extras" => [
            "extra1" => (string)"zencard",
            "extra2" => (string)$insert_id,
        ],
        "extrasEpayco" => [
            "extra5" => "P65"
        ],
        "epaycoMethodsDisable" => [],
        "method"=> "POST",
        "checkout_version"=>"2",
        "autoClick" => false,
        "noRedirectOnClose"=> true,
        "forceResponse"=>false,//mostrar detalle de orden
        "uniqueTransactionPerBill"=> false,
    );
    $path = "payment/session/create";
    $response = [
      "success" => false,
      "data" => []
    ];
    if (!empty($token)) {
        $epayco_status_session = $this->getEpaycoSessionId($path, $payload, $token);
       if (is_array($epayco_status_session) && isset($epayco_status_session['success']) && $epayco_status_session['success']) {
          if (isset($epayco_status_session['data']) && is_array($epayco_status_session['data'])) {
              $sessionId =  $epayco_status_session['data']['sessionId'];
              $response = [
                "success" => true,
                "data" => $sessionId
              ];
          }
        }else{
          $messageError = (is_array($epayco_status_session) && isset($epayco_status_session['textResponse'])) ? $epayco_status_session['textResponse'] : '';
          $errorMessage = "";
          if (isset($epayco_status_session['data']['errors'])) {
              $errors = $epayco_status_session['data']['errors'];
              if(is_array($errors)){
                  foreach ($errors as $error) {
                      $errorMessage = $error['errorMessage'] . "\n";
                  }
              }else{
                  $errorMessage = $errors. "\n";
              }
          } elseif (isset($epayco_status_session['data']['error']['errores'])) {
              $errores = $epayco_status_session['data']['error']['errores'];
              foreach ($errores as $error) {
                  $errorMessage = $error['errorMessage'] . "\n";
              }
          }
          //$processReturnFailMessage = $messageError . " " . $errorMessage;
          $response = [
            "success" => false,
            "data" => $errorMessage
          ];
      }
    }
    return $response;	
  }

//funcion para crear el boton de pago

    function process_button() 
    {
		global $db, $order, $currencies, $currency;
		  
		  
		//referencia de venta
		$refventa=time();
		  
		//moneda
		$my_currency = $order->info['currency'];
		  
		//descripcion de la venta
		$descripcion = "";
	  	for($i = 0; $i < sizeof($order->products); $i++)  {
				
				$descripcion .= $order->products[$i]['name']." x ".$order->products[$i]['qty'].", ";
			}
			
		//nombre del comprador	
		$nombre_cliente = $order->customer['firstname']." ".$order->customer['lastname'];
		
		//telefono del comprador	
		$telefono = $order->customer['telephone'];
		
		//iva del pedido
		$iva = number_format($order->info['tax'] * $currencies->get_value($my_currency),2,'.','');
					
		//se organiza la descripcion de la compra
		$descripcion = substr($descripcion,0,250);
		
		//valor total de la compra
	    	
		    $total=$order->info['total'];             
        $valor = round($total*$order->info['currency_value'],2);
        $valor = number_format($valor, 2, '.', '');
        
	    
	   //cadena para la firma digital
	    $cadtmp = $this->pcustId."~".$this->pKey."~".$refventa."~".$valor."~".$my_currency;
		
		//firma digital
		$firma = md5($cadtmp);
			
	  	//pagina de respuesta
		$url_respuesta= HTTP_SERVER . DIR_WS_CATALOG . "index.php?main_page=checkout_success";  
    if (!isset($_SESSION['epayco_token'])) {
        $_SESSION['epayco_token'] = $this->generateToken();
    }
	  	//creacion del boton de pago
       	$process_button_string =				   
							  zen_draw_hidden_field('amount', $valor).
								zen_draw_hidden_field('usuarioId', $this->pcustId).
								//zen_draw_hidden_field('name', "Compra en la tienda ".'::' . STORE_NAME . '::').
                zen_draw_hidden_field('description', $descripcion ).
								zen_draw_hidden_field('currency', $my_currency).
								zen_draw_hidden_field('test', $this->prueba).
                zen_draw_hidden_field('nombreComprador', $nombre_cliente).
								zen_draw_hidden_field('emailComprador', $order->customer['email_address']).
								zen_draw_hidden_field('iva', $iva).
								zen_draw_hidden_field('url_respuesta', $url_respuesta).
								zen_draw_hidden_field('url_confirmacion', HTTP_SERVER . DIR_WS_CATALOG . "confirmacion.php").
								zen_draw_hidden_field('lang', $_SESSION['languages_code']).
                zen_draw_hidden_field('token', $_SESSION['epayco_token']['token']);

     return $process_button_string;
    }


    function after_process(){
       return false;
    }

    function output_error() {
      return false;
    }

    function check(){
       global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYCO_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    return $this->_check;
	}



	//funcion para instalar el plugin de Payco en el modulo administrativo
    function install(){
      global $db, $messageStack;
      if (defined('MODULE_PAYMENT_PAYCO_STATUS')) {
        $messageStack->add_session(sprintf(TEXT_ERROR_MODULE_ALREADY_INSTALLED, $this->title), 'error');
        zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=epayco', 'NONSSL'));
        return 'failed';
      }
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable ePayco Module', 'MODULE_PAYMENT_PAYCO_STATUS', 'True', 'Do you want to accept ePayco payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
      //$db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Transaction Currency', 'MODULE_PAYMENT_PAYCO_CURRENCY', 'Selected Currency', 'Which currency should the order be sent to ePayco as? <br>NOTE: if an unsupported currency is sent to ePayco, it will be auto-converted to USD.', '6', '3', 'zen_cfg_select_option(array(\'Selected Currency\', \'Only USD\', \'Only AUD\', \'Only CAD\', \'Only EUR\', \'Only GBP\', \'Only CHF\', \'Only CZK\', \'Only DKK\', \'Only HKD\', \'Only HUF\', \'Only JPY\', \'Only NOK\', \'Only NZD\', \'Only PLN\', \'Only SEK\', \'Only SGD\', \'Only THB\', \'Only MXN\', \'Only ILS\', \'Only PHP\', \'Only TWD\', \'Only BRL\', \'Only MYR\', \'Only TRY\', \'Only INR\'), ', now())");
      $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('P_CUST_ID', 'MODULE_PAYMENT_PAYCO_PCUSTID', '', 'Enter your P_CUST_ID.', '6', '25', now(), '')");
      $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('P_KEY', 'MODULE_PAYMENT_PAYCO_PKEY', '', 'Enter your P_KEY.', '6', '25', now(), '')");
      $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('PUBLICK_KEY', 'MODULE_PAYMENT_PAYCO_PUBLICK_KEY', '', 'Enter your PUBLICK_KEY.', '6', '25', now(), 'zen_cfg_password_display')");
      $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('PRIVATE_KEY', 'MODULE_PAYMENT_PAYCO_PRIVATE_KEY', '', 'Enter your PRIVATE_KEY.', '6', '25', now(), 'zen_cfg_password_display')");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Checkout Test Mode', 'MODULE_PAYMENT_PAYCO_CHECKOUT_MODE', 'No', 'Do you want to make transaction on test mode?  If set to True.', '6', '22', 'zen_cfg_select_option(array(\'No\',\'Yes\'), ', now())");
      
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAYCO_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAYCO_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PAYCO_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
      $this->notify('NOTIFY_PAYMENT_PAYCO_INSTALLED');
	}
	
	//funcion para eliminar el plugin desde el modulo administrativo
    function remove() {
      global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
      $this->notify('NOTIFY_PAYMENT_PAYCO_UNINSTALLED');
    }

    function keys(){
      return array
      (
      'MODULE_PAYMENT_PAYCO_STATUS',
      'MODULE_PAYMENT_PAYCO_ZONE',
      'MODULE_PAYMENT_PAYCO_ORDER_STATUS_ID',
      'MODULE_PAYMENT_PAYCO_SORT_ORDER',
	    'MODULE_PAYMENT_PAYCO_CHECKOUT_MODE',
      'MODULE_PAYMENT_PAYCO_PCUSTID',
      'MODULE_PAYMENT_PAYCO_PKEY',
      'MODULE_PAYMENT_PAYCO_PUBLICK_KEY',
      'MODULE_PAYMENT_PAYCO_PRIVATE_KEY'
      );
    }

   function generateToken() {
      
      if (!isset($_COOKIE[$this->pcustId])) {
        $publicKey = trim($this->publickKey);
        $privateKey = trim($this->privateKey);
        $bearer_token = base64_encode($publicKey . ":" . $privateKey);
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . $bearer_token
        );
          $bearer_token = $this->authentication("login", [], $headers);
          $cookie_value = $bearer_token["token"] ?? '';
          setcookie($this->pcustId, $cookie_value, time() + (60 * 14), "/");
      } else {
          $bearer_token = $_COOKIE[$this->pcustId];
      }
      return $bearer_token;
  }

  function getEpaycoSessionId($path,$data, $token)
  {
      if ($token) {
          $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        );
          return $this->authentication($path, $data, $headers);
      }
  }

  function authentication($path = "login", $data = [], $headers = [])
  {
      $url = 'https://eks-apify-service.epayco.io/' . $path;
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
      ));

      $response = curl_exec($curl);
      if ($response === false) {
          return array('curl_error' => curl_error($curl), 'curerrno' => curl_errno($curl));
      }
      curl_close($curl);

      $result = json_decode($response, true);

      return $result ?? null;
 }


  
}



?>
