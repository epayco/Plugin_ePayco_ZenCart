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

    function actualizarEstadoOrden($orderId, $statusId, $comentario = '', $notificarCliente = 0, $ocultarEnCuenta = false) {
        $orderId = (int)$orderId;
        $statusId = (int)$statusId;

        if ($orderId <= 0 || $statusId <= 0) {
            return false;
        }

        if (!function_exists('zen_update_orders_history')) {
            include_once(DIR_WS_FUNCTIONS . 'functions_osh_update.php');
        }

        $notifyCustomer = $ocultarEnCuenta ? -1 : ((int)$notificarCliente === 1 ? 1 : 0);

        $resultado = zen_update_orders_history(
            $orderId,
            (string)$comentario,
            null,
            $statusId,
            $notifyCustomer
        );

        return ($resultado > 0);
    }
  
	//var_export($_POST);
  	//Se verifica si se esta enviando informacion por el metodo Post y si no es as� no se permite ver la pagina por navegador.
	if($_POST['x_respuesta']){	
        switch (trim($_POST['x_cod_transaction_state'])) {
            case 1: // Approved
                $estado = 'Processing';
                break;
             case 2: case 4: case 10: case 11: // Cancelled, failed or rejected
                $estado = 'Rechazada';
                break;
            case 3: case 7: // Pending
                $estado = 'Pending';
                break;
             case 6: // Reversed
                $estado = 'Revertida';
                break;
            default:
                $estado = 'Desconocida';
        }
		//consulta para encontrar el id del estado de las transacciones	 
		$order_status_query = "select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name like '%" . $estado . "%'";	
    	$order_status = $db->Execute($order_status_query);

        $orders_status_history = "select orders_status_id from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = " . trim($_POST['x_extra2']) . " order by date_added desc limit 1";	
    	$orders_history = $db->Execute($orders_status_history);
        
        if($order_status->fields['orders_status_id'] != $orders_history->fields['orders_status_id']){
        //consulta para actualizar el estado del pago respecto a la informacion que envia payco	
            actualizarEstadoOrden(
                trim($_POST['x_extra2']),
                $order_status->fields['orders_status_id'],
                'Estado actualizado desde confirmacion.php: ' . $_POST['x_respuesta']
            );
            echo "Estado de la orden actualizado a: " . $estado;
        }else{
            if($order_status->fields['orders_status_id'] == $orders_history->fields['orders_status_id']){
                echo "El estado de la orden ya se encuentra actualizado a: " . $estado;
            }else{
            echo "No se encontro estado de la orden";
            }
        }
    }else{
        echo "No se recibieron datos por POST.";
    }
?>