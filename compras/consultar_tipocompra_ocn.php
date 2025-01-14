<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
require_once("../includes/funciones_proveedor.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

if ($_POST['idproveedor'] > 0) {
    $idproveedor = intval($_POST['idproveedor']);
}
$idproveedor = intval($idproveedor);
$obtener_tipocompra = $_POST['obtener_tipocompra'];
if ($obtener_tipocompra == 1) {
    $ocnum = intval($_POST['ocnum']);
    $consulta = "SELECT tipocompra, idtipo_origen,idtipo_moneda from compras_ordenes where ocnum = $ocnum";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    echo json_encode([
        "success" => true,
        "tipocompra" => $rs->fields['tipocompra'],
        "idtipo_origen" => $rs->fields['idtipo_origen'],
        "idtipo_moneda" => $rs->fields['idtipo_moneda']
    ]);
}
