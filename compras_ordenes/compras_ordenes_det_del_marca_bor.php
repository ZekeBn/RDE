<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");





$ocnum = intval($_GET['ocnum']);
if ($ocnum == 0) {
    header("location: compras_ordenes.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from compras_ordenes_detalles
inner join compras_ordenes on compras_ordenes.ocnum = compras_ordenes_detalles.ocnum 
where 
compras_ordenes.ocnum = $ocnum
and compras_ordenes.estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ocseria = intval($rs->fields['ocseria']);
$ocnum = intval($rs->fields['ocnum']);
if ($ocseria == 0) {
    header("location: compras_ordenes.php");
    exit;
}

$consulta = "
delete from compras_ordenes_detalles where ocnum = $ocnum and marca_borra = 1
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

header("location: compras_ordenes_det.php?id=".$ocnum);
exit;
