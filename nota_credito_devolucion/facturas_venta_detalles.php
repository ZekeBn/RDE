<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once("../devoluciones/preferencias_devolucion.php");

$idcliente = $_POST['idcliente_notacred'];
$devolucion = intval($_POST['devolucion']);
// echo $devolucion;exit;
$whereadd = "";
$joinadd = "";
$selectadd = "";
if ($devolucion == 1 && $preferencias_devolucion_impotacion == 1) {
    $whereadd = "and retiros_ordenes.idnotacred = 0";
    $joinadd = "INNER JOIN devolucion on devolucion.idventa = ventas.idventa 
  INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion.iddevolucion ";
    $selectadd = ",retiros_ordenes.idorden_retiro";
}

$consulta = "select 
ventas.idventa,
ventas.factura,
ventas.fecha,
cliente.idcliente,
cliente.nombre as cliente,
ventas.total_venta
$selectadd
from 
ventas 
INNER JOIN cliente on cliente.idcliente = ventas.idcliente 
$joinadd
where 
cliente.idcliente = $idcliente
$whereadd
";
// echo $consulta;exit;
$rs_ventas_cliente = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<script type="text/javascript">
  function seleccionar_factura(event,factura,idorden_retiro){
    event.preventDefault();
    console.log(factura);
    $("#factura").attr('data-hidden-idorden-retiro', idorden_retiro);
    $("#factura").val(factura);
    cerrar_modal();
  }
	function cerrar_venta(event,idventa,posicion){
    event.preventDefault();
    $("#box_venta_"+posicion+"_"+idventa).css('display', 'none');	
    $("#box_close_venta_"+posicion+"_"+idventa).css('display', 'none');	
  }
  function mostrar_detalle(event,idventa,idorden_retiro,posicion){
    event.preventDefault();
    direccionurl="ventas_detalle.php";
    var parametros = {
	  "idventa"         : idventa,
    "idorden_retiro"  : idorden_retiro,
    "devolucion"      : <?php echo $devolucion ? $devolucion : 0; ?>
	  };
    console.log(parametros);
    $.ajax({		  
      data:  parametros,
      url:   direccionurl,
      type:  'post',
      cache: false,
      timeout: 3000,  // I chose 3 secs for kicks: 3000
      crossDomain: true,
      beforeSend: function () {
        //$("#facturas_box").html('Cargando...');
        // $("#facturas_det_box").html('Cargando...');			
      },
      success:  function (response, textStatus, xhr) {
        $("#box_venta_"+posicion+"_"+idventa).css('display', 'contents');	
        $("#box_close_venta_"+posicion+"_"+idventa).css('display', 'table-row');	
        $("#venta_"+posicion+"_"+idventa).html(response);	
      },
      error: function(jqXHR, textStatus, errorThrown) {
        if(jqXHR.status == 404){
          alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
        }else if(jqXHR.status == 0){
          alert('Se ha rechazado la conexión.');
        }else{
          alert(jqXHR.status+' '+errorThrown);
        }
      }});
  }
</script>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
      <th></th>
			<th align="center">Idventa</th>
			<th align="center">Factura</th>
			<th align="center">fecha</th>
			<th align="center">Idcliente</th>
			<th align="center">Cliente</th>
			<th align="center">Total Venta</th>
		</tr>
	  </thead>
	  <tbody>
<?php
$posicion = 0;
while (!$rs_ventas_cliente->EOF) { ?>
		<tr>
      <td>
        <div class="btn-group">
          <a href="javascript:void(0);" class="btn btn-sm btn-default" onclick="mostrar_detalle(event, <?php echo $rs_ventas_cliente->fields['idventa']; ?>, <?php echo $rs_ventas_cliente->fields['idorden_retiro']; ?>,<?php echo $posicion;?>)" title="detalles" data-toggle="tooltip" data-placement="right"  data-original-title="detalles"><span class="fa fa-search"></span></a>
          <a href="javascript:void(0);" class="btn btn-sm btn-default" onclick="seleccionar_factura(event,'<?php echo $rs_ventas_cliente->fields['factura']; ?>',<?php echo $rs_ventas_cliente->fields['idorden_retiro']; ?>)" title="seleccionar" data-toggle="tooltip" data-placement="right"  data-original-title="seleccionar"><span class="fa fa-check"></span> Seleccionar</a>
				</div>
      </td>
      <td align="center"><?php echo intval($rs_ventas_cliente->fields['idventa']); ?></td>
      <td align="center"><?php echo antixss($rs_ventas_cliente->fields['factura']); ?></td>
      <td align="center"><?php echo formatofecha("d/m/Y H:i:s", $rs_ventas_cliente->fields['fecha']); ?></td>
      <td align="center"><?php echo intval($rs_ventas_cliente->fields['idcliente']); ?></td>
      <td align="center"><?php echo antixss($rs_ventas_cliente->fields['cliente']); ?></td>
      <td align="center"><?php echo formatomoneda($rs_ventas_cliente->fields['total_venta']); ?></td>
    </tr>
    <tr style="display:none;border: #c2c2c2 1px solid;background: #EE964B;color: white;font-weight: bold;" id="box_close_venta_<?php echo $posicion;?>_<?php echo $rs_ventas_cliente->fields['idventa']; ?>"><td colspan="7">
      <div style="display:flex;justify-content:space-between;align-items: baseline;">
        <p>Detalles</p>
      <a href="javascript:void(0);" onclick="cerrar_venta(event,<?php echo $rs_ventas_cliente->fields['idventa']; ?>,<?php echo $posicion;?>);" class="btn btn-sm btn-default">
				<span class="fa fa-close"></span>
			</a>
		</div>
		</td>
	</tr>
	<tr style="display:none;" id="box_venta_<?php echo $posicion;?>_<?php echo $rs_ventas_cliente->fields['idventa']; ?>">
		<td id="venta_<?php echo $posicion;?>_<?php echo $rs_ventas_cliente->fields['idventa']; ?>" style="border: #c2c2c2 1px solid;box-shadow: 2px 2px 4px 2px rgba(0, 0, 0, 0.2);" colspan="7"></td>
	</tr>
<?php

$rs_ventas_cliente->MoveNext();
    $posicion++;
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />