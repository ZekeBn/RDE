<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
$errores = "";

$id_mesa = $_POST['id_mesa'];
$parametros_array = [
  "id_mesa" => $id_mesa
];

$rs_mesas_respuesta = verificar_pedidos_pendientes($parametros_array);
$idpedido_mesero = $rs_mesas_respuesta['idpedido_mesero'];
$idpedido_cuenta = $rs_mesas_respuesta['idpedido_cuenta'];

$data = ["success" => true,"idpedido_mesero" => $idpedido_mesero > 0,"idpedido_cuenta" => $idpedido_cuenta > 0 ];


// Set the appropriate headers to indicate JSON content
header('Content-Type: application/json');

// Encode the data array as JSON and output it
echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
