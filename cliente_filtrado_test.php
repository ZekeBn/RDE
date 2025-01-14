<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");



$parametros_array = [
    'razon_social' => 'A',
    'ruc' => '',
    'documento' => '',
    'limite' => 20,
];
$clientes = buscar_cliente($parametros_array);

print_r($clientes);
