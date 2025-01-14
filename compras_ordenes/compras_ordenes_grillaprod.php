<?php
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("./preferencias_compras_ordenes.php");
require_once("../compras/preferencias_compras.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../compras/preferencias_compras.php");

$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);

/////////////////////////////////////////

$editar_cabecera = intval($_POST['editar_cabecera']);

if ($editar_cabecera == 1) {

	// validaciones basicas
	$valido = "S";
	$errores = "";
	$fecha = antisqlinyeccion($_POST['fecha'], "text");
	$idcot = antisqlinyeccion($_POST['idcot'], "text");
	$ocnum = antisqlinyeccion($_POST['ocnum'], "int");
	//$generado_por=antisqlinyeccion($_POST['generado_por'],"int");
	$tipocompra = antisqlinyeccion($_POST['idtipocompra'], "int");
	$idtipo_origen = antisqlinyeccion($_POST['idtipo_origen'], "int");

	if ($idtipo_origen_importacion == $idtipo_origen) {
		$idtipo_moneda = antisqlinyeccion($_POST['idtipo_moneda'], "int");
	} else {

		if ($multimoneda_local == "S") {
			$idtipo_moneda = antisqlinyeccion($_POST['idtipo_moneda'], "int");
		} else {
			$idtipo_moneda = 0;
		}
	}

	$fecha_entrega = antisqlinyeccion($_POST['fecha_entrega'], "text");
	if ($fecha_entrega == "NULL") {
		$fecha_entrega = "00-00-00";
	}
	if (intval($idtipo_moneda) == 0 && $idtipo_origen_importacion == $idtipo_origen) {
		$valido = "N";
		$errores .= " - No indico el tipo de Moneda en una Orden de Compra tipo Importacion.<br />";
	}
	$idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");

	if (trim($fecha) == '') {
		$valido = "N";
		$errores .= " - El campo fecha no puede estar vacio.<br />";
	}

	if (intval($tipocompra) == 0) {
		$valido = "N";
		$errores .= " - No indico si la compra sera contado o credito.<br />";
	}

	if (intval($idproveedor) == 0) {
		$valido = "N";
		$errores .= " - El indico el proveedor.<br />";
	}

	// si todo es correcto inserta
	if ($valido == "S") {

		$consulta = "
		update compras_ordenes
		set
		fecha=$fecha,
		tipocompra=$tipocompra,
		fecha_entrega=$fecha_entrega,
		idproveedor=$idproveedor,
		idcot=$idcot,
		idtipo_origen=$idtipo_origen,
		idtipo_moneda=$idtipo_moneda
		where
		ocnum = $ocnum";
		$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
	}
}

//////////////////////////////////////

if (intval($ocnum) == 0) {
	$ocn = intval($_POST['ocn']);
} else {
	$ocn = $ocnum;
}

//buscando moneda guarani
$consulta = "SELECT tipo_moneda.idtipo, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE UPPER(tipo_moneda.descripcion) like \"%GUARANI%\" ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_guarani = $rs_guarani->fields["idtipo"];
$nombre_moneda_guarani = $rs_guarani->fields["nombre"];

// buscar moneda de la orden
$consulta = "Select 
compras_ordenes.idtipo_moneda, 
tipo_moneda.descripcion as nombre_moneda 
from 
compras_ordenes 
INNER JOIN tipo_moneda on tipo_moneda.idtipo = compras_ordenes.idtipo_moneda 
where 
compras_ordenes.estado = $ocn";
$rs_orden = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idtipo_moneda_orden = $rs_orden->fields['idtipo_moneda'];
$nombre_moneda_orden = $rs_orden->fields['nombre_moneda'];

//Traemos los productos seleccionados para la compra
$buscar = "
Select compras_ordenes_detalles.*,                
( Select nombre from medidas where medidas.id_medida = compras_ordenes_detalles.idmedida ) as medida,
(select insumos_lista.descripcion from insumos_lista where idinsumo = compras_ordenes_detalles.idprod )  as articulo,
(
	select productos.barcode 
	from insumos_lista 
	inner join productos on productos.idprod_serial = insumos_lista.idproducto
	 where 
	 idinsumo = compras_ordenes_detalles.idprod
)  as barcode,
(
	select cant_medida2 
	from insumos_lista 
	where 
	idinsumo = compras_ordenes_detalles.idprod
) as cant_medida2,
(
	select cant_medida3 
	from insumos_lista 
	where 
	idinsumo = compras_ordenes_detalles.idprod
) as cant_medida3
from  compras_ordenes_detalles 
inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
where 
compras_ordenes_detalles.ocnum=$ocn 
and compras_ordenes.estado = 1
order by compras_ordenes_detalles.descripcion asc";
$rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
// echo $idtipo_moneda_orden;
// exit;

