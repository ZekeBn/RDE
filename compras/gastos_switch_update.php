<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "55";
require_once("../includes/rsusuario.php");

$idcompra = $_POST['idcompra'];


$update = "UPDATE compras 
SET usa_cot_despacho = CASE 
    WHEN usa_cot_despacho = 'S' THEN 'N'
    ELSE 'S'
END
WHERE idcompra = $idcompra";
$conexion->Execute($update) or die(errorpg($conexion, $update));

$buscar = "SELECT usa_cot_despacho FROM compras WHERE idcompra = $idcompra";
$rs_deposito = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usa_cot_despacho = $rs_deposito->fields['usa_cot_despacho'];
if ($usa_cot_despacho == "S") {
    $checked = "checked";
} else {
    $checked = "";
}
// se reemplazo por js
$html_checkbox = '<input name="gasto_cot_despacho_'.$idcompra.'" id="gasto_cot_despacho_'.$idcompra.'" type="checkbox" value="S" class="js-switch" onChange="registrar_cot_despacho_gasto('.$idcompra.'); " '.$checked.' />';

echo json_encode([
    "usa_cot_despacho" => $usa_cot_despacho,
    "success" => true,
    "html_checkbox" => $html_checkbox,
    "idcompra" => $idcompra
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
