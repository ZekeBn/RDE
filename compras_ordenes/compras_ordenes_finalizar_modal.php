<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../compras/preferencias_compras.php");
require_once("../proveedores/preferencias_proveedores.php");

$ocn = $_POST['ocnum'];


$buscar = "
select embarque.estado_embarque
from compras_ordenes 
LEFT JOIN embarque on embarque.ocnum = compras_ordenes.ocnum and embarque.estado=1
LEFT JOIN puertos on puertos.idpuerto = embarque.idpuerto
LEFT JOIN compras on compras.ocnum = compras_ordenes.ocnum
where 
compras_ordenes.ocnum_ref =$ocn
and compras_ordenes.estado = 2
";
$rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$contador = 0;
while (!$rscu->EOF) {
    $estado_embarque = $rscu->fields['estado_embarque'];
    $contador = $estado_embarque == 1 ? $contador + 1 : $contador;
    $rscu->MoveNext();

}


$consulta = "SELECT cmp_dt.idprod,cmp_dt.idmedida,
		(cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante,
		(cmp_dt.cantidad - COALESCE(cmp_comprado.total_comprado, 0)) AS cantidad_faltante,
		cmp_dt.precio_compra,cmp_dt.descripcion,cmp_dt.precio_compra_total,cmp_dt.descuento
				FROM compras_ordenes_detalles AS cmp_dt
				INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_dt.ocnum
				INNER JOIN insumos_lista ON insumos_lista.idinsumo = cmp_dt.idprod
				LEFT JOIN (
					SELECT cmp_det.idprod, SUM(cmp_det.cantidad) AS total_comprado
					FROM compras_ordenes_detalles AS cmp_det
					INNER JOIN compras_ordenes AS cmp ON cmp.ocnum = cmp_det.ocnum and cmp.estado !=6
					AND cmp.ocnum_ref = $ocn 
					GROUP BY cmp_det.idprod
					) AS cmp_comprado ON cmp_comprado.idprod = cmp_dt.idprod
				WHERE cmp_dt.ocnum = $ocn ";

// este select busca en compras
$rs_detalles = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cantidad_total_faltante = 0;
while (!$rs_detalles->EOF) {
    $cantidad_total_faltante = $cantidad_total_faltante + floatval($rs_detalles->fields['cantidad_faltante']);
    $rs_detalles->MoveNext();
}


?>
<?php if ($contador > 0) { ?>
    <h2>Por favor, elimine las proformas activas que no tengan compras asociadas</h2>
<?php } ?>
<?php if ($contador == 0 && $cantidad_total_faltante > 0) { ?>
    <h2 style="text-align:center;">¿Quieres completar la orden de compra a pesar de que falten artículos?</h2>
    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12" style="display:flex;justify-content:center;">
<a href="javascript:void(0);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Detalle" onclick="eliminar_orden(<?php echo trim($ocn) ?>);"><span class="fa fa-check"></span> Finalizar Orden</a>
                    </div></div>
				<div class="clearfix"></div>

<?php } ?>