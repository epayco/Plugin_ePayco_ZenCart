<?php
/*
osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com
  Payco Payment Module
  Copyright (c) 2007 Payco software
  Released under the GNU General Public License
  
  Version 1.0
*/

//funcion para  crear consultas con los datos que se captura de la instalacion del plugin
	function tep_db_query($query){
		global $db;
		return($db->Execute($query));
	}
	
	function tep_db_num_rows($query){
		return($query->RecordCount());
	}



//se crea la clase del metodo de pago de payco
	
class payco{

//declaracion de variables
    var $code, $title, $description, $enabled, $usuario_id, $clave_secreta, $tasa_iva, $prueba;

// class constructor
	function payco()
	{
      global $order;

//asignacion de valores
      $this->code = 'payco';
      $this->title = MODULE_PAYMENT_PAYCO_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_PAYCO_TEXT_DESCRIPTION;
      $this->enabled = ((MODULE_PAYMENT_PAYCO_STATUS == 'True') ? true : false);      
      $this->sort_order = MODULE_PAYMENT_PAYCO_SORT_ORDER;

      
	  if ((int)MODULE_PAYMENT_PAYCO_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_PAYCO_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();
	  
	  /* a que direccion se redirecciona despues de dar click en el boton de pago, se direcciona al checkout el cual crea la orden y envia los datos al sistema */
      $this->form_action_url = DIR_WS_CATALOG . 'checkout_process_pol.php';
	  
	  /* Numero de usuario en Payco   */
      $this->usuario_id = MODULE_PAYMENT_PAYCO_ID_COM;
	  
	  /* Indica si la transaccion es de prueba (1) o no (0)*/
	  $this->prueba = MODULE_PAYMENT_PAYCO_PRUEBA;
	  
	  /* Clave de encripcion para la firma digital (Obligatorio para usuarios nuevos) */
	  $this->clave_secreta = MODULE_PAYMENT_PAYCO_ID_SEED;
	  
	  /* La tasa de IVA que aplica para sus productos, debe coincidir con la configurada en el oscommerce */
	  $this->tasa_iva = 0.16;
	        
    }

// class methods

    //funcion para actualizar el estado del plugin
    function update_status() 
	{
      global $order, $db;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYCO_ZONE > 0) ) {
        $check_flag = false;
        $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" 
		. MODULE_PAYMENT_PAYCO_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while (!$check->EOF) {
          if ($check->fields['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
          $check->MoveNext();
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
                   'module' => $this->title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      return false;
    }
    

//funcion para crear el boton de pago

    function process_button() 
    {
		global $order, $currencies, $currency, $languages;
		  
		  
		//referencia de venta
		$refventa=time();
		  
		//moneda
		$my_currency = $order->info['currency'];
		  
		//descripcion de la venta
	  
	  	/*for($i = 0; $i < sizeof($order->products); $i++)  {
				
				$descripcion .= "[".$order->products[$i]['name']." x ".$order->products[$i]['qty']."] ";
			}*/
			
		//nombre del comprador	
		$nombre_cliente = $order->customer['firstname']." ".$order->customer['lastname'];
		
		//telefono del comprador	
		$telefono = $order->customer['telephone'];
		
		//iva del pedido
		$iva = number_format($order->info['tax'] * $currencies->get_value($my_currency),2,'.','');
			
		//base de devolucion
		$baseDevolucionIva = number_format(($iva / $this->tasa_iva),2,'.','');
		
		//se organiza la descripcion de la compra
		$descripcion = substr($descripcion,0,250);
		
		//valor total de la compra
	    	
		$total=$order->info['total'];             
        $valor = round($total*$order->info['currency_value'],2);
        $valor = number_format($valor, 2, '.', '');
        
	    
	   //cadena para la firma digital
	    $cadtmp = $this->clave_secreta."~".$this->usuario_id."~".$refventa."~".$valor."~".$my_currency;
		
		//firma digital
		$firma = md5($cadtmp);
			
	  	//pagina de respuesta
		$url_respuesta= HTTP_SERVER . DIR_WS_CATALOG . "index.php?main_page=checkout_success";  
	  
	  
	  	//creacion del boton de pago
       	$process_button_string =				   
							   	zen_draw_hidden_field('valor', $valor).
								zen_draw_hidden_field('extra1', urlencode("Nombre Cliente : ".$nombre_cliente." Telefono : ".$telefono)).
								zen_draw_hidden_field('refVenta', $refventa).
								zen_draw_hidden_field('usuarioId', $this->usuario_id).
								zen_draw_hidden_field('descripcion', "Compra en la tienda ".'::' . STORE_NAME . '::').
								zen_draw_hidden_field('moneda', $my_currency).
								zen_draw_hidden_field('prueba', $this->prueba).
								zen_draw_hidden_field('emailComprador', $order->customer['email_address']).
								zen_draw_hidden_field('iva', $iva).
								zen_draw_hidden_field('baseDevolucionIva', $baseDevolucionIva).
								zen_draw_hidden_field('plantilla', "").
								zen_draw_hidden_field('url_respuesta', $url_respuesta).
								zen_draw_hidden_field('url_confirmacion', HTTP_SERVER . DIR_WS_CATALOG . "confirmacion.php").
								zen_draw_hidden_field('lng', $_SESSION['languages_code']);

		//creacion de la firma digital
		if(strlen($this->clave_secreta)) {
				$process_button_string = $process_button_string."\n".zen_draw_hidden_field('firma', $firma);
			}

     return $process_button_string;
    }

    function before_process(){
        	return false;		
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
      
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Activar módulo Pagosonline', 'MODULE_PAYMENT_PAYCO_STATUS', 'True', 'Quiere aceptar pagos usando Payco?', '6', '3', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
      
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Id Usuario', 'MODULE_PAYMENT_PAYCO_ID_COM', '', 'Codigo de usuario (usuarioId) proporcionado por Payco', '6', '4', now())");
      
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Llave secreta', 'MODULE_PAYMENT_PAYCO_ID_SEED', '', 'Clave de encriptación proporcionada por Payco', '6', '4', now())");
      
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('URL de la pasarela de pago', 'MODULE_PAYMENT_PAYCO_URL', 'https://secure2.payco.co/payment.php', 'Direccion en internet de la pasarela de pago', '6', '4', now())");
	  
	   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Prueba', 'MODULE_PAYMENT_PAYCO_PRUEBA', '1', 'Prueba es la variable para poner su carrito en modo de pruebas, si quiere hacer transacciones en modo de pruebas debe ingresar 1 y si si quiere estar en produccion debe dejarlo en o', '6', '4', now())");
      
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Orden de aparicion.', 'MODULE_PAYMENT_PAYCO_SORT_ORDER', '0', 'Orden de aparicion. Numero menor es mostrado antes que los mayores.', '6', '0', now())");

      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Zona de pago', 'MODULE_PAYMENT_PAYCO_ZONE', '0', 'Si selecciona una zona, este módulo sólo estará disponible en esa zona.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
	        
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Estado del pedido', 'MODULE_PAYMENT_PAYCO_ORDER_STATUS_ID', '0', 'Seleccione el estado del pedido un vez procesado con este módulo', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
       
	}
	
	//funcion para eliminar el plugin desde el modulo administrativo
    function remove(){
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys(){
      return array
      (
      'MODULE_PAYMENT_PAYCO_STATUS',
      'MODULE_PAYMENT_PAYCO_ID_COM',
      'MODULE_PAYMENT_PAYCO_ID_SEED',
      'MODULE_PAYMENT_PAYCO_URL',
	  'MODULE_PAYMENT_PAYCO_PRUEBA',
      'MODULE_PAYMENT_PAYCO_SORT_ORDER',
      'MODULE_PAYMENT_PAYCO_ZONE',
      'MODULE_PAYMENT_PAYCO_ORDER_STATUS_ID'
      );
    }
  }



?>
