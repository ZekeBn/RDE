<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Methods, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$data = json_decode(file_get_contents("php://input"), true);
$errores = "";
$idsucursal = antisqlinyeccion($data['id_sucursal'], "int");
$consulta = "SELECT 
  mesas_pedidos.*, 
  mesas.idmesa, 
  mesas.numero_mesa 
FROM 
  mesas_pedidos 
  INNER JOIN mesas_atc on mesas_atc.idatc = mesas_pedidos.idatc 
  inner join mesas on mesas.idmesa = mesas_atc.idmesa 
WHERE 
  mesas_atc.idsucursal = $idsucursal
  and mesas_atc.estado = 1
  and mesas_pedidos.estado = 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$data = $rs->GetArray();

// Set the appropriate headers to indicate JSON content
header('Content-Type: application/json');

// Encode the data array as JSON and output it
echo json_encode($data);
