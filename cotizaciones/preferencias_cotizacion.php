<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

global $cotiza_dia_anterior;
global $editar_fecha;
global $usa_cot_compra;


$rsprefcompra = select_table_col_limit("preferencias_cotizacion", "cotiza_dia_anterior, editar_fecha, usa_cot_compra", "1");
$cotiza_dia_anterior = trim($rsprefcompra['cotiza_dia_anterior']);
$editar_fecha = trim($rsprefcompra['editar_fecha']);
$usa_cot_compra = trim($rsprefcompra['usa_cot_compra']);
