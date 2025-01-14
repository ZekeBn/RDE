<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
require_once("../includes/rsusuario.php");

$ocnum = intval($_POST['ocnum']);

if ($ocnum > 0) {
    $consulta = "UPDATE compras_ordenes SET estado = 1 WHERE compras_ordenes.ocnum = $ocnum;";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    echo json_encode(["success" => true]);
}
