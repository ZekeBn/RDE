<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../includes/funciones_iva.php");
require_once("../includes/funciones_compras.php");

$idtransaccion = intval($idtransaccion);
$ocnum = intval($ocnum);

$consulta_ocnum = "SELECT 
ocnum,
CONCAT('Orden N.: ',ocnum,' | Fecha: ',DATE_FORMAT(fecha,\"%d/%m/%Y\")) as ocdesc
from compras_ordenes 
where 
  ocnum = $ocnum
";
$ocdesc = $conexion->Execute($consulta_ocnum) or die(errorpg($conexion, $consulta_ocnum));
$ocdesc = $ocdesc->fields['ocdesc'];



$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
if ($id_tipo_origen_importacion == 0) {
    $errores = "- Por favor cree el Origen IMPORTACON.<br />";
}

$buscar = "select tmp.idcompra_ref from tmpcompras as tmp where tmp.idtran=$idtransaccion and tmp.estado=1";
$rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcompra_ref = $rscab->fields['idcompra_ref'];
$data = "idcompra_ref=".$idcompra_ref;
php_console_log($data);

if ($idcompra_ref > 0) {
    $buscar = "select tmp.idtran, tmp.facturacompra, tipo_moneda.banderita, tipo_moneda.idtipo as moneda,
				tipo_moneda.idtipo as idmoneda_select, tmp.idtipo_origen, tmp.descripcion, 
				(select nombre from proveedores where idproveedor=tmp.proveedor) as descproveedor
				from tmpcompras as tmp
				left join tipo_moneda on tipo_moneda.idtipo = tmp.moneda
				where tmp.idtran=$idtransaccion and tmp.estado=1";

} else {
    $buscar = "Select tmp.idtran, tmp.facturacompra, tipo_moneda.banderita,tipo_moneda.idtipo as moneda,
			cotizaciones.tipo_moneda as idmoneda_select, tmp.idtipo_origen, tmp.descripcion,
			(select nombre from proveedores where idproveedor=tmp.proveedor) as descproveedor,
			cotizaciones.cotizacion as cot_compra, cotizaciones.fecha as cot_fecha
			from tmpcompras as tmp
			LEFT JOIN cotizaciones on cotizaciones.idcot = tmp.idcot
			LEFT JOIN tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
			where tmp.idtran=$idtransaccion   and tmp.estado = 1 ";
}
$rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idtransaccion = $rscab->fields['idtran'];
$idtipo_origen = $rscab->fields['idtipo_origen'];
$descripcion = $rscab->fields['descripcion'];
$factura = trim($rscab->fields['facturacompra']);
$suc = substr($factura, 0, 3);
$pex = substr($factura, 3, 3);
$fa = substr($factura, 6, 15);
$cot_compra = $rscab->fields['cot_compra'];
$cot_fecha = date("d/m/Y", strtotime($rscab->fields['cot_fecha']));
$moneda_compra = $rscab->fields['moneda'];//Moneda origen seleccionada en la cabecera
$cotizacion_compra = floatval($rscab->fields['cambio']);//Cotizacion de la compra que puede
// venir de OC o de la seleccion en la cabecera
$proveedor_char = trim($rscab->fields['descproveedor']);//Nombre del proveedor, solo para mostrar en pantalla
$factura_completa = $suc."-".$pex."-".$fa;
$data = "moneda_compra=".$moneda_compra;
php_console_log($data);

if ($moneda_compra == 0) {
    //Buscamos la moneda predeterminada
    $buscar = "Select * from tipo_moneda where nacional='S' ";
    $rsmoneda_ppal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $ima = trim($rsmoneda_ppal->fields['banderita']);

    $banderita = "<img src='../img/$ima' width=20vw />";
} else {
    $ima = trim($rscab->fields['banderita']);
    $banderita = "<img src='../img/$ima' width=20vw />";
}
$nacional = ($id_moneda_nacional != $idmoneda_select && intval($idmoneda_select) != 0) ? false : true ;
if ($nacional) {
    $moneda_nombre = $nombre_moneda_nacional;
}
?>

<style>
	.alert-comentario{
		color: #73879C;
		background-color: #F9F9F9;
		border-color: #F9F9F9;
	}
</style>


<div class="row">
	<div class="col-md-12 col-sm-12 col-xs-12">
		<div class="x_panel">
			<div class="x_title">
			<h2 class="col-md-6 col-sm-6 col-xs-12"><span class="fa fa-edit"></span>&nbsp;Registrando compra</h2>
			<div class="nav navbar-right panel_toolbox ">
					<a href="javascript:void(0);" class="btn btn-sm btn-success hover_embarque color_azul_botones" title="Ver detalles de cabecera" onclick="mostrar_datos(<?php echo $idtransaccion ?>);">Editar Cabecera &nbsp;Idt:<?php echo $idtransaccion ?>&nbsp;<span class="fa fa-search" style="color:#fff;" ></span> </a>
					<?php if ($ocnum > 0) { ?>
						<a href="javascript:void(0);" class="btn btn-sm btn-success hover_embarque color_azul_botones" title="Generar auto completado" onclick="genera_auto(<?php echo $idtransaccion ?>);">Autocompletar&nbsp;<?php echo $ocdesc; ?>&nbsp;<span class="fa fa-edit" style="color:#fff;" ></span> </a>
					<?php } ?>
			</div>
			
				<div class="clearfix"></div>
			</div>
			<div class="x_content">
			
			<div class="col-md-12 col-xs-12" id="titulo_compras">
				
			
			
			<div class="detalles_cot">
					<div class="row" >
						<div class="col-md-12 col-xs-12">
								<div class="table-responsive">
									<table class="table table-striped jambo_table bulk_action">
										<tbody>
											<tr>
												
												<th  align="center">Proveedor</th>
												<th  style="background:#eee;" align="center"><?php echo $proveedor_char; ?></th>
												<th  align="center">Factura Nº</th>
												<?php if ($id_tipo_origen_importacion == $idtipo_origen) { ?>
													<th  style="background:#eee;" align="center"><?php echo $factura; ?></th>
												<?php } else { ?>
													<th  style="background:#eee;" align="center"><?php echo $factura_completa; ?></th>
												
											<?php } ?>
											</tr>
										</tbody>
									</table>
								</div>
						</div>
						
						<div class="col-md-12 col-xs-12">
							<div class="table-responsive">
								<table class="table table-striped jambo_table bulk_action">
									<tbody>
										<tr>
											<th  align="center"><?php echo $banderita; ?></th>
											<th  style="background:#eee;" align="center"><?php echo $moneda_nombre; ?></th>
											<?php if (!$nacional) { ?>
												<th  align="center">Cotizacion</th>
												<th  style="background:#eee;" align="center"><?php echo formatomoneda($cot_compra, 2, "S"); ?></th>
												<th  align="center">Fecha cotizacion</th>
												<th  style="background:#eee;" align="center"><?php echo $cot_fecha;?></th>
											<?php }  ?>
										</tr>
									</tbody>
								</table>
							</div>
						</div>


		






					</div>
					<?php if ($descripcion != "") { ?>
						<div class="alert alert-success alert-comentario" style="box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.3);" role="alert">
					   	<h2>Comentario</h2>
						<?php echo strtolower($descripcion); ?>
					   </div>
					<?php } ?>

			</div>
			</div>
		</div>
	</div>
	</div>