$ver_query = $buscar;

$tprod = $rscu->RecordCount();

$buscar = "SELECT *,
( select insumos_lista.descripcion from insumos_lista where idinsumo = compras_ordenes_detalles.idprod )  as articulo,
(
	select productos.barcode 
	from insumos_lista 
	inner join productos on productos.idprod_serial = insumos_lista.idproducto
	 where 
	 idinsumo = compras_ordenes_detalles.idprod
)  as barcode
from  compras_ordenes_detalles 
inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum
where 
compras_ordenes_detalles.ocnum=$ocn 
and compras_ordenes.estado = 1
order by compras_ordenes_detalles.ocseria desc
limit 1";
$rsultag = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$cotizacion = "SELECT cotizaciones.cotizacion, costo_ref, tipo_moneda.descripcion as nombre_moneda, tipo_moneda.banderita
from compras_ordenes 
LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
INNER JOIN tipo_moneda on tipo_moneda.idtipo = compras_ordenes.idtipo_moneda
where ocnum = $ocn";
$rscotizacion = $conexion->Execute($cotizacion) or die(errorpg($conexion, $cotizacion));

$cot_ref = floatval($rscotizacion->fields['cotizacion']);
$costo_ref = floatval($rscotizacion->fields['costo_ref']);
$banderita = antixss($rscotizacion->fields['banderita']);
$nombre_moneda = antixss($rscotizacion->fields['nombre_moneda']);

if ($preferencias_importacion == "N") {
	//buscando moneda nacional
	$consulta = "SELECT tipo_moneda.banderita, tipo_moneda.descripcion as nombre FROM tipo_moneda WHERE nacional='S'";
	$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
	$banderita = $rs_guarani->fields["banderita"];
	$nombre_moneda = $rs_guarani->fields["nombre"];
}
?>

