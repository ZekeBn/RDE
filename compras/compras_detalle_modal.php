<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");

$idcompra_post = $_POST['idcompra'];
$consulta = "
select compras_detalles.codprod, compras_detalles.subtotal, compras_detalles.cantidad, insumos_lista.descripcion as insumo
from compras_detalles
INNER JOIN insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
WHERE
idcompra = $idcompra_post
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>




<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idinsumo</th>
			<th align="center">Insumo</th>
			<th align="center">Cantidad</th>
			<th align="center">Total</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			
			<td align="center"><?php echo intval($rs->fields['codprod']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['insumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['cantidad']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['subtotal'], 0, "N"); ?></td>
			
		</tr>
<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>

	  </tbody>
	  
    </table>
</div>
<br />
