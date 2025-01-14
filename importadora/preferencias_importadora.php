<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");

// global $obliga_cdc;
global $carga_masiva_importacion;

$carga_masiva_importacion = "S";


// $rsprefcompra = select_table_col_limit("preferencias_compras","cot_fecha_fact,multimoneda_local, obliga_cdc, tipocomprobante_def, importacion,obliga_oc","1");
// $obliga_cdc=trim($rsprefcompra['obliga_cdc']);
