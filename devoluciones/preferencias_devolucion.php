<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
global $preferencias_devolucion_impotacion;

$rspreferenciaDevolucion = select_table_col_limit("preferencias_devolucion", "devolucion_importacion", "1");
$preferencias_devolucion_impotacion = trim($rspreferenciaDevolucion['devolucion_importacion']);
