<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";
require_once("../includes/rsusuario.php");
// tiempo de espera
set_time_limit(120);

// recibe parametros
$ruc_comp = trim($_REQUEST['ruc']);

// consulta en cliente y si no existe en hacienda
$parametros_array = [
    'ruc' => $ruc_comp
];
//print_r($parametros_array);exit;
// respuesta
$res = ruc_hacienda($parametros_array);
//print_r($res);exit;
// convierte a formato json
$respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
exit;