<style>
	.footer_grilla_prod {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
</style>

<!-- <strong>Ultimo Agregado:</strong> <?php //echo trim($rsultag->fields['articulo'])?> [<?php //echo trim($rsultag->fields['idprod'])?>] - <?php //echo trim($rsultag->fields['barcode'])?> -->
<br />
<div class="table-responsive">
	<table width="100%" class="table table-bordered jambo_table bulk_action">
		<thead>
			<tr>
				<th align="center">&nbsp;</th>
				<th align="center"><strong>Codigo</strong></th>
				<th align="center"><strong>Codigo Barras</strong></th>
				<th align="center"><strong>Producto</strong></th>
				<th align="center"><strong>Medida Compra</strong></th>
				<th align="center"><strong>Unidades</strong></th>
				<?php if ($preferencias_medidas_referenciales == "S") { ?>
					<th align="center"><strong>Caja</strong></th>
					<th align="center"><strong>Pallets</strong></th>
				<?php } ?>
				<th align="center"><strong>Costo Unit.</strong></th>
				<?php if ($descuento == "S") { ?>
					<th align="center"><strong>descuento</strong></th>
				<?php } ?>
				<th align="center"><strong>Sub Total</strong></th>
			</tr>
		</thead>
		<tbody>
			<?php $tot = 0;

			while (!$rscu->EOF) {

				$subt = $rscu->fields['precio_compra_total'];
				$tot = $tot + $subt;
				$cant_medida2 = floatval($rscu->fields['cant_medida2']);
				$cant_medida3 = floatval($rscu->fields['cant_medida3']);
				$pallet = 0;
				$caja = 0;

				if ($cant_medida2 != 0 && $cant_medida != 1 && floatval($rscu->fields['cantidad']) % $cant_medida2 == 0) {
					$caja = $rscu->fields['cantidad'] / $rscu->fields['cant_medida2'];
				}
				//echo $cant_medida3; exit;
				if ($caja != 0 && $cant_medida3 != 0 && $cant_medida2 != 1 && $cant_medida3 != 1) {
					$pallet = $caja / $cant_medida3;
					$pallet = number_format($pallet, 2, '.', '');
				}
				$medida = $rscu->fields['medida'];
				//echo "Cant ".$cantidad, "Cant2 ".$cant_medida2, "Cant3 ".$cant_medida3; exit;
			?>
				<tr>
					<td>
						<div class="btn-group">
							<!--<a href="compras_ordenes_det_edit.php?id=<?php echo $rscu->fields['ocseria']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>-->
							<a href="javascript:editar_oc(<?php echo $rscu->fields['ocseria']; ?>);" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right" data-original-title="Editar"><span class="fa fa-edit"></span></a>
							<a href="compras_ordenes_det_del.php?id=<?php echo $rscu->fields['ocseria']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right" data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
							<span id="bor_<?php echo $rscu->fields['ocseria']; ?>">
								<?php if ($rscu->fields['marca_borra'] == 0) { ?>
									<a href="javascript:marca_borrado(<?php echo $rscu->fields['ocseria']; ?>);" class="btn btn-sm btn-default" title="Marcar para borrar"><span class="fa fa-toggle-off"></span> No se Borrara</a>
								<?php } else { ?>
									<a href="javascript:desmarca_borrado(<?php echo $rscu->fields['ocseria']; ?>);" class="btn btn-sm btn-default" title="Desmarcar (no borrar)"><span class="fa fa-toggle-on"></span> Se Borrara</a>
								<?php } ?>
							</span>
						</div>
					</td>
					<td align="left"><?php echo trim($rscu->fields['idprod']) ?></td>
					<td align="left"><?php echo trim($rscu->fields['barcode']) ?></td>
					<td align="left"><?php echo trim($rscu->fields['articulo']) ?></td>
					<td align="right"><?php echo ($rscu->fields['medida']); ?></td>
					<td align="right"><?php echo formatomoneda($rscu->fields['cantidad'], 4, 'N'); ?></td>
					<?php if ($preferencias_medidas_referenciales == "S") { ?>
						<td><?php echo $caja; ?></td>
						<td><?php echo $pallet; ?></td>
					<?php } ?>
					<td align="right"><?php
										if ($id_moneda_guarani == $idtipo_moneda_orden) {
											echo formatomoneda($rscu->fields['precio_compra'], 0, 'N');
										} else {
											echo formatomoneda($rscu->fields['precio_compra'], 3, 'N');
										}
										?></td>
					<?php if ($descuento == "S") { ?>
						<td align="right"><?php echo formatomoneda($rscu->fields['descuento'], 4, 'N'); ?></td>
					<?php } ?>
					<td align="right">
						<?php
						if ($id_moneda_guarani == $idtipo_moneda_orden) {
							echo formatomoneda($rscu->fields['precio_compra_total'], 0, 'N');
						} else {
							echo formatomoneda($rscu->fields['precio_compra_total'], 2, 'S');
						}
						?>
					</td>
				</tr>
			<?php $rscu->MoveNext();
			} ?>
			<tr>
				<td height="26" colspan="15" align="right">
					<div class="footer_grilla_prod">
						<div>
							[<?php echo $nombre_moneda; ?>]
							<?php if ($banderita != '') { ?><img src="../img/<?php echo $banderita ?>" width="20vw" /><?php } ?>
						</div>
						<div>
							<strong>Total Pedido:</strong> <?php
															if ($id_moneda_guarani == $idtipo_moneda_orden) {
																echo formatomoneda($tot, 0, 'N');
															} else {
																echo formatomoneda($tot, 2, 'S');
															}
															?>

						</div>
					</div>
				</td>
			</tr>
			<?php if ($costo_ref > 0) { ?>
				<tr>
					<td height="26" colspan="15" align="center">
						<div style="font-size: 1.4rem;">
							<strong>Cotizacion Ref. Gs: <?php
														echo formatomoneda($cot_ref, 2, 'S');
														?></strong>
							<strong>Total Pedido Gs:<?php echo formatomoneda($costo_ref, 'N'); ?></strong>
						</div>
					</td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
</div>