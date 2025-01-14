<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "107";
//error_reporting(E_ALL);
require_once("../includes/rsusuario.php");

$idcompra = intval($_GET['id']);
if ($idcompra == 0) {
    header("location: gest_adm_depositos_compras.php");
    exit;
}
//echo ticket_compra($idcompra);exit;
$cot_array = usa_cotizacion($idcompra);


$buscar = "
Select compras.idtran, fecha_compra,factura_numero,nombre,usuario,tipo,gest_depositos_compras.idcompra,
proveedores.nombre as proveedor, compras.facturacompra,
(select tipocompra from tipocompra where idtipocompra = compras.tipocompra) as tipocompra,
compras.total as monto_factura, compras.ocnum, 
(select nombre from sucursales where idsucu = compras.sucursal) as sucursal,
(select usuario from usuarios where compras.registrado_por = usuarios.idusu) as registrado_por,
registrado as registrado_el, compras.idcompra, compras.idtipocomprobante, compras.timbrado, compras.vto_timbrado,
(select tipocomprobante from tipo_comprobante where idtipocomprobante = compras.idtipocomprobante) as tipocomprobante,
compras.cdc
from gest_depositos_compras
inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por
inner join compras on compras.idcompra = gest_depositos_compras.idcompra
where 
revisado_por > 0 
and compras.estado <> 6
and compras.idcompra = $idcompra
order by gest_depositos_compras.fecha_compra desc 
limit 1
";
//echo $buscar;
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcompra = intval($rs->fields['idcompra']);
if ($idcompra == 0) {
    header("location: gest_adm_depositos_compras.php");
    exit;
}




?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
	<script>
		function imprime_cliente(){
				//alert('a');
				var texto = document.getElementById("texto").value;
				//alert(texto);
				var parametros = {
						"tk"      : texto,
						'tk_json' : ''
				};
			$.ajax({
						data:  parametros,
						url:   'http://localhost/impresorweb/ladocliente.php',
						type:  'post',
						dataType: 'html',
						beforeSend: function () {
								$("#impresion_box").html("Enviando Impresion...");
						},
						crossDomain: true,
						success:  function (response) {
								//$("#impresion_box").html(response);	
								//si impresion es correcta marcar
								var str = response;
								var res = str.substr(0, 18);
								//alert(res);
								if(res == 'Impresion Correcta'){
									$("#impresion_box").html("Impresion Enviada!<hr />");
								}else{
									$("#impresion_box").html(response);	
								}
								
								
						}
				});
			
		}	

	</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Compra Verificada Exitosamente!</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="gest_reg_compras_resto_new.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Registro de Compras</a>
<a href="gest_adm_depositos_compras.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Verificar Compras</a>
<a href="../compras_ordenes/compras_ordenes_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Nueva Compra</a>
<a href="javascript:imprime_cliente();void(0);" class="btn btn-sm btn-default"><span class="fa fa-print"></span> Imprimir Ticket</a>
</p>
<hr />  
<div id="impresion_box"></div>

<strong>Compra Verificada:</strong><br />
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			
            <th align="center">Idcompra</th>
			<th align="center">Idtran</th>
			<th align="center">Proveedor</th>
			<th align="center">Fecha compra</th>
			<th align="center">Factura</th>
			<th align="center">Condicion</th>
			<th align="center">Monto factura</th>
			<th align="center">Orden Num.</th>
			<th align="center">Sucursal</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>

		<tr>

			
            <td align="right"><?php echo intval($rs->fields['idcompra']);  ?></td>
			<td align="right"><?php echo intval($rs->fields['idtran']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_compra'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_compra']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['facturacompra']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipocompra']); ?></td>
			<td align="right">
			<?php
                $monto_factura = $rs->fields['monto_factura'];
