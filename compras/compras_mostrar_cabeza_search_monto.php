<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

require_once("../compras_ordenes/preferencias_compras_ordenes.php");
require_once("./preferencias_compras.php");


$idtransaccion = $_POST['idtransaccion'];

$consulta = "
SELECT totalcompra
FROM tmpcompras
where 
estado = 1
and idtran = $idtransaccion 
";

$rs_tmp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$monto_factura = $rs_tmp->fields['totalcompra'];



echo json_encode([
    "monto_factura" => $monto_factura,
   "success" => true
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
