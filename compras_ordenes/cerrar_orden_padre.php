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



$eliminar_orden_padre = intval($_POST['eliminar_orden_padre']);
$ocn = intval($_POST['ocn']);
if ($eliminar_orden_padre == 1) {
    $buscar = "
        update compras_ordenes
        set 
            estado_orden=2
        where 
            ocnum = $ocn
    ";
    $rs_proforma = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

}

echo json_encode([

    "success" => true

], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
