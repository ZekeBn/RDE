<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "598";
// $modulo="1";
// $submodulo="2";
$dirsup = "S";
require_once("../includes/rsusuario.php");



// validaciones basicas
$valido = "S";
$errores = "";



// recibe parametros

$descripcion = antisqlinyeccion($_POST['descripcion'], "text");
$idpais = antisqlinyeccion($_POST['idpais'], "int");
$idpto = antisqlinyeccion($_POST['iddepartamento'], "int");
$idciudad = antisqlinyeccion($_POST['idciudad'], "int");
$registrado_por = $idusu;
$registrado_el = antisqlinyeccion($ahora, "text");
$estado = antisqlinyeccion("1", "float");



if (trim($_POST['descripcion']) == '') {
    $valido = "N";
    $errores .= " - El campo descripcion no puede estar vacio.<br />";
}
if (intval($_POST['idpais']) == 0) {
    $valido = "N";
    $errores .= " - El campo idpais no puede ser cero o nulo.<br />";
}
if (intval($_POST['iddepartamento']) == 0) {
    $valido = "N";
    $errores .= " - El campo idpto no puede ser cero o nulo.<br />";
}
if (intval($_POST['idciudad']) == 0) {
    $valido = "N";
    $errores .= " - El campo idciudad no puede ser cero o nulo.<br />";
}

if ($valido == "S") {
    $idpuerto = select_max_id_suma_uno("puertos", "idpuerto")["idpuerto"];
    $consulta = "
    insert into puertos
    (idpuerto,descripcion, idpais, idpto, idciudad, registrado_por, registrado_el, estado)
    values
    ($idpuerto, $descripcion, $idpais, $idpto, $idciudad, $registrado_por, $registrado_el, $estado)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}


echo json_encode(["valido" => $valido,"errores" => $errores]);
