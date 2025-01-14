<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");
require_once("../proveedores/preferencias_proveedores.php");
// recibe parametros
$marca = antisqlinyeccion($_POST['marca'], "text");


// validaciones basicas
$valido = "S";
$errores = "";


if (trim($_POST['marca']) == '') {
    $valido = "N";
    $errores .= " - El campo marca no puede estar vacio.<br />";
}


// si todo es correcto actualiza
if ($valido == "S") {

    $consulta = "
    insert into marca
    (marca, idempresa, creado_por, creado_el)
    values
    ($marca, $idempresa, $idusu, '$ahora')
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



}
echo json_encode(["errores" => $errores,"valido" => $valido]);
