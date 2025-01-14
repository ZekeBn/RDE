<?php
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../compras/preferencias_compras.php");
require_once("./preferencias_compras_ordenes.php");
require_once("../insumos/preferencias_insumos_listas.php");

if (intval($ocnum) == 0) {
	$ocn = intval($_POST['ocn']);
} else {
	$ocn = $ocnum;
}

//Traemos los productos seleccionados para la compra
$buscar = "
Select *, cotizaciones.cotizacion,
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
LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
where 
compras_ordenes_detalles.ocnum=$ocn 
and compras_ordenes.estado = 2
order by compras_ordenes_detalles.descripcion asc";
$rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tprod = $rscu->RecordCount();

$cot_ref = floatval($rscu->fields['cotizacion']);
$costo_ref = floatval($rscu->fields['costo_ref']);

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

$cotizacion = "SELECT 
cotizaciones.cotizacion, 
costo_ref, 
tipo_moneda.descripcion as nombre_moneda, 
tipo_moneda.banderita 
from 
compras_ordenes 
LEFT JOIN cotizaciones on cotizaciones.idcot = compras_ordenes.idcot 
LEFT JOIN tipo_moneda on tipo_moneda.idtipo = compras_ordenes.idtipo_moneda
where 
ocnum = $ocn";
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
	.transito {
		background: #ce2d4fa8;
		color: white;
		font-weight: bold;

	}

	.completo {
		background: #D7FFAB;
		color: #405467;
		font-weight: bold;
	}

	.footer_grilla_prod {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
</style>
<div class="table-responsive">
	<table width="100%" class="table table-bordered jambo_table bulk_action">
		<thead>
			<tr>
				<th align="center"><strong>Codigo</strong></th>
				<th align="center"><strong>Codigo Barras</strong></th>
				<th align="center"><strong>Producto</strong></th>
				<!-- <th  align="center" ><strong>Completo</strong></th> -->
				<th align="center"><strong>Medida Compra</strong></th>
				<th align="center"><strong>Unidades</strong></th>
				<?php if ($preferencias_medidas_referenciales == "S") { ?>
					<th align="center"><strong>Caja</strong></th>
					<th align="center"><strong>Pallets</strong></th>
				<?php } ?>
				<?php if ($preferencias_importacion == "S") { ?>
					<th align="center"><strong>Faltante</strong></th>
				<?php } ?>
				<!-- <th  align="center" ><strong>Transito</strong></th> -->
				<th align="center"><strong>Precio Compra</strong></th>
				<th align="center"><strong>Sub Total</strong></th>

			</tr>
		</thead>
		<tbody>
			<?php $tot = 0;
			while (!$rscu->EOF) {
				$subt = $rscu->fields['precio_compra_total'];
				$tot = $tot + $subt;
				$pallet = 0;
				$caja = 0;
				if (floatval($rscu->fields['cant_medida2']) != 0 && floatval($rscu->fields['cant_medida2']) != 1 && floatval($rscu->fields['cant_medida2']) != 0 && floatval($rscu->fields['cantidad']) % floatval($rscu->fields['cant_medida2']) == 0) {
					$caja = $rscu->fields['cantidad'] / $rscu->fields['cant_medida2'];
				}
				if ($caja != 0 && floatval($rscu->fields['cant_medida3']) != 0 && (floatval($rscu->fields['cant_medida2']) != 1 && floatval($rscu->fields['cant_medida3']) != 1)) {
					$pallet = $caja / $rscu->fields['cant_medida3'];
				}
			?>
				<tr <?php if (intval($rscu->fields['cant_transito']) != 0) { ?> class="transito" <?php } else { ?> class="completo" <?php } ?>>
					<td align="left"><?php echo trim($rscu->fields['idprod']) ?></td>
					<td align="left"><?php echo trim($rscu->fields['barcode']) ?></td>
					<td align="left"><?php echo trim($rscu->fields['articulo']) ?></td>
					<!-- <td align="left"><?php //echo intval($rscu->fields['cant_transito']) == 0 ? "Si":"No"?></td> -->
					<td align="right"><?php echo ($rscu->fields['medida']); ?></td>
					<td align="right"><?php echo formatomoneda($rscu->fields['cantidad'], 4, 'N'); ?></td>
					<?php if ($preferencias_medidas_referenciales == "S") { ?>
						<td><?php echo $caja; ?></td>
						<td><?php echo $pallet; ?></td>
					<?php } ?>
					<?php if ($preferencias_importacion == "S") { ?>
						<td align="right"><?php echo formatomoneda($rscu->fields['cant_transito'], 4, 'N'); ?></td>
					<?php } ?>
					<!-- <td align="right"><?php // echo formatomoneda($rscu->fields['cant_transito'],4,'N');?></td> -->
					<td align="right"><?php echo formatomoneda($rscu->fields['precio_compra'], 2, 'S'); ?></td>
					<td align="right"><?php echo formatomoneda($rscu->fields['precio_compra'], 2, 'S'); ?></td>
					<td align="right"><?php echo formatomoneda($rscu->fields['precio_compra_total'], 2, 'S'); ?></td>
				</tr>
			<?php $rscu->MoveNext();
			} ?>
			<tr>
				<td height="26" colspan="19" align="right">
					<div class="footer_grilla_prod">
						<div>
							[<?php echo $nombre_moneda; ?>]
							<?php if ($banderita != '') { ?><img src="../img/<?php echo $banderita ?>" width="20vw" /><?php } ?>
						</div>
						<strong>Total Pedido:</strong> <?php echo formatomoneda($tot, 2, 'N'); ?>
					</div>
				</td>
			</tr>
			<?php if ($costo_ref > 0) { ?>
				<?php if ($preferencias_importacion == "S") { ?>
					<tr>
						<td height="26" colspan="19" align="right">
							<strong>Cotizacion Ref. Gs:</strong> <?php echo formatomoneda($cot_ref, 2, 'N'); ?>
							<strong>Total Pedido Gs:</strong> <?php echo formatomoneda($costo_ref, 0, 'N'); ?>
						</td>
					</tr>
				<?php } ?>
			<?php } ?>
		</tbody>
	</table>
</div>