<?php

/*osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com
  Payco Payment Module
  Copyright (c) 2007 Payco software
  Released under the GNU General Public License
*/
  
	//se incluyen archivos de la aplicacion y de la orden
	include_once('includes/application_top.php');	
	include(DIR_WS_CLASSES . 'order.php');
  
	//var_export($_POST);
  	//Se verifica si se esta enviando informacion por el metodo Post y si no es así no se permite ver la pagina por navegador.
	if($_POST['x_respuesta']){	
		
		// se verifica los estados de la transaccion para asi actualizar el nombre en la orden		
		$estado = $_POST['x_respuesta'];			
		

		$ultimo_estado = "select max(orders_status_id) as id from " . TABLE_ORDERS_STATUS ;		
		$ultimo_estado_zencart = $db->Execute($ultimo_estado);
//	$ultimo_estado_zencart = mysql_fetch_assoc($ultimo_estado_zencart);

		$id1 = ($ultimo_estado_zencart->fields['id']+1);
		$id2 = ($ultimo_estado_zencart->fields['id']+2);
		$id3 = ($ultimo_estado_zencart->fields['id']+3);


		$verificar = " select count(orders_status_id) as cantidad from  " . TABLE_ORDERS_STATUS." where orders_status_name ='Aceptada' or orders_status_name ='Rechazada' or orders_status_name ='Pendiente'" ;		
		$verificar_estados = $db->Execute($verificar);

		if($verificar_estados->fields['cantidad'] == "0"){
			$order_status_payco = "insert into " . TABLE_ORDERS_STATUS . " (orders_status_id,language_id,orders_status_name) values (".$id1.",'0','Aceptada'),(".$id2.",'0','Rechazada'),(".$id3.",'0','Pendiente')";
			$db->Execute($order_status_payco);
		}


		//consulta para encontrar el id del estado de las transacciones	 
		$order_status_query = "select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name like '%" . $estado . "%'";	
    	$order_status = $db->Execute($order_status_query);

	
		//consulta para actualizar el estado del pago respecto a la informacion que envia payco	
	  	$update_order_status_query = "update " . TABLE_ORDERS_STATUS_HISTORY . " set orders_status_id = '" 
									. $order_status->fields['orders_status_id'] . "' where orders_id = '" . $_POST['x_id_factura'] . "'";
		$db->Execute($update_order_status_query);
		
		//actualiza el estado del pago en la orden
		$update_order_query = "update " . TABLE_ORDERS . " set orders_status = '" . $order_status->fields['orders_status_id'] 
								. "' where orders_id = '" . $_POST['x_id_factura'] . "'";
	
    	$db->Execute($update_order_query);
		
		
		//si payco envia la variable con este valor es que se aprobo el pago y luego se redirecciona al checkout	
		/*if($_POST['x_respuesta']) {
			header("Location: ".DIR_WS_MODULES."checkout_process.php"); 
		}*/
		
  echo '<html>
        <head>
            <link href="default.css" type=text/css rel=stylesheet> 
        </head>
            <body>
                <div class="">
                    <h1> Transaccion '.$_REQUEST['x_respuesta'].'</h1>
                    <h3> Apreciado cliente, la transaccion No.'. $_REQUEST['x_transaction_id'].'     
                    fue recibida por nuestro sistema.</h3>
                    <h2>Datos de compra:</h3>
                    <table >
                        <tbody>
                        <tr>
                            <th width="240"><strong> Codigo de Referencia: </strong>&nbsp;</th>
                            <td width="240">'.$_REQUEST['x_id_factura'].'</td>
                        </tr>
                        <tr>
                            <th><strong> Valor: </strong></th>
                            <td>'.$_REQUEST['x_amount'].'</td>
                        </tr>
                        <tr>
                            <th><strong> Moneda: </strong></th>
                            <td>'.$_REQUEST['x_currency_code'].'</td>
                        </tr>
                        </tbody>
                    </table>
                    <h2>Datos de la transaccion:</h2>
                    <table>
                        <tbody>
                            <tr>
                                <th width="240"><strong> Fecha de Procesamiento: </strong>&nbsp;</th>
                                <td width="240">'.$_REQUEST['x_fecha_transaccion'].'</td>
                            </tr>
                            <tr>
                                <th><strong> Recibo No.: </strong></th>
                                <td>'.$_REQUEST['x_transaction_id'].'</td>
                            </tr>
                            <tr>
                                <th><strong> Transaccion No.: </strong></th>
                                <td>'.$_REQUEST['x_ref_payco'].'</td>
                            </tr>
                            
                            <tr>
                                <th><strong> Banco o Franquicia: </strong></th>
                                <td>'.$_REQUEST['x_franchise'].'</td>
                            </tr>
                             <tr>
                                <th><strong> Codigo de aprobacion: </strong></th>
                                <td>'.$_REQUEST['x_aproval_code'].'</td>
                            </tr>
                            <tr>
                                <th><strong> Codigo de Respuesta POL: </strong></th>
                                <td>'.$_REQUEST['x_response_reason_text'].'</td>
                            </tr>
                            <tr>
                                <td><a href="/">Regresar a la tienda</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </body>
        </html>';

		//no permite que puedan acceder a la pagina desde el explorador
	}else{
    	echo "<br>Usted no esta autorizado para ver esta pagina.";
  	}
	
	
?>