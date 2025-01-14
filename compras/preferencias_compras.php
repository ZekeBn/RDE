<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

global $obliga_cdc;
global $preferencias_importacion;
global $tipocomprobante_def;
global $obliga_oc;
global $multimoneda_local;
global $preferencias_cot_fecha_fact;
global $costo_cero;

$rsprefcompra = select_table_col_limit("preferencias_compras", "costo_cero,cot_fecha_fact,multimoneda_local, obliga_cdc, tipocomprobante_def, importacion,obliga_oc", "1");
$obliga_tipocomprobante = 'S';
$obliga_cdc = trim($rsprefcompra['obliga_cdc']);
$preferencias_importacion = trim($rsprefcompra['importacion']);
$tipocomprobante_def = trim($rsprefcompra['tipocomprobante_def']);
$obliga_oc = trim($rsprefcompra['obliga_oc']);
$multimoneda_local = trim($rsprefcompra['multimoneda_local']);
$preferencias_cot_fecha_fact = trim($rsprefcompra['cot_fecha_fact']);
$costo_cero = $rsprefcompra['costo_cero'];
