<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "55";
require_once("../includes/rsusuario.php");

$iddeposito = $_POST['iddeposito'];


$buscar = "SELECT autosel_compras FROM gest_depositos WHERE iddeposito = $iddeposito";
$rs_deposito = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$autosel_compras = $rs_deposito->fields['autosel_compras'];
$id_activo_anterior = "";
if ($autosel_compras == "N") {
    $select = "select iddeposito from gest_depositos where autosel_compras = 'S'";
    $rs_activo = $conexion->Execute($select) or die(errorpg($conexion, $select));
    $id_activo_anterior = $rs_activo->fields['iddeposito'];

    $update = "UPDATE gest_depositos 
    SET autosel_compras = 'N'
    WHERE autosel_compras = 'S' ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}
$update = "UPDATE gest_depositos 
SET autosel_compras = CASE 
    WHEN autosel_compras = 'S' THEN 'N'
    ELSE 'S'
END
WHERE iddeposito = $iddeposito";
$conexion->Execute($update) or die(errorpg($conexion, $update));

$buscar = "SELECT autosel_compras FROM gest_depositos WHERE iddeposito = $iddeposito";
$rs_deposito = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$autosel_compras = $rs_deposito->fields['autosel_compras'];
if ($autosel_compras == "S") {
    $checked = "checked";
} else {
    $checked = "";
}
// se reemplazo por js
$html_checkbox = '<input name="producto" id="box_'.$iddeposito.'" type="checkbox" value="S" class="js-switch" onChange="registra_permiso('.$iddeposito.'); " '.$checked.' />';
$html_checkbox_anterior = '<input name="producto" id="box_'.$id_activo_anterior.'" type="checkbox" value="N" class="js-switch" onChange="registra_permiso('.$id_activo_anterior.'); "  />';

echo json_encode([
    "autosel_compras" => $autosel_compras,
    "success" => true,
    "html_checkbox" => $html_checkbox,
    "id_activo_anterior" => $id_activo_anterior,
    "html_checkbox_anterior" => $html_checkbox_anterior
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
