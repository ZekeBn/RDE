<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("./mesas_qr_funciones.php");
require_once("./mesas_preferencias.php");
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Methods, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$errores = "";
$valido = "S";
$data = json_decode(file_get_contents("php://input"), true);
$usuario = antisqlinyeccion($data['usuario'], "text");
$clave = antisqlinyeccion($data['clave'], "clave");


$usuariologin = antisqlinyeccion($usuariologin, 'text');
// busca si existe en bd
$buscar = "Select * from usuarios where usuario=UPPER($usuario) and clave=MD5($clave)";
$rsu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$encontrado = $rsu->RecordCount();
// si existe

if ($rsu->fields['idusu'] > 0) {
    // valida que no este bloqueado
    if ($rsu->fields['bloqueado'] == 'S') {
        $errores = "Su usuario fue bloqueado por muchos intentos fallidos de iniciar sesion,
            favor contacte con la administracion;";
    }
    // valida que este activo
    if (intval($rsu->fields['estado']) != 1) {
        $errores .= "Tu usuario fue desactivado por la administracion;";
    }

}

// si existe en la BD y no esta bloqueado o inactivo loguear
if ($encontrado > 0 && $valido == "S") {
    $idusu = $rsu->fields['idusu'];
    $buscar = "Select ssuni from usuarios_mozos where idusu=$idusu and estado_mozo=1";
    $rsu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $ssuni = intval($rsu->fields['ssuni']);
    if ($ssuni > 0) {
        $token_sin_comas = generarUUID20();
        $token = antisqlinyeccion($token_sin_comas, 'clave');
        $buscar = "UPDATE usuarios_mozos set token=$token where ssuni=$ssuni";
        $rsu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $data = ["success" => true,"idmozo" => $ssuni,"token" => $token_sin_comas];

    } else {
        $errores .= "Actualmente, no hay un camarero activo asociado a su cuenta o disponible en este momento;";
        $data = ["success" => false,"errores" => $errores];
    }
}
if ($valido == "N") {
    $data = ["success" => false,"errores" => $errores];
}
////////////////////////////////////////////////////



// Set the appropriate headers to indicate JSON content
header('Content-Type: application/json');

// Encode the data array as JSON and output it
echo json_encode($data);
