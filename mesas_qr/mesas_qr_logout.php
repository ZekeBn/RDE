<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");

// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
if (!isset($_SESSION)) {
    session_start();
}
$_SESSION['pin_atc_cliente'] = null;
$_SESSION['id_mesa'] = null;
header('Content-Type: application/json');

// Encode the data array as JSON and output it
$data = ["success" => true ];

echo json_encode($data);
