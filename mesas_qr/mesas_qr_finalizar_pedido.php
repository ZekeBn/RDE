<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
$errores = "";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Methods, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$data = json_decode(file_get_contents("php://input"), true);
$idpedido = antisqlinyeccion($data['idpedido'], "int");

$data = "";

$consulta = "SELECT 
    estado
FROM 
    mesas_pedidos 
WHERE 
    idpedido=$idpedido
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$estado = intval($rs->fields['estado']);
if ($estado == 1) {
    $consulta = "UPDATE mesas_pedidos set estado = 2 where idpedido=$idpedido
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rs) {
        $data = ["success" => true ];
    }
} else {
    $data = ["success" => false,"error" => "Error: El pedido con id: $idpedido ya fue atendido estado: $estado" ];
}

// Set the appropriate headers to indicate JSON content
header('Content-Type: application/json');

// Encode the data array as JSON and output it
echo json_encode($data);
