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
$consulta = "SELECT s.direccion, s.nombre, s.fantasia_sucursal, s.razon_social_sucursal, 
              e.empresa, e.direccion as direccion_empresa, e.telefono, e.fondo, e.linea 
              FROM sucursales s 
              JOIN empresas e ON e.idempresa = s.idempresa
              WHERE s.idsucu = $idsucursal
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// Set the appropriate headers to indicate JSON content
header('Content-Type: application/json');

$array_retorno = [

  "direccion_sucursal" => $rs->fields['direccion'],
  "nombre_sucursal" => $rs->fields['nombre'],
  "razon_social_sucursal" => $rs->fields['razon_social_sucursal'],
  "nombre_empresa" => $rs->fields['empresa'],
  "direccion_empresa" => $rs->fields['direccion_empresa'],
  "telefono_empresa" => $rs->fields['telefono'],
  "color_fondo_empresa" => $rs->fields['fondo'],
  "linea_color_empresa" => $rs->fields['linea']

];

// Encode the data array as JSON and output it
echo json_encode($array_retorno);
