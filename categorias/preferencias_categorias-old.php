<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");

global $margen_seguridad;
global $sub_categoria_secundaria;

$rsprefcompra = select_table_col_limit("preferencias_categorias", "margen_seguridad,sub_categoria_secundaria", "1");
$margen_seguridad = trim($rsprefcompra['margen_seguridad']);
$sub_categoria_secundaria = trim($rsprefcompra['sub_categoria_secundaria']);
