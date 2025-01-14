<?php
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

if (intval($ocnum) == 0) {
    $ocn = intval($_POST['ocn']);
} else {
    $ocn = $ocnum;
}
//Traemos los productos seleccionados para la compra
$buscar = "
SELECT cmp_det.codprod, SUM(cmp_det.cantidad) as cantidad, (select insumos_lista.descripcion from insumos_lista where idinsumo = cmp_det.codprod ) as producto
FROM compras_detalles AS cmp_det
INNER JOIN compras AS cmp ON cmp.idcompra = cmp_det.idcompra AND cmp.ocnum in (Select ocnum from compras_ordenes where ocnum_ref = $ocn)
GROUP BY cmp_det.codprod
";

$rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



?>
.
<br />

<div class="table-responsive">
	<h2>Productos Asociados</h2>
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
		<tr>
        	<th  align="center" ><strong>Codigo</strong></th>
			<th  align="center" ><strong>Productos</strong></th>
          
            
		</tr>
    </thead>
    <tbody>
        <?php $tot = 0;
while (!$rscu->EOF) {
    $subt = $rscu->fields['precio_compra_total'];
    $tot = $tot + $subt;?>
        <tr >
			
            
        	<td align="left"><?php echo trim($rscu->fields['codprod']) ?></td>
            <td align="left"><?php echo trim($rscu->fields['producto']) ?></td>
          
        
        </tr>
        <?php $rscu->MoveNext();
} ?>
        

		
        </tbody>
	</table>
</div>
