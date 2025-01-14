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
require_once("preferencias_compras.php");



// funciones para stock
require_once("../includes/funciones_stock.php");

if ($idcompra == 0) {
    if (intval($_POST['idcompra']) != 0) {
        $idcompra = intval($_POST['idcompra']);
    }
    if (intval($_GET['idcompra']) != 0) {
        $idcompra = intval($_GET['idcompra']);
    }
}
$idmoneda_select = intval($idmoneda_select);
if ($idmoneda_select == 0) {
    if (intval($_POST['idmoneda_select']) != 0) {
        $idmoneda_select = intval($_POST['idmoneda_select']);
    }
    if (intval($_GET['idmoneda_select']) != 0) {
        $idmoneda_select = intval($_GET['idmoneda_select']);
    }
}
$id_moneda_nacional = intval($id_moneda_nacional);
if ($id_moneda_nacional == 0) {
    if (intval($_POST['id_moneda_nacional']) != 0) {
        $id_moneda_nacional = intval($_POST['id_moneda_nacional']);
    }
    if (intval($_GET['id_moneda_nacional']) != 0) {
        $id_moneda_nacional = intval($_GET['id_moneda_nacional']);
    }
}
$cotizacion = floatval($cotizacion);
if ($cotizacion == 0) {
    if (floatval($_POST['cotizacion']) != 0) {
        $cotizacion = floatval($_POST['cotizacion']);
    }
    if (floatval($_GET['cotizacion']) != 0) {
        $cotizacion = floatval($_GET['cotizacion']);
    }
}

$consulta = "
SELECT despacho.cotizacion as cot_despacho FROM despacho WHERE idcompra = $idcompra 
";
$rs_despa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$cot_despacho = floatval($rs_despa->fields['cot_despacho']);

$buscar = "
Select compras.idtran, compras.usa_cot_despacho, fecha_compra,factura_numero,nombre,usuario,tipo,gest_depositos_compras.idcompra,
proveedores.nombre as proveedor, compras.facturacompra, compras.obsfactura,
(select tipocompra from tipocompra where idtipocompra = compras.tipocompra) as tipocompra,
compras.total as monto_factura, compras.ocnum, 
(select nombre from sucursales where idsucu = compras.sucursal) as sucursal,
(select usuario from usuarios where compras.registrado_por = usuarios.idusu) as registrado_por,
registrado as registrado_el, compras.idcompra, compras.descripcion
from gest_depositos_compras
inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por
inner join compras on compras.idcompra = gest_depositos_compras.idcompra
where 
revisado_por=0 
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




if (!isset($nombre_moneda)) {
    // consulta a la tabla
    $consulta = "
	select tipo_moneda.banderita, compras.idtipo_origen ,compras.moneda as idmoneda, cotizaciones.cotizacion, tipo_moneda.descripcion as nom_moneda
	from compras
	LEFT JOIN cotizaciones on cotizaciones.idcot = compras.idcot
	LEFT JOIN tipo_moneda on tipo_moneda.idtipo = compras.moneda
	where
	compras.idcompra = $idcompra 
	";
    $rs_cot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $idmoneda_select = $rs_cot->fields['idmoneda'];
    $cotizacion = $rs_cot->fields['cotizacion'];
    $nombre_moneda = $rs_cot->fields['nom_moneda'];
    $idtipo_origen = $rs_cot->fields['idtipo_origen'];
    $banderita = $rs_cot->fields['banderita'];

}


?>

<div class="table-responsive">
		<table width="100%" class="table table-bordered jambo_table bulk_action">
			<thead>
				<tr>
					<th align="center">Transacci&oacute;n</th>
					<th align="center">Id Compra</th>
					<th align="center">Proveedor</th>
					<th align="center">Fecha compra</th>
					<th align="center">Factura</th>
					<th align="center">Condici&oacute;n</th>
					<th align="center">Monto factura <?php if ($idmoneda_select != $id_moneda_nacional) { ?>Moneda Nacional<?php } ?></th>
					<?php if ($idmoneda_select != $id_moneda_nacional) { ?>
						<th align="center">Monto Factura <?php echo $nombre_moneda?></th>
					<?php } ?>
					<th align="center">Orden Num.</th>
					<th align="center">Sucursal</th>
					<th align="center">Registrado por</th>
					<th align="center">Registrado el</th>
					<th align="center">Comentario</th>
					<?php if ($idmoneda_select != $id_moneda_nacional && $cot_despacho != 0 && $preferencias_importacion == "S") { ?>

						<th align="center"> Usa Cot Despacho</th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php
                while (!$rs->EOF) { ?>
					<tr>
	
						<td align="right"><?php echo intval($rs->fields['idtran']);  ?></td>
						<td align="right"><?php echo intval($rs->fields['idcompra']);  ?></td>
						<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
						<td align="center"><?php if ($rs->fields['fecha_compra'] != "") {
						    echo date("d/m/Y", strtotime($rs->fields['fecha_compra']));
						} ?></td>
						<td align="center"><?php echo antixss($rs->fields['facturacompra']); ?></td>
						<td align="center"><?php echo antixss($rs->fields['tipocompra']); ?></td>
						<?php if ($idmoneda_select != $id_moneda_nacional) { ?>
							<?php if ($rs->fields['usa_cot_despacho'] == "S") { ?>
								<td align="right"><?php echo formatomoneda(($rs->fields['monto_factura'] / $cotizacion) * $cot_despacho) ;  ?></td>
							<?php } else { ?>
								<td align="right"><?php echo formatomoneda(($rs->fields['monto_factura'])) ;  ?></td>
							<?php } ?>
						<?php } else { ?>
							<td align="right"><?php echo formatomoneda($rs->fields['monto_factura']);  ?></td>
						<?php } ?>

						<?php if ($idmoneda_select != $id_moneda_nacional) { ?>
								<td align="right"><?php echo formatomoneda($rs->fields['monto_factura'] / $cotizacion, "2", "S");  ?></td>
						<?php }?>
						
						<td align="center"><?php echo antixss($rs->fields['ocnum']); ?></td>
						<td align="right"><?php echo antixss($rs->fields['sucursal']);  ?></td>
						<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
						<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
						    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
						} ?></td>
						<td align="center"><?php echo antixss($rs->fields['obsfactura']); ?></td>
						<?php

                        if ($cot_despacho != 0 && $preferencias_importacion == "S") { ?>
							<td align="center" id="box_td_compra_cot_despacho">
								<input name="compra_cot_despacho" id="compra_cot_despacho" type="checkbox" value="S" class="js-switch" onChange="registrar_cot_despacho(<?php echo $rs->fields['idcompra']; ?>);" <?php if (($rs->fields['usa_cot_despacho']) == "S") {
								    echo "checked";
								} ?>   >
							</td>
						<?php } ?>
					</tr>
				<?php $rs->MoveNext();
                } //$rs->MoveFirst();?>
			</tbody>
		</table>
	</div>
	<br />
	<div id="gest_admin_deposito_compra_listas">
		<?php require_once("./gest_adm_depositos_compras_lista.php"); ?>
	</div>
	<br />
	