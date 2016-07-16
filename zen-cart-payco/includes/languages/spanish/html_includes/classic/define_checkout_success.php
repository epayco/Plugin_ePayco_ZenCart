<p>
<!-- body //-->
	<table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td>
			
		  <?php echo $NOMBRE_COMERCIO ?></td>
        		
		</td>
      </tr>
      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
          <td class="main">
<?php	
		$estado = $_REQUEST['x_respuesta'];
		$mensaje = $_REQUEST['x_response_reason_text'];
?>
		  <p>Gracias por su Compra.<br>
              El estado de su transacci&oacute;n es : <b><?php echo $mensaje ?></b>
			  <br>
              Para futuras referencias conserve los siguientes datos:</p>
            <p>
				Numero de referencia : [ <b><?php echo $_REQUEST['x_ref_payco']; ?></b> - <b><?php echo $_REQUEST['x_transaction_id'] ?></b> ]<br>
				Valor : <b><?php echo $_REQUEST['x_currency_code']." $".number_format($_REQUEST['x_amount'],2,',','.') ?></b><br>
				Fecha : <b><?php echo date("d-M-y") ?></b><br>
				Banco : <b></b><br>
				Codigo unico de seguimiento : <b><?php echo $_REQUEST['x_id_factura'] ?><br>
				Descripcion : <b>Compra de productos o servicios</b><br><br>
				<a href="#" onClick="javascript:window.print();">Imprimir</a>
			</p>
            </td>
      </tr>
      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td>
		
		
		<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
          <tr class="infoBoxContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td width="10"><?php echo zen_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
                <td align="right"><?php echo '<a title="Click aca para seguir comprando" href="' . zen_href_link(FILENAME_DEFAULT, '', 'SSL'). '">' . zen_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>'; ?></td>
                <td width="10"><?php echo zen_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table>
		
		
		</td>
      </tr>
    </table>
	
</p>

