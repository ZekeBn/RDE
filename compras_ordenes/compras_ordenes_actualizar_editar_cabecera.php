<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

require_once("../insumos/preferencias_insumos_listas.php");
require_once("../proveedores/preferencias_proveedores.php");
require_once("./preferencias_compras_ordenes.php");
require_once("../compras/preferencias_compras.php");

$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);

$editar_cabecera = intval($_POST['editar_cabecera']);
if ($editar_cabecera == 1) {
    // validaciones basicas
    $valido = "S";
    $errores = "";

    $fecha = antisqlinyeccion($_POST['fecha'], "text");
    $idcot = antisqlinyeccion($_POST['idcot'], "text");
    $ocnum = antisqlinyeccion($_POST['ocnum'], "int");
    //$generado_por=antisqlinyeccion($_POST['generado_por'],"int");
    $tipocompra = antisqlinyeccion($_POST['idtipocompra'], "int");
    $idtipo_origen = antisqlinyeccion($_POST['idtipo_origen'], "int");
    if ($idtipo_origen_importacion == $idtipo_origen) {
        $idtipo_moneda = antisqlinyeccion($_POST['idtipo_moneda'], "int");
    } else {
        if ($multimoneda_local == "S") {
            $idtipo_moneda = antisqlinyeccion($_POST['idtipo_moneda'], "int");
        } else {
            $idtipo_moneda = 0;
        }
    }
    $fecha_entrega = antisqlinyeccion($_POST['fecha_entrega'], "text");
    if ($fecha_entrega == "NULL") {
        $fecha_entrega = "00-00-00";
    }
    if (intval($idtipo_moneda) == 0 && $idtipo_origen_importacion == $idtipo_origen) {
        $valido = "N";
        $errores .= " - No indico el tipo de Moneda en una Orden de Compra tipo Importacion.<br />";
    }
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");




    if (trim($fecha) == '') {
        $valido = "N";
        $errores .= " - El campo fecha no puede estar vacio.<br />";
    }

    if (intval($tipocompra) == 0) {
        $valido = "N";
        $errores .= " - No indico si la compra sera contado o credito.<br />";
    }


    if (intval($idproveedor) == 0) {
        $valido = "N";
        $errores .= " - El indico el proveedor.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update compras_ordenes
		set
		fecha=$fecha,
		tipocompra=$tipocompra,
		fecha_entrega=$fecha_entrega,
		idproveedor=$idproveedor,
		idcot=$idcot,
		idtipo_origen=$idtipo_origen,
		idtipo_moneda=$idtipo_moneda
		where
		ocnum = $ocnum
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

}
