<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");



$idapp = antisqlinyeccion($_POST['idapp'], "text");
$usuario = antisqlinyeccion($_POST['usuario'], "text");
$clave = antisqlinyeccion(md5(trim($_POST['clave'])), "clave");
$latitud = antisqlinyeccion(trim($_POST['latitud']), "text");
$longitud = antisqlinyeccion(trim($_POST['longitud']), "text");

// mas datos
$nombre_host = antisqlinyeccion(gethostbyaddr($_SERVER["REMOTE_ADDR"]), "text");
$agente = antisqlinyeccion($_SERVER['HTTP_USER_AGENT'], "text");
$ip = antisqlinyeccion($_SERVER['REMOTE_ADDR'], "text");
$ahora = date("Y-m-d H:i:s");
$ipreal = antisqlinyeccion(ip_real(), "text");
$latitud = antisqlinyeccion($_POST['lt'], "text");
$longitud = antisqlinyeccion($_POST['lg'], "text");

$consulta = "
select * 
from app_usuarios 
where 
usuario = $usuario 
and clave = $clave 
and idapp = $idapp
and bloqueado = 'N' 
and estado = 1 
";
$rslogin = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idusuario = intval($rslogin->fields['idusu']);
//echo $consulta;exit;
$idusuario = intval($rslogin->fields['idusu']);

if ($idusuario > 0) {

    $token = md5($idusuario.date("YmdHis").rand());
    $datos_login = [
        'idusuario' => $idusuario,
        'usuario' => $rslogin->fields['usuario'],
        'token' => $token
    ];

    $consulta = "
	update app_usuarios set token = '$token' where idusu = $idusuario and estado = 8
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // registra logueo
    $consulta = "
	INSERT INTO app_usuarios_accesos
	(idusuario, ip, ip_real, fechahora, latitud, longitud, hostname, agente)
	VALUES
	($idusuario,$ip,$ipreal,'$ahora',$latitud,$longitud, $nombre_host, $agente)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $respuesta = [
        'status' => 200,
        'data' => $datos_login
    ];

} else {
    $respuesta = [
        'status' => 0,
        'data' => 'Usuario o clave incorrecto.'
    ];
}

// convierte a formato json
$respuesta = json_encode($respuesta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
