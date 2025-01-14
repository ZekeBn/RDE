<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "619";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$idcliente = $_POST['idcliente'];

$consulta = "
select cliente.*, lista_precios_venta.lista_precio, tipo_credito.descripcion
from cliente 
left join lista_precios_venta on lista_precios_venta.idlistaprecio = cliente.idlistaprecio
left join tipo_credito on tipo_credito.dias_credito = cliente.dias_credito
where cliente.idcliente = $idcliente";

$rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$response = $rsc->GetArray();

echo json_encode($response);
