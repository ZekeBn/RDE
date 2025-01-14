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
$idproducto = $_POST['idproducto'];

$consulta = "
select p.idprod, p.descripcion, plp.precio, il.idmedida2, p.tipoiva from productos as p
inner join insumos_lista as il on il.idproducto = p.idprod
inner join productos_listaprecios as plp on plp.idproducto = p.idprod and plp.idlistaprecio = 
(select cl.idlistaprecio from cliente cl where cl.idcliente = $idcliente) and plp.estado = 1
where p.idprod = $idproducto;
";

$rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$response = $rsc->GetArray();

echo json_encode($response);
