<?php

require_once("../includes/conexion.php");

$idcompra = 6;
$consulta = "SELECT cotizaciones.cotizacion from cotizaciones wHERE cotizaciones.idcot = (SELECT compras.idcot from compras where idcompra = $idcompra)";
$rs_detalles_compra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cot['cot_compra'] = floatval($rs_detalles_compra->fields['cotizacion']);
echo "ANTES";
print_r($cot);

if ($cot['cot_compra'] == 0) {
    $consulta = "select cotizacion from cotizaciones where cotizaciones.fecha = 
    (select compras.fechacompra from compras where idcompra = $idcompra)  order by cotizaciones.idcot desc limit 1";
    $gscotiz = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $cot['cot_compra'] = floatval($gscotiz->fields['cotizacion']);
}
echo "DESPUES";
print_r($cot);
