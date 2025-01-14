<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");
global $proveedores_moneda;
global $proveedores_agente_retencion;
global $tipo_servicio;
global $proveedores_importacion;
global $proveedores_cta_cte;
global $proveedores_acuerdos_comerciales_archivo;
global $proveedores_sin_factura;
global $proveedores_tipo_compra;
global $dias_entrega;
global $proveedores_obliga_ruc;
global $proveedores_ruc_duplicado;
global $proveedores_razon_social_duplicado;

$rs_preferencias_proveedores = select_table_col_limit(
    "preferencias_proveedores",
    "ruc_duplicado,razon_social_duplicado,obliga_ruc,agente_retencion, tipo_servicio, importacion,cta_cte,acuerdos_comerciales_archivo,sin_factura,tipo_compra,dias_entrega",
    "1"
);

$proveedores_agente_retencion = $rs_preferencias_proveedores["agente_retencion"];
$tipo_servicio = $rs_preferencias_proveedores["tipo_servicio"];
$proveedores_importacion = $rs_preferencias_proveedores["importacion"];
$proveedores_cta_cte = $rs_preferencias_proveedores["cta_cte"];
$proveedores_acuerdos_comerciales_archivo = $rs_preferencias_proveedores["acuerdos_comerciales_archivo"];
$proveedores_sin_factura = $rs_preferencias_proveedores["sin_factura"];
$proveedores_tipo_compra = $rs_preferencias_proveedores['tipo_compra'];
$proveedores_dias_entrega = $rs_preferencias_proveedores['dias_entrega'];
$proveedores_obliga_ruc = $rs_preferencias_proveedores['obliga_ruc'];
$proveedores_ruc_duplicado = $rs_preferencias_proveedores['ruc_duplicado'];
$proveedores_razon_social_duplicado = $rs_preferencias_proveedores['razon_social_duplicado'];
