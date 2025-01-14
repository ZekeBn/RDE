<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
require_once("../includes/funciones_proveedor.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../categorias/preferencias_categorias.php");
require_once("../insumos/funciones_insumos.php");


$descripcion = $_POST['descripcion'];
$idconcepto = $_POST['idconcepto'];
$idmedida = $_POST['idmedida'];
$idtipoiva_compra = $_POST['idtipoiva_compra'];
$idgrupoinsu = $_POST['idgrupoinsu'];
$hab_compra = $_POST['hab_compra'];
$hab_invent = $_POST['hab_invent'];
$solo_conversion = $_POST['solo_conversion'];
$cuentacont = $_POST['cuentacont'];
$rendimiento_porc = $_POST['rendimiento_porc'];

$parametros_array = [
    'descripcion' => $descripcion,
    'idconcepto' => $idconcepto,
    'idmedida' => $idmedida,
    'idtipoiva_compra' => $idtipoiva_compra,
    'idgrupoinsu' => $idgrupoinsu,
    'hab_compra' => $hab_compra,
    'hab_invent' => $hab_invent,
    'solo_conversion' => $solo_conversion,
    'cuentacont' => $cuentacont,
    'rendimiento_porc' => $rendimiento_porc
];
$res = verificar_insumos($parametros_array);
echo json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
