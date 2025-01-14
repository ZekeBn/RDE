<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "55";
require_once("../includes/rsusuario.php");

global $usa_almacenamiento;
global $preferencia_autosel_compras;
global $preferencia_autosel_devoluciones;
global $preferencia_conteo_por_producto;


$rsprefdeposito = select_table_col_limit("preferencias_depositos", "usa_almacenamiento,autosel_compras,autosel_devoluciones,conteo_por_producto", "1");
$usa_almacenamiento = trim($rsprefdeposito['usa_almacenamiento']);
$preferencia_autosel_compras = trim($rsprefdeposito['autosel_compras']);
$preferencia_autosel_devoluciones = trim($rsprefdeposito['autosel_devoluciones']);
$preferencia_conteo_por_producto = trim($rsprefdeposito['conteo_por_producto']);
