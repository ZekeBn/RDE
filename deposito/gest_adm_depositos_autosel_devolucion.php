<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");

$iddeposito = $_POST['iddeposito'];


$buscar = "SELECT autosel_devolucion FROM gest_depositos WHERE iddeposito = $iddeposito";
$rs_deposito = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$autosel_devolucion = $rs_deposito->fields['autosel_devolucion'];
$id_activo_anterior = "";
if ($autosel_devolucion == "N") {
    $select = "select iddeposito from gest_depositos where autosel_devolucion = 'S'";
    $rs_activo = $conexion->Execute($select) or die(errorpg($conexion, $select));
    $id_activo_anterior = $rs_activo->fields['iddeposito'];

    $update = "UPDATE gest_depositos 
    SET autosel_devolucion = 'N'
    WHERE autosel_devolucion = 'S' ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}
$update = "UPDATE gest_depositos 
SET autosel_devolucion = CASE 
    WHEN autosel_devolucion = 'S' THEN 'N'
    ELSE 'S'
END
WHERE iddeposito = $iddeposito";
$conexion->Execute($update) or die(errorpg($conexion, $update));

$buscar = "SELECT autosel_devolucion FROM gest_depositos WHERE iddeposito = $iddeposito";
$rs_deposito = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$autosel_devolucion = $rs_deposito->fields['autosel_devolucion'];
if ($autosel_devolucion == "S") {
    $checked = "checked";
} else {
    $checked = "";
}
// se reemplazo por js
$html_checkbox = '<input name="producto" id="box_devolucion_'.$iddeposito.'" type="checkbox" value="S" class="js-switch" onChange="registra_permiso_devolucion('.$iddeposito.'); " '.$checked.' />';
$html_checkbox_anterior = '<input name="producto" id="box_devolucion_'.$id_activo_anterior.'" type="checkbox" value="N" class="js-switch" onChange="registra_permiso_devolucion('.$id_activo_anterior.'); "  />';

echo json_encode([
    "autosel_devolucion" => $autosel_devolucion,
    "success" => true,
    "html_checkbox" => $html_checkbox,
    "id_activo_anterior" => $id_activo_anterior,
    "html_checkbox_anterior" => $html_checkbox_anterior
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
