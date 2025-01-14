<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");
global $preferencias_facturas_multiples;
global $descuento;
global $facturas_finalizadas;
global $mostrar_codigo_origen;
global $insumos_proveedor;
global $ocultar_tk_vincular;
$rsprefcompra = select_table_col_limit("preferencias_compras_orden", "ocultar_tk_vincular,mostrar_codigo_origen,facturas_multiples,descuento,facturas_finalizadas,insumos_proveedor", "1");
$preferencias_facturas_multiples = trim($rsprefcompra['facturas_multiples']);
$descuento = trim($rsprefcompra['descuento']);
$facturas_finalizadas = $rsprefcompra['facturas_finalizadas'];
$mostrar_codigo_origen = $rsprefcompra['mostrar_codigo_origen'];
$insumos_proveedor = $rsprefcompra['insumos_proveedor'];
$ocultar_tk_vincular = $rsprefcompra['ocultar_tk_vincular'];