if ($cot_array['usa_cot_despacho'] == "S") {
    $monto_factura = ($monto_factura / $cot_array['cot_compra']) * $cot_array['cot_despacho'];

}
echo formatomoneda($monto_factura);
?>
			  </td>
			<td align="center"><?php echo antixss($rs->fields['ocnum']); ?></td>
			<td align="right"><?php echo antixss($rs->fields['sucursal']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
		</tr>

	  </tbody>
    </table>
</div>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Tipo Comprobante</th>
            <th align="center">CDC</th>
			<th align="center">Timbrado</th>
			<th align="center">Vencimiento Timbrado</th>
		</tr>
	  </thead>
	  <tbody>

		<tr>

			<td align="right"><?php echo antixss($rs->fields['tipocomprobante']);  ?> [<?php echo intval($rs->fields['idtipocomprobante']);  ?>]</td>
            <td align="right"><?php echo antixss($rs->fields['cdc']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['timbrado']); ?></td>
			<td align="center"><?php if ($rs->fields['vto_timbrado'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['vto_timbrado']));
			} ?></td>

		</tr>

	  </tbody>
    </table>
</div>
<?php
// consulta a la tabla
$consulta = "
select * , compras_detalles.costo as costo, insumos_lista.descripcion as descripcion, 
(select cn_conceptos.descripcion from cn_conceptos where cn_conceptos.idconcepto = insumos_lista.idconcepto) as concepto
from compras_detalles 
inner join insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
where 
idcompra = $idcompra
order by insumos_lista.descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cantidad = intval($rs->fields['cantidad']);
?>
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Codigo</th>
            <th align="center">Producto</th>
            <th align="center">Concepto</th>
			<th align="center">Cantidad</th>
			<th align="center">Costo</th>
			<th align="center">Subtotal</th>
			<th align="center">Iva %</th>
			<th align="center">Lote</th>
			<th align="center">Vencimiento</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>


			<td align="center"><?php echo antixss($rs->fields['idinsumo']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['concepto']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N');  ?></td>
			<td align="right">
				<?php
                    $costo = $rs->fields['costo'];
    if ($cot_array['usa_cot_despacho'] == "S") {
        $costo = ($costo / $cot_array['cot_compra']) * $cot_array['cot_despacho'];
        $subtotal = ($costo * $cantidad);
    }
    echo formatomoneda($costo);
    ?>
			</td>
			<td align="right">
				<?php
    $subtotal = $rs->fields['subtotal'];
    if ($cot_array['usa_cot_despacho'] == "S") {
        $subtotal = $monto_factura = ($subtotal / $cot_array['cot_compra']) * $cot_array['cot_despacho'];
    }
    echo formatomoneda($subtotal);
    ?></td>
			<td align="center"><?php echo intval($rs->fields['iva']); ?>%</td>
			<td align="center"><?php echo antixss($rs->fields['lote']); ?></td>
			<td align="center"><?php if ($rs->fields['vencimiento'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['vencimiento']));
			} ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<?php
$consulta = "
select gest_depositos.descripcion as deposito, facturas_proveedores.iddeposito, sucursales.nombre as sucursal,
facturas_proveedores.fecha_valida, facturas_proveedores.validado_por,
(select usuario from usuarios where idusu = facturas_proveedores.validado_por) as usu_validado_por
from facturas_proveedores 
inner join gest_depositos on gest_depositos.iddeposito = facturas_proveedores.iddeposito
inner join sucursales on sucursales.idsucu = gest_depositos.idsucursal
where 
idcompra = $idcompra
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<strong>Estado del Stock:</strong> 
<?php if ($rs->fields['iddeposito'] > 0) { ?>
Ingresado<br />
<strong>Deposito:</strong>  <?php echo $rs->fields['deposito']; ?> [<?php echo $rs->fields['iddeposito']; ?>]<br />
<strong>Local:</strong>  <?php echo $rs->fields['sucursal']; ?><br />
<strong>Fecha Validado:</strong>  <?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_valida'])); ?><br />
<strong>Usuario Validador:</strong>  <?php echo $rs->fields['usu_validado_por']; ?><br />
<?php } else { ?>
Pendiente de ingreso
<?php } ?>
<div class="clearfix"></div>
<hr />
<textarea name="texto" id="texto" cols="120" rows="40" style="width:400px; display:none;" >
<?php echo ticket_compra($idcompra); ?>
</textarea>


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
