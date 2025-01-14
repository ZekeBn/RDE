<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("includes/rsusuario.php");
global $preferencias_medidas_referenciales;
global $preferencias_medidas_edi;
global $preferencias_configuraciones_alternativas;
global $preferencias_codigo_fob;
global $preferencias_medidas_fisicas;
global $preferencias_costo_promedio;
global $preferencias_usa_iva_variable;
$rsprefinsumo = select_table_col_limit("preferencias_insumos_listas", "usa_iva_variable,medidas_referenciales, medidas_edi, medidas_fisicas, costo_promedio, codigo_fob,configuraciones_alternativas", "1");
$preferencias_medidas_referenciales = trim($rsprefinsumo['medidas_referenciales']);
$preferencias_medidas_edi = trim($rsprefinsumo['medidas_edi']);
$preferencias_configuraciones_alternativas = trim($rsprefinsumo['configuraciones_alternativas']);
$preferencias_codigo_fob = trim($rsprefinsumo['codigo_fob']);
$preferencias_medidas_fisicas = trim($rsprefinsumo['medidas_fisicas']);
$preferencias_costo_promedio = trim($rsprefinsumo['costo_promedio']);
$preferencias_usa_iva_variable = trim($rsprefinsumo['usa_iva_variable']);
