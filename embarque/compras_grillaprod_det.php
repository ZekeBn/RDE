<?php
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "42";
$submodulo = "598";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../compras/preferencias_compras.php");
require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("../insumos/preferencias_insumos_listas.php");

if (intval($idcompra) == 0) {
    $idcompra = intval($_POST['idcompra']);
} else {
    $idcompra = $idcompra;
}
//Traemos los productos seleccionados para la compra
$buscar = "
Select *, cotizaciones.cotizacion,
( Select nombre from medidas where medidas.id_medida = compras_detalles.idmedida ) as medida,
( select insumos_lista.descripcion from insumos_lista where idinsumo = compras_detalles.codprod )  as articulo,
(
	select productos.barcode 
	from insumos_lista 
	inner join productos on productos.idprod_serial = insumos_lista.idproducto
	where 
	idinsumo = compras_detalles.codprod
)  as barcode,
(
	select cant_medida2 
	from insumos_lista 
	where 
	idinsumo = compras_detalles.codprod
) as cant_medida2,
(
	select cant_medida3 
	from insumos_lista 
	where 
	idinsumo = compras_detalles.codprod
) as cant_medida3
from  compras_detalles 
inner join compras on compras.idcompra = compras_detalles.idcompra
LEFT JOIN cotizaciones on cotizaciones.idcot = compras.idcot
where 
compras.idcompra=$idcompra
order by articulo asc
";
$rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tprod = $rscu->RecordCount();





$cotizacion = "SELECT cotizaciones.cotizacion, tipo_moneda.descripcion as nombre_moneda, tipo_moneda.banderita
from compras
LEFT JOIN cotizaciones on cotizaciones.idcot = compras.idcot
INNER JOIN tipo_moneda on tipo_moneda.idtipo = cotizaciones.tipo_moneda
where idcompra = $idcompra";
$rscotizacion = $conexion->Execute($cotizacion) or die(errorpg($conexion, $cotizacion));
$cot_ref = floatval($rscotizacion->fields['cotizacion']);
$banderita = antixss($rscotizacion->fields['banderita']);
$nombre_moneda = antixss($rscotizacion->fields['nombre_moneda']);

?>
.
<style>
	.footer_grilla_prod{
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.transito{
		background: #ce2d4fa8;
    	color: white;
		font-weight: bold;

	}
	.completo{
		background: #D7FFAB;
    	color: #405467;
		font-weight: bold;
	}
</style>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
		<tr>
        	<th  align="center" ><strong>Codigo</strong></th>
            <th  align="center" ><strong>Codigo Barras</strong></th>
			<th  align="center" ><strong>Producto</strong></th>
            <th  align="center" ><strong>Medida Compra</strong></th>
            <th  align="center" ><strong>Unidades</strong></th>
			<?php if ($preferencias_medidas_referenciales == "S") { ?>
				<th  align="center" ><strong>Caja</strong></th>
				<th  align="center" ><strong>Pallets</strong></th>
			<?php } ?>
            <!-- <th  align="center" ><strong>Transito</strong></th> -->
            <th  align="center" ><strong>Precio Compra</strong></th>
            <th  align="center" ><strong>Sub Total</strong></th>
            
		</tr>
    </thead>
    <tbody>
        <?php $tot = 0;
while (!$rscu->EOF) {
    $subt = $rscu->fields['subtotal'];
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
        <tr >
			
            
        	<td align="left"><?php echo trim($rscu->fields['codprod']) ?></td>
            <td align="left"><?php echo trim($rscu->fields['barcode']) ?></td>
            <td align="left"><?php echo trim($rscu->fields['articulo']) ?></td>
            <!-- <td align="left"><?php //echo intval($rscu->fields['cant_transito']) == 0 ? "Si":"No"?></td> -->
			<td align="right"><?php echo($rscu->fields['medida']); ?></td>
			<td align="right"><?php echo formatomoneda($rscu->fields['cantidad'], 2, 'S'); ?></td>
			<?php if ($preferencias_medidas_referenciales == "S") { ?>
					<td><?php echo $caja; ?></td>
					<td><?php echo number_format($pallet, 2); ?></td>
				<?php } ?>
			<td align="right"><?php echo formatomoneda($rscu->fields['costo'], 2, 'S'); ?></td>
			<td align="right"><?php echo formatomoneda($rscu->fields['subtotal'], 2, 'S'); ?></td>

        
        </tr>
        <?php $rscu->MoveNext();
} ?>
        <tr>
		<td height="26" colspan="19" align="right" >
				<div class="footer_grilla_prod">
					<div>
						[<?php echo $nombre_moneda; ?>]
						<?php if ($banderita != '') {?><img src="../img/<?php echo $banderita?>"  width="20vw" /><?php }?>
					</div>
					<div><strong>Total Pedido:</strong> <?php echo formatomoneda($tot, 2, 'S');?></div>
				</div>
        	</td>
        </tr>
		<?php if ($cot_ref > 0) { ?>
			<tr>
				<td height="26" colspan="19" align="center">
					<div style="font-size: 1.4rem;">
						<strong>Cotizacion Ref. Gs: <?php echo formatomoneda($cot_ref, 2, 'S');?></strong>
						<strong>Total Pedido Gs:<?php echo formatomoneda($cot_ref * $tot, 2, 'N');?></strong> 
					</div>
					
				</td>
			</tr>
		<?php }
		?>
        </tbody>
	</table>
</div>
