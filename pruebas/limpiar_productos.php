<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");


$consulta = "
DELETE FROM `insumos_lista` WHERE idinsumo not in (5,4,8);
";
// $consulta = "
// DELETE FROM `insumos_lista` WHERE 1;
// ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
DELETE FROM `ingredientes` WHERE 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
DELETE FROM `productos` WHERE 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
DELETE FROM `recetas_detalles` WHERE 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
DELETE FROM `productos_vencimiento` WHERE 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
DELETE FROM `costo_productos` WHERE 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
DELETE FROM `producto_impresora` WHERE 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
DELETE FROM `recetas` WHERE 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$consulta = "
DELETE FROM productos_sucursales WHERE 1;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
exit;
